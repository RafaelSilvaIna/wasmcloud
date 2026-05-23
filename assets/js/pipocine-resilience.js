(function () {
    'use strict';

    if (window.__PIPOCINE_RESILIENCE__) return;
    window.__PIPOCINE_RESILIENCE__ = true;

    const VERSION = '20260523.2';
    const CHANNEL = 'pipocine-sync';
    const QUEUE_KEY = 'pipocine:mutation-queue:v1';
    const SAFE_MUTATIONS = [
        '/api/v3/watch-progress/save',
        '/api/v3/watched-episodes/mark',
        '/api/v3/watched-episodes/unmark',
        '/api/v3/library/watch'
    ];

    const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
    const bc = 'BroadcastChannel' in window ? new BroadcastChannel(CHANNEL) : null;

    function toUrl(input) {
        try {
            if (input instanceof Request) return new URL(input.url);
            return new URL(String(input), window.location.href);
        } catch (_) {
            return null;
        }
    }

    function methodOf(input, init) {
        return String((init && init.method) || (input instanceof Request ? input.method : 'GET') || 'GET').toUpperCase();
    }

    function isSameOrigin(url) {
        return url && url.origin === window.location.origin;
    }

    function isSafeMutation(url, method) {
        return method === 'POST' && isSameOrigin(url) && SAFE_MUTATIONS.includes(url.pathname);
    }

    function cloneHeaders(input, init) {
        const headers = new Headers(input instanceof Request ? input.headers : undefined);
        if (init && init.headers) {
            new Headers(init.headers).forEach((value, key) => headers.set(key, value));
        }
        if (!headers.has('Accept')) headers.set('Accept', 'application/json, text/plain, */*');
        return headers;
    }

    function withTimeout(input, init, timeoutMs) {
        if (!nativeFetch || !window.AbortController || (init && init.keepalive)) {
            return nativeFetch(input, init);
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), timeoutMs);
        const originalSignal = init && init.signal;
        if (originalSignal) {
            if (originalSignal.aborted) controller.abort();
            else originalSignal.addEventListener('abort', () => controller.abort(), { once: true });
        }

        return nativeFetch(input, { ...init, signal: controller.signal })
            .finally(() => window.clearTimeout(timer));
    }

    function delay(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    async function resilientFetch(input, init) {
        if (!nativeFetch) throw new Error('Fetch unavailable');

        const url = toUrl(input);
        const method = methodOf(input, init);
        const sameOrigin = isSameOrigin(url);
        const normalized = { ...(init || {}) };

        if (sameOrigin && normalized.credentials === undefined) {
            normalized.credentials = 'same-origin';
        }

        if (sameOrigin) {
            normalized.headers = cloneHeaders(input, normalized);
        }

        const timeoutMs = method === 'GET' || method === 'HEAD' ? 7000 : 12000;
        const retryCount = sameOrigin && method === 'GET' ? 1 : 0;
        let lastError = null;
        let response = null;

        for (let attempt = 0; attempt <= retryCount; attempt += 1) {
            try {
                response = await withTimeout(input, normalized, timeoutMs);
                if (!(sameOrigin && method === 'GET' && response && response.status >= 500 && attempt < retryCount)) {
                    notifyMutationIfNeeded(url, method, response);
                    return response;
                }
            } catch (error) {
                lastError = error;
                if (attempt >= retryCount) break;
            }

            await delay(180 + Math.floor(Math.random() * 220));
        }

        if (isSafeMutation(url, method)) {
            const queued = enqueueWindowMutation(url, normalized);
            if (queued) {
                notifyMutation(url.pathname, { queued: true });
                requestServiceWorkerFlush();
                return new Response(JSON.stringify({
                    success: false,
                    sucesso: false,
                    queued: true,
                    offline: true
                }), {
                    status: 202,
                    headers: { 'Content-Type': 'application/json; charset=utf-8' }
                });
            }
        }

        if (response) return response;
        throw lastError || new Error('Network request failed');
    }

    function serializableBody(body) {
        if (body == null) return '';
        if (typeof body === 'string') return body;
        if (body instanceof URLSearchParams) return body.toString();
        return null;
    }

    function enqueueWindowMutation(url, init) {
        try {
            const body = serializableBody(init.body);
            if (body === null) return false;

            const headers = {};
            new Headers(init.headers || undefined).forEach((value, key) => {
                if (['content-type', 'accept'].includes(key.toLowerCase())) headers[key] = value;
            });

            const queue = JSON.parse(window.localStorage.getItem(QUEUE_KEY) || '[]');
            queue.push({
                id: Date.now() + '-' + Math.random().toString(16).slice(2),
                url: url.href,
                method: String(init.method || 'POST').toUpperCase(),
                headers,
                body,
                createdAt: Date.now()
            });
            window.localStorage.setItem(QUEUE_KEY, JSON.stringify(queue.slice(-80)));
            return true;
        } catch (_) {
            return false;
        }
    }

    async function flushWindowQueue() {
        if (!nativeFetch || !navigator.onLine) return;

        let queue;
        try {
            queue = JSON.parse(window.localStorage.getItem(QUEUE_KEY) || '[]');
        } catch (_) {
            queue = [];
        }

        if (!Array.isArray(queue) || queue.length === 0) return;

        const remaining = [];
        for (const item of queue) {
            try {
                const response = await nativeFetch(item.url, {
                    method: item.method,
                    headers: item.headers,
                    body: item.body,
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (response.ok) {
                    notifyMutation(new URL(item.url).pathname, { replayed: true });
                    continue;
                }
            } catch (_) {}

            remaining.push(item);
        }

        try {
            window.localStorage.setItem(QUEUE_KEY, JSON.stringify(remaining.slice(-80)));
        } catch (_) {}
    }

    function notifyMutationIfNeeded(url, method, response) {
        if (!url || !isSameOrigin(url) || ['GET', 'HEAD', 'OPTIONS'].includes(method)) return;
        if (response && response.ok) notifyMutation(url.pathname, { status: response.status });
    }

    function notifyMutation(path, detail) {
        const message = {
            type: 'PIPOCINE_MUTATION',
            path,
            detail: detail || {},
            at: Date.now(),
            version: VERSION
        };

        if (bc) bc.postMessage(message);

        try {
            window.localStorage.setItem('pipocine:last-mutation', JSON.stringify(message));
        } catch (_) {}

        window.dispatchEvent(new CustomEvent('pipocine:mutation', { detail: message }));
    }

    function handleSyncMessage(message) {
        if (!message || message.type !== 'PIPOCINE_MUTATION') return;
        window.dispatchEvent(new CustomEvent('pipocine:sync', { detail: message }));
    }

    function requestServiceWorkerFlush() {
        if (!navigator.serviceWorker || !navigator.serviceWorker.controller) return;
        navigator.serviceWorker.controller.postMessage({ type: 'FLUSH_QUEUE' });
    }

    function registerServiceWorker() {
        const local = ['localhost', '127.0.0.1'].includes(window.location.hostname);
        const secure = window.location.protocol === 'https:' || local;
        if (!('serviceWorker' in navigator) || !secure) return;

        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(registration => {
                    registration.update().catch(() => {});
                    if (registration.waiting) registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                })
                .catch(() => {});
        });

        navigator.serviceWorker.addEventListener('message', event => {
            const data = event.data || {};
            if (data.type === 'PIPOCINE_MUTATION_REPLAYED') {
                notifyMutation(data.path || '/', { replayed: true, source: 'service-worker' });
            }
        });
    }

    function markConnectionState() {
        document.documentElement.dataset.network = navigator.onLine ? 'online' : 'offline';
    }

    if (nativeFetch) {
        window.fetch = resilientFetch;
    }

    if (bc) bc.addEventListener('message', event => handleSyncMessage(event.data));

    window.addEventListener('storage', event => {
        if (event.key !== 'pipocine:last-mutation' || !event.newValue) return;
        try {
            handleSyncMessage(JSON.parse(event.newValue));
        } catch (_) {}
    });

    window.addEventListener('online', () => {
        markConnectionState();
        flushWindowQueue();
        requestServiceWorkerFlush();
    });

    window.addEventListener('offline', markConnectionState);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            flushWindowQueue();
            requestServiceWorkerFlush();
        }
    });

    markConnectionState();
    registerServiceWorker();
    flushWindowQueue();
})();
