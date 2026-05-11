<?php
declare(strict_types=1);

final class AdminUsersPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-users-shell {
                display: grid;
                grid-template-columns: minmax(320px, 420px) minmax(0, 1fr);
                gap: 16px;
                margin-top: 16px;
            }

            .admin-users-toolbar,
            .admin-users-list,
            .admin-user-detail {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
            }

            .admin-users-toolbar {
                padding: 14px;
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .admin-search {
                flex: 1;
                min-height: 42px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                color: #fff;
                padding: 0 12px;
                outline: none;
            }

            .admin-users-list {
                margin-top: 12px;
                overflow: hidden;
            }

            .admin-user-row {
                width: 100%;
                border: 0;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
                background: transparent;
                color: #e2e8f0;
                padding: 14px;
                display: grid;
                grid-template-columns: 42px minmax(0, 1fr) auto;
                gap: 12px;
                text-align: left;
                cursor: pointer;
            }

            .admin-user-row:hover,
            .admin-user-row.active {
                background: rgba(229, 9, 20, .10);
            }

            .admin-user-avatar-mini {
                width: 42px;
                height: 42px;
                border-radius: 8px;
                background: #e50914;
                color: #fff;
                display: grid;
                place-items: center;
                font-weight: 800;
                overflow: hidden;
            }

            .admin-user-avatar-mini img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .admin-user-main {
                min-width: 0;
            }

            .admin-user-name {
                display: block;
                color: #fff;
                font-weight: 760;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .admin-user-meta {
                display: block;
                margin-top: 4px;
                color: #94a3b8;
                font-size: .8rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .admin-status-pill {
                align-self: start;
                border-radius: 999px;
                padding: 5px 9px;
                font-size: .72rem;
                font-weight: 800;
                text-transform: uppercase;
                color: #fff;
                background: #15803d;
            }

            .admin-status-pill.suspended { background: #b45309; }
            .admin-status-pill.banned { background: #b91c1c; }

            .admin-user-detail {
                min-height: 560px;
                padding: 18px;
            }

            .admin-detail-empty {
                min-height: 520px;
                display: grid;
                place-items: center;
                color: #94a3b8;
                text-align: center;
            }

            .admin-detail-header {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: flex-start;
                border-bottom: 1px solid rgba(148, 163, 184, .12);
                padding-bottom: 16px;
            }

            .admin-detail-title h2 {
                margin: 0;
                color: #fff;
                font-size: 1.28rem;
            }

            .admin-detail-title p {
                margin: 6px 0 0;
                color: #94a3b8;
            }

            .admin-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: flex-end;
            }

            .admin-action-btn {
                min-height: 38px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #111827;
                color: #fff;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 12px;
                cursor: pointer;
                font-weight: 760;
            }

            .admin-action-btn.danger {
                background: rgba(185, 28, 28, .18);
                border-color: rgba(239, 68, 68, .38);
            }

            .admin-action-btn.warn {
                background: rgba(180, 83, 9, .18);
                border-color: rgba(245, 158, 11, .38);
            }

            .admin-action-btn.good {
                background: rgba(21, 128, 61, .18);
                border-color: rgba(34, 197, 94, .38);
            }

            .admin-facts {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
                margin: 16px 0;
            }

            .admin-fact {
                border: 1px solid rgba(148, 163, 184, .12);
                border-radius: 8px;
                padding: 12px;
                background: rgba(148, 163, 184, .05);
            }

            .admin-fact span {
                display: block;
                color: #94a3b8;
                font-size: .72rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .admin-fact strong {
                display: block;
                margin-top: 7px;
                color: #fff;
                font-size: .9rem;
                overflow-wrap: anywhere;
            }

            .admin-section-title {
                margin: 20px 0 10px;
                color: #fff;
                font-size: 1rem;
            }

            .admin-profile-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
                gap: 10px;
            }

            .admin-profile-card,
            .admin-log-row {
                border: 1px solid rgba(148, 163, 184, .12);
                border-radius: 8px;
                background: #0a0c10;
                padding: 12px;
            }

            .admin-profile-card strong,
            .admin-log-row strong {
                color: #fff;
            }

            .admin-profile-card span,
            .admin-log-row span {
                display: block;
                color: #94a3b8;
                margin-top: 5px;
                font-size: .82rem;
                overflow-wrap: anywhere;
            }

            .admin-log-list {
                display: grid;
                gap: 8px;
                max-height: 360px;
                overflow: auto;
                padding-right: 4px;
            }

            .admin-modal {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .68);
                display: none;
                place-items: center;
                z-index: 120;
                padding: 18px;
            }

            .admin-modal.active {
                display: grid;
            }

            .admin-modal-card {
                width: min(520px, 100%);
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #0f131a;
                padding: 18px;
            }

            .admin-modal-card h3 {
                margin: 0 0 14px;
                color: #fff;
            }

            .admin-modal-card label {
                display: grid;
                gap: 6px;
                color: #94a3b8;
                font-size: .84rem;
                font-weight: 760;
                margin-top: 10px;
            }

            .admin-modal-card textarea,
            .admin-modal-card input {
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                color: #fff;
                padding: 10px;
                min-height: 42px;
            }

            .admin-modal-card textarea {
                min-height: 96px;
                resize: vertical;
            }

            .admin-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 16px;
            }

            @media (max-width: 1120px) {
                .admin-users-shell {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 720px) {
                .admin-facts {
                    grid-template-columns: 1fr 1fr;
                }

                .admin-detail-header {
                    display: grid;
                }

                .admin-actions {
                    justify-content: flex-start;
                }
            }
        </style>

        <section data-admin-route="users" class="admin-route-panel" hidden>
            <div class="admin-users-toolbar">
                <i data-lucide="search"></i>
                <input class="admin-search" id="admin-user-search" type="search" placeholder="Buscar por email, telefone ou nome">
                <button class="admin-action-btn" type="button" id="admin-users-refresh"><i data-lucide="refresh-cw"></i>Atualizar</button>
            </div>

            <div class="admin-users-shell">
                <div class="admin-users-list" id="admin-users-list"></div>
                <article class="admin-user-detail" id="admin-user-detail">
                    <div class="admin-detail-empty">
                        <div>
                            <i data-lucide="user-search"></i>
                            <p>Selecione uma conta para ver perfis, logs e acoes.</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <div class="admin-modal" id="admin-moderation-modal" aria-hidden="true">
            <form class="admin-modal-card" id="admin-moderation-form">
                <h3 id="admin-moderation-title">Acao administrativa</h3>
                <input type="hidden" name="action">
                <input type="hidden" name="user_id">
                <label>
                    Motivo
                    <textarea name="reason" required placeholder="Explique o motivo que aparecera para o usuario."></textarea>
                </label>
                <label id="admin-duration-field">
                    Duracao em minutos
                    <input type="number" name="duration_minutes" min="15" step="15" value="1440">
                </label>
                <div class="admin-modal-actions">
                    <button class="admin-action-btn" type="button" data-close-moderation>Cancelar</button>
                    <button class="admin-action-btn danger" type="submit">Confirmar</button>
                </div>
            </form>
        </div>

        <script>
            (function () {
                const list = document.getElementById('admin-users-list');
                const detail = document.getElementById('admin-user-detail');
                const search = document.getElementById('admin-user-search');
                const refresh = document.getElementById('admin-users-refresh');
                const modal = document.getElementById('admin-moderation-modal');
                const form = document.getElementById('admin-moderation-form');
                const durationField = document.getElementById('admin-duration-field');
                const modalTitle = document.getElementById('admin-moderation-title');
                let currentUserId = null;
                let searchTimer = null;

                const statusLabel = {
                    active: 'Ativa',
                    suspended: 'Suspensa',
                    banned: 'Banida'
                };

                const eventLabel = {
                    account_registered: 'Cadastro criado',
                    account_created_session_started: 'Sessao inicial',
                    login_success: 'Login realizado',
                    login_blocked_moderation: 'Login bloqueado',
                    '2fa_challenge_created': 'Codigo 2FA solicitado',
                    '2fa_code_verified': 'Codigo 2FA validado',
                    '2fa_code_failed': 'Codigo 2FA incorreto'
                };

                function esc(value) {
                    return String(value ?? '').replace(/[&<>"']/g, char => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                    }[char]));
                }

                function fmtDate(value) {
                    if (!value) return '-';
                    const date = new Date(String(value).replace(' ', 'T'));
                    if (Number.isNaN(date.getTime())) return value;
                    return date.toLocaleString('pt-BR');
                }

                function initials(name) {
                    const clean = String(name || 'U').trim();
                    return clean ? clean.charAt(0).toUpperCase() : 'U';
                }

                async function api(path, options = {}) {
                    const response = await fetch('/api/admin/' + path, {
                        headers: { 'Content-Type': 'application/json' },
                        ...options
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || data.message || 'Falha na acao.');
                    }
                    return data;
                }

                function renderUsers(users) {
                    if (!users.length) {
                        list.innerHTML = '<div class="admin-detail-empty"><p>Nenhum usuario encontrado.</p></div>';
                        return;
                    }

                    list.innerHTML = users.map(user => {
                        const status = user.moderation_status || 'active';
                        const identifier = user.email || user.phone || 'sem identificador';
                        const avatar = user.avatar_url
                            ? `<img src="${esc(user.avatar_url)}" alt="">`
                            : esc(initials(user.full_name || identifier));
                        return `
                            <button class="admin-user-row ${Number(user.id) === Number(currentUserId) ? 'active' : ''}" type="button" data-user-id="${esc(user.id)}">
                                <span class="admin-user-avatar-mini">${avatar}</span>
                                <span class="admin-user-main">
                                    <span class="admin-user-name">${esc(user.full_name || 'Usuario sem nome')}</span>
                                    <span class="admin-user-meta">${esc(identifier)} · ${esc(user.profiles_count)} perfis · ultimo login ${esc(fmtDate(user.last_login_at))}</span>
                                </span>
                                <span class="admin-status-pill ${esc(status)}">${esc(statusLabel[status] || status)}</span>
                            </button>
                        `;
                    }).join('');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function loadUsers() {
                    list.innerHTML = '<div class="admin-detail-empty"><p>Carregando usuarios...</p></div>';
                    const q = encodeURIComponent(search?.value || '');
                    const data = await api('users?q=' + q);
                    renderUsers(data.users || []);
                }

                function renderDetails(data) {
                    const user = data.user;
                    const status = user.moderation_status || 'active';
                    const identifier = user.email || user.phone || 'sem identificador';
                    detail.innerHTML = `
                        <div class="admin-detail-header">
                            <div class="admin-detail-title">
                                <h2>${esc(user.full_name || 'Usuario sem nome')}</h2>
                                <p>${esc(identifier)}</p>
                            </div>
                            <div class="admin-actions">
                                <button class="admin-action-btn warn" type="button" data-open-action="suspend"><i data-lucide="timer-off"></i>Suspender</button>
                                <button class="admin-action-btn danger" type="button" data-open-action="ban"><i data-lucide="ban"></i>Banir</button>
                                <button class="admin-action-btn good" type="button" data-open-action="reactivate"><i data-lucide="rotate-ccw"></i>Reativar</button>
                            </div>
                        </div>
                        <div class="admin-facts">
                            <div class="admin-fact"><span>Status</span><strong>${esc(statusLabel[status] || status)}</strong></div>
                            <div class="admin-fact"><span>Email</span><strong>${esc(user.email || '-')}</strong></div>
                            <div class="admin-fact"><span>Telefone</span><strong>${esc(user.phone || '-')}</strong></div>
                            <div class="admin-fact"><span>Criada em</span><strong>${esc(fmtDate(user.created_at))}</strong></div>
                            <div class="admin-fact"><span>Motivo</span><strong>${esc(user.moderation_reason || '-')}</strong></div>
                            <div class="admin-fact"><span>Suspensao ate</span><strong>${esc(fmtDate(user.moderation_until))}</strong></div>
                            <div class="admin-fact"><span>Atualizada</span><strong>${esc(fmtDate(user.updated_at))}</strong></div>
                            <div class="admin-fact"><span>ID</span><strong>#${esc(user.id)}</strong></div>
                        </div>
                        <h3 class="admin-section-title">Perfis desta conta</h3>
                        <div class="admin-profile-grid">
                            ${(data.profiles || []).map(profile => `
                                <div class="admin-profile-card">
                                    <strong>${esc(profile.profile_name)}</strong>
                                    <span>@${esc(profile.username || 'sem-usuario')}</span>
                                    <span>${Number(profile.is_kids) ? 'Perfil infantil' : 'Perfil padrao'} · ${Number(profile.is_watching) ? 'assistindo agora' : 'offline'}</span>
                                    <span>Ultima atividade: ${esc(fmtDate(profile.last_active_at))}</span>
                                </div>
                            `).join('') || '<div class="admin-profile-card"><span>Nenhum perfil criado.</span></div>'}
                        </div>
                        <h3 class="admin-section-title">Logs recentes da conta</h3>
                        <div class="admin-log-list">
                            ${(data.logs || []).map(log => `
                                <div class="admin-log-row">
                                    <strong>${esc(eventLabel[log.event_type] || log.event_type)}</strong>
                                    <span>${esc(fmtDate(log.created_at))} · IP ${esc(log.ip_address || '-')}</span>
                                    <span>${esc(log.user_agent || '-')}</span>
                                </div>
                            `).join('') || '<div class="admin-log-row"><span>Nenhum log registrado.</span></div>'}
                        </div>
                        <h3 class="admin-section-title">Historico administrativo</h3>
                        <div class="admin-log-list">
                            ${(data.moderation_history || []).map(item => `
                                <div class="admin-log-row">
                                    <strong>${esc(item.action)}</strong>
                                    <span>${esc(fmtDate(item.created_at))} · ${esc(item.admin_name || item.admin_email || 'Admin')}</span>
                                    <span>${esc(item.reason || '-')}</span>
                                </div>
                            `).join('') || '<div class="admin-log-row"><span>Nenhuma acao administrativa registrada.</span></div>'}
                        </div>
                    `;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function loadDetails(userId) {
                    currentUserId = userId;
                    document.querySelectorAll('.admin-user-row').forEach(row => row.classList.toggle('active', row.dataset.userId === String(userId)));
                    detail.innerHTML = '<div class="admin-detail-empty"><p>Carregando detalhes...</p></div>';
                    renderDetails(await api('users/' + userId));
                }

                function openAction(action) {
                    form.reset();
                    form.action.value = action;
                    form.user_id.value = currentUserId;
                    durationField.style.display = action === 'suspend' ? 'grid' : 'none';
                    modalTitle.textContent = action === 'suspend' ? 'Suspender conta' : (action === 'ban' ? 'Banir conta' : 'Reativar conta');
                    modal.classList.add('active');
                }

                async function submitAction(event) {
                    event.preventDefault();
                    const payload = Object.fromEntries(new FormData(form).entries());
                    const action = payload.action;
                    const userId = payload.user_id;
                    delete payload.action;
                    delete payload.user_id;
                    if (payload.duration_minutes) payload.duration_minutes = Number(payload.duration_minutes);
                    await api(`users/${userId}/${action}`, { method: 'POST', body: JSON.stringify(payload) });
                    modal.classList.remove('active');
                    await loadUsers();
                    await loadDetails(userId);
                }

                list?.addEventListener('click', event => {
                    const row = event.target.closest('[data-user-id]');
                    if (row) loadDetails(row.dataset.userId);
                });

                detail?.addEventListener('click', event => {
                    const btn = event.target.closest('[data-open-action]');
                    if (btn && currentUserId) openAction(btn.dataset.openAction);
                });

                form?.addEventListener('submit', submitAction);
                document.addEventListener('click', event => {
                    if (event.target.matches('[data-close-moderation]') || event.target === modal) {
                        modal.classList.remove('active');
                    }
                });

                refresh?.addEventListener('click', loadUsers);
                search?.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(loadUsers, 260);
                });

                window.AdminUsersPanel = { load: loadUsers };
            })();
        </script>
        <?php
    }
}
