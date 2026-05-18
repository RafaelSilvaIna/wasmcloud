(function () {
    'use strict';

    const API = '/api/admin/ads-reviews';
    const POLL_MS = 2500;
    let booted = false;
    let filter = 'queue';
    let query = '';
    let selectedId = null;
    let timer = null;
    let campaigns = [];
    let selectedSnapshot = '';

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }

    function label(status) {
        return {
            pending_review: 'Fila',
            in_review: 'Em análise',
            changes_requested: 'Ajustes',
            approved: 'Aprovado',
            active: 'Ativo',
            paused: 'Pausado',
            rejected: 'Rejeitado',
        }[status] || status;
    }

    function tone(status) {
        return {
            pending_review: 'info',
            in_review: 'info',
            changes_requested: 'warning',
            approved: 'success',
            active: 'success',
            paused: 'muted',
            rejected: 'danger',
        }[status] || 'neutral';
    }

    function fmt(value) {
        if (!value) return '—';
        return new Date(value).toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short'});
    }

    async function api(path, options = {}) {
        const response = await fetch(API + path, {
            headers: {'Content-Type': 'application/json'},
            ...options,
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Falha na API.');
        return data;
    }

    async function loadBoard(preserveSelection = true, forceDetail = false) {
        const params = new URLSearchParams({filter, q: query});
        const data = await api('/board?' + params.toString());
        campaigns = data.campaigns || [];
        renderCounts(data.counts || {});
        renderQueue(campaigns);
        if (preserveSelection && selectedId && campaigns.some(item => Number(item.id) === Number(selectedId))) {
            const selected = campaigns.find(item => Number(item.id) === Number(selectedId));
            const nextSnapshot = snapshotOf(selected);
            const editorBusy = ['adr-public-note', 'adr-internal-note'].includes(document.activeElement?.id);
            if (forceDetail || (!editorBusy && nextSnapshot !== selectedSnapshot)) {
                loadDetail(selectedId);
            }
        } else if (campaigns.length) {
            selectedId = null;
            selectedSnapshot = '';
            loadDetail(campaigns[0].id);
        } else {
            selectedId = null;
            selectedSnapshot = '';
            renderEmptyDetail();
        }
    }

    function renderCounts(counts) {
        ['pending_review','in_review','approved','active','changes_requested','rejected'].forEach(key => {
            const el = document.getElementById('adr-count-' + key);
            if (el) el.textContent = counts[key] || 0;
        });
        const badge = document.getElementById('admin-ads-review-badge');
        if (badge) badge.textContent = Number(counts.pending_review || 0) + Number(counts.in_review || 0) || '';
    }

    function renderQueue(items) {
        const root = document.getElementById('adr-queue');
        if (!root) return;
        if (!items.length) {
            root.innerHTML = '<div class="adr-empty">Nenhum anúncio nesta visão.</div>';
            return;
        }
        root.innerHTML = items.map(item => `
            <button class="adr-queue-item ${Number(item.id) === Number(selectedId) ? 'active' : ''}" data-id="${item.id}" type="button">
                <div class="adr-queue-top">
                    <span class="adr-queue-title">${esc(item.name)}</span>
                    <span class="adr-badge ${tone(item.status)}">${label(item.status)}</span>
                </div>
                <span class="adr-muted">${esc(item.brand_name)} · ${item.creative_type === 'video' ? 'Vídeo' : 'Imagem/GIF'}</span>
                <span class="adr-muted">Enviado ${fmt(item.submitted_at)}</span>
            </button>
        `).join('');
        root.querySelectorAll('[data-id]').forEach(button => button.addEventListener('click', () => loadDetail(button.dataset.id)));
    }

    async function loadDetail(id) {
        selectedId = Number(id);
        const current = campaigns.find(item => Number(item.id) === Number(selectedId));
        selectedSnapshot = snapshotOf(current);
        renderQueue(campaigns);
        const data = await api('/' + selectedId);
        renderDetail(data.campaign, data.events || []);
    }

    function renderDetail(campaign, events) {
        const root = document.getElementById('adr-detail');
        if (!root) return;
        const src = campaign.cdn_token ? `/cdn/ads=${campaign.cdn_token}` : '';
        const media = campaign.creative_type === 'video'
            ? `<video src="${esc(src)}" controls muted playsinline preload="metadata"></video>`
            : `<img src="${esc(src)}" alt="">`;
        root.innerHTML = `
            <div class="adr-detail-top">
                <div>
                    <div class="adr-muted">#${campaign.id} · ${esc(campaign.brand_name)}</div>
                    <h2 style="margin:4px 0 0;color:#fff">${esc(campaign.name)}</h2>
                </div>
                <span class="adr-badge ${tone(campaign.status)}">${label(campaign.status)}</span>
            </div>
            <div class="adr-preview">${media}</div>
            <div class="adr-meta-grid">
                <div class="adr-meta"><span>Marca</span><strong>${esc(campaign.brand_name)}</strong></div>
                <div class="adr-meta"><span>Contato</span><strong>${esc(campaign.account_email)}</strong></div>
                <div class="adr-meta"><span>Formato</span><strong>${campaign.creative_type === 'video' ? 'Vídeo' : 'Imagem / GIF'}${campaign.creative_duration_seconds ? ' · ' + campaign.creative_duration_seconds + 's' : ''}</strong></div>
                <div class="adr-meta"><span>Comportamento</span><strong>${campaign.can_skip == 1 ? 'Pode pular' : 'Obrigatório'}</strong></div>
                <div class="adr-meta"><span>Destino</span>${campaign.redirect_url ? `<a href="${esc(campaign.redirect_url)}" target="_blank" rel="noopener">${esc(campaign.redirect_url)}</a>` : '<strong>Sem link</strong>'}</div>
                <div class="adr-meta"><span>Trava</span><strong>${campaign.lock_admin_name ? esc(campaign.lock_admin_name) + ' até ' + fmt(campaign.review_lock_expires_at) : 'Livre'}</strong></div>
            </div>
            <div class="adr-meta"><span>Descrição</span><strong>${esc(campaign.description || '—')}</strong></div>
            <div class="adr-note-grid">
                <label><span class="adr-muted">Nota pública</span><textarea id="adr-public-note" placeholder="Visível ao anunciante"></textarea></label>
                <label><span class="adr-muted">Nota interna</span><textarea id="adr-internal-note" placeholder="Somente administração"></textarea></label>
            </div>
            <div class="adr-actions">${actionButtons(campaign.status)}</div>
            <section class="adr-timeline">
                <h3 style="margin:4px 0;color:#fff">Histórico</h3>
                ${events.length ? events.map(event => `
                    <article class="adr-event">
                        <strong>${label(event.to_status)}</strong>
                        <span class="adr-muted">${fmt(event.created_at)}${event.admin_name ? ' · ' + esc(event.admin_name) : ''}</span>
                        <span>${esc(event.public_note || event.note || 'Sem observação pública.')}</span>
                    </article>
                `).join('') : '<span class="adr-muted">Sem eventos ainda.</span>'}
            </section>
        `;
        root.querySelectorAll('[data-action]').forEach(button => button.addEventListener('click', () => perform(button.dataset.action)));
    }

    function renderEmptyDetail() {
        const root = document.getElementById('adr-detail');
        if (root) root.innerHTML = '<div class="adr-empty">Selecione um anÃºncio para iniciar a revisÃ£o.</div>';
    }

    function actionButtons(status) {
        if (status === 'pending_review') return '<button class="adr-btn primary" data-action="start">Iniciar revisão</button>';
        if (status === 'in_review') return [
            '<button class="adr-btn success" data-action="approve">Aprovar</button>',
            '<button class="adr-btn warning" data-action="request-changes">Solicitar ajustes</button>',
            '<button class="adr-btn danger" data-action="reject">Rejeitar</button>',
        ].join('');
        if (status === 'approved') return '<button class="adr-btn success" data-action="publish">Publicar</button>';
        if (status === 'active') return '<button class="adr-btn warning" data-action="pause">Pausar</button>';
        if (status === 'paused') return '<button class="adr-btn success" data-action="resume">Reativar</button>';
        return '';
    }

    async function perform(action) {
        if (!selectedId) return;
        const publicNote = document.getElementById('adr-public-note')?.value.trim() || '';
        const internalNote = document.getElementById('adr-internal-note')?.value.trim() || '';
        try {
            await api(`/${selectedId}/${action}`, {
                method: 'POST',
                body: JSON.stringify({public_note: publicNote, internal_note: internalNote}),
            });
            await loadBoard(true, true);
        } catch (error) {
            alert(error.message);
        }
    }

    function init() {
        if (booted) return;
        booted = true;
        document.querySelectorAll('[data-adr-filter]').forEach(button => button.addEventListener('click', () => {
            filter = button.dataset.adrFilter;
            document.querySelectorAll('[data-adr-filter]').forEach(item => item.classList.toggle('active', item === button));
            selectedId = null;
            loadBoard(false);
        }));
        document.getElementById('adr-search')?.addEventListener('input', debounce(event => {
            query = event.target.value.trim();
            selectedId = null;
            loadBoard(false);
        }, 220));
        loadBoard(false);
        timer = setInterval(() => {
            if (document.visibilityState === 'visible') loadBoard(true).catch(() => {});
        }, POLL_MS);
    }

    function debounce(fn, wait) {
        let timeout = null;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), wait);
        };
    }

    function snapshotOf(item) {
        if (!item) return '';
        return [item.id, item.status, item.updated_at, item.review_lock_admin_id, item.review_lock_expires_at].join('|');
    }

    window.AdminAdsReviewPanel = { init, refresh: () => loadBoard(true) };
})();
