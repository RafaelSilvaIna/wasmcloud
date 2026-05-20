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
    const shell = document.querySelector('.box-shell');
    const readerPage = document.getElementById('box-reader-page');
    const readerPageBack = document.getElementById('box-reader-page-back');
    const readerPageTitle = document.getElementById('box-reader-page-title');
    const readerPageDate = document.getElementById('box-reader-page-date');
    const readerPageBody = document.getElementById('box-reader-page-body');
    const readerPageActions = document.getElementById('box-reader-page-actions');
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

    const pad = (value) => String(value).padStart(2, '0');

    const localDateKey = (date) => {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    };

    const parseBoxDate = (value) => {
        const date = new Date(String(value || '').replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const dateKey = (value) => {
        const date = parseBoxDate(value);
        if (!date) return 'Sem data';
        return localDateKey(date);
    };

    const dateLabel = (key) => {
        if (key === 'Sem data') return key;
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        const [year, month, day] = key.split('-').map(Number);
        const keyDate = new Date(year, month - 1, day);
        if (key === localDateKey(today)) return 'Hoje';
        if (key === localDateKey(yesterday)) return 'Ontem';
        return keyDate.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
    };

    const fullDate = (value) => {
        const date = parseBoxDate(value);
        if (!date) return 'Mensagem';
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

        if (item.type === 'subscription_renewal') {
            return `
                <p><strong>Seu Plano Gold esta perto de acabar.</strong></p>
                <p>${esc(item.body || 'Faca uma renovacao do seu plano para continuar com seus beneficios Pipocine.')}</p>
                <ul>
                    <li>Beneficios ativos</li>
                    <li>Perfis e seguranca</li>
                    <li>Player completo</li>
                    <li>Experiencia sem anuncios</li>
                </ul>
            `;
        }

        if (item.type === 'courtesy_expiring') {
            return `
                <p><strong>Sua cortesia Pipocine esta perto de acabar.</strong></p>
                <p>${esc(item.body || 'Assine o Plano Gold do Pipocine para continuar com a experiencia completa.')}</p>
                <ul>
                    <li>Plano Gold proprio</li>
                    <li>Continuidade dos beneficios</li>
                    <li>Mais controle da conta</li>
                    <li>Suporte prioritario</li>
                </ul>
            `;
        }

        if (item.type === 'family_removed') {
            return `
                <p><strong>Seu beneficio familiar foi encerrado.</strong></p>
                <p>${esc(item.body || 'Assine o Plano Gold para continuar com seus beneficios e desbloquear muito mais.')}</p>
                <ul>
                    <li>Plano Gold proprio</li>
                    <li>Sem depender de vinculo familiar</li>
                    <li>Beneficios premium</li>
                    <li>Controle total da assinatura</li>
                </ul>
            `;
        }

        return `<p>${esc(item.body || 'Mensagem informativa do Pipocine.')}</p>`;
    }

    function actionUrl(item) {
        const url = String(item.payload?.action_url || '');
        if (url === '/plan' || url === '/plan/me') return url;
        return '';
    }

    function actionLabel(item) {
        return String(item.payload?.action_label || 'Abrir');
    }

    function readerActionsHtml(item) {
        const pendingFamilyInvite = item.type === 'family_invite' && item.action_status === 'pending';
        if (pendingFamilyInvite) {
            return `
                <button class="box-btn decline" type="button" data-reader-action="decline" data-id="${item.id}">Recusar</button>
                <button class="box-btn accept" type="button" data-reader-action="accept" data-id="${item.id}">Aceitar convite</button>
            `;
        }

        const url = actionUrl(item);
        if (url) {
            return `<a class="box-btn accept" href="${esc(url)}">${esc(actionLabel(item))}</a>`;
        }

        return '<button class="box-btn decline" type="button" data-reader-page-close>Fechar</button>';
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

    function showList() {
        shell?.classList.remove('reading');
        if (readerPage) {
            readerPage.hidden = true;
            readerPage.setAttribute('aria-hidden', 'true');
        }
    }

    async function fetchItem(id) {
        const response = await fetch('/api/v4/box/item?id=' + encodeURIComponent(id));
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success || !data.item) {
            throw new Error(data.message || 'Nao foi possivel abrir esta mensagem.');
        }
        return data.item;
    }

    async function markItemRead(item) {
        if (!item || item.status !== 'unread') return;

        await api('/api/v4/box/read', { id: item.id });
        item.status = 'read';
        const unread = currentItems.filter((entry) => entry.status === 'unread').length;
        render(currentItems, unread);
    }

    async function openReader(id) {
        const item = currentItems.find((entry) => Number(entry.id) === Number(id));
        if (!item) return;

        let safeItem = item;
        try {
            safeItem = await fetchItem(id);
            const index = currentItems.findIndex((entry) => Number(entry.id) === Number(id));
            if (index >= 0) currentItems[index] = safeItem;
        } catch (error) {
            window.PipoNotification?.error(error.message);
            return;
        }

        if (readerPage && readerPageTitle && readerPageDate && readerPageBody && readerPageActions) {
            readerPageTitle.textContent = safeItem.title;
            readerPageDate.textContent = fullDate(safeItem.created_at);
            readerPageBody.innerHTML = messageBody(safeItem);
            readerPageActions.innerHTML = readerActionsHtml(safeItem);
            readerPage.hidden = false;
            readerPage.setAttribute('aria-hidden', 'false');
            shell?.classList.add('reading');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                await markItemRead(safeItem);
            } catch (_) {}

            return;
        }

        readerTitle.textContent = safeItem.title;
        readerDate.textContent = fullDate(safeItem.created_at);
        readerBody.innerHTML = messageBody(safeItem);

        readerActions.innerHTML = readerActionsHtml(safeItem);

        reader?.classList.add('open');
        reader?.setAttribute('aria-hidden', 'false');

        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            await markItemRead(safeItem);
        } catch (_) {}
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

    readerPageActions?.addEventListener('click', async (event) => {
        const close = event.target.closest('[data-reader-page-close]');
        if (close) {
            showList();
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
            showList();
            await load();
        } catch (error) {
            window.PipoNotification?.error(error.message);
            button.disabled = false;
        }
    });

    readerPageBack?.addEventListener('click', showList);

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
