<?php
declare(strict_types=1);

final class AdminSubscriptionsPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-sub-page { display: grid; gap: 14px; }
            .admin-sub-head,
            .admin-sub-card,
            .admin-sub-chart,
            .admin-sub-table-card,
            .admin-sub-modal-card {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
            }
            .admin-sub-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px;
            }
            .admin-sub-head h2 { margin: 0; color: #fff; font-size: 1.12rem; }
            .admin-sub-head p { margin: 5px 0 0; color: #94a3b8; }
            .admin-sub-tabs { display: flex; gap: 6px; }
            .admin-sub-tab,
            .admin-sub-btn {
                min-height: 38px;
                border: 1px solid rgba(148, 163, 184, .20);
                border-radius: 8px;
                background: #111827;
                color: #e2e8f0;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 12px;
                cursor: pointer;
                font-weight: 760;
            }
            .admin-sub-tab.active,
            .admin-sub-btn:hover { border-color: rgba(229, 9, 20, .45); background: rgba(229, 9, 20, .12); color: #fff; }
            .admin-sub-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }
            .admin-sub-card { padding: 16px; }
            .admin-sub-card span {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #94a3b8;
                font-size: .76rem;
                font-weight: 820;
                text-transform: uppercase;
            }
            .admin-sub-card strong { display: block; margin-top: 10px; color: #fff; font-size: 1.55rem; }
            .admin-sub-chart,
            .admin-sub-table-card { padding: 16px; }
            .admin-sub-chart h3,
            .admin-sub-table-card h3 { margin: 0 0 12px; color: #fff; font-size: 1rem; }
            .admin-sub-svg { width: 100%; height: 250px; display: block; }
            .admin-sub-table-wrap { overflow-x: auto; }
            .admin-sub-table { width: 100%; min-width: 860px; border-collapse: collapse; }
            .admin-sub-table th,
            .admin-sub-table td { padding: 12px; border-bottom: 1px solid rgba(148, 163, 184, .10); text-align: left; color: #e2e8f0; font-size: .86rem; }
            .admin-sub-table th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; }
            .admin-sub-pill { border-radius: 999px; padding: 5px 9px; font-size: .72rem; font-weight: 820; background: rgba(148,163,184,.12); color: #cbd5e1; }
            .admin-sub-pill.paid { color: #38bdf8; background: rgba(56,189,248,.12); }
            .admin-sub-pill.courtesy { color: #f59e0b; background: rgba(245,158,11,.12); }
            .admin-sub-pill.active { color: #22c55e; background: rgba(34,197,94,.12); }
            .admin-sub-modal {
                position: fixed;
                inset: 0;
                z-index: 150;
                display: none;
                place-items: center;
                background: rgba(0,0,0,.68);
                padding: 18px;
            }
            .admin-sub-modal.active { display: grid; }
            .admin-sub-modal-card { width: min(620px, 100%); padding: 18px; }
            .admin-sub-modal-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
            .admin-sub-modal-head h3 { margin: 0; color: #fff; }
            .admin-sub-field { display: grid; gap: 7px; margin-top: 12px; }
            .admin-sub-field label { color: #94a3b8; font-size: .82rem; font-weight: 760; }
            .admin-sub-field input,
            .admin-sub-field textarea,
            .admin-sub-field select {
                min-height: 42px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                color: #fff;
                padding: 10px;
            }
            .admin-sub-field textarea { min-height: 86px; resize: vertical; }
            .admin-sub-user-results { display: grid; gap: 6px; margin-top: 8px; max-height: 170px; overflow: auto; }
            .admin-sub-user-option {
                border: 1px solid rgba(148,163,184,.12);
                border-radius: 8px;
                background: #0a0c10;
                color: #e2e8f0;
                padding: 10px;
                text-align: left;
                cursor: pointer;
            }
            .admin-sub-user-option.active { border-color: rgba(229,9,20,.55); background: rgba(229,9,20,.12); }
            .admin-sub-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
            @media (max-width: 1000px) { .admin-sub-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 640px) { .admin-sub-head { display: grid; } .admin-sub-grid { grid-template-columns: 1fr; } }
        </style>

        <section data-admin-route="subscriptions" class="admin-route-panel" hidden>
            <div class="admin-sub-page" id="admin-sub-root">
                <div class="admin-sub-head">
                    <div>
                        <h2>Assinaturas</h2>
                        <p>Receita mensal, assinantes, cortesias e historico de planos.</p>
                    </div>
                    <div class="admin-sub-tabs">
                        <button class="admin-sub-tab active" type="button" data-sub-tab="all">Todas</button>
                        <button class="admin-sub-tab" type="button" data-sub-tab="paid">Pagas</button>
                        <button class="admin-sub-tab" type="button" data-sub-tab="courtesy">Cortesias</button>
                        <button class="admin-sub-btn" type="button" id="admin-sub-open-grant"><i data-lucide="gift"></i>Dar assinatura</button>
                    </div>
                </div>

                <div class="admin-sub-grid">
                    <article class="admin-sub-card"><span><i data-lucide="banknote"></i>Faturamento do mes</span><strong data-sub-stat="month_revenue">-</strong></article>
                    <article class="admin-sub-card"><span><i data-lucide="users"></i>Assinantes pagos</span><strong data-sub-stat="paid_subscribers">-</strong></article>
                    <article class="admin-sub-card"><span><i data-lucide="gift"></i>Cortesias ativas</span><strong data-sub-stat="courtesy_subscribers">-</strong></article>
                    <article class="admin-sub-card"><span><i data-lucide="calendar-check"></i>Pagantes 30 dias</span><strong data-sub-stat="monthly_payers">-</strong></article>
                </div>

                <article class="admin-sub-chart">
                    <h3>Receita dos ultimos 30 dias</h3>
                    <div id="admin-sub-revenue-chart"></div>
                </article>

                <article class="admin-sub-table-card">
                    <h3 id="admin-sub-table-title">Todas as assinaturas</h3>
                    <div class="admin-sub-table-wrap">
                        <table class="admin-sub-table">
                            <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Plano</th>
                                <th>Origem</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th>Inicio</th>
                                <th>Expira</th>
                            </tr>
                            </thead>
                            <tbody id="admin-sub-tbody"></tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="admin-sub-modal" id="admin-sub-grant-modal" aria-hidden="true">
                <form class="admin-sub-modal-card" id="admin-sub-grant-form">
                    <div class="admin-sub-modal-head">
                        <h3>Dar assinatura de cortesia</h3>
                        <button class="admin-sub-btn" type="button" data-close-sub-modal aria-label="Fechar"><i data-lucide="x"></i></button>
                    </div>
                    <input type="hidden" name="user_id" id="admin-sub-selected-user">
                    <div class="admin-sub-field">
                        <label for="admin-sub-user-search">Email, telefone ou nome do usuario</label>
                        <input id="admin-sub-user-search" type="search" autocomplete="off" placeholder="Digite para buscar">
                        <div class="admin-sub-user-results" id="admin-sub-user-results"></div>
                    </div>
                    <div class="admin-sub-field">
                        <label for="admin-sub-duration">Duracao da cortesia</label>
                        <select id="admin-sub-duration" name="duration_days">
                            <option value="3">3 dias</option>
                            <option value="7">7 dias</option>
                            <option value="14">14 dias</option>
                            <option value="20" selected>20 dias</option>
                        </select>
                    </div>
                    <div class="admin-sub-field">
                        <label for="admin-sub-reason">Motivo</label>
                        <textarea id="admin-sub-reason" name="reason" placeholder="Ex: suporte, teste, compensacao"></textarea>
                    </div>
                    <p style="margin:12px 0 0;color:#94a3b8;font-size:.84rem">Cortesias nao entram no faturamento e nao classificam o usuario como pagante.</p>
                    <div class="admin-sub-actions">
                        <button class="admin-sub-btn" type="button" data-close-sub-modal>Cancelar</button>
                        <button class="admin-sub-btn" type="submit"><i data-lucide="check"></i>Conceder</button>
                    </div>
                </form>
            </div>
        </section>

        <script>
            (function () {
                const root = document.getElementById('admin-sub-root');
                if (!root) return;

                let subscriptions = [];
                let currentTab = 'all';
                let selectedUserId = 0;
                let searchTimer = null;

                function esc(value) {
                    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
                }

                function money(value) {
                    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
                }

                function date(value) {
                    if (!value) return '-';
                    const parsed = new Date(String(value).replace(' ', 'T'));
                    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString('pt-BR');
                }

                async function api(path, options = {}) {
                    const response = await fetch('/api/admin/' + path, { headers: { 'Content-Type': 'application/json' }, ...options });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.error || 'Falha na acao.');
                    return data;
                }

                function chart(points) {
                    const width = 760, height = 250, pad = 28;
                    const max = Math.max(1, ...points.map(item => Number(item.revenue || 0)));
                    const step = points.length > 1 ? (width - pad * 2) / (points.length - 1) : 0;
                    const coords = points.map((item, index) => {
                        const x = pad + index * step;
                        const y = height - pad - (Number(item.revenue || 0) / max) * (height - pad * 2);
                        return [x, y];
                    });
                    const poly = coords.map(pair => pair.join(',')).join(' ');
                    const dots = coords.map(([x, y]) => `<circle cx="${x}" cy="${y}" r="3.5"></circle>`).join('');
                    return `<svg class="admin-sub-svg" viewBox="0 0 ${width} ${height}">
                        <line x1="${pad}" y1="${height - pad}" x2="${width - pad}" y2="${height - pad}" stroke="rgba(148,163,184,.22)"></line>
                        <polyline points="${poly}" fill="none" stroke="#e50914" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <g fill="#e50914">${dots}</g>
                    </svg>`;
                }

                function renderTable() {
                    const rows = subscriptions.filter(item => {
                        if (currentTab === 'paid') return (item.source || 'paid') === 'paid';
                        if (currentTab === 'courtesy') return item.source === 'admin_courtesy';
                        return true;
                    });

                    document.getElementById('admin-sub-table-title').textContent =
                        currentTab === 'paid' ? 'Assinaturas pagas' : currentTab === 'courtesy' ? 'Cortesias administrativas' : 'Todas as assinaturas';

                    document.getElementById('admin-sub-tbody').innerHTML = rows.length ? rows.map(item => {
                        const source = item.source === 'admin_courtesy' ? 'Cortesia' : 'Pago';
                        return `<tr>
                            <td>${esc(item.full_name || '-')}</td>
                            <td>${esc(item.email || '-')}</td>
                            <td>${esc(item.plan_code || '-')}</td>
                            <td><span class="admin-sub-pill ${item.source === 'admin_courtesy' ? 'courtesy' : 'paid'}">${source}</span></td>
                            <td><span class="admin-sub-pill ${item.status === 'active' ? 'active' : ''}">${esc(item.status)}</span></td>
                            <td>${money(item.amount_paid)}</td>
                            <td>${date(item.started_at)}</td>
                            <td>${date(item.expires_at)}</td>
                        </tr>`;
                    }).join('') : '<tr><td colspan="8">Nenhuma assinatura encontrada.</td></tr>';
                }

                async function loadSubscriptions() {
                    const data = await api('subscriptions');
                    subscriptions = data.subscriptions || [];
                    const summary = data.summary || {};
                    root.querySelector('[data-sub-stat="month_revenue"]').textContent = money(summary.month_revenue);
                    root.querySelector('[data-sub-stat="paid_subscribers"]').textContent = summary.paid_subscribers || 0;
                    root.querySelector('[data-sub-stat="courtesy_subscribers"]').textContent = summary.courtesy_subscribers || 0;
                    root.querySelector('[data-sub-stat="monthly_payers"]').textContent = summary.monthly_payers || 0;
                    document.getElementById('admin-sub-revenue-chart').innerHTML = chart(data.series || []);
                    renderTable();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function searchUsers() {
                    const q = document.getElementById('admin-sub-user-search').value.trim();
                    const target = document.getElementById('admin-sub-user-results');
                    if (q.length < 2) {
                        target.innerHTML = '';
                        return;
                    }
                    const data = await api('subscriptions/search-users?q=' + encodeURIComponent(q));
                    target.innerHTML = (data.users || []).map(user => `
                        <button class="admin-sub-user-option" type="button" data-sub-user="${esc(user.id)}">
                            <strong>${esc(user.full_name || 'Sem nome')}</strong><br>
                            <span>${esc(user.email || user.phone || '-')}</span>
                        </button>
                    `).join('') || '<p style="color:#94a3b8;margin:0">Nenhum usuario encontrado.</p>';
                }

                document.addEventListener('click', event => {
                    const tab = event.target.closest('[data-sub-tab]');
                    if (tab) {
                        currentTab = tab.dataset.subTab || 'all';
                        root.querySelectorAll('[data-sub-tab]').forEach(item => item.classList.toggle('active', item === tab));
                        renderTable();
                    }

                    if (event.target.closest('#admin-sub-open-grant')) {
                        document.getElementById('admin-sub-grant-modal').classList.add('active');
                    }

                    if (event.target.closest('[data-close-sub-modal]') || event.target.id === 'admin-sub-grant-modal') {
                        document.getElementById('admin-sub-grant-modal').classList.remove('active');
                    }

                    const user = event.target.closest('[data-sub-user]');
                    if (user) {
                        selectedUserId = Number(user.dataset.subUser || 0);
                        document.getElementById('admin-sub-selected-user').value = selectedUserId;
                        document.querySelectorAll('[data-sub-user]').forEach(item => item.classList.toggle('active', item === user));
                    }
                });

                document.getElementById('admin-sub-user-search').addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(searchUsers, 240);
                });

                document.getElementById('admin-sub-grant-form').addEventListener('submit', async event => {
                    event.preventDefault();
                    const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
                    payload.user_id = Number(payload.user_id || selectedUserId || 0);
                    payload.duration_days = Math.min(20, Number(payload.duration_days || 20));
                    await api('subscriptions/grant-courtesy', { method: 'POST', body: JSON.stringify(payload) });
                    document.getElementById('admin-sub-grant-modal').classList.remove('active');
                    event.currentTarget.reset();
                    selectedUserId = 0;
                    await loadSubscriptions();
                });

                window.AdminSubscriptionsPanel = { load: loadSubscriptions };
            })();
        </script>
        <?php
    }
}
