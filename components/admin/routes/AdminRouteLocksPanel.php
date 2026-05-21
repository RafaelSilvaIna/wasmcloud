<?php
declare(strict_types=1);

final class AdminRouteLocksPanel
{
    public static function render(): void
    {
        ?>
        <section data-admin-route="route-locks" class="admin-route-panel" hidden>
            <style>
                .rl-shell { display: grid; gap: 16px; }
                .rl-head {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 14px;
                    flex-wrap: wrap;
                }
                .rl-head h2 { margin: 0; color: #fff; font-size: 1.2rem; }
                .rl-head p { margin: 6px 0 0; color: #94a3b8; line-height: 1.5; }
                .rl-actions { display: flex; gap: 10px; flex-wrap: wrap; }
                .rl-btn {
                    min-height: 38px;
                    border: 1px solid rgba(148,163,184,.18);
                    border-radius: 8px;
                    background: #10151f;
                    color: #e2e8f0;
                    padding: 0 13px;
                    font-weight: 750;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                .rl-btn svg { width: 16px; height: 16px; }
                .rl-btn.primary { border-color: rgba(229,9,20,.45); background: #e50914; color: #fff; }
                .rl-btn.danger { border-color: rgba(248,113,113,.45); color: #fecaca; }
                .rl-btn.ghost { background: transparent; }
                .rl-btn:disabled { opacity: .55; cursor: wait; }
                .rl-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1.45fr) minmax(320px, .75fr);
                    gap: 16px;
                }
                .rl-panel {
                    border: 1px solid rgba(148,163,184,.16);
                    border-radius: 10px;
                    background: #0f131a;
                    overflow: hidden;
                }
                .rl-panel-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    padding: 14px;
                    border-bottom: 1px solid rgba(148,163,184,.12);
                }
                .rl-panel-head strong { color: #fff; }
                .rl-search {
                    width: min(360px, 100%);
                    min-height: 38px;
                    border: 1px solid rgba(148,163,184,.18);
                    border-radius: 8px;
                    background: #080b11;
                    color: #fff;
                    padding: 0 12px;
                }
                .rl-table-wrap { overflow: auto; }
                .rl-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 760px;
                }
                .rl-table th,
                .rl-table td {
                    padding: 12px 14px;
                    border-bottom: 1px solid rgba(148,163,184,.1);
                    text-align: left;
                    vertical-align: middle;
                    font-size: .88rem;
                }
                .rl-table th {
                    color: #94a3b8;
                    font-size: .72rem;
                    text-transform: uppercase;
                    letter-spacing: .08em;
                    background: #0b0f16;
                }
                .rl-path { color: #fff; font-weight: 760; }
                .rl-meta { display:block; margin-top:4px; color:#64748b; font-size:.78rem; }
                .rl-badge {
                    display: inline-flex;
                    min-height: 24px;
                    align-items: center;
                    border-radius: 999px;
                    padding: 0 9px;
                    font-size: .72rem;
                    font-weight: 800;
                    border: 1px solid rgba(148,163,184,.18);
                    color: #94a3b8;
                }
                .rl-badge.locked { border-color: rgba(248,113,113,.35); color: #fecaca; background: rgba(127,29,29,.28); }
                .rl-badge.open { color: #bbf7d0; border-color: rgba(34,197,94,.28); background: rgba(20,83,45,.22); }
                .rl-mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
                .rl-manual {
                    display: grid;
                    grid-template-columns: minmax(0,1fr) 130px;
                    gap: 10px;
                    padding: 14px;
                    border-bottom: 1px solid rgba(148,163,184,.12);
                }
                .rl-input,
                .rl-select,
                .rl-textarea {
                    width: 100%;
                    border: 1px solid rgba(148,163,184,.18);
                    border-radius: 8px;
                    background: #080b11;
                    color: #fff;
                    padding: 0 12px;
                    font: inherit;
                }
                .rl-input,
                .rl-select { min-height: 40px; }
                .rl-textarea { min-height: 94px; resize: vertical; padding-top: 10px; line-height: 1.45; }
                .rl-log-list { display:grid; gap:10px; padding:14px; }
                .rl-stat {
                    border: 1px solid rgba(148,163,184,.12);
                    border-radius: 8px;
                    padding: 12px;
                    background: #0a0e15;
                }
                .rl-stat strong { display:block; color:#fff; }
                .rl-stat span { display:block; margin-top:4px; color:#94a3b8; font-size:.78rem; }
                .rl-log {
                    display:grid;
                    gap:4px;
                    border-top: 1px solid rgba(148,163,184,.1);
                    padding-top:10px;
                    color:#cbd5e1;
                    font-size:.84rem;
                }
                .rl-log:first-child { border-top:0; padding-top:0; }
                .rl-log small { color:#64748b; }
                .rl-empty { padding: 24px; color: #94a3b8; text-align: center; }
                .rl-modal[hidden] { display:none; }
                .rl-modal {
                    position: fixed;
                    inset: 0;
                    z-index: 60;
                    display: grid;
                    place-items: center;
                    padding: 22px;
                    background: rgba(3, 6, 12, .78);
                }
                .rl-dialog {
                    width: min(430px, 100%);
                    border: 1px solid rgba(148,163,184,.2);
                    border-radius: 8px;
                    background: #0d1117;
                    padding: 22px;
                    box-shadow: 0 24px 70px rgba(0,0,0,.46);
                }
                .rl-dialog h3 { margin:0; color:#fff; font-size:1.05rem; }
                .rl-form { display:grid; gap:12px; margin-top:16px; }
                .rl-form label { display:grid; gap:7px; color:#94a3b8; font-size:.82rem; font-weight:750; }
                .rl-dialog-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:4px; }
                @media (max-width: 1100px) {
                    .rl-grid { grid-template-columns: 1fr; }
                    .rl-manual { grid-template-columns: 1fr; }
                }
            </style>

            <div class="rl-shell">
                <div class="rl-head">
                    <div>
                        <h2>Rotas e manutencao</h2>
                        <p>Controle em tempo real das paginas publicas roteadas pelo sistema.</p>
                    </div>
                    <div class="rl-actions">
                        <button class="rl-btn ghost" type="button" id="rl-refresh"><i data-lucide="refresh-cw"></i>Atualizar</button>
                    </div>
                </div>

                <div class="rl-grid">
                    <article class="rl-panel">
                        <div class="rl-panel-head">
                            <strong>Rotas detectadas</strong>
                            <input class="rl-search" id="rl-search" type="search" placeholder="Buscar rota">
                        </div>
                        <form class="rl-manual" id="rl-manual-form">
                            <input class="rl-input" name="route_path" placeholder="/rota-manual" required>
                            <select class="rl-select" name="match_type">
                                <option value="exact">Exata</option>
                                <option value="prefix">Prefixo</option>
                                <option value="regex">Regex</option>
                            </select>
                            <input class="rl-input" name="maintenance_title" placeholder="Titulo do aviso" value="Pagina em manutencao">
                            <button class="rl-btn primary" type="submit"><i data-lucide="lock"></i>Fechar</button>
                        </form>
                        <div class="rl-table-wrap">
                            <table class="rl-table">
                                <thead>
                                    <tr>
                                        <th>Rota</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Acoes</th>
                                    </tr>
                                </thead>
                                <tbody id="rl-routes-body">
                                    <tr><td colspan="4" class="rl-empty">Carregando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <aside class="rl-panel">
                        <div class="rl-panel-head">
                            <strong>Logs do frontend</strong>
                            <button class="rl-btn ghost" type="button" id="rl-logs-refresh"><i data-lucide="activity"></i>Ver</button>
                        </div>
                        <div class="rl-log-list" id="rl-logs-body">
                            <div class="rl-empty">Carregando...</div>
                        </div>
                    </aside>
                </div>
            </div>

            <div class="rl-modal" id="rl-lock-modal" hidden>
                <form class="rl-dialog" id="rl-lock-form">
                    <h3>Fechar rota</h3>
                    <div class="rl-form">
                        <label>
                            Titulo
                            <input class="rl-input" name="maintenance_title" value="Pagina em manutencao" maxlength="120">
                        </label>
                        <label>
                            Mensagem
                            <textarea class="rl-textarea" name="maintenance_message" maxlength="500">Estamos ajustando esta area. Volte em instantes.</textarea>
                        </label>
                        <div class="rl-dialog-actions">
                            <button class="rl-btn ghost" type="button" id="rl-cancel-lock">Cancelar</button>
                            <button class="rl-btn primary" type="submit"><i data-lucide="lock"></i>Fechar</button>
                        </div>
                    </div>
                </form>
            </div>

            <script>
            (function () {
                const API = '/api/admin/route-locks';
                const state = { routes: [], pending: null, loaded: false };

                const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[ch]));
                const ms = (value) => `${Math.round(Number(value || 0))} ms`;

                async function request(path, options = {}) {
                    const response = await fetch(API + path, {
                        headers: { 'Content-Type': 'application/json' },
                        ...options
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || data.message || 'Falha na operacao.');
                    }
                    return data;
                }

                function renderRoutes(routes) {
                    const body = document.getElementById('rl-routes-body');
                    if (!routes.length) {
                        body.innerHTML = '<tr><td colspan="4" class="rl-empty">Nenhuma rota encontrada.</td></tr>';
                        return;
                    }

                    body.innerHTML = routes.slice(0, 180).map((route, index) => `
                        <tr>
                            <td>
                                <span class="rl-path">${esc(route.route_path)}</span>
                                <span class="rl-meta">${esc(route.page_file || route.source || 'manual')}</span>
                            </td>
                            <td>${esc(route.match_type)}</td>
                            <td><span class="rl-badge ${route.is_locked ? 'locked' : 'open'}">${route.is_locked ? 'Fechada' : 'Aberta'}</span></td>
                            <td>
                                <div class="rl-mini-actions">
                                    ${route.is_locked
                                        ? `<button class="rl-btn ghost" type="button" data-rl-action="unlock" data-index="${index}"><i data-lucide="unlock"></i>Abrir</button>`
                                        : `<button class="rl-btn danger" type="button" data-rl-action="lock" data-index="${index}"><i data-lucide="lock"></i>Fechar</button>`}
                                </div>
                            </td>
                        </tr>
                    `).join('');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                function renderLogs(data) {
                    const root = document.getElementById('rl-logs-body');
                    const stats = (data.stats || []).slice(0, 5).map(item => `
                        <div class="rl-stat">
                            <strong>${esc(item.path)}</strong>
                            <span>${Number(item.total_hits || 0).toLocaleString('pt-BR')} acessos · ${ms(item.avg_duration_ms)} medio</span>
                        </div>
                    `).join('');

                    const logs = (data.logs || []).slice(0, 18).map(log => `
                        <div class="rl-log">
                            <strong>${esc(log.path)} ${log.was_locked == 1 ? '<span class="rl-badge locked">bloqueada</span>' : ''}</strong>
                            <small>${esc(log.method)} · ${Number(log.status_code || 200)} · ${ms(log.duration_ms)} · ${esc(log.created_at)}</small>
                        </div>
                    `).join('');

                    root.innerHTML = stats + (logs || '<div class="rl-empty">Sem logs ainda.</div>');
                }

                async function loadRoutes() {
                    const q = document.getElementById('rl-search')?.value || '';
                    const data = await request('/routes?q=' + encodeURIComponent(q));
                    state.routes = data.routes || [];
                    renderRoutes(state.routes);
                }

                async function loadLogs() {
                    const data = await request('/logs?range=1d&limit=80');
                    renderLogs(data);
                }

                function openLockModal(route) {
                    state.pending = route;
                    const modal = document.getElementById('rl-lock-modal');
                    const form = document.getElementById('rl-lock-form');
                    form.maintenance_title.value = route.maintenance_title || 'Pagina em manutencao';
                    form.maintenance_message.value = route.maintenance_message || 'Estamos ajustando esta area. Volte em instantes.';
                    modal.hidden = false;
                }

                function closeLockModal() {
                    document.getElementById('rl-lock-modal').hidden = true;
                    state.pending = null;
                }

                async function setRoute(route, locked, extra = {}) {
                    const payload = {
                        route_path: route.route_path,
                        match_type: route.match_type,
                        page_file: route.page_file || '',
                        route_label: route.route_label || route.source || 'Manual',
                        maintenance_title: extra.maintenance_title || route.maintenance_title || 'Pagina em manutencao',
                        maintenance_message: extra.maintenance_message || route.maintenance_message || 'Estamos ajustando esta area. Volte em instantes.'
                    };
                    await request(locked ? '/lock' : '/unlock', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    await Promise.all([loadRoutes(), loadLogs()]);
                }

                function bind() {
                    document.getElementById('rl-refresh')?.addEventListener('click', () => Promise.all([loadRoutes(), loadLogs()]));
                    document.getElementById('rl-logs-refresh')?.addEventListener('click', loadLogs);
                    document.getElementById('rl-search')?.addEventListener('input', function () {
                        clearTimeout(this._rlTimer);
                        this._rlTimer = setTimeout(loadRoutes, 250);
                    });
                    document.getElementById('rl-routes-body')?.addEventListener('click', async function (event) {
                        const button = event.target.closest('[data-rl-action]');
                        if (!button) return;
                        const route = state.routes[Number(button.dataset.index)];
                        if (!route) return;
                        if (button.dataset.rlAction === 'lock') {
                            openLockModal(route);
                            return;
                        }
                        button.disabled = true;
                        try {
                            await setRoute(route, false);
                        } finally {
                            button.disabled = false;
                        }
                    });
                    document.getElementById('rl-cancel-lock')?.addEventListener('click', closeLockModal);
                    document.getElementById('rl-lock-modal')?.addEventListener('click', function (event) {
                        if (event.target === this) closeLockModal();
                    });
                    document.getElementById('rl-lock-form')?.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        if (!state.pending) return;
                        const button = this.querySelector('button[type="submit"]');
                        button.disabled = true;
                        try {
                            await setRoute(state.pending, true, Object.fromEntries(new FormData(this).entries()));
                            closeLockModal();
                        } finally {
                            button.disabled = false;
                        }
                    });
                    document.getElementById('rl-manual-form')?.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        const button = this.querySelector('button[type="submit"]');
                        button.disabled = true;
                        try {
                            const payload = Object.fromEntries(new FormData(this).entries());
                            payload.route_label = 'Manual';
                            payload.maintenance_message = 'Estamos ajustando esta area. Volte em instantes.';
                            await request('/lock', { method: 'POST', body: JSON.stringify(payload) });
                            this.reset();
                            this.match_type.value = 'exact';
                            await Promise.all([loadRoutes(), loadLogs()]);
                        } finally {
                            button.disabled = false;
                        }
                    });
                }

                window.AdminRouteLocksPanel = {
                    async init() {
                        if (state.loaded) {
                            await Promise.all([loadRoutes(), loadLogs()]);
                            return;
                        }
                        state.loaded = true;
                        bind();
                        await Promise.all([loadRoutes(), loadLogs()]);
                    },
                    load: loadRoutes
                };
            })();
            </script>
        </section>
        <?php
    }
}
