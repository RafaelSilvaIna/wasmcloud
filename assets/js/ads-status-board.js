(function () {
    'use strict';

    const API = '/api/ads/campaigns/status-board';
    const POLL_MS = 2500;
    let campaigns = [];
    let selectedId = null;
    let revision = -1;

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }

    function fmt(value) {
        if (!value) return '—';
        return new Date(value).toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short'});
    }

    async function loadBoard(force = false) {
        const response = await fetch(API);
        const data = await response.json();
        if (!response.ok || !data.success) return;
        if (!force && Number(data.revision) === Number(revision)) return;
        revision = Number(data.revision || 0);
        campaigns = data.campaigns || [];
        renderCounts(data.counts || {});
        if (!selectedId && campaigns.length) selectedId = Number(campaigns[0].id);
        if (selectedId && !campaigns.some(item => Number(item.id) === Number(selectedId))) selectedId = campaigns[0] ? Number(campaigns[0].id) : null;
        renderList();
        renderDetail();
    }

    function renderCounts(counts) {
        ['pending_review','in_review','approved','active'].forEach(key => {
            const el = document.getElementById('ads-count-' + key);
            if (el) el.textContent = counts[key] || 0;
        });
    }

    function renderList() {
        const root = document.getElementById('ads-status-list');
        if (!root) return;
        if (!campaigns.length) {
            root.innerHTML = '<div class="ads-empty"><div><h3>Nada para monitorar ainda</h3><p>Quando você criar o primeiro anúncio, ele aparecerá aqui.</p></div></div>';
            return;
        }
        root.innerHTML = campaigns.map(item => `
            <button class="ads-status-item ${Number(item.id) === Number(selectedId) ? 'active' : ''}" data-id="${item.id}" type="button">
                <div class="ads-status-item-top">
                    <h3>${esc(item.name)}</h3>
                    <span class="ads-badge ${esc(item.tone)}">${esc(item.label)}</span>
                </div>
                <span class="ads-muted">${esc(item.journey.summary)}</span>
                <div class="ads-mini-progress"><span style="width:${Number(item.journey.progress || 0)}%"></span></div>
            </button>
        `).join('');
        root.querySelectorAll('[data-id]').forEach(button => button.addEventListener('click', () => {
            selectedId = Number(button.dataset.id);
            renderList();
            renderDetail();
        }));
    }

    function renderDetail() {
        const root = document.getElementById('ads-status-detail');
        const item = campaigns.find(campaign => Number(campaign.id) === Number(selectedId));
        if (!root) return;
        if (!item) {
            root.innerHTML = '<div class="ads-empty"><div><h3>Nenhum anÃºncio selecionado</h3><p>Escolha um criativo para ver a jornada completa.</p></div></div>';
            return;
        }
        const src = item.cdn_token ? `/cdn/ads=${item.cdn_token}` : '';
        const media = item.creative_type === 'video'
            ? `<video src="${esc(src)}" controls muted playsinline preload="metadata"></video>`
            : `<img src="${esc(src)}" alt="">`;
        root.innerHTML = `
            <div class="ads-detail-head">
                <div>
                    <h3>${esc(item.name)}</h3>
                    <span class="ads-muted">${esc(item.journey.summary)}</span>
                </div>
                <span class="ads-badge ${esc(item.tone)}">${esc(item.label)}</span>
            </div>
            <div class="ads-creative-preview">${media}</div>
            <section class="ads-journey">
                ${(item.journey.steps || []).map((step, index) => `
                    <article class="ads-journey-step ${esc(step.state)}">
                        <b>${index + 1}</b>
                        <strong>${esc(step.label)}</strong>
                    </article>
                `).join('')}
            </section>
            <section class="ads-event-list">
                <h3 style="margin:0;">Histórico sincronizado</h3>
                ${(item.events || []).length ? item.events.map(event => `
                    <article class="ads-event">
                        <strong>${esc(event.public_note || event.to_status)}</strong>
                        <small>${fmt(event.created_at)}</small>
                    </article>
                `).join('') : '<span class="ads-muted">Sem eventos públicos ainda.</span>'}
            </section>
        `;
    }

    loadBoard(true);
    setInterval(() => {
        if (document.visibilityState === 'visible') loadBoard(false).catch(() => {});
    }, POLL_MS);
})();
