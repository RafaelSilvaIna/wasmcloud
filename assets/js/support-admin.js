/**
 * Pipocine Support Admin JS
 * Chat list, real-time polling, send messages, reply, close/reopen, user info tab.
 */
(function () {
    'use strict';

    const API = '/api/suporte';
    const POLL_INTERVAL      = 8000;
    const POLL_INTERVAL_CHAT = 2200;

    let selectedChatId  = null;
    let lastMsgId       = 0;
    let filterStatus    = 'open';
    let pollListTimer   = null;
    let pollChatTimer   = null;
    let lastPollTime    = nowISO();
    let pendingImage    = null;
    let replyTo         = null;
    let typingTimer     = null;
    let booted          = false;

    // ----------------------------------------------------------------
    // INIT
    // ----------------------------------------------------------------
    function init() {
        if (booted) return;
        booted = true;

        const filterBtns    = document.querySelectorAll('[data-sp-admin-filter]');
        const sendBtn       = document.getElementById('spa-send-btn');
        const attachBtn     = document.getElementById('spa-attach-btn');
        const fileInput     = document.getElementById('spa-file-input');
        const imgRemoveBtn  = document.getElementById('spa-img-remove');
        const textarea      = document.getElementById('spa-textarea');
        const closeBtn      = document.getElementById('spa-close-btn');
        const reopenBtn     = document.getElementById('spa-reopen-btn');
        const tabBtns       = document.querySelectorAll('[data-spa-tab]');
        const searchInput   = document.getElementById('spa-search');
        const refreshBtn    = document.getElementById('spa-refresh-btn');

        filterBtns.forEach(btn => btn.addEventListener('click', () => {
            filterStatus = btn.dataset.spAdminFilter;
            filterBtns.forEach(b => b.classList.toggle('active', b === btn));
            loadChatList();
        }));

        searchInput?.addEventListener('input', debounce(() => loadChatList(), 250));
        refreshBtn?.addEventListener('click', () => loadChatList());

        sendBtn?.addEventListener('click', sendMessage);
        attachBtn?.addEventListener('click', () => fileInput?.click());
        imgRemoveBtn?.addEventListener('click', clearImagePreview);
        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) handleImageSelect(file);
        });
        closeBtn?.addEventListener('click', closeChat);
        reopenBtn?.addEventListener('click', reopenChat);

        textarea?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        textarea?.addEventListener('input', () => {
            autoResize(textarea);
            sendAdminTyping();
        });

        tabBtns.forEach(btn => btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('[data-spa-tab-panel]').forEach(p => {
                p.hidden = p.dataset.spaTapPanel !== btn.dataset.spaTap &&
                           p.dataset.spaTabPanel !== btn.dataset.spaTab;
            });
        }));

        // Reply button delegation
        document.getElementById('spa-messages')?.addEventListener('click', e => {
            const rb = e.target.closest('[data-spa-reply]');
            if (rb) {
                const msgEl = rb.closest('[data-msg-id]');
                setReply({
                    id:          parseInt(msgEl?.dataset.msgId ?? '0'),
                    sender_name: msgEl?.dataset.senderName ?? '',
                    body:        msgEl?.dataset.body ?? '',
                });
            }

            const img = e.target.closest('.spa-msg-image');
            if (img) openLightbox(img.src);
        });

        document.getElementById('spa-reply-close')?.addEventListener('click', clearReply);

        // Lightbox
        const lb = document.getElementById('spa-lightbox');
        document.getElementById('spa-lightbox-close')?.addEventListener('click', () => lb?.classList.remove('active'));
        lb?.addEventListener('click', e => { if (e.target === lb) lb.classList.remove('active'); });

        // Init
        loadChatList();
        startListPoll();
        const initialChat = parseInt(new URLSearchParams(window.location.search).get('chat') || '0', 10);
        if (initialChat) selectChat(initialChat);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                loadChatList();
                if (selectedChatId) pollChatNow();
            }
        });
    }

    // ----------------------------------------------------------------
    // CHAT LIST
    // ----------------------------------------------------------------
    async function loadChatList() {
        const list = document.getElementById('spa-chat-list');
        if (!list) return;

        try {
            const params = new URLSearchParams();
            if (filterStatus) params.set('status', filterStatus);
            const query = document.getElementById('spa-search')?.value.trim();
            if (query) params.set('q', query);

            const data = await apiGet(`admin/chats?${params.toString()}`);
            renderChatList(data.chats ?? []);
            updateCounts(data.counts ?? {});
            lastPollTime = data.server_time || nowISO();
        } catch (e) {
            if (list) list.innerHTML = `<p class="spa-list-empty">Erro ao carregar chats.</p>`;
        }
    }

    function renderChatList(chats) {
        const list = document.getElementById('spa-chat-list');
        if (!list) return;

        if (!chats.length) {
            list.innerHTML = `<p class="spa-list-empty">Nenhum chat ${filterStatus === 'open' ? 'aberto' : filterStatus === 'closed' ? 'encerrado' : 'encontrado'}.</p>`;
            return;
        }

        list.innerHTML = chats.map(chat => `
            <button class="spa-chat-item ${chat.id == selectedChatId ? 'active' : ''}"
                    type="button" data-spa-chat-id="${chat.id}">
                <div class="spa-chat-item-top">
                    <span class="spa-chat-item-name">${esc(chat.user_full_name || chat.guest_name || 'Visitante')}</span>
                    <span class="spa-chat-item-time">${timeAgo(chat.last_message_at || chat.created_at)}</span>
                </div>
                <div class="spa-chat-item-sub">
                    <span>${esc(truncate(chat.subject, 38))}</span>
                    ${chat.unread_admin > 0 ? `<span class="spa-unread-badge">${chat.unread_admin}</span>` : ''}
                </div>
                <div class="spa-chat-item-preview">${esc(truncate(chat.last_preview || 'Sem mensagens ainda', 70))}</div>
                <div class="spa-chat-item-bottom">
                    <span class="spa-status-pill spa-status-${esc(chat.status)}">${statusLabel(chat.status)}</span>
                    ${chat.assigned_admin ? `<span class="spa-assignee">${esc(chat.assigned_admin)}</span>` : ''}
                </div>
            </button>
        `).join('');

        list.querySelectorAll('[data-spa-chat-id]').forEach(btn => {
            btn.addEventListener('click', () => selectChat(parseInt(btn.dataset.spaChatId)));
        });
    }

    function updateCounts(counts) {
        const el = document.getElementById('spa-count-open');
        if (el) el.textContent = counts.open ?? '0';
        const openEl = document.getElementById('spa-stat-open');
        const pendingEl = document.getElementById('spa-stat-pending');
        const closedEl = document.getElementById('spa-stat-closed');
        if (openEl) openEl.textContent = counts.open ?? '0';
        if (pendingEl) pendingEl.textContent = counts.pending ?? '0';
        if (closedEl) closedEl.textContent = counts.closed ?? '0';

        const total = (Number(counts.open) || 0) + (Number(counts.pending) || 0);
        const badge = document.getElementById('spa-unread-badge-total');
        if (badge) badge.textContent = total > 0 ? total : '';
    }

    // ----------------------------------------------------------------
    // SELECT CHAT
    // ----------------------------------------------------------------
    async function selectChat(chatId) {
        selectedChatId = chatId;
        lastMsgId = 0;
        clearTimeout(pollChatTimer);
        syncChatUrl(chatId);

        document.getElementById('spa-chat-panel')?.style.removeProperty('display');
        document.getElementById('spa-placeholder')?.style.setProperty('display', 'none');

        try {
            const data = await apiGet(`admin/chat/${chatId}`);
            renderMessages(data.messages ?? []);
            renderUserInfo(data.user_info ?? null, data.chat ?? {});
            updateAdminChatHeader(data.chat ?? {});
            document.querySelectorAll('[data-spa-chat-id]').forEach(btn => {
                btn.classList.toggle('active', parseInt(btn.dataset.spaChatId) === chatId);
            });

            if (data.messages?.length) {
                lastMsgId = data.messages[data.messages.length - 1].id;
            }
        } catch (e) {
            setError('Erro ao carregar chat: ' + e.message);
        }

        // Refresh list to clear unread badge
        loadChatList();
        startChatPoll();
    }

    function renderMessages(messages) {
        const container = document.getElementById('spa-messages');
        if (!container) return;
        container.innerHTML = '';

        if (!messages.length) {
            container.innerHTML = `<p class="spa-empty-msg">Nenhuma mensagem ainda.</p>`;
            return;
        }

        messages.forEach(msg => appendMessage(msg));
        container.scrollTop = container.scrollHeight;
    }

    function appendMessage(msg) {
        const container = document.getElementById('spa-messages');
        if (!container) return;

        if (container.querySelector('[data-msg-id="' + msg.id + '"]')) return;
        container.querySelector('.spa-empty-msg')?.remove();

        const isUser   = msg.sender === 'user';
        const isSystem = msg.sender_name === 'Sistema';
        const cls      = isSystem ? 'spa-msg--system' : (isUser ? 'spa-msg--user' : 'spa-msg--admin');

        let replyHtml = '';
        if (msg.reply_to_message_id && msg.reply_body) {
            replyHtml = `<div class="spa-msg-reply"><strong>${esc(msg.reply_sender_name ?? '')}</strong>: ${esc(truncate(msg.reply_body, 80))}</div>`;
        }

        let imageHtml = '';
        if (msg.has_image && msg.image_token) {
            imageHtml = `<br><img class="spa-msg-image" src="/api/suporte/image/${esc(msg.image_token)}" alt="Imagem" loading="lazy">`;
        }

        const el = document.createElement('div');
        el.className = `spa-msg ${cls}`;
        el.dataset.msgId      = msg.id;
        el.dataset.senderName = msg.sender_name ?? '';
        el.dataset.body       = msg.body ?? '';

        el.innerHTML = `
            <div class="spa-msg-content">
                ${isSystem ? '' : `<div class="spa-msg-meta"><strong>${esc(msg.sender_name)}</strong> &middot; ${timeAgo(msg.created_at)}</div>`}
                ${replyHtml}
                <div class="spa-msg-bubble">${esc(msg.body)}${imageHtml}</div>
            </div>
            ${isSystem ? '' : `<button class="spa-reply-btn" data-spa-reply type="button">Responder</button>`}
        `;

        container.appendChild(el);
    }

    function renderUserInfo(userInfo, chat) {
        const panel = document.getElementById('spa-user-info');
        if (!panel) return;

        if (!userInfo) {
            panel.innerHTML = `
                <div class="spa-user-anon">
                    <div class="spa-user-icon-lg"></div>
                    <strong>Visitante Anonimo</strong>
                    <span>IP: ${esc(chat.ip_address ?? '-')}</span>
                    <span>Chat criado: ${formatDate(chat.created_at)}</span>
                </div>
            `;
            return;
        }

        panel.innerHTML = `
            <div class="spa-user-detail">
                <div class="spa-user-avatar-wrap">
                    ${userInfo.avatar_url
                        ? `<img src="${esc(userInfo.avatar_url)}" alt="${esc(userInfo.full_name)}" class="spa-user-avatar">`
                        : `<div class="spa-user-icon-lg"></div>`
                    }
                </div>
                <strong class="spa-user-name">${esc(userInfo.full_name)}</strong>
                <table class="spa-user-table">
                    <tr><td>E-mail</td><td>${esc(userInfo.email ?? '-')}</td></tr>
                    <tr><td>Telefone</td><td>${esc(userInfo.phone ?? '-')}</td></tr>
                    <tr><td>Plano</td><td>${esc(userInfo.plan_type ?? 'Gratuito')}</td></tr>
                    <tr><td>Plano expira</td><td>${esc(userInfo.plan_expires ? formatDate(userInfo.plan_expires) : '-')}</td></tr>
                    <tr><td>Cadastro</td><td>${esc(formatDate(userInfo.created_at))}</td></tr>
                    <tr><td>IP</td><td>${esc(chat.ip_address ?? '-')}</td></tr>
                    <tr><td>Status chat</td><td>${statusLabel(chat.status)}</td></tr>
                </table>
            </div>
        `;
    }

    function updateAdminChatHeader(chat) {
        const title  = document.getElementById('spa-chat-title');
        const status = document.getElementById('spa-chat-status');
        const closeBtn  = document.getElementById('spa-close-btn');
        const reopenBtn = document.getElementById('spa-reopen-btn');

        if (title)  title.textContent = `${chat.subject ?? 'Chat #' + chat.id} - ${chat.user_full_name || chat.guest_name || 'Visitante'}`;
        if (status) {
            status.textContent = statusLabel(chat.status);
            status.className   = `spa-status-pill spa-status-${chat.status}`;
        }

        if (closeBtn)  closeBtn.hidden  = chat.status === 'closed';
        if (reopenBtn) reopenBtn.hidden = chat.status !== 'closed';
    }

    // ----------------------------------------------------------------
    // SEND MESSAGE (ADMIN)
    // ----------------------------------------------------------------
    async function sendMessage() {
        if (!selectedChatId) return;

        const textarea = document.getElementById('spa-textarea');
        const sendBtn  = document.getElementById('spa-send-btn');
        const body     = textarea?.value.trim() ?? '';

        if (!body && !pendingImage) return;
        if (sendBtn) sendBtn.disabled = true;

        try {
            if (pendingImage) {
                const fd = new FormData();
                fd.append('chat_id', String(selectedChatId));
                fd.append('image', pendingImage.file);
                if (body)   fd.append('body',     body);
                if (replyTo) fd.append('reply_to', String(replyTo.id));
                await apiPost('admin/messages/send', fd, true);
            } else {
                await apiPost('admin/messages/send', {
                    chat_id:  selectedChatId,
                    body,
                    reply_to: replyTo?.id ?? null,
                });
            }

            if (textarea) { textarea.value = ''; autoResize(textarea); }
            clearImagePreview();
            clearReply();
            pollChatNow();
        } catch (e) {
            setError(e.message);
        } finally {
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    // ----------------------------------------------------------------
    // CLOSE / REOPEN
    // ----------------------------------------------------------------
    async function closeChat() {
        if (!selectedChatId) return;
        if (!confirm('Encerrar este atendimento?')) return;
        try {
            await apiPost(`admin/chat/${selectedChatId}/close`, {});
            selectChat(selectedChatId);
            loadChatList();
        } catch (e) { setError(e.message); }
    }

    async function reopenChat() {
        if (!selectedChatId) return;
        try {
            await apiPost(`admin/chat/${selectedChatId}/reopen`, {});
            selectChat(selectedChatId);
            loadChatList();
        } catch (e) { setError(e.message); }
    }

    // ----------------------------------------------------------------
    // REPLY
    // ----------------------------------------------------------------
    function setReply(msg) {
        replyTo = msg;
        const bar = document.getElementById('spa-reply-bar');
        const txt = document.getElementById('spa-reply-text');
        if (bar) bar.classList.add('active');
        if (txt) txt.innerHTML = `Respondendo a <strong>${esc(msg.sender_name)}</strong>: ${esc(truncate(msg.body, 60))}`;
        document.getElementById('spa-textarea')?.focus();
    }

    function clearReply() {
        replyTo = null;
        document.getElementById('spa-reply-bar')?.classList.remove('active');
    }

    // ----------------------------------------------------------------
    // IMAGE
    // ----------------------------------------------------------------
    function handleImageSelect(file) {
        if (!file.type.startsWith('image/')) { setError('Apenas imagens.'); return; }
        if (file.size > 5 * 1024 * 1024) { setError('Imagem maior que 5 MB.'); return; }
        const url = URL.createObjectURL(file);
        pendingImage = { file, url };
        const preview = document.getElementById('spa-img-preview');
        const img     = document.getElementById('spa-img-preview-img');
        if (preview) preview.classList.add('active');
        if (img) img.src = url;
    }

    function clearImagePreview() {
        if (pendingImage?.url) URL.revokeObjectURL(pendingImage.url);
        pendingImage = null;
        const preview   = document.getElementById('spa-img-preview');
        const fileInput = document.getElementById('spa-file-input');
        if (preview) preview.classList.remove('active');
        if (fileInput) fileInput.value = '';
    }

    // ----------------------------------------------------------------
    // ADMIN TYPING
    // ----------------------------------------------------------------
    function sendAdminTyping() {
        if (!selectedChatId) return;
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            fetch(`${API}/admin/messages/typing`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ chat_id: selectedChatId }),
            }).catch(() => {});
        }, 450);
    }

    // ----------------------------------------------------------------
    // POLLING
    // ----------------------------------------------------------------
    function startListPoll() {
        clearInterval(pollListTimer);
        pollListTimer = setInterval(() => {
            pollListUpdates();
        }, POLL_INTERVAL);
    }

    function startChatPoll() {
        clearTimeout(pollChatTimer);
        pollChatTimer = setTimeout(pollChat, POLL_INTERVAL_CHAT);
    }

    async function pollChatNow() {
        clearTimeout(pollChatTimer);
        await pollChat();
    }

    async function pollChat() {
        if (!selectedChatId) return;
        try {
            const data = await apiGet(`admin/chat/${selectedChatId}/poll?after=${lastMsgId}`);
            const newMsgs = data.messages ?? [];
            newMsgs.forEach(appendMessage);
            if (newMsgs.length) {
                lastMsgId = newMsgs[newMsgs.length - 1].id;
                document.getElementById('spa-messages')?.scrollTo({ top: 99999, behavior: 'smooth' });
                loadChatList();
            }

            // Typing
            const typingEl = document.getElementById('spa-typing');
            if (typingEl) typingEl.hidden = !data.typing;

            updateAdminChatHeader(data.chat ?? {});
        } catch (_) {}

        startChatPoll();
    }

    async function pollListUpdates() {
        if (document.visibilityState !== 'visible') return;

        try {
            const data = await apiGet(`admin/poll?since=${encodeURIComponent(lastPollTime)}`);
            updateCounts(data.counts ?? {});
            lastPollTime = data.server_time || nowISO();
            if ((data.updated_chat_ids ?? []).length) {
                loadChatList();
            }
        } catch (_) {}
    }

    async function pollAdminStats() {
        try {
            const data = await apiGet('admin/stats');
            updateCounts(data.counts ?? {});
        } catch (_) {}
    }

    // ----------------------------------------------------------------
    // LIGHTBOX
    // ----------------------------------------------------------------
    function openLightbox(src) {
        const lb = document.getElementById('spa-lightbox');
        if (!lb) return;
        lb.querySelector('img').src = src;
        lb.classList.add('active');
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------
    async function apiGet(path) {
        const res = await fetch(`${API}/${path}`);
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.error ?? 'Erro na API.');
        return json;
    }

    async function apiPost(path, data, isFormData = false) {
        const opts = { method: 'POST' };
        if (isFormData) {
            opts.body = data;
        } else {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(data);
        }
        const res  = await fetch(`${API}/${path}`, opts);
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.error ?? 'Erro na API.');
        return json;
    }

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    }

    function truncate(str, n) {
        const s = String(str ?? '');
        return s.length > n ? s.slice(0, n) + '...' : s;
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '-';
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return `${Math.floor(diff / 60)}min`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        return new Date(dateStr).toLocaleDateString('pt-BR');
    }

    function formatDate(str) {
        if (!str) return '-';
        return new Date(str).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function statusLabel(s) {
        return { open: 'Aberto', pending: 'Pendente', closed: 'Encerrado' }[s] ?? s;
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function debounce(fn, wait) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function syncChatUrl(chatId) {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('route', 'suporte');
            url.searchParams.set('chat', String(chatId));
            history.replaceState({ route: 'suporte', chat: chatId }, '', url.pathname + url.search);
        } catch (_) {}
    }

    function nowISO() {
        return new Date().toISOString().replace('T', ' ').slice(0, 19);
    }

    function setError(msg) {
        const el = document.getElementById('spa-error');
        if (!el) { console.error('[v0 support-admin]', msg); return; }
        el.textContent = msg;
        el.hidden = false;
        setTimeout(() => { el.hidden = true; }, 5000);
    }

    // ----------------------------------------------------------------
    // BOOT
    // ----------------------------------------------------------------
    window.AdminSupportPanel = { init };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
