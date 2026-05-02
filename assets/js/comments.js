/**
 * PIPOCINE — Sistema de Comentários v3
 * Gerencia toda a interação do painel de comentários:
 * abertura, criação, edição, exclusão, likes, menções e paginação.
 *
 * Depende de:
 *   - CommentsSection.php (HTML/estrutura)
 *   - /api/v3/comments/*  (endpoints PHP)
 *   - /api/v3/mentions/*  (endpoints PHP)
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       CONFIGURAÇÃO / ESTADO
    ──────────────────────────────────────────────────────────── */
    const state = {
        contentId:    null,
        contentType:  null,
        profileId:    null,
        myAvatar:     null,
        myUsername:   null,
        isOpen:       false,
        page:         1,
        hasMore:      false,
        loading:      false,
        replyTo:      null,  // { id, username }
        editingId:    null,
        // Cache de perfis para autocomplete de menção
        profileCache: [],
    };

    /* ──────────────────────────────────────────────────────────
       REFS DE ELEMENTOS
    ──────────────────────────────────────────────────────────── */
    const $ = (id) => document.getElementById(id);

    const refs = {
        fab:              () => $('pip-comments-fab'),
        panel:            () => $('pip-comments-panel'),
        overlay:          () => $('pip-comments-overlay'),
        close:            () => $('pip-comments-close'),
        list:             () => $('pip-cp-list'),
        loadingEl:        () => $('pip-cp-loading'),
        emptyEl:          () => $('pip-cp-empty'),
        loadMoreBtn:      () => $('pip-cp-load-more'),
        totalLabel:       () => $('pip-cp-total-label'),
        countBadge:       () => $('pip-comments-count-badge'),
        textarea:         () => $('pip-cp-textarea'),
        submitBtn:        () => $('pip-cp-submit'),
        charCount:        () => $('pip-cp-char-count'),
        replyIndicator:   () => $('pip-cp-reply-indicator'),
        replyToLabel:     () => $('pip-cp-reply-to-label'),
        replyCancel:      () => $('pip-cp-reply-cancel'),
        mentionDropdown:  () => $('pip-cp-mention-dropdown'),
        myAvatarImg:      () => $('pip-cp-my-avatar'),
    };

    /* ──────────────────────────────────────────────────────────
       INICIALIZAÇÃO
    ──────────────────────────────────────────────────────────── */
    function init() {
        // Lê parâmetros da URL (mesmos da view.php)
        const params = new URLSearchParams(window.location.search);
        state.contentId   = params.get('id');
        state.contentType = params.get('type') || 'movie';

        if (!state.contentId) return; // não é uma página de conteúdo

        // Busca dados do perfil atual via endpoint existente
        fetchCurrentProfile();

        // Eventos
        refs.fab()?.addEventListener('click', openPanel);
        refs.close()?.addEventListener('click', closePanel);
        refs.overlay()?.addEventListener('click', closePanel);
        refs.replyCancel()?.addEventListener('click', clearReply);
        refs.loadMoreBtn()?.addEventListener('click', loadMore);
        refs.textarea()?.addEventListener('input', onTextareaInput);
        refs.textarea()?.addEventListener('keydown', onTextareaKeydown);
        refs.submitBtn()?.addEventListener('click', submitComment);

        // Fecha com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && state.isOpen) closePanel();
        });
    }

    async function fetchCurrentProfile() {
        try {
            const res  = await fetch('/api/profiles/current');
            const data = await res.json();
            if (data.sucesso && data.profile) {
                state.profileId  = data.profile.id;
                state.myAvatar   = data.profile.profile_image;
                state.myUsername = data.profile.username;
                if (refs.myAvatarImg()) {
                    refs.myAvatarImg().src = data.profile.profile_image ||
                        `https://api.dicebear.com/7.x/adventurer/svg?seed=${data.profile.id}`;
                    refs.myAvatarImg().alt = data.profile.profile_name || 'Meu perfil';
                }
            }
        } catch (_) { /* silencioso */ }
    }

    /* ──────────────────────────────────────────────────────────
       PAINEL — ABRIR / FECHAR
    ──────────────────────────────────────────────────────────── */
    function openPanel() {
        state.isOpen = true;
        refs.panel()?.classList.add('open');
        refs.panel()?.setAttribute('aria-hidden', 'false');
        refs.fab()?.setAttribute('aria-expanded', 'true');
        refs.overlay()?.classList.add('active');
        document.body.classList.add('pip-comments-open');

        // Carrega comentários na primeira abertura
        if (state.page === 1 && !state.loading) {
            loadComments(true);
        }
    }

    function closePanel() {
        state.isOpen = false;
        refs.panel()?.classList.remove('open');
        refs.panel()?.setAttribute('aria-hidden', 'true');
        refs.fab()?.setAttribute('aria-expanded', 'false');
        refs.overlay()?.classList.remove('active');
        document.body.classList.remove('pip-comments-open');
    }

    /* ──────────────────────────────────────────────────────────
       LISTAGEM DE COMENTÁRIOS
    ──────────────────────────────────────────────────────────── */
    async function loadComments(reset = false) {
        if (state.loading) return;
        state.loading = true;

        if (reset) {
            state.page = 1;
            refs.list().innerHTML     = '';
            refs.list().style.display = 'none';
            refs.emptyEl().style.display  = 'none';
            refs.loadingEl().style.display = 'flex';
            refs.loadMoreBtn().style.display = 'none';
        }

        try {
            const url = `/api/v3/comments?content_id=${state.contentId}&content_type=${state.contentType}&page=${state.page}`;
            const res  = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();

            refs.loadingEl().style.display = 'none';

            if (!data.sucesso) throw new Error(data.erro || 'Erro ao carregar.');

            const { comments, total, has_more } = data;
            state.hasMore = has_more;

            if (total === 0) {
                refs.emptyEl().style.display = 'flex';
                refs.countBadge().textContent = '';
                refs.totalLabel().textContent = '';
                return;
            }

            refs.list().style.display = 'block';
            refs.totalLabel().textContent = `${total}`;
            refs.countBadge().textContent = total > 99 ? '99+' : String(total);

            comments.forEach(c => appendComment(c, refs.list()));

            refs.loadMoreBtn().style.display = has_more ? 'block' : 'none';

        } catch (err) {
            refs.loadingEl().style.display = 'none';
            refs.emptyEl().style.display   = 'flex';
        } finally {
            state.loading = false;
        }
    }

    function loadMore() {
        if (!state.hasMore || state.loading) return;
        state.page++;
        loadComments(false);
    }

    /* ──────────────────────────────────────────────────────────
       RENDERIZAÇÃO DE COMENTÁRIO
    ──────────────────────────────────────────────────────────── */
    function appendComment(comment, container, prepend = false) {
        const tpl   = document.getElementById('tpl-pip-comment');
        if (!tpl) return;

        const node  = tpl.content.cloneNode(true);
        const li    = node.querySelector('.pip-comment');

        const profile = comment.profile || {};
        const avatar  = profile.profile_image ||
            `https://api.dicebear.com/7.x/adventurer/svg?seed=${profile.id || 0}`;

        // Atributos data
        li.dataset.id        = comment.id;
        li.dataset.profileId = comment.profile?.id || 0;
        li.dataset.owner     = String(comment.profile?.id === state.profileId);

        // Avatar
        const avatarEl = li.querySelector('.pip-comment-avatar');
        avatarEl.src = avatar;
        avatarEl.alt = profile.profile_name || 'Perfil';

        // Header
        li.querySelector('.pip-comment-author').textContent  = profile.profile_name || 'Usuário';
        li.querySelector('.pip-comment-username').textContent = `@${profile.username || 'usuario'}`;

        const timeEl = li.querySelector('.pip-comment-time');
        timeEl.textContent = formatDate(comment.created_at);
        timeEl.setAttribute('datetime', comment.created_at);
        timeEl.title = new Date(comment.created_at).toLocaleString('pt-BR');

        if (comment.is_edited) {
            li.querySelector('.pip-comment-edited').style.display = 'inline';
        }

        // Texto (HTML com menções)
        const textEl = li.querySelector('.pip-comment-text');
        textEl.innerHTML = comment.body_html || escHtml(comment.body);

        // Like
        const likeBtn   = li.querySelector('.pip-like-btn');
        const likeCount = li.querySelector('.pip-like-count');
        likeCount.textContent = comment.likes_count > 0 ? String(comment.likes_count) : '';
        likeBtn.setAttribute('aria-pressed', String(comment.viewer_liked));
        likeBtn.addEventListener('click', () => handleLike(comment.id, likeBtn, likeCount));

        // Responder
        const replyBtn = li.querySelector('.pip-reply-btn');
        replyBtn.addEventListener('click', () => setReplyTo(comment.id, profile.username));

        // Ver respostas
        const toggleRepliesBtn = li.querySelector('.pip-toggle-replies-btn');
        const repliesLabel     = li.querySelector('.pip-replies-label');
        const repliesList      = li.querySelector('.pip-replies-list');

        if (comment.replies_count > 0) {
            toggleRepliesBtn.style.display = 'inline-flex';
            repliesLabel.textContent = `${comment.replies_count} resposta${comment.replies_count > 1 ? 's' : ''}`;
            toggleRepliesBtn.addEventListener('click', () =>
                toggleReplies(comment.id, toggleRepliesBtn, repliesList, repliesLabel)
            );
        }

        // Menu de dono (editar/deletar)
        const ownerMenu  = li.querySelector('.pip-comment-owner-menu');
        const editBtn    = li.querySelector('.pip-edit-btn');
        const deleteBtn  = li.querySelector('.pip-delete-btn');

        if (comment.profile?.id === state.profileId) {
            ownerMenu.style.display = 'flex';
            editBtn.addEventListener('click', () => openEditMode(li, comment));
            deleteBtn.addEventListener('click', () => handleDelete(comment.id, li));
        }

        if (prepend) {
            container.prepend(li);
        } else {
            container.appendChild(li);
        }
    }

    /* ──────────────────────────────────────────────────────────
       CRIAR COMENTÁRIO
    ──────────────────────────────────────────────────────────── */
    async function submitComment() {
        const textarea = refs.textarea();
        const body     = textarea.value.trim();
        if (!body) return;

        refs.submitBtn().disabled = true;

        try {
            const payload = {
                content_id:   state.contentId,
                content_type: state.contentType,
                body,
                parent_id:    state.replyTo?.id ?? null,
            };

            const res  = await fetch('/api/v3/comments/create', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify(payload),
            });
            const data = await res.json();

            if (!data.sucesso) throw new Error(data.erro || 'Erro ao publicar.');

            const comment = data.comment;
            // Limpa o campo e reabilita o botão
            textarea.value = '';
            textarea.dispatchEvent(new Event('input'));
            autoResizeTextarea(textarea);
            updateCharCount(0);
            refs.submitBtn().disabled = true;
            clearReply();

            // Se é resposta: insere dentro da sub-lista do pai
            if (comment.parent_id) {
                const parentLi   = refs.list().querySelector(`[data-id="${comment.parent_id}"]`);
                if (parentLi) {
                    let repliesList = parentLi.querySelector('.pip-replies-list');
                    repliesList.style.display = 'block';
                    appendComment(comment, repliesList, false);

                    // Atualiza contagem de respostas
                    const toggleBtn   = parentLi.querySelector('.pip-toggle-replies-btn');
                    const repliesLbl  = parentLi.querySelector('.pip-replies-label');
                    const currentCount = parseInt(repliesLbl.textContent) || 0;
                    const newCount    = currentCount + 1;
                    repliesLbl.textContent = `${newCount} resposta${newCount > 1 ? 's' : ''}`;
                    toggleBtn.style.display = 'inline-flex';
                    toggleBtn.setAttribute('aria-expanded', 'true');
                }
            } else {
                // Comentário raiz: adiciona no topo
                refs.list().style.display = 'block';
                refs.emptyEl().style.display = 'none';
                appendComment(comment, refs.list(), true);
            }

            // Atualiza contador
            const currentTotal = parseInt(refs.totalLabel().textContent) || 0;
            const newTotal = currentTotal + 1;
            refs.totalLabel().textContent = String(newTotal);
            refs.countBadge().textContent = newTotal > 99 ? '99+' : String(newTotal);

        } catch (err) {
            alert(`Erro: ${err.message}`);
        } finally {
            refs.submitBtn().disabled = false;
        }
    }

    /* ──────────────────────────────────────────────────────────
       EDITAR COMENTÁRIO
    ──────────────────────────────────────────────────────────── */
    function openEditMode(li, comment) {
        const textEl     = li.querySelector('.pip-comment-text');
        const actionsEl  = li.querySelector('.pip-comment-actions');

        // Cria área de edição
        const wrap = document.createElement('div');
        wrap.className = 'pip-comment-edit-wrap';
        wrap.innerHTML = `
            <textarea class="pip-comment-edit-textarea">${escHtml(comment.body)}</textarea>
            <div class="pip-comment-edit-actions">
                <button class="pip-comment-edit-save">Salvar</button>
                <button class="pip-comment-edit-cancel">Cancelar</button>
            </div>
        `;

        textEl.style.display    = 'none';
        actionsEl.style.display = 'none';
        li.querySelector('.pip-comment-body-wrap').insertBefore(wrap, textEl);

        const editTextarea = wrap.querySelector('.pip-comment-edit-textarea');
        editTextarea.focus();
        editTextarea.setSelectionRange(editTextarea.value.length, editTextarea.value.length);

        wrap.querySelector('.pip-comment-edit-cancel').addEventListener('click', () => {
            wrap.remove();
            textEl.style.display    = '';
            actionsEl.style.display = '';
        });

        wrap.querySelector('.pip-comment-edit-save').addEventListener('click', async () => {
            const newBody = editTextarea.value.trim();
            if (!newBody) return;

            try {
                const res  = await fetch('/api/v3/comments/edit', {
                    method:      'PUT',
                    credentials: 'same-origin',
                    headers:     { 'Content-Type': 'application/json' },
                    body:        JSON.stringify({ comment_id: comment.id, body: newBody }),
                });
                const data = await res.json();
                if (!data.sucesso) throw new Error(data.erro || 'Erro ao editar.');

                const updated = data.comment;
                textEl.innerHTML = updated.body_html || escHtml(updated.body);
                comment.body     = updated.body;
                comment.body_html = updated.body_html;

                const editedTag = li.querySelector('.pip-comment-edited');
                if (editedTag) editedTag.style.display = 'inline';

                wrap.remove();
                textEl.style.display    = '';
                actionsEl.style.display = '';
            } catch (err) {
                alert(`Erro: ${err.message}`);
            }
        });
    }

    /* ──────────────────────────────────────────────────────────
       DELETAR COMENTÁRIO
    ──────────────────────────────────────────────────────────── */
    async function handleDelete(commentId, li) {
        if (!confirm('Tem certeza que deseja excluir este comentário?')) return;

        try {
            const res  = await fetch('/api/v3/comments/delete', {
                method:      'DELETE',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ comment_id: commentId }),
            });
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.erro || 'Erro ao excluir.');

            // Animação de remoção
            li.style.transition = 'opacity .25s, height .25s, padding .25s';
            li.style.opacity    = '0';
            li.style.overflow   = 'hidden';
            setTimeout(() => li.remove(), 260);

            // Atualiza contador
            const current = parseInt(refs.totalLabel().textContent) || 0;
            const updated = Math.max(0, current - 1);
            refs.totalLabel().textContent = String(updated);
            refs.countBadge().textContent = updated > 0 ? (updated > 99 ? '99+' : String(updated)) : '';

        } catch (err) {
            alert(`Erro: ${err.message}`);
        }
    }

    /* ──────────────────────────────────────────────────────────
       LIKES
    ──────────────────────────────────────────────────────────── */
    async function handleLike(commentId, btn, countEl) {
        const wasLiked = btn.getAttribute('aria-pressed') === 'true';

        // Optimistic update
        btn.setAttribute('aria-pressed', String(!wasLiked));
        const currentCount = parseInt(countEl.textContent) || 0;
        const newCount = wasLiked ? Math.max(0, currentCount - 1) : currentCount + 1;
        countEl.textContent = newCount > 0 ? String(newCount) : '';

        try {
            const res  = await fetch('/api/v3/comments/like', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ comment_id: commentId }),
            });
            const data = await res.json();
            if (!data.sucesso) throw new Error('Erro.');

            btn.setAttribute('aria-pressed', String(data.liked));
            countEl.textContent = data.likes_count > 0 ? String(data.likes_count) : '';
        } catch (_) {
            // Reverte
            btn.setAttribute('aria-pressed', String(wasLiked));
            countEl.textContent = currentCount > 0 ? String(currentCount) : '';
        }
    }

    /* ──────────────────────────────────────────────────────────
       RESPOSTAS (TOGGLE)
    ──────────────────────────────────────────────────────────── */
    async function toggleReplies(commentId, btn, repliesList, labelEl) {
        const isExpanded = btn.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            repliesList.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            return;
        }

        // Busca respostas se lista vazia
        if (repliesList.children.length === 0) {
            btn.disabled = true;
            try {
                const res  = await fetch(`/api/v3/comments/replies?parent_id=${commentId}`, {
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!data.sucesso) throw new Error('Erro.');

                data.replies.forEach(r => appendComment(r, repliesList));
                labelEl.textContent = `${data.replies.length} resposta${data.replies.length !== 1 ? 's' : ''}`;
            } catch (_) { /* silencioso */ } finally {
                btn.disabled = false;
            }
        }

        repliesList.style.display = 'block';
        btn.setAttribute('aria-expanded', 'true');
    }

    /* ──────────────────────────────────────────────────────────
       REPLY — definir / limpar
    ──────────────────────────────────────────────────────────── */
    function setReplyTo(commentId, username) {
        state.replyTo = { id: commentId, username };
        refs.replyIndicator().style.display = 'flex';
        refs.replyToLabel().textContent     = `Respondendo a @${username}`;
        refs.textarea().value               = `@${username} `;
        refs.textarea().focus();
        autoResizeTextarea(refs.textarea());
        updateCharCount(refs.textarea().value.length);
    }

    function clearReply() {
        state.replyTo = null;
        refs.replyIndicator().style.display = 'none';
        refs.replyToLabel().textContent     = '';
    }

    /* ──────────────────────────────────────────────────────────
       TEXTAREA — eventos
    ──────────────────────────────────────────────────────────── */
    function onTextareaInput(e) {
        const textarea  = e.target;
        const submitBtn = refs.submitBtn();
        autoResizeTextarea(textarea);
        updateCharCount(textarea.value.length);
        if (submitBtn) submitBtn.disabled = textarea.value.trim().length === 0;

        // Detecta @menção para autocomplete
        handleMentionInput(textarea);
    }

    function onTextareaKeydown(e) {
        // Ctrl+Enter / Cmd+Enter — submete
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            if (!refs.submitBtn().disabled) submitComment();
            return;
        }

        // Navega no dropdown de menções com setas
        const dropdown = refs.mentionDropdown();
        if (dropdown.style.display !== 'none') {
            const items = dropdown.querySelectorAll('.pip-cp-mention-item');
            const active = dropdown.querySelector('.pip-cp-mention-item.active');
            let idx = Array.from(items).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = (idx + 1) % items.length;
                items.forEach(i => i.classList.remove('active'));
                items[idx]?.classList.add('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = (idx - 1 + items.length) % items.length;
                items.forEach(i => i.classList.remove('active'));
                items[idx]?.classList.add('active');
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                const activeItem = dropdown.querySelector('.pip-cp-mention-item.active');
                if (activeItem) {
                    e.preventDefault();
                    insertMention(activeItem.dataset.username, refs.textarea());
                    dropdown.style.display = 'none';
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        }
    }

    function autoResizeTextarea(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 160) + 'px';
    }

    function updateCharCount(len) {
        const el = refs.charCount();
        if (!el) return;
        el.textContent = `${len} / 2000`;
        el.classList.toggle('pip-char-warn',  len > 1600);
        el.classList.toggle('pip-char-limit', len >= 1950);
    }

    /* ──────────────────────────────────────────────────────────
       AUTOCOMPLETE DE MENÇÃO
    ──────────────────────────────────────────────────────────── */
    let mentionDebounce = null;

    function handleMentionInput(textarea) {
        const val   = textarea.value;
        const pos   = textarea.selectionStart;
        const slice = val.slice(0, pos);
        const match = slice.match(/@([a-zA-Z0-9_]{1,30})$/);

        if (!match) {
            refs.mentionDropdown().style.display = 'none';
            return;
        }

        clearTimeout(mentionDebounce);
        mentionDebounce = setTimeout(() => searchMentions(match[1]), 250);
    }

    async function searchMentions(query) {
        if (query.length < 1) {
            refs.mentionDropdown().style.display = 'none';
            return;
        }

        try {
            const res  = await fetch(`/api/profiles/list`, { credentials: 'same-origin' });
            const data = await res.json();

            const profiles = (data.profiles || data.data || []).filter(p =>
                p.username?.toLowerCase().startsWith(query.toLowerCase()) ||
                p.profile_name?.toLowerCase().startsWith(query.toLowerCase())
            ).slice(0, 6);

            renderMentionDropdown(profiles);
        } catch (_) {
            refs.mentionDropdown().style.display = 'none';
        }
    }

    function renderMentionDropdown(profiles) {
        const dropdown = refs.mentionDropdown();
        if (!profiles.length) {
            dropdown.style.display = 'none';
            return;
        }

        dropdown.innerHTML = profiles.map((p, i) => {
            const avatar = p.profile_image ||
                `https://api.dicebear.com/7.x/adventurer/svg?seed=${p.id}`;
            return `
                <li class="pip-cp-mention-item${i === 0 ? ' active' : ''}"
                    role="option"
                    data-username="${escAttr(p.username)}"
                    data-avatar="${escAttr(avatar)}">
                    <img src="${escAttr(avatar)}" alt="${escAttr(p.profile_name)}">
                    <div>
                        <div class="pip-cp-mention-item-name">${escHtml(p.profile_name)}</div>
                        <div class="pip-cp-mention-item-username">@${escHtml(p.username)}</div>
                    </div>
                </li>
            `;
        }).join('');

        dropdown.querySelectorAll('.pip-cp-mention-item').forEach(item => {
            item.addEventListener('click', () => {
                insertMention(item.dataset.username, refs.textarea());
                dropdown.style.display = 'none';
            });
        });

        dropdown.style.display = 'block';
    }

    function insertMention(username, textarea) {
        const val   = textarea.value;
        const pos   = textarea.selectionStart;
        const slice = val.slice(0, pos);
        const rest  = val.slice(pos);
        const replaced = slice.replace(/@[a-zA-Z0-9_]*$/, `@${username} `);
        textarea.value = replaced + rest;
        textarea.focus();
        const newPos = replaced.length;
        textarea.setSelectionRange(newPos, newPos);
        autoResizeTextarea(textarea);
        updateCharCount(textarea.value.length);
        refs.submitBtn().disabled = textarea.value.trim().length === 0;
    }

    /* ──────────────────────────────────────────────────────────
       UTILITÁRIOS
    ──────────────────────────────────────────────────────────── */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now  = new Date();
        const diff = (now - date) / 1000; // segundos

        if (diff < 60)        return 'agora mesmo';
        if (diff < 3600)      return `${Math.floor(diff / 60)}min atrás`;
        if (diff < 86400)     return `${Math.floor(diff / 3600)}h atrás`;
        if (diff < 604800)    return `${Math.floor(diff / 86400)}d atrás`;

        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escAttr(str) {
        return String(str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ──────────────────────────────────────────────────��───────
       BOOTSTRAP
    ──────────────────────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
