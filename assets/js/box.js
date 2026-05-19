(function () {
    const list = document.getElementById('box-list');
    const count = document.getElementById('box-count');
    const refresh = document.getElementById('box-refresh');
    const reader = document.getElementById('box-reader');
    const readerTitle = document.getElementById('box-reader-title');
    const readerDate = document.getElementById('box-reader-date');
    const readerBody = document.getElementById('box-reader-body');
    const readerActions = document.getElementById('box-reader-actions');
    const readerClose = document.getElementById('box-reader-close');
    let currentItems = [];

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);

    const statusText = (status) => ({
        pending: 'Pendente',
        accepted: 'Aceito',
        declined: 'Recusado',
        canceled: 'Cancelado',
        none: 'Informativo'
    })[status] || 'Informativo';

    const dateKey = (value) => {
        const date = new Date(String(value || '').replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return 'Sem data';
        return date.toISOString().slice(0, 10);
    };

    const dateLabel = (key) => {
        if (key === 'Sem data') return key;
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        const keyDate = new Date(`${key}T00:00:00`);
        if (key === today.toISOString().slice(0, 10)) return 'Hoje';
        if (key === yesterday.toISOString().slice(0, 10)) return 'Ontem';
        return keyDate.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
    };

    const fullDate = (value) => {
        const date = new Date(String(value || '').replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return 'Mensagem';
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    async function api(path, payload) {
        const response = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || data.error || 'Nao foi possivel concluir a acao.');
        }
        return data;
    }

    function groupedByDay(items) {
        return items.reduce((groups, item) => {
            const key = dateKey(item.created_at);
            groups[key] = groups[key] || [];
            groups[key].push(item);
            return groups;
        }, {});
    }

    function itemPreview(item) {
        if (item.type === 'family_invite') {
            return `${item.actor?.name || 'Uma conta Pipocine'} enviou um convite familiar.`;
        }
        return item.body || 'Mensagem do Pipocine.';
    }

    function messageBody(item) {
        if (item.type === 'family_invite') {
            return `
                <p><strong>${esc(item.actor?.name || 'Um titular')}</strong> convidou sua conta para entrar na familia do Plano Gold.</p>
                <p>Ao aceitar, voce passa a receber beneficios familiares especificos no Pipocine.</p>
                <ul>
                    <li>Sem anuncios</li>
                    <li>Personalizacao de perfil</li>
                    <li>Player completo</li>
                    <li>Qualidade 2K</li>
                </ul>
                <p>Se nao reconhecer essa solicitacao, recuse. Sua decisao fica registrada com seguranca.</p>
            `;
        }

        return `<p>${esc(item.body || 'Mensagem informativa do Pipocine.')}</p>`;
    }

    function render(items, unread) {
        currentItems = items;
        count.textContent = unread === 1 ? '1 nova notificacao' : `${unread} novas notificacoes`;

        if (!items.length) {
            list.innerHTML = '<div class="box-empty">Sua caixa de entrada esta vazia.</div>';
            return;
        }

        const groups = groupedByDay(items);
        list.innerHTML = Object.keys(groups).map((key) => `
            <section class="box-day">
                <div class="box-day-label">${esc(dateLabel(key))}</div>
                ${groups[key].map((item) => `
                    <article class="box-item ${item.status === 'unread' ? 'unread' : ''}" data-id="${item.id}">
                        <div class="box-item-icon"><i data-lucide="${item.status === 'unread' ? 'mail' : 'mail-open'}"></i></div>
                        <div>
                            <h2>${esc(item.title)}</h2>
                            <p>${esc(itemPreview(item))}</p>
                            <div class="box-meta">
                                <span>${esc(item.actor?.name || 'Pipocine')}</span>
                                <span class="box-status ${esc(item.action_status)}">${statusText(item.action_status)}</span>
                            </div>
                        </div>
                        <button class="box-open-btn" type="button" data-open-message="${item.id}">
                            <span>Abrir</span>
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </article>
                `).join('')}
            </section>
        `).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function load() {
        list.innerHTML = '<div class="box-empty">Carregando sua caixa...</div>';
        const response = await fetch('/api/v4/box/items');
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Nao foi possivel carregar sua Box.');
        render(data.items || [], Number(data.unread || 0));
    }

    function closeReader() {
        reader?.classList.remove('open');
        reader?.setAttribute('aria-hidden', 'true');
        readerActions.innerHTML = '';
    }

    async function openReader(id) {
        const item = currentItems.find((entry) => Number(entry.id) === Number(id));
        if (!item) return;

        readerTitle.textContent = item.title;
        readerDate.textContent = fullDate(item.created_at);
        readerBody.innerHTML = messageBody(item);

        const pendingFamilyInvite = item.type === 'family_invite' && item.action_status === 'pending';
        readerActions.innerHTML = pendingFamilyInvite ? `
            <button class="box-btn decline" type="button" data-reader-action="decline" data-id="${item.id}">Recusar</button>
            <button class="box-btn accept" type="button" data-reader-action="accept" data-id="${item.id}">Aceitar convite</button>
        ` : '<button class="box-btn decline" type="button" data-reader-close>Fechar</button>';

        reader?.classList.add('open');
        reader?.setAttribute('aria-hidden', 'false');

        if (item.status === 'unread') {
            try {
                await api('/api/v4/box/read', { id: item.id });
                item.status = 'read';
                const unread = currentItems.filter((entry) => entry.status === 'unread').length;
                render(currentItems, unread);
            } catch (_) {}
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    list?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-open-message]');
        if (!button) return;
        openReader(Number(button.dataset.openMessage));
    });

    readerActions?.addEventListener('click', async (event) => {
        const close = event.target.closest('[data-reader-close]');
        if (close) {
            closeReader();
            return;
        }

        const button = event.target.closest('[data-reader-action]');
        if (!button) return;

        const endpoint = button.dataset.readerAction === 'accept'
            ? '/api/v4/box/family/accept'
            : '/api/v4/box/family/decline';

        button.disabled = true;
        try {
            const data = await api(endpoint, { id: Number(button.dataset.id || 0) });
            window.PipoNotification?.success(data.message || 'Acao concluida.');
            closeReader();
            await load();
        } catch (error) {
            window.PipoNotification?.error(error.message);
            button.disabled = false;
        }
    });

    readerClose?.addEventListener('click', closeReader);
    reader?.addEventListener('click', (event) => {
        if (event.target === reader) closeReader();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeReader();
    });

    refresh?.addEventListener('click', () => {
        load().catch((error) => window.PipoNotification?.error(error.message));
    });

    document.addEventListener('DOMContentLoaded', () => {
        load().catch((error) => {
            list.innerHTML = '<div class="box-empty">Nao foi possivel carregar sua caixa.</div>';
            window.PipoNotification?.error(error.message);
        });
    });
})();
