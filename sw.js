/* PipoCine resilience service worker */
'use strict';

const VERSION = '20260523.2';
const CACHE_NAMES = {
    shell: `pipocine-shell-${VERSION}`,
    static: `pipocine-static-${VERSION}`,
    pages: `pipocine-pages-${VERSION}`,
    api: `pipocine-api-${VERSION}`,
    media: `pipocine-media-${VERSION}`
};

const PRECACHE = [
    '/manifest.webmanifest',
    '/assets/js/pipocine-resilience.js?v=20260523.2',
    '/assets/img/logo-pipocine.png',
    '/assets/css/style.css',
    '/assets/css/header.css',
    '/assets/css/content-card.css',
    '/assets/js/header.js',
    '/assets/js/content-card.js'
];

const CACHEABLE_API_PREFIXES = [
    '/api/v2/conteudo',
    '/api/v2/trending',
    '/api/v2/info',
    '/api/v2/plataforma',
    '/api/v2/busca',
    '/api/v3/comments',
    '/api/profiles/avatars'
];

const HANDLE_API_GETS = false;

const SAFE_MUTATIONS = [
    '/api/v3/watch-progress/save',
    '/api/v3/watched-episodes/mark',
    '/api/v3/watched-episodes/unmark',
    '/api/v3/library/watch'
];

const DB_NAME = 'pipocine-resilience';
const DB_VERSION = 1;
const STORE = 'mutations';

self.addEventListener('install', event => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_NAMES.shell);
        await Promise.all(PRECACHE.map(async url => {
            try {
                await cache.add(new Request(url, { cache: 'reload' }));
            } catch (_) {}
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keep = new Set(Object.values(CACHE_NAMES));
        const names = await caches.keys();
        await Promise.all(names.map(name => keep.has(name) ? undefined : caches.delete(name)));
        if (self.registration.navigationPreload) {
            try {
                await self.registration.navigationPreload.enable();
            } catch (_) {}
        }
        await self.clients.claim();
        await flushQueue();
    })());
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' && request.method !== 'HEAD') {
        if (url.origin === location.origin && url.pathname === '/api/auth/logout') {
            event.waitUntil(clearPrivateCaches());
            return;
        }

        if (isSafeMutation(url, request.method)) {
            event.respondWith(networkOrQueue(request));
        }
        return;
    }

    if (url.origin === location.origin && url.pathname === '/sw.js') {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, CACHE_NAMES.pages, 2500, () => offlineHtml(), event));
        return;
    }

    if (HANDLE_API_GETS && isCacheableApi(url)) {
        event.respondWith(networkFirst(request, CACHE_NAMES.api, 3500, () => offlineJson()));
        return;
    }

    if (isStaticAsset(request, url)) {
        event.respondWith(staleWhileRevalidate(request, CACHE_NAMES.static));
        return;
    }

    if (isMediaAsset(request, url)) {
        event.respondWith(cacheFirst(request, CACHE_NAMES.media));
    }
});

self.addEventListener('message', event => {
    const data = event.data || {};
    if (data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    if (data.type === 'FLUSH_QUEUE') {
        event.waitUntil(flushQueue());
        return;
    }

    if (data.type === 'CLEAR_PRIVATE_CACHES') {
        event.waitUntil(clearPrivateCaches());
    }
});

self.addEventListener('sync', event => {
    if (event.tag === 'pipocine-sync-queue') {
        event.waitUntil(flushQueue());
    }
});

function isCacheableApi(url) {
    return url.origin === location.origin
        && CACHEABLE_API_PREFIXES.some(prefix => url.pathname.startsWith(prefix));
}

function isSafeMutation(url, method) {
    return method === 'POST'
        && url.origin === location.origin
        && SAFE_MUTATIONS.includes(url.pathname);
}

function isStaticAsset(request, url) {
    if (url.origin !== location.origin) return false;
    if (url.pathname.startsWith('/assets/')) return true;
    return ['style', 'script', 'font', 'worker'].includes(request.destination);
}

function isMediaAsset(request, url) {
    if (request.destination === 'image') return true;
    return ['image.tmdb.org', 'images.unsplash.com'].includes(url.hostname);
}

async function networkFirst(request, cacheName, timeoutMs, fallbackFactory, event) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    try {
        const preload = event && event.preloadResponse ? await event.preloadResponse : null;
        const response = preload || await fetchWithTimeout(request, timeoutMs);
        if (shouldCache(request, response)) {
            event && event.waitUntil(cache.put(request, response.clone()).catch(() => {}));
            if (!event) cache.put(request, response.clone()).catch(() => {});
        }
        return response;
    } catch (_) {
        if (cached) return cached;
        return fallbackFactory();
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    const update = fetch(request).then(response => {
        if (shouldCache(request, response)) {
            cache.put(request, response.clone()).catch(() => {});
        }
        return response;
    }).catch(() => cached || Response.error());

    return cached || update;
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (shouldCache(request, response, true)) {
        cache.put(request, response.clone()).catch(() => {});
    }
    return response;
}

function fetchWithTimeout(request, timeoutMs) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), timeoutMs);
        fetch(request).then(response => {
            clearTimeout(timer);
            resolve(response);
        }).catch(error => {
            clearTimeout(timer);
            reject(error);
        });
    });
}

function shouldCache(request, response, allowOpaque) {
    if (!response) return false;
    if (allowOpaque && response.type === 'opaque') return true;
    if (response.status !== 200) return false;

    const cacheControl = response.headers.get('Cache-Control') || '';
    if (/no-store/i.test(cacheControl)) return false;

    if (isCacheableApi(new URL(request.url))) {
        return (response.headers.get('Content-Type') || '').includes('application/json');
    }

    return true;
}

async function networkOrQueue(request) {
    const queueRequest = request.clone();

    try {
        const response = await fetch(request.clone());
        if (response.status < 500) return response;
    } catch (_) {}

    await queueMutation(queueRequest);
    await registerSync();
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

async function queueMutation(request) {
    const url = new URL(request.url);
    const headers = {};
    request.headers.forEach((value, key) => {
        if (['content-type', 'accept'].includes(key.toLowerCase())) headers[key] = value;
    });

    const item = {
        id: Date.now() + '-' + Math.random().toString(16).slice(2),
        url: url.href,
        path: url.pathname,
        method: request.method,
        headers,
        body: await request.text(),
        createdAt: Date.now()
    };

    const db = await openDb();
    await txDone(db, STORE, 'readwrite', store => store.put(item));
}

async function flushQueue() {
    const db = await openDb();
    const items = await txDone(db, STORE, 'readonly', store => store.getAll());
    if (!Array.isArray(items) || items.length === 0) return;

    for (const item of items) {
        try {
            const response = await fetch(item.url, {
                method: item.method,
                headers: item.headers,
                body: item.body,
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                if (response.status >= 500) break;
                await deleteMutation(db, item.id);
                continue;
            }

            await deleteMutation(db, item.id);
            await notifyClients({
                type: 'PIPOCINE_MUTATION_REPLAYED',
                path: item.path,
                at: Date.now()
            });
        } catch (_) {
            break;
        }
    }
}

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'id' });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function txDone(db, storeName, mode, action) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, mode);
        const store = tx.objectStore(storeName);
        const request = action(store);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
        tx.onerror = () => reject(tx.error);
    });
}

function deleteMutation(db, id) {
    return txDone(db, STORE, 'readwrite', store => store.delete(id));
}

async function registerSync() {
    if (!self.registration.sync) return;
    try {
        await self.registration.sync.register('pipocine-sync-queue');
    } catch (_) {}
}

async function notifyClients(message) {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clients.forEach(client => client.postMessage(message));
}

async function clearPrivateCaches() {
    await Promise.all([
        caches.delete(CACHE_NAMES.pages),
        caches.delete(CACHE_NAMES.api)
    ]);
}

function offlineJson() {
    return new Response(JSON.stringify({
        success: false,
        sucesso: false,
        offline: true,
        erro: 'Conteudo indisponivel offline.'
    }), {
        status: 503,
        headers: {
            'Content-Type': 'application/json; charset=utf-8',
            'Cache-Control': 'no-store'
        }
    });
}

function offlineHtml() {
    return new Response(
        '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>PipoCine</title><style>' +
        'body{margin:0;background:#08090d;color:#f8fafc;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;display:grid;min-height:100vh;place-items:center}' +
        'main{width:min(520px,calc(100% - 32px));text-align:center}img{width:72px;height:72px;object-fit:contain;margin-bottom:24px}' +
        'h1{font-size:24px;margin:0 0 10px}p{color:#aab2c0;line-height:1.6;margin:0}' +
        '</style></head><body><main><img src="/assets/img/logo-pipocine.png" alt="PipoCine">' +
        '<h1>Sem conexao agora.</h1><p>Quando a rede voltar, o PipoCine tenta sincronizar automaticamente.</p>' +
        '</main></body></html>',
        {
            status: 503,
            headers: {
                'Content-Type': 'text/html; charset=utf-8',
                'Cache-Control': 'no-store'
            }
        }
    );
}
