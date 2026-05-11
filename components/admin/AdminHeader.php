<?php
declare(strict_types=1);

final class AdminHeader
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-header {
                height: 64px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                border-bottom: 1px solid rgba(148, 163, 184, .14);
                background: rgba(10, 12, 16, .86);
                backdrop-filter: blur(16px);
                padding: 0 24px;
                position: sticky;
                top: 0;
                z-index: 20;
            }

            .admin-header-title {
                display: grid;
                gap: 2px;
            }

            .admin-header-title strong {
                color: #fff;
                font-size: 1rem;
                line-height: 1.2;
            }

            .admin-header-title span {
                color: #94a3b8;
                font-size: .78rem;
            }

            .admin-header-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .admin-identity {
                max-width: 280px;
                color: #e2e8f0;
                font-size: .86rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .admin-icon-btn {
                width: 38px;
                height: 38px;
                border-radius: 8px;
                border: 1px solid rgba(148, 163, 184, .22);
                background: transparent;
                color: #e2e8f0;
                display: inline-grid;
                place-items: center;
                cursor: pointer;
            }

            .admin-icon-btn:hover {
                border-color: rgba(229, 9, 20, .56);
                color: #fff;
                background: rgba(229, 9, 20, .1);
            }

            .admin-icon-btn svg {
                width: 18px;
                height: 18px;
            }

            /* Modal do usuário */
            .admin-user-modal {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .6);
                backdrop-filter: blur(4px);
                display: none;
                place-items: center;
                z-index: 100;
            }

            .admin-user-modal.active {
                display: grid;
            }

            .admin-user-modal-content {
                width: min(360px, 90vw);
                border: 1px solid rgba(148, 163, 184, .2);
                border-radius: 12px;
                background: #0f131a;
                padding: 24px;
                box-shadow: 0 24px 70px rgba(0, 0, 0, .5);
            }

            .admin-user-modal-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 20px;
            }

            .admin-user-avatar {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #e50914, #b20710);
                display: grid;
                place-items: center;
                color: #fff;
                font-size: 1.2rem;
                font-weight: 600;
            }

            .admin-user-info h3 {
                margin: 0;
                color: #fff;
                font-size: 1rem;
            }

            .admin-user-info p {
                margin: 4px 0 0;
                color: #94a3b8;
                font-size: .82rem;
            }

            .admin-session-timer {
                margin-top: 16px;
                padding: 14px;
                border: 1px solid rgba(148, 163, 184, .14);
                border-radius: 8px;
                background: rgba(148, 163, 184, .06);
            }

            .admin-session-timer-label {
                color: #94a3b8;
                font-size: .78rem;
                margin-bottom: 6px;
            }

            .admin-session-timer-value {
                color: #fff;
                font-size: 1.1rem;
                font-weight: 600;
                font-family: ui-monospace, monospace;
            }

            .admin-session-timer-value.warning {
                color: #f59e0b;
            }

            .admin-session-timer-value.danger {
                color: #ef4444;
            }

            .admin-user-modal-close {
                width: 100%;
                margin-top: 16px;
                padding: 12px;
                border: 1px solid rgba(148, 163, 184, .2);
                border-radius: 8px;
                background: transparent;
                color: #e2e8f0;
                font-size: .9rem;
                cursor: pointer;
                transition: all .2s;
            }

            .admin-user-modal-close:hover {
                border-color: #e50914;
                background: rgba(229, 9, 20, .1);
            }

            @media (max-width: 760px) {
                .admin-header {
                    padding: 0 16px;
                }

                .admin-identity {
                    display: none;
                }
            }
        </style>

        <header class="admin-header">
            <div class="admin-header-title">
                <strong>Painel PipoCine</strong>
                <span>Administracao segura</span>
            </div>
            <div class="admin-header-actions">
                <span class="admin-identity" data-admin-name></span>
                <button class="admin-icon-btn" type="button" data-admin-user aria-label="Usuario">
                    <i data-lucide="user"></i>
                </button>
                <button class="admin-icon-btn" type="button" data-admin-logout aria-label="Sair">
                    <i data-lucide="log-out"></i>
                </button>
            </div>
        </header>

        <!-- Modal do Usuario -->
        <div class="admin-user-modal" id="admin-user-modal">
            <div class="admin-user-modal-content">
                <div class="admin-user-modal-header">
                    <div class="admin-user-avatar" id="admin-user-avatar">A</div>
                    <div class="admin-user-info">
                        <h3 id="admin-user-name">Administrador</h3>
                        <p id="admin-user-email">admin@email.com</p>
                    </div>
                </div>
                <div class="admin-session-timer">
                    <div class="admin-session-timer-label">Sessao expira em</div>
                    <div class="admin-session-timer-value" id="admin-session-timer">--:--:--</div>
                </div>
                <button class="admin-user-modal-close" id="admin-user-modal-close">Fechar</button>
            </div>
        </div>

        <script>
            (function() {
                const modal = document.getElementById('admin-user-modal');
                const userBtn = document.querySelector('[data-admin-user]');
                const closeBtn = document.getElementById('admin-user-modal-close');
                const avatar = document.getElementById('admin-user-avatar');
                const nameEl = document.getElementById('admin-user-name');
                const emailEl = document.getElementById('admin-user-email');
                const timerEl = document.getElementById('admin-session-timer');

                let sessionData = null;
                let timerInterval = null;

                function formatTime(seconds) {
                    const h = Math.floor(seconds / 3600);
                    const m = Math.floor((seconds % 3600) / 60);
                    const s = seconds % 60;
                    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                }

                function updateTimerDisplay() {
                    if (!sessionData || !sessionData.expires_in) {
                        timerEl.textContent = '--:--:--';
                        return;
                    }

                    const now = Math.floor(Date.now() / 1000);
                    const expiresAt = sessionData.expires_at;
                    const remaining = Math.max(0, expiresAt - now);

                    timerEl.textContent = formatTime(remaining);

                    // Altera cor baseado no tempo restante
                    timerEl.classList.remove('warning', 'danger');
                    if (remaining < 300) { // < 5 minutos
                        timerEl.classList.add('danger');
                    } else if (remaining < 600) { // < 10 minutos
                        timerEl.classList.add('warning');
                    }
                }

                async function loadSessionData() {
                    try {
                        const response = await fetch('/api/admin/session');
                        if (!response.ok) throw new Error('Sessao invalida');

                        const data = await response.json();
                        if (data.success && data.session) {
                            sessionData = data.session;

                            // Atualiza dados do usuario
                            const admin = sessionData.admin;
                            nameEl.textContent = admin.display_name || 'Administrador';
                            emailEl.textContent = admin.email;
                            avatar.textContent = (admin.display_name || 'A').charAt(0).toUpperCase();

                            updateTimerDisplay();
                        }
                    } catch (err) {
                        console.error('Erro ao carregar sessao:', err);
                    }
                }

                function openModal() {
                    loadSessionData();
                    modal.classList.add('active');

                    // Inicia contador em tempo real
                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = setInterval(updateTimerDisplay, 1000);
                }

                function closeModal() {
                    modal.classList.remove('active');
                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                }

                userBtn?.addEventListener('click', openModal);
                closeBtn?.addEventListener('click', closeModal);

                // Fecha ao clicar fora
                modal?.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });

                // Logout
                document.addEventListener('click', async function(event) {
                    const btn = event.target.closest('[data-admin-logout]');
                    if (!btn) return;

                    await fetch('/api/admin/auth/logout', { method: 'POST' });
                    window.location.reload();
                });

                // Fecha com ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.classList.contains('active')) {
                        closeModal();
                    }
                });
            })();
        </script>
        <?php
    }
}
