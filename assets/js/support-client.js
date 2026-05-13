/**
 * Pipocine Support Client
 * Handles chat creation, polling, sending, images, localStorage sync.
 */
(function () {
    'use strict';

    const STORAGE_KEY   = 'pipo_support';
    const API_BASE      = '/api/suporte';
    const POLL_ACTIVE   = 3000;   // ms while chat window is focused
    const POLL_IDLE     = 10000;  // ms when page is background
    const POLL_CLOSED   = 30000;  // ms when chat is closed
    const TYPING_DEBOUNCE = 2500;

    let state = {
        chatId:       null,
        sessionToken: null,
        status:       'new',  // new | open | pending | closed
        lastMsgId:    0,
        replyTo:      null,   // { id, sender_name, body }
        pendingImage: null,   // { file, previewUrl }
    };

    let pollTimer    = null;
    let typingTimer  = null;
    let controller   = null;

    // ----------------------------------------------------------------
    // INIT
    // ----------------------------------------------------------------
    function init() {
        restoreFromStorage();

        // Register elements
        const startBtn     = document.getElementById('sp-start-btn');
        const sendBtn      = document.getElementById('sp-send-btn');
        const textarea     = document.getElementById('sp-textarea');
        const fileInput    = document.getElementById('sp-file-input');
        const attachBtn    = document.getElementById('sp-attach-btn');
        const imgRemoveBtn = document.getElementById('sp-img-remove');
        const newChatBtn   = document.getElementById('sp-new-chat-btn');
        const lightbox     = document.getElementById('sp-lightbox');
        const lightboxClose = document.getElementById('sp-lightbox-close');

        startBtn?.addEventListener('click', startChat);
        sendBtn?.addEventListener('click', sendMessage);
        attachBtn?.addEventListener('click', () => fileInput?.click());
        imgRemoveBtn?.addEventListener('click', clearImagePreview);
        newChatBtn?.addEventListener('click', resetToNew);
        lightboxClose?.addEventListener('click', () => lightbox?.classList.remove('active'));
        lightbox?.addEventListener('click', e => { if (e.target === lightbox) lightbox.classList.remove('active'); });

        textarea?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        textarea?.addEventListener('input', () => {
            autoResize(textarea);
            sendTyping();
        });

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) handleImageSelect(file);
        });

        // Drag & drop on chat area
        const messagesEl = document.getElementById('sp-messages');
        messagesEl?.addEventListener('dragover', e => { e.preventDefault(); messagesEl.classList.add('sp-drag-over'); });
        messagesEl?.addEventListener('dragleave', () => messagesEl.classList.remove('sp-drag-over'));
        messagesEl?.addEventListener('drop', e => {
            e.preventDefault();
            messagesEl.classList.remove('sp-drag-over');
            const file = e.dataTransfer?.files?.[0];
            if (file && file.type.startsWith('image/')) handleImageSelect(file);
        });

        // Message replies (event delegation)
        messagesEl?.addEventListener('click', e => {
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

        // Sync after login
        const isAuth = document.body.dataset.userId;
        if (isAuth && state.sessionToken && !state.synced) {
            syncToUser();
        }

        // Visibility API for adaptive polling
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                schedulePoll(0); // immediate
            }
        });

        if (state.chatId && state.status !== 'new') {
            showChat();
            schedulePoll(0);
        } else {
            showStart();
        }
    }

    // ----------------------------------------------------------------
    // LOCAL STORAGE
    // ----------------------------------------------------------------
    function saveToStorage() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                chatId:       state.chatId,
                sessionToken: state.sessionToken,
                status:       state.status,
                lastMsgId:    state.lastMsgId,
                synced:       state.synced ?? false,
            }));
        } catch (_) {}
    }

    function restoreFromStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const data = JSON.parse(raw);
            state.chatId       = data.chatId       || null;
            state.sessionToken = data.sessionToken || null;
            state.status       = data.status       || 'new';
            state.lastMsgId    = data.lastMsgId    || 0;
            state.synced       = data.synced       || false;
        } catch (_) {}
    }

    function clearStorage() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
    }

    // ----------------------------------------------------------------
    // SYNC AFTER LOGIN
    // ----------------------------------------------------------------
    async function syncToUser() {
        if (!state.sessionToken) return;
        try {
            await apiPost('chat/sync', { session_token: state.sessionToken });
            state.synced = true;
            saveToStorage();
        } catch (_) {}
    }

    // ----------------------------------------------------------------
    // START CHAT
    // ----------------------------------------------------------------
    async function startChat() {
        const subjectInput = document.getElementById('sp-subject-input');
        const subject      = subjectInput?.value.trim() || 'Duvida geral';
        const startBtn     = document.getElementById('sp-start-btn');
        const guestName    = document.getElementById('sp-guest-name')?.value.trim() || null;

        if (startBtn) startBtn.disabled = true;

        try {
            const data = await apiPost('chat/create', { subject, guest_name: guestName });
            state.chatId       = data.chat_id;
            state.sessionToken = data.session_token;
            state.status       = 'open';
            state.lastMsgId    = 0;
            saveToStorage();
            showChat();
            schedulePoll(0);
        } catch (e) {
            setError(e.message);
        } finally {
            if (startBtn) startBtn.disabled = false;
        }
    }

    // ----------------------------------------------------------------
    // SEND MESSAGE
    // ----------------------------------------------------------------
    async function sendMessage() {
        if (!state.chatId || !state.sessionToken) return;
        if (state.status === 'closed') return;

        const textarea = document.getElementById('sp-textarea');
        const sendBtn  = document.getElementById('sp-send-btn');
        const body     = textarea?.value.trim() ?? '';

        if (!body && !state.pendingImage) return;

        if (sendBtn) sendBtn.disabled = true;

        try {
            const headers = { 'X-Support-Token': state.sessionToken };

            if (state.pendingImage) {
                const fd = new FormData();
                fd.append('image', state.pendingImage.file);
                if (body) fd.append('body', body);
                if (state.replyTo) fd.append('reply_to', String(state.replyTo.id));
                await apiPost('messages/send', fd, true, headers);
            } else {
                await apiPost('messages/send', {
                    body,
                    reply_to: state.replyTo?.id ?? null,
                }, false, headers);
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
        if (!state.chatId || !state.sessionToken) return;

        // Cancel previous inflight
        controller?.abort();
        controller = new AbortController();

        try {
            const url = `${API_BASE}/messages/poll?after=${state.lastMsgId}`;
            const res = await fetch(url, {
                signal:  controller.signal,
                headers: { 'X-Support-Token': state.sessionToken },
            });

            if (!res.ok) throw new Error('Poll failed');
            const data = await res.json();

            if (data.messages?.length) {
                data.messages.forEach(appendMessage);
                state.lastMsgId = data.messages[data.messages.length - 1].id;
                saveToStorage();
                blinkTitle('Nova mensagem');
            }

            updateTypingIndicator(data.typing ?? false);
            updateChatStatus(data.status ?? state.status);

            const interval = state.status === 'closed'
                ? POLL_CLOSED
                : document.visibilityState === 'visible' ? POLL_ACTIVE : POLL_IDLE;

            schedulePoll(interval);
        } catch (e) {
            if (e.name !== 'AbortError') {
                schedulePoll(POLL_IDLE); // backoff on error
            }
        }
    }

    // ----------------------------------------------------------------
    // UI: RENDER MESSAGES
    // ----------------------------------------------------------------
    function appendMessage(msg) {
        const container = document.getElementById('sp-messages');
        const emptyEl   = container?.querySelector('.sp-empty');
        if (emptyEl) emptyEl.remove();

        const isUser    = msg.sender === 'user';
        const isSystem  = msg.sender_name === 'Sistema';
        const cls       = isSystem ? 'sp-msg--system' : (isUser ? 'sp-msg--user' : 'sp-msg--admin');

        let replyHtml = '';
        if (msg.reply_to_message_id && msg.reply_body) {
            replyHtml = `<div class="sp-msg-reply"><strong>${esc(msg.reply_sender_name ?? 'Resposta')}</strong><br>${esc(truncate(msg.reply_body, 80))}</div>`;
        }

        let imageHtml = '';
        if (msg.has_image && msg.image_token) {
            imageHtml = `<img class="sp-msg-image" src="/api/suporte/image/${esc(msg.image_token)}" alt="Imagem" loading="lazy">`;
        }

        const el = document.createElement('div');
        el.className    = `sp-msg ${cls}`;
        el.dataset.msgId      = msg.id;
        el.dataset.senderName = msg.sender_name ?? '';
        el.dataset.body       = msg.body ?? '';

        const avatarHtml = isSystem ? '' : `
            <div class="sp-msg-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            </div>
        `;

        const replyBtnHtml = isSystem ? '' : `<button class="sp-msg-reply-btn" type="button" title="Responder">Responder</button>`;

        el.innerHTML = `
            ${avatarHtml}
            <div class="sp-msg-content">
                ${isSystem ? '' : `<div class="sp-msg-meta"><span class="sp-msg-sender">${esc(msg.sender_name)}</span><span>${timeAgo(msg.created_at)}</span></div>`}
                ${replyHtml}
                <div class="sp-msg-bubble">${esc(msg.body)}${imageHtml}</div>
            </div>
            ${replyBtnHtml}
        `;

        container?.appendChild(el);
        container?.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
    }

    function updateTypingIndicator(active) {
        const el = document.getElementById('sp-typing');
        el?.classList.toggle('active', active);
    }

    function updateChatStatus(status) {
        if (state.status === status) return;
        state.status = status;
        saveToStorage();

        const dot   = document.getElementById('sp-status-dot');
        const label = document.getElementById('sp-status-label');
        const inputBar = document.getElementById('sp-input-bar');
        const closedBar = document.getElementById('sp-chat-closed');
        const replyBar = document.getElementById('sp-reply-bar');

        if (dot) dot.className = 'sp-status-dot ' + (status !== 'open' ? status : '');
        if (label) {
            label.className = 'sp-chat-status-label' + (status === 'closed' ? ' closed' : '');
            label.textContent = { open: 'Aberto', pending: 'Pendente', closed: 'Encerrado' }[status] ?? status;
        }

        if (status === 'closed') {
            inputBar?.style.setProperty('display', 'none');
            replyBar?.classList.remove('active');
            closedBar?.style.setProperty('display', 'flex');
        } else {
            inputBar?.style.removeProperty('display');
            closedBar?.style.setProperty('display', 'none');
        }
    }

    // ----------------------------------------------------------------
    // REPLY
    // ----------------------------------------------------------------
    function setReply(msg) {
        state.replyTo = msg;
        const bar = document.getElementById('sp-reply-bar');
        const txt = document.getElementById('sp-reply-text');
        if (bar) bar.classList.add('active');
        if (txt) txt.innerHTML = `Respondendo a <strong>${esc(msg.sender_name)}</strong>: ${esc(truncate(msg.body, 60))}`;
        document.getElementById('sp-reply-bar-close')?.addEventListener('click', clearReply, { once: true });
        document.getElementById('sp-textarea')?.focus();
    }

    function clearReply() {
        state.replyTo = null;
        document.getElementById('sp-reply-bar')?.classList.remove('active');
    }

    // ----------------------------------------------------------------
    // IMAGE
    // ----------------------------------------------------------------
    function handleImageSelect(file) {
        const maxSize = 5 * 1024 * 1024;
        if (!file.type.startsWith('image/')) { setError('Apenas imagens sao permitidas.'); return; }
        if (file.size > maxSize) { setError('Imagem muito grande. Maximo 5 MB.'); return; }

        const url = URL.createObjectURL(file);
        state.pendingImage = { file, previewUrl: url };

        const preview = document.getElementById('sp-img-preview');
        const img     = document.getElementById('sp-img-preview-img');
        if (preview) preview.classList.add('active');
        if (img) img.src = url;
    }

    function clearImagePreview() {
        if (state.pendingImage?.previewUrl) URL.revokeObjectURL(state.pendingImage.previewUrl);
        state.pendingImage = null;

        const preview   = document.getElementById('sp-img-preview');
        const fileInput = document.getElementById('sp-file-input');
        if (preview) preview.classList.remove('active');
        if (fileInput) fileInput.value = '';
    }

    // ----------------------------------------------------------------
    // TYPING INDICATOR
    // ----------------------------------------------------------------
    function sendTyping() {
        if (!state.chatId || !state.sessionToken) return;
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            fetch(`${API_BASE}/messages/typing`, {
                method:  'POST',
                headers: { 'X-Support-Token': state.sessionToken, 'Content-Type': 'application/json' },
                body:    '{}',
            }).catch(() => {});
        }, TYPING_DEBOUNCE);
    }

    // ----------------------------------------------------------------
    // UI: SHOW/HIDE PANELS
    // ----------------------------------------------------------------
    function showStart() {
        document.getElementById('sp-start-section')?.style.removeProperty('display');
        document.getElementById('sp-chat-section')?.style.setProperty('display', 'none');
    }

    function showChat() {
        document.getElementById('sp-start-section')?.style.setProperty('display', 'none');
        document.getElementById('sp-chat-section')?.style.removeProperty('display');

        const sub = document.getElementById('sp-chat-subject');
        if (sub && state.chatId) sub.textContent = `#${state.chatId}`;
    }

    function resetToNew() {
        clearTimeout(pollTimer);
        controller?.abort();
        clearStorage();
        state = { chatId: null, sessionToken: null, status: 'new', lastMsgId: 0, replyTo: null, pendingImage: null };
        const container = document.getElementById('sp-messages');
        if (container) container.innerHTML = `<div class="sp-empty"><p>Inicie uma conversa para comecar.</p></div>`;
        updateChatStatus('open');
        showStart();
    }

    function setError(msg) {
        const el = document.getElementById('sp-error-msg');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 5000);
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------
    async function apiPost(path, data, isFormData = false, extraHeaders = {}) {
        const headers = { ...extraHeaders };
        let body;

        if (state.sessionToken && !headers['X-Support-Token']) {
            headers['X-Support-Token'] = state.sessionToken;
        }

        if (isFormData) {
            body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data);
        }

        const res = await fetch(`${API_BASE}/${path}`, { method: 'POST', headers, body });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.error ?? 'Erro ao comunicar com o servidor.');
        return json;
    }

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    }

    function truncate(str, n) {
        return String(str ?? '').length > n ? String(str).slice(0, n) + '...' : String(str ?? '');
    }

    function timeAgo(dateStr) {
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60)   return 'agora';
        if (diff < 3600) return `${Math.floor(diff / 60)}min`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        return new Date(dateStr).toLocaleDateString('pt-BR');
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    let titleTimer = null;
    let origTitle  = document.title;
    function blinkTitle(msg) {
        clearInterval(titleTimer);
        let on = true;
        titleTimer = setInterval(() => {
            document.title = on ? msg : origTitle;
            on = !on;
        }, 1200);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                clearInterval(titleTimer);
                document.title = origTitle;
            }
        }, { once: true });
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
