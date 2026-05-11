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
                <button class="admin-icon-btn" type="button" data-admin-logout aria-label="Sair">
                    <i data-lucide="log-out"></i>
                </button>
            </div>
        </header>

        <script>
            document.addEventListener('click', async function (event) {
                const btn = event.target.closest('[data-admin-logout]');
                if (!btn) return;

                await fetch('/api/admin/auth/logout', { method: 'POST' });
                window.location.reload();
            });
        </script>
        <?php
    }
}
