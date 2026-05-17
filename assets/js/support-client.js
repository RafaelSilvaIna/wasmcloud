/**
 * Pipocine Support Client — v2
 * Runs only on the chat page (sp-chat-page body class).
 * Resolves the session token from localStorage (or URL param for guests),
 * then polls and handles messaging.
 */
(function () {
    'use strict';

    // Only run on the chat page
    if (!document.body.classList.contains('sp-chat-page')) return;

    const API_BASE       = '/api/suporte';
    const STORAGE_KEY    = 'pipo_support';
    const HISTORY_KEY    = 'pipo_support_history';
    const POLL_ACTIVE    = 3000;
    const POLL_IDLE      = 10000;
    const POLL_CLOSED    = 30000;

    const chatId    = parseInt(document.body.dataset.chatId || '0', 10);
    const urlToken  = document.body.dataset.urlToken || null;

    let sessionToken = null;
    let pollTimer    = null;
    let controller   = null;
    let lastMsgId    = 0;
    let chatStatus   = 'open';
    let replyTo      = null;
    let pendingImage = null;
    let polling      = false; // guard: only one in-flight poll at a time
    let typingTimer  = null;

    // ----------------------------------------------------------------
    // INIT
    // ----------------------------------------------------------------
    async function init() {
        if (!chatId) {
            showDenied();
            return;
        }

        const isAuth = document.body.dataset.auth === '1';

        sessionToken = resolveToken();

        // If no local token but user is authenticated, ask the server for the token
        if (!sessionToken && isAuth) {
            sessionToken = await fetchTokenForUser();
        }

        if (!sessionToken) {
            showDenied();
            return;
        }

        // Update localStorage with this chat context
        persistCurrent();

        showChatUI();
        bindEvents();
        schedulePoll(0);

        // Adaptive polling on tab focus
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') schedulePoll(0);
        });
    }

    async function fetchTokenForUser() {
        try {
            const res = await fetch(API_BASE + '/chat/token-for-user?chat_id=' + chatId);
            if (!res.ok) return null;
            const data = await res.json();
            if (!data.success || !data.session_token) return null;
            return data.session_token;
        } catch (_) {
            return null;
        }
    }

    // ----------------------------------------------------------------
    // TOKEN RESOLUTION
    // ----------------------------------------------------------------
    function resolveToken() {
        // 1. URL param (guest redirect from novo.php)
        if (urlToken) return urlToken;

        // 2. Current active localStorage chat
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                const d = JSON.parse(raw);
                if (d.chatId === chatId && d.sessionToken) return d.sessionToken;
            }
        } catch (_) {}

        // 3. History archive
        try {
            const raw = localStorage.getItem(HISTORY_KEY);
            if (raw) {
                const arr = JSON.parse(raw);
                const match = arr.find(function (d) { return d.chatId === chatId && d.sessionToken; });
                if (match) return match.sessionToken;
            }
        } catch (_) {}

        return null;
    }

    function persistCurrent() {
        try {
            const existing = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
            // If there's a different active chat, archive it first
            if (existing && existing.chatId && existing.chatId !== chatId) {
                const hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                if (!hist.find(function (d) { return d.chatId === existing.chatId; })) {
                    hist.unshift(existing);
                    if (hist.length > 20) hist.length = 20;
                    localStorage.setItem(HISTORY_KEY, JSON.stringify(hist));
                }
            }
            const current = (existing && existing.chatId === chatId) ? existing : {};
            localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.assign({}, current, {
                chatId,
                sessionToken,
                status: chatStatus,
                lastMsgId,
                updatedAt: new Date().toISOString(),
            })));
        } catch (_) {}
    }

    function saveStatus(status) {
        chatStatus = status;
        try {
            const raw  = localStorage.getItem(STORAGE_KEY);
            const data = raw ? JSON.parse(raw) : {};
            data.status    = status;
            data.updatedAt = new Date().toISOString();
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (_) {}
    }

    function saveLastMsgId(id) {
        lastMsgId = id;
        try {
            const raw  = localStorage.getItem(STORAGE_KEY);
            const data = raw ? JSON.parse(raw) : {};
            data.lastMsgId = id;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (_) {}
    }

    // ----------------------------------------------------------------
    // UI STATES
    // ----------------------------------------------------------------
    function showChatUI() {
        document.getElementById('sp-chat-loading')?.style.setProperty('display', 'none');
        document.getElementById('sp-chat-denied')?.style.setProperty('display', 'none');
        const ui = document.getElementById('sp-chat-ui');
        if (ui) ui.style.removeProperty('display');
    }

    function showDenied() {
        document.getElementById('sp-chat-loading')?.style.setProperty('display', 'none');
        document.getElementById('sp-chat-ui')?.style.setProperty('display', 'none');
        const denied = document.getElementById('sp-chat-denied');
        if (denied) denied.style.removeProperty('display');
    }

    // ----------------------------------------------------------------
    // EVENTS
    // ----------------------------------------------------------------
    function bindEvents() {
        const sendBtn      = document.getElementById('sp-send-btn');
        const textarea     = document.getElementById('sp-textarea');
        const fileInput    = document.getElementById('sp-file-input');
        const attachBtn    = document.getElementById('sp-attach-btn');
        const imgRemoveBtn = document.getElementById('sp-img-remove');
        const lightbox     = document.getElementById('sp-lightbox');
        const lightboxClose = document.getElementById('sp-lightbox-close');
        const replyBarClose = document.getElementById('sp-reply-bar-close');
        const messagesEl   = document.getElementById('sp-messages');

        sendBtn?.addEventListener('click', sendMessage);
        attachBtn?.addEventListener('click', function () { fileInput?.click(); });
        imgRemoveBtn?.addEventListener('click', clearImagePreview);
        lightboxClose?.addEventListener('click', function () { lightbox?.classList.remove('active'); });
        lightbox?.addEventListener('click', function (e) {
            if (e.target === lightbox) lightbox.classList.remove('active');
        });
        replyBarClose?.addEventListener('click', clearReply);

        textarea?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        textarea?.addEventListener('input', function () {
            autoResize(textarea);
            sendUserTyping();
        });

        fileInput?.addEventListener('change', function () {
            const file = fileInput.files?.[0];
            if (file) handleImageSelect(file);
        });

        // Drag-and-drop
        messagesEl?.addEventListener('dragover', function (e) {
            e.preventDefault();
            messagesEl.classList.add('sp-drag-over');
        });
        messagesEl?.addEventListener('dragleave', function () {
            messagesEl.classList.remove('sp-drag-over');
        });
        messagesEl?.addEventListener('drop', function (e) {
            e.preventDefault();
            messagesEl.classList.remove('sp-drag-over');
            const file = e.dataTransfer?.files?.[0];
            if (file && file.type.startsWith('image/')) handleImageSelect(file);
        });

        // Reply / lightbox delegation
        messagesEl?.addEventListener('click', function (e) {
            const replyBtn = e.target.closest('.sp-msg-reply-btn');
            if (replyBtn) {
                const msgEl = replyBtn.closest('.sp-msg');
                setReply({
                    id:          parseInt(msgEl?.dataset.msgId ?? '0'),
                    sender_name: msgEl?.dataset.senderName ?? '',
                    body:        msgEl?.dataset.body ?? '',
                });
            }
            const img = e.target.closest('.sp-msg-image');
            if (img && lightbox) {
                lightbox.querySelector('img').src = img.src;
                lightbox.classList.add('active');
            }
        });
    }

    // ----------------------------------------------------------------
    // SEND MESSAGE
    // ----------------------------------------------------------------
    async function sendMessage() {
        if (!sessionToken || chatStatus === 'closed') return;

        const textarea = document.getElementById('sp-textarea');
        const sendBtn  = document.getElementById('sp-send-btn');
        const body     = textarea?.value.trim() ?? '';

        if (!body && !pendingImage) return;
        if (sendBtn) sendBtn.disabled = true;

        try {
            const headers = { 'X-Support-Token': sessionToken };

            if (pendingImage) {
                const fd = new FormData();
                fd.append('image', pendingImage.file);
                if (body) fd.append('body', body);
                if (replyTo) fd.append('reply_to', String(replyTo.id));
                await apiPost('messages/send', fd, true, headers);
            } else {
                await apiPost('messages/send', { body, reply_to: replyTo?.id ?? null }, false, headers);
            }

            if (textarea) { textarea.value = ''; autoResize(textarea); }
            clearImagePreview();
            clearReply();
            schedulePoll(0);
        } catch (e) {
            setError(e.message);
        } finally {
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    // ----------------------------------------------------------------
    // POLL
    // ----------------------------------------------------------------
    function schedulePoll(delay) {
        clearTimeout(pollTimer);
        pollTimer = setTimeout(doPoll, delay);
    }

    async function doPoll() {
        if (!sessionToken) return;
        if (polling) return; // prevent concurrent polls
        polling = true;

        controller?.abort();
        controller = new AbortController();

        try {
            const res = await fetch(`${API_BASE}/messages/poll?after=${lastMsgId}`, {
                signal:  controller.signal,
                headers: { 'X-Support-Token': sessionToken },
            });

            if (!res.ok) {
                if (res.status === 401 || res.status === 403) { showDenied(); return; }
                throw new Error('Poll failed');
            }

            const data = await res.json();

            if (data.messages?.length) {
                let hasNew = false;
                data.messages.forEach(function (msg) {
                    if (!appendMessage(msg)) hasNew = true;
                });
                saveLastMsgId(data.messages[data.messages.length - 1].id);
                if (hasNew && data.messages.some(function (msg) { return msg.sender === 'admin'; })) {
                    blinkTitle('Nova mensagem');
                }
            }

            updateTypingIndicator(data.typing ?? false);
            updateChatStatus(data.status ?? chatStatus);

            // Update sidebar subject if provided
            if (data.subject) updateSubjectUI(data.subject);

            const interval = chatStatus === 'closed'
                ? POLL_CLOSED
                : document.visibilityState === 'visible' ? POLL_ACTIVE : POLL_IDLE;

            schedulePoll(interval);
        } catch (e) {
            if (e.name !== 'AbortError') schedulePoll(POLL_IDLE);
        } finally {
            polling = false;
        }
    }

    // ----------------------------------------------------------------
    // RENDER MESSAGES
    // ----------------------------------------------------------------
    // Returns true if message was a duplicate (already in DOM), false if newly added.
    function appendMessage(msg) {
        const container = document.getElementById('sp-messages');

        // Deduplication: skip if a node with this ID already exists
        if (container?.querySelector('[data-msg-id="' + msg.id + '"]')) return true;

        const emptyEl = container?.querySelector('.sp-empty');
        if (emptyEl) emptyEl.remove();

        const isUser   = msg.sender === 'user';
        const isSystem = msg.sender_name === 'Sistema';
        const cls      = isSystem ? 'sp-msg--system' : (isUser ? 'sp-msg--user' : 'sp-msg--admin');

        let replyHtml = '';
        if (msg.reply_to_message_id && msg.reply_body) {
            replyHtml = '<div class="sp-msg-reply"><strong>' + esc(msg.reply_sender_name ?? 'Resposta') + '</strong><br>' + esc(truncate(msg.reply_body, 80)) + '</div>';
        }

        let imageHtml = '';
        if (msg.has_image && msg.image_token) {
            imageHtml = '<img class="sp-msg-image" src="/api/suporte/image/' + esc(msg.image_token) + '" alt="Imagem" loading="lazy">';
        }

        const el = document.createElement('div');
        el.className           = 'sp-msg ' + cls;
        el.dataset.msgId       = msg.id;
        el.dataset.senderName  = msg.sender_name ?? '';
        el.dataset.body        = msg.body ?? '';

        const avatarHtml   = isSystem ? '' : '<div class="sp-msg-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>';
        const replyBtnHtml = isSystem ? '' : '<button class="sp-msg-reply-btn" type="button">Responder</button>';

        el.innerHTML = avatarHtml +
            '<div class="sp-msg-content">' +
                (isSystem ? '' : '<div class="sp-msg-meta"><span class="sp-msg-sender">' + esc(msg.sender_name) + '</span><span>' + timeAgo(msg.created_at) + '</span></div>') +
                replyHtml +
                '<div class="sp-msg-bubble">' + esc(msg.body) + imageHtml + '</div>' +
            '</div>' +
            replyBtnHtml;

        // Insert before typing indicator
        const typing = document.getElementById('sp-typing');
        if (typing) container.insertBefore(el, typing);
        else container?.appendChild(el);

        container?.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
        return false; // newly added
    }

    function updateSubjectUI(subject) {
        const sidebarSubject = document.getElementById('sp-sidebar-subject');
        const panelTitle     = document.getElementById('sp-panel-title');
        const navLabel       = document.getElementById('sp-nav-chat-label');
        if (sidebarSubject) sidebarSubject.textContent = subject;
        if (panelTitle) panelTitle.textContent = subject;
        if (navLabel) navLabel.textContent = subject;
    }

    function updateTypingIndicator(active) {
        document.getElementById('sp-typing')?.classList.toggle('active', active);
    }

    function updateChatStatus(status) {
        if (chatStatus === status && status !== 'open') return;
        saveStatus(status);

        const dot       = document.getElementById('sp-status-dot-chat');
        const label     = document.getElementById('sp-status-label-chat');
        const sidebarTxt = document.getElementById('sp-sidebar-status-text');
        const inputBar  = document.getElementById('sp-input-bar');
        const closedBar = document.getElementById('sp-chat-closed');
        const replyBar  = document.getElementById('sp-reply-bar');

        const statusMap = { open: 'Aberto', pending: 'Pendente', closed: 'Encerrado' };

        if (dot) dot.className = 'sp-status-dot' + (status !== 'open' ? ' ' + status : '');
        if (label) {
            label.className   = 'sp-chat-status-label' + (status === 'closed' ? ' closed' : '');
            label.textContent = statusMap[status] ?? status;
        }
        if (sidebarTxt) sidebarTxt.textContent = statusMap[status] ?? status;

        if (status === 'closed') {
            inputBar?.style.setProperty('display', 'none');
            replyBar?.classList.remove('active');
            closedBar?.style.removeProperty('display');
        } else {
            inputBar?.style.removeProperty('display');
            closedBar?.style.setProperty('display', 'none');
        }
    }

    // ----------------------------------------------------------------
    // REPLY
    // ----------------------------------------------------------------
    function setReply(msg) {
        replyTo = msg;
        const bar = document.getElementById('sp-reply-bar');
        const txt = document.getElementById('sp-reply-text');
        if (bar) bar.classList.add('active');
        if (txt) txt.innerHTML = 'Respondendo a <strong>' + esc(msg.sender_name) + '</strong>: ' + esc(truncate(msg.body, 60));
        document.getElementById('sp-textarea')?.focus();
    }

    function clearReply() {
        replyTo = null;
        document.getElementById('sp-reply-bar')?.classList.remove('active');
    }

    // ----------------------------------------------------------------
    // IMAGE
    // ----------------------------------------------------------------
    function handleImageSelect(file) {
        if (!file.type.startsWith('image/')) { setError('Apenas imagens sao permitidas.'); return; }
        if (file.size > 5 * 1024 * 1024) { setError('Imagem muito grande. Maximo 5 MB.'); return; }
        const url = URL.createObjectURL(file);
        pendingImage = { file, previewUrl: url };
        const preview = document.getElementById('sp-img-preview');
        const img     = document.getElementById('sp-img-preview-img');
        if (preview) preview.classList.add('active');
        if (img) img.src = url;
    }

    function clearImagePreview() {
        if (pendingImage?.previewUrl) URL.revokeObjectURL(pendingImage.previewUrl);
        pendingImage = null;
        document.getElementById('sp-img-preview')?.classList.remove('active');
        const fi = document.getElementById('sp-file-input');
        if (fi) fi.value = '';
    }

    function sendUserTyping() {
        if (!sessionToken || chatStatus === 'closed') return;

        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () {
            fetch(API_BASE + '/messages/typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Support-Token': sessionToken,
                },
                body: JSON.stringify({ chat_id: chatId }),
            }).catch(function () {});
        }, 450);
    }

    // ----------------------------------------------------------------
    // SYNC TO USER AFTER LOGIN
    // ----------------------------------------------------------------
    async function syncToUser() {
        try {
            await apiPost('chat/sync', { session_token: sessionToken });
        } catch (_) {}
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------
    async function apiPost(path, data, isFormData, extraHeaders) {
        const headers = Object.assign({}, extraHeaders || {});
        let body;
        if (sessionToken && !headers['X-Support-Token']) headers['X-Support-Token'] = sessionToken;
        if (isFormData) {
            body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data);
        }
        const res  = await fetch(API_BASE + '/' + path, { method: 'POST', headers, body });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.error ?? 'Erro ao comunicar com o servidor.');
        return json;
    }

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function truncate(str, n) {
        return String(str ?? '').length > n ? String(str).slice(0, n) + '...' : String(str ?? '');
    }

    function timeAgo(dateStr) {
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return Math.floor(diff / 60) + 'min';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return new Date(dateStr).toLocaleDateString('pt-BR');
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function setError(msg) {
        const el = document.getElementById('sp-error-msg');
        if (!el) return;
        el.textContent = msg;
        el.style.display = '';
        setTimeout(function () { el.style.display = 'none'; }, 5000);
    }

    let titleTimer = null;
    const origTitle = document.title;
    function blinkTitle(msg) {
        clearInterval(titleTimer);
        let on = true;
        titleTimer = setInterval(function () {
            document.title = on ? msg : origTitle;
            on = !on;
        }, 1000);
        setTimeout(function () { clearInterval(titleTimer); document.title = origTitle; }, 8000);
    }

    // ----------------------------------------------------------------
    // BOOT
    // ----------------------------------------------------------------
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
