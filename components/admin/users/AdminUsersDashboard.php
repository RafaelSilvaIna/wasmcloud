<?php
declare(strict_types=1);

final class AdminUsersDashboard
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-users-page {
                display: grid;
                gap: 14px;
            }

            .admin-users-topbar,
            .admin-users-table-card,
            .admin-user-details,
            .admin-adminlogs-modal-card,
            .admin-action-modal-card {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
            }

            .admin-users-topbar {
                display: grid;
                grid-template-columns: minmax(260px, 1fr) auto auto;
                gap: 10px;
                align-items: center;
                padding: 14px;
            }

            .admin-input-shell {
                min-height: 42px;
                display: flex;
                align-items: center;
                gap: 10px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                padding: 0 12px;
                color: #94a3b8;
            }

            .admin-input-shell input,
            .admin-filter-button {
                width: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                color: #fff;
                min-height: 40px;
            }

            .admin-filter-button {
                width: 210px;
                display: inline-flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                cursor: pointer;
                font-weight: 740;
            }

            .admin-context-wrap {
                position: relative;
            }

            .admin-context-menu {
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                width: 230px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #0f131a;
                box-shadow: 0 18px 60px rgba(0, 0, 0, .45);
                padding: 6px;
                z-index: 60;
                display: none;
            }

            .admin-context-menu.active {
                display: grid;
            }

            .admin-context-item {
                min-height: 38px;
                border: 0;
                border-radius: 6px;
                background: transparent;
                color: #cbd5e1;
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 0 10px;
                cursor: pointer;
                text-align: left;
                font-weight: 700;
            }

            .admin-context-item:hover,
            .admin-context-item.active {
                color: #fff;
                background: rgba(229, 9, 20, .14);
            }

            .admin-icon-button,
            .admin-text-button {
                min-height: 40px;
                border: 1px solid rgba(148, 163, 184, .20);
                border-radius: 8px;
                background: #111827;
                color: #e2e8f0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 0 12px;
                font-weight: 740;
                cursor: pointer;
            }

            .admin-icon-button:hover,
            .admin-text-button:hover {
                border-color: rgba(229, 9, 20, .50);
                background: rgba(229, 9, 20, .10);
                color: #fff;
            }

            .admin-text-button.warn {
                color: #f59e0b;
                border-color: rgba(245, 158, 11, .32);
                background: rgba(245, 158, 11, .08);
            }

            .admin-text-button.danger {
                color: #ef4444;
                border-color: rgba(239, 68, 68, .32);
                background: rgba(239, 68, 68, .08);
            }

            .admin-text-button.good {
                color: #22c55e;
                border-color: rgba(34, 197, 94, .32);
                background: rgba(34, 197, 94, .08);
            }

            .admin-session-lock {
                display: none;
                border: 1px solid rgba(239, 68, 68, .30);
                border-radius: 8px;
                background: rgba(239, 68, 68, .08);
                color: #fecaca;
                padding: 12px 14px;
                font-weight: 720;
            }

            .admin-session-lock.active {
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .admin-users-table-card {
                overflow: hidden;
            }

            .admin-users-summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid rgba(148, 163, 184, .12);
            }

            .admin-users-summary h2 {
                margin: 0;
                color: #fff;
                font-size: 1.02rem;
            }

            .admin-users-summary span {
                color: #94a3b8;
                font-size: .82rem;
            }

            .admin-table-wrap {
                overflow-x: auto;
            }

            .admin-users-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 760px;
            }

            .admin-users-table th,
            .admin-users-table td {
                padding: 13px 16px;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
                text-align: left;
            }

            .admin-users-table th {
                color: #94a3b8;
                font-size: .72rem;
                text-transform: uppercase;
                letter-spacing: 0;
            }

            .admin-users-table td {
                color: #e2e8f0;
                font-size: .88rem;
            }

            .admin-account-cell {
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 230px;
            }

            .admin-user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #e50914;
                color: #fff;
                display: grid;
                place-items: center;
                font-weight: 820;
                overflow: hidden;
            }

            .admin-user-avatar.large {
                width: 72px;
                height: 72px;
                font-size: 1.5rem;
            }

            .admin-user-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .admin-account-name {
                color: #fff;
                font-weight: 780;
            }

            .admin-account-sub {
                color: #94a3b8;
                font-size: .78rem;
                margin-top: 3px;
            }

            .admin-status-pill {
                border-radius: 999px;
                padding: 5px 9px;
                font-size: .70rem;
                font-weight: 820;
                text-transform: uppercase;
                color: #22c55e;
                background: rgba(34, 197, 94, .12);
            }

            .admin-status-pill.suspended {
                color: #f59e0b;
                background: rgba(245, 158, 11, .12);
            }

            .admin-status-pill.banned {
                color: #ef4444;
                background: rgba(239, 68, 68, .12);
            }

            .admin-plan-pill {
                border-radius: 999px;
                padding: 5px 9px;
                font-size: .72rem;
                color: #e2e8f0;
                background: rgba(148, 163, 184, .12);
            }

            .admin-plan-pill.paid {
                color: #38bdf8;
                background: rgba(56, 189, 248, .12);
            }

            .admin-user-details {
                padding: 18px;
                display: none;
                gap: 18px;
            }

            .admin-user-details.active {
                display: grid;
            }

            .admin-detail-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding-bottom: 16px;
                border-bottom: 1px solid rgba(148, 163, 184, .12);
            }

            .admin-detail-identity {
                display: flex;
                align-items: center;
                gap: 14px;
                min-width: 0;
            }

            .admin-detail-identity h2 {
                margin: 0;
                color: #fff;
                font-size: 1.35rem;
            }

            .admin-detail-identity p {
                margin: 5px 0 0;
                color: #94a3b8;
            }

            .admin-detail-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 8px;
            }

            .admin-info-table,
            .admin-logs-table {
                width: 100%;
                border-collapse: collapse;
            }

            .admin-info-table th,
            .admin-info-table td,
            .admin-logs-table th,
            .admin-logs-table td {
                padding: 12px;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
                text-align: left;
                color: #e2e8f0;
                font-size: .86rem;
            }

            .admin-info-table th,
            .admin-logs-table th {
                color: #94a3b8;
                font-size: .72rem;
                text-transform: uppercase;
            }

            .admin-section-title {
                margin: 0 0 10px;
                color: #fff;
                font-size: 1rem;
            }

            .admin-collapsible-head {
                width: 100%;
                min-height: 44px;
                border: 1px solid rgba(148, 163, 184, .12);
                border-radius: 8px;
                background: #0a0c10;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 0 12px;
                cursor: pointer;
                font-weight: 800;
            }

            .admin-collapsible-body {
                display: none;
                margin-top: 10px;
            }

            .admin-collapsible-body.active {
                display: block;
            }

            .admin-chart-box {
                border: 1px solid rgba(148, 163, 184, .12);
                border-radius: 8px;
                background: #0a0c10;
                padding: 14px;
            }

            .admin-activity-chart {
                width: 100%;
                height: 180px;
                display: block;
            }

            .admin-modal-layer {
                position: fixed;
                inset: 0;
                z-index: 140;
                display: none;
                place-items: center;
                background: rgba(0, 0, 0, .68);
                padding: 18px;
            }

            .admin-modal-layer.active {
                display: grid;
            }

            .admin-adminlogs-modal-card,
            .admin-action-modal-card {
                width: min(760px, 100%);
                max-height: min(760px, 90vh);
                overflow: auto;
                padding: 18px;
            }

            .admin-action-modal-card {
                width: min(520px, 100%);
            }

            .admin-modal-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 14px;
            }

            .admin-modal-head h3 {
                margin: 0;
                color: #fff;
            }

            .admin-field {
                display: grid;
                gap: 7px;
                margin-top: 12px;
            }

            .admin-field label {
                color: #94a3b8;
                font-size: .82rem;
                font-weight: 760;
            }

            .admin-field textarea,
            .admin-field input {
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                color: #fff;
                padding: 11px;
            }

            .admin-field textarea {
                min-height: 100px;
                resize: vertical;
            }

            .admin-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 16px;
            }

            @media (max-width: 760px) {
                .admin-users-topbar {
                    grid-template-columns: 1fr;
                }

                .admin-detail-head {
                    display: grid;
                }

                .admin-detail-actions {
                    justify-content: flex-start;
                }
            }
        </style>

        <section data-admin-route="users" class="admin-route-panel" hidden>
            <div class="admin-users-page" id="admin-users-root">
                <div class="admin-session-lock" id="admin-users-session-lock">
                    <i data-lucide="lock"></i>
                    Sessao administrativa expirada. Entre novamente para continuar.
                </div>

                <div class="admin-users-topbar">
                    <div class="admin-input-shell">
                        <i data-lucide="search"></i>
                        <input id="admin-users-search" type="search" placeholder="Buscar usuarios em tempo real">
                    </div>
                    <div class="admin-input-shell">
                        <i data-lucide="filter"></i>
                        <div class="admin-context-wrap">
                            <button class="admin-filter-button" type="button" id="admin-users-filter-button" data-filter-value="all">
                                <span id="admin-users-filter-label">Todos os usuarios</span>
                                <i data-lucide="chevron-down"></i>
                            </button>
                            <div class="admin-context-menu" id="admin-users-filter-menu">
                                <button class="admin-context-item active" type="button" data-filter-option="all"><i data-lucide="users"></i>Todos os usuarios</button>
                                <button class="admin-context-item" type="button" data-filter-option="active"><i data-lucide="check-circle"></i>Ativos</button>
                                <button class="admin-context-item" type="button" data-filter-option="suspended"><i data-lucide="timer-off"></i>Suspensos</button>
                                <button class="admin-context-item" type="button" data-filter-option="banned"><i data-lucide="ban"></i>Banidos</button>
                                <button class="admin-context-item" type="button" data-filter-option="paid"><i data-lucide="badge-dollar-sign"></i>Plano pago</button>
                                <button class="admin-context-item" type="button" data-filter-option="free"><i data-lucide="ticket"></i>Plano gratuito</button>
                            </div>
                        </div>
                    </div>
                    <button class="admin-text-button" type="button" id="admin-open-adminlogs">
                        <i data-lucide="history"></i>
                        Logs admin
                    </button>
                </div>

                <article class="admin-users-table-card" id="admin-users-list-view">
                    <div class="admin-users-summary">
                        <div>
                            <h2>Usuarios</h2>
                            <span id="admin-users-count">Carregando...</span>
                        </div>
                        <button class="admin-icon-button" type="button" id="admin-users-refresh" aria-label="Atualizar">
                            <i data-lucide="refresh-cw"></i>
                        </button>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-users-table">
                            <thead>
                            <tr>
                                <th>Conta</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Plano</th>
                                <th>Perfis</th>
                                <th>Ultimo login</th>
                                <th>Acoes</th>
                            </tr>
                            </thead>
                            <tbody id="admin-users-tbody"></tbody>
                        </table>
                    </div>
                </article>

                <article class="admin-user-details" id="admin-user-details-view"></article>
            </div>

            <div class="admin-modal-layer" id="admin-adminlogs-modal" aria-hidden="true">
                <article class="admin-adminlogs-modal-card">
                    <div class="admin-modal-head">
                        <h3>Logs recentes do administrador</h3>
                        <button class="admin-icon-button" type="button" data-close-adminlogs aria-label="Fechar"><i data-lucide="x"></i></button>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-logs-table">
                            <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Admin</th>
                                <th>IP</th>
                                <th>Data</th>
                            </tr>
                            </thead>
                            <tbody id="admin-adminlogs-tbody"></tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="admin-modal-layer" id="admin-user-action-modal" aria-hidden="true">
                <form class="admin-action-modal-card" id="admin-user-action-form">
                    <div class="admin-modal-head">
                        <h3 id="admin-user-action-title">Acao</h3>
                        <button class="admin-icon-button" type="button" data-close-action aria-label="Fechar"><i data-lucide="x"></i></button>
                    </div>
                    <input type="hidden" name="action">
                    <input type="hidden" name="user_id">
                    <div class="admin-field">
                        <label for="admin-action-reason">Motivo</label>
                        <textarea id="admin-action-reason" name="reason" required></textarea>
                    </div>
                    <div class="admin-field" id="admin-action-duration-field">
                        <label for="admin-action-duration">Duracao da suspensao</label>
                        <input id="admin-action-duration" name="duration" type="text" value="30 minutos" placeholder="Ex: 30 minutos, 12 horas, 2 dias">
                    </div>
                    <div class="admin-modal-actions">
                        <button class="admin-text-button" type="button" data-close-action>Cancelar</button>
                        <button class="admin-text-button danger" type="submit">Confirmar</button>
                    </div>
                </form>
            </div>
        </section>

        <script>
            (function () {
                const root = document.getElementById('admin-users-root');
                if (!root) return;

                const els = {
                    search: document.getElementById('admin-users-search'),
                    filterButton: document.getElementById('admin-users-filter-button'),
                    filterLabel: document.getElementById('admin-users-filter-label'),
                    filterMenu: document.getElementById('admin-users-filter-menu'),
                    tbody: document.getElementById('admin-users-tbody'),
                    count: document.getElementById('admin-users-count'),
                    refresh: document.getElementById('admin-users-refresh'),
                    listView: document.getElementById('admin-users-list-view'),
                    detailView: document.getElementById('admin-user-details-view'),
                    sessionLock: document.getElementById('admin-users-session-lock'),
                    adminLogsModal: document.getElementById('admin-adminlogs-modal'),
                    adminLogsTbody: document.getElementById('admin-adminlogs-tbody'),
                    actionModal: document.getElementById('admin-user-action-modal'),
                    actionForm: document.getElementById('admin-user-action-form'),
                    actionTitle: document.getElementById('admin-user-action-title'),
                    durationField: document.getElementById('admin-action-duration-field')
                };

                let users = [];
                let currentUserId = null;
                let searchTimer = null;
                let sessionExpired = false;
                let currentFilter = 'all';

                const statusLabel = { active: 'Ativo', suspended: 'Suspenso', banned: 'Banido' };
                const filterLabel = {
                    all: 'Todos os usuarios',
                    active: 'Ativos',
                    suspended: 'Suspensos',
                    banned: 'Banidos',
                    paid: 'Plano pago',
                    free: 'Plano gratuito'
                };
                const eventLabel = {
                    admin_login_success: 'Login admin',
                    admin_logout: 'Logout admin',
                    admin_user_suspended: 'Suspendeu usuario',
                    admin_user_banned: 'Baniu usuario',
                    admin_user_reactivated: 'Reativou usuario',
                    account_registered: 'Cadastro',
                    account_created_session_started: 'Primeira sessao',
                    login_success: 'Login',
                    login_blocked_moderation: 'Login bloqueado',
                    '2fa_challenge_created': '2FA solicitado',
                    '2fa_code_verified': '2FA validado',
                    '2fa_code_failed': '2FA falhou'
                };

                function esc(value) {
                    return String(value ?? '').replace(/[&<>"']/g, char => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                    }[char]));
                }

                function fmtDate(value) {
                    if (!value) return '-';
                    const date = new Date(String(value).replace(' ', 'T'));
                    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('pt-BR');
                }

                function initials(value) {
                    const text = String(value || 'U').trim();
                    return text ? text.charAt(0).toUpperCase() : 'U';
                }

                function avatar(user, large = false) {
                    const cls = large ? 'admin-user-avatar large' : 'admin-user-avatar';
                    if (user.avatar_url) {
                        return `<span class="${cls}"><img src="${esc(user.avatar_url)}" alt=""></span>`;
                    }
                    return `<span class="${cls}">${esc(initials(user.full_name || user.email || user.phone))}</span>`;
                }

                function planText(user) {
                    return user.plan_type === 'paid' ? 'Pago' : 'Gratuito';
                }

                async function assertSession() {
                    if (sessionExpired) return false;
                    try {
                        const response = await fetch('/api/admin/session');
                        const data = await response.json();
                        if (!response.ok || !data.success || !data.session || Number(data.session.expires_in || 0) <= 0) {
                            expireSession();
                            return false;
                        }
                        return true;
                    } catch (error) {
                        expireSession();
                        return false;
                    }
                }

                function expireSession() {
                    sessionExpired = true;
                    els.sessionLock.classList.add('active');
                    root.querySelectorAll('button, input, select, textarea').forEach(item => {
                        if (!item.matches('[data-close-adminlogs], [data-close-action]')) item.disabled = true;
                    });
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function api(path, options = {}) {
                    if (!(await assertSession())) throw new Error('Sessao expirada.');
                    const response = await fetch('/api/admin/' + path, {
                        headers: { 'Content-Type': 'application/json' },
                        ...options
                    });
                    const data = await response.json();
                    if (response.status === 401 || response.status === 403) {
                        expireSession();
                        throw new Error(data.error || 'Sessao expirada.');
                    }
                    if (!response.ok || !data.success) throw new Error(data.error || data.message || 'Falha na acao.');
                    return data;
                }

                function renderList() {
                    const filter = currentFilter;
                    const filtered = users.filter(user => {
                        const status = user.moderation_status || 'active';
                        if (['active', 'suspended', 'banned'].includes(filter)) return status === filter;
                        if (filter === 'paid') return user.plan_type === 'paid';
                        if (filter === 'free') return user.plan_type !== 'paid';
                        return true;
                    });

                    els.count.textContent = `${filtered.length} exibidos de ${users.length}`;
                    if (!filtered.length) {
                        els.tbody.innerHTML = `<tr><td colspan="7">Nenhum usuario encontrado.</td></tr>`;
                        return;
                    }

                    els.tbody.innerHTML = filtered.map(user => {
                        const status = user.moderation_status || 'active';
                        return `
                            <tr>
                                <td>
                                    <div class="admin-account-cell">
                                        ${avatar(user)}
                                        <div>
                                            <div class="admin-account-name">${esc(user.full_name || 'Sem nome')}</div>
                                            <div class="admin-account-sub">ID #${esc(user.id)}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>${esc(user.email || '-')}</td>
                                <td><span class="admin-status-pill ${esc(status)}">${esc(statusLabel[status] || status)}</span></td>
                                <td><span class="admin-plan-pill ${user.plan_type === 'paid' ? 'paid' : ''}">${esc(planText(user))}</span></td>
                                <td>${esc(user.profiles_count || 0)}</td>
                                <td>${esc(fmtDate(user.last_login_at))}</td>
                                <td><button class="admin-text-button" type="button" data-user-more="${esc(user.id)}"><i data-lucide="settings"></i>Mais acoes</button></td>
                            </tr>
                        `;
                    }).join('');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function loadUsers() {
                    if (sessionExpired) return;
                    els.tbody.innerHTML = `<tr><td colspan="7">Carregando usuarios...</td></tr>`;
                    const q = encodeURIComponent(els.search.value || '');
                    const filter = encodeURIComponent(currentFilter || 'all');
                    try {
                        const data = await api(`users?q=${q}&filter=${filter}`);
                        users = data.users || [];
                        renderList();
                    } catch (error) {
                        if (!sessionExpired) els.tbody.innerHTML = `<tr><td colspan="7">${esc(error.message)}</td></tr>`;
                    }
                }

                function drawChart(points) {
                    const width = 640;
                    const height = 180;
                    const pad = 24;
                    const max = Math.max(1, ...points.map(p => Number(p.total || 0)));
                    const step = points.length > 1 ? (width - pad * 2) / (points.length - 1) : 0;
                    const coords = points.map((p, index) => {
                        const x = pad + (index * step);
                        const y = height - pad - ((Number(p.total || 0) / max) * (height - pad * 2));
                        return [x, y];
                    });
                    const polyline = coords.map(pair => pair.join(',')).join(' ');
                    const circles = coords.map(([x, y]) => `<circle cx="${x}" cy="${y}" r="4"></circle>`).join('');
                    return `
                        <svg class="admin-activity-chart" viewBox="0 0 ${width} ${height}" role="img" aria-label="Grafico de atividade do usuario">
                            <line x1="${pad}" y1="${height - pad}" x2="${width - pad}" y2="${height - pad}" stroke="rgba(148,163,184,.22)"></line>
                            <polyline points="${polyline}" fill="none" stroke="#e50914" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            <g fill="#e50914">${circles}</g>
                        </svg>
                    `;
                }

                async function openDetails(userId) {
                    currentUserId = userId;
                    els.detailView.classList.add('active');
                    els.listView.style.display = 'none';
                    els.detailView.innerHTML = '<p style="color:#94a3b8;margin:0">Carregando detalhes...</p>';
                    try {
                        const data = await api('users/' + userId);
                        renderDetails(data);
                    } catch (error) {
                        els.detailView.innerHTML = `<p style="color:#fecaca;margin:0">${esc(error.message)}</p>`;
                    }
                }

                function renderDetails(data) {
                    const user = data.user;
                    const status = user.moderation_status || 'active';
                    const profilesActive = Number(user.active_profiles_count || 0);
                    const logs = data.logs || [];

                    els.detailView.innerHTML = `
                        <div class="admin-detail-head">
                            <div class="admin-detail-identity">
                                ${avatar(user, true)}
                                <div>
                                    <h2>${esc(user.full_name || 'Sem nome')}</h2>
                                    <p>${esc(user.email || user.phone || '-')}</p>
                                </div>
                            </div>
                            <div class="admin-detail-actions">
                                <button class="admin-text-button" type="button" data-back-users><i data-lucide="arrow-left"></i>Voltar</button>
                                ${status === 'active' ? `
                                    <button class="admin-text-button warn" type="button" data-open-user-action="suspend"><i data-lucide="timer-off"></i>Suspender</button>
                                    <button class="admin-text-button danger" type="button" data-open-user-action="ban"><i data-lucide="ban"></i>Banir</button>
                                ` : `<button class="admin-text-button good" type="button" data-open-user-action="reactivate"><i data-lucide="rotate-ccw"></i>Reativar</button>`}
                            </div>
                        </div>

                        <section>
                            <h3 class="admin-section-title">Informacoes da conta</h3>
                            <div class="admin-table-wrap">
                                <table class="admin-info-table">
                                    <thead><tr><th>Email</th><th>Telefone</th><th>Status</th><th>Perfis ativos</th><th>Plano</th></tr></thead>
                                    <tbody>
                                        <tr>
                                            <td>${esc(user.email || '-')}</td>
                                            <td>${esc(user.phone || '-')}</td>
                                            <td><span class="admin-status-pill ${esc(status)}">${esc(statusLabel[status] || status)}</span></td>
                                            <td>${esc(profilesActive)} de ${esc((data.profiles || []).length)}</td>
                                            <td><span class="admin-plan-pill ${user.plan_type === 'paid' ? 'paid' : ''}">${esc(planText(user))}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section>
                            <h3 class="admin-section-title">Atividade do usuario</h3>
                            <div class="admin-chart-box">${drawChart(data.activity_chart || [])}</div>
                        </section>

                        <section>
                            <button class="admin-collapsible-head" type="button" data-toggle-user-logs>
                                <span>Logs recentes (${logs.length})</span>
                                <i data-lucide="chevron-down"></i>
                            </button>
                            <div class="admin-collapsible-body" id="admin-user-logs-body">
                                <div class="admin-table-wrap">
                                <table class="admin-logs-table">
                                    <thead><tr><th>Evento</th><th>IP</th><th>Navegador</th><th>Data</th></tr></thead>
                                    <tbody>
                                        ${logs.length ? logs.slice(0, 12).map(log => `
                                            <tr>
                                                <td>${esc(eventLabel[log.event_type] || log.event_type)}</td>
                                                <td>${esc(log.ip_address || '-')}</td>
                                                <td>${esc(log.user_agent || '-')}</td>
                                                <td>${esc(fmtDate(log.created_at))}</td>
                                            </tr>
                                        `).join('') : '<tr><td colspan="4">Nenhum log registrado.</td></tr>'}
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </section>
                    `;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function openAdminLogs() {
                    els.adminLogsTbody.innerHTML = '<tr><td colspan="4">Carregando...</td></tr>';
                    els.adminLogsModal.classList.add('active');
                    try {
                        const data = await api('audit-logs');
                        const logs = data.logs || [];
                        els.adminLogsTbody.innerHTML = logs.length ? logs.map(log => `
                            <tr>
                                <td>${esc(eventLabel[log.event_type] || log.event_type)}</td>
                                <td>${esc(log.admin_name || log.admin_email || '-')}</td>
                                <td>${esc(log.ip_address || '-')}</td>
                                <td>${esc(fmtDate(log.created_at))}</td>
                            </tr>
                        `).join('') : '<tr><td colspan="4">Nenhum log administrativo.</td></tr>';
                    } catch (error) {
                        els.adminLogsTbody.innerHTML = `<tr><td colspan="4">${esc(error.message)}</td></tr>`;
                    }
                }

                function openAction(action) {
                    els.actionForm.reset();
                    els.actionForm.action.value = action;
                    els.actionForm.user_id.value = currentUserId;
                    els.actionTitle.textContent = action === 'suspend' ? 'Suspender usuario' : action === 'ban' ? 'Banir usuario' : 'Reativar usuario';
                    els.durationField.style.display = action === 'suspend' ? 'grid' : 'none';
                    els.actionModal.classList.add('active');
                }

                async function submitAction(event) {
                    event.preventDefault();
                    const payload = Object.fromEntries(new FormData(els.actionForm).entries());
                    const action = payload.action;
                    const userId = payload.user_id;
                    delete payload.action;
                    delete payload.user_id;
                    await api(`users/${userId}/${action}`, { method: 'POST', body: JSON.stringify(payload) });
                    els.actionModal.classList.remove('active');
                    await loadUsers();
                    await openDetails(userId);
                }

                document.addEventListener('click', event => {
                    const more = event.target.closest('[data-user-more]');
                    if (more) openDetails(more.dataset.userMore);

                    if (event.target.closest('[data-back-users]')) {
                        els.detailView.classList.remove('active');
                        els.listView.style.display = '';
                    }

                    const action = event.target.closest('[data-open-user-action]');
                    if (action) openAction(action.dataset.openUserAction);

                    if (event.target.closest('[data-toggle-user-logs]')) {
                        document.getElementById('admin-user-logs-body')?.classList.toggle('active');
                    }

                    if (event.target.closest('#admin-open-adminlogs')) openAdminLogs();
                    if (event.target.closest('[data-close-adminlogs]') || event.target === els.adminLogsModal) els.adminLogsModal.classList.remove('active');
                    if (event.target.closest('[data-close-action]') || event.target === els.actionModal) els.actionModal.classList.remove('active');

                    const filterButton = event.target.closest('#admin-users-filter-button');
                    if (filterButton) {
                        els.filterMenu.classList.toggle('active');
                    }

                    const filterOption = event.target.closest('[data-filter-option]');
                    if (filterOption) {
                        currentFilter = filterOption.dataset.filterOption || 'all';
                        els.filterButton.dataset.filterValue = currentFilter;
                        els.filterLabel.textContent = filterLabel[currentFilter] || 'Todos os usuarios';
                        els.filterMenu.querySelectorAll('[data-filter-option]').forEach(item => item.classList.toggle('active', item === filterOption));
                        els.filterMenu.classList.remove('active');
                        loadUsers();
                    }

                    if (!event.target.closest('.admin-context-wrap')) {
                        els.filterMenu.classList.remove('active');
                    }
                });

                els.actionForm.addEventListener('submit', submitAction);
                els.refresh.addEventListener('click', loadUsers);
                els.search.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(loadUsers, 220);
                });

                setInterval(assertSession, 15000);
                window.AdminUsersPanel = { load: loadUsers, refresh: loadUsers };
            })();
        </script>
        <?php
    }
}
