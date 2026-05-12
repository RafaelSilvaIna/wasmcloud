<?php
declare(strict_types=1);

final class AdminSidebar
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-sidebar {
                width: 248px;
                min-height: 100vh;
                border-right: 1px solid rgba(148, 163, 184, .14);
                background: #0a0c10;
                padding: 22px 14px;
                position: sticky;
                top: 0;
                align-self: start;
            }

            .admin-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 0 10px 24px;
                color: #fff;
                font-weight: 800;
            }

            .admin-brand-mark {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                background: #e50914;
                display: grid;
                place-items: center;
                color: #fff;
                font-size: .9rem;
            }

            .admin-nav {
                display: grid;
                gap: 4px;
            }

            .admin-nav a {
                min-height: 42px;
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 8px;
                padding: 0 10px;
                color: #94a3b8;
                text-decoration: none;
                font-size: .92rem;
                font-weight: 650;
            }

            .admin-nav a.active,
            .admin-nav a:hover {
                color: #fff;
                background: rgba(229, 9, 20, .12);
            }

            .admin-nav svg {
                width: 18px;
                height: 18px;
            }

            @media (max-width: 900px) {
                .admin-sidebar {
                    width: 100%;
                    min-height: auto;
                    position: static;
                    border-right: 0;
                    border-bottom: 1px solid rgba(148, 163, 184, .14);
                    padding: 14px;
                }

                .admin-brand {
                    padding-bottom: 12px;
                }

                .admin-nav {
                    display: flex;
                    overflow-x: auto;
                    padding-bottom: 2px;
                }

                .admin-nav a {
                    flex: 0 0 auto;
                }
            }
        </style>

        <aside class="admin-sidebar">
            <div class="admin-brand">
                <span class="admin-brand-mark">PC</span>
                <span>Admin</span>
            </div>
            <nav class="admin-nav" aria-label="Navegacao administrativa">
                <a class="active" href="?route=overview" data-admin-nav="overview"><i data-lucide="layout-dashboard"></i><span>Visao geral</span></a>
                <a href="?route=users" data-admin-nav="users"><i data-lucide="users"></i><span>Usuarios</span></a>
                <a href="?route=subscriptions" data-admin-nav="subscriptions"><i data-lucide="credit-card"></i><span>Assinaturas</span></a>
                <a href="?route=metrics" data-admin-nav="metrics"><i data-lucide="chart-no-axes-combined"></i><span>Metricas de uso</span></a>
                <a href="#security"><i data-lucide="shield-check"></i><span>Seguranca</span></a>
            </nav>
        </aside>

        <script>
            document.addEventListener('click', function (event) {
                const link = event.target.closest('.admin-nav a');
                if (!link) return;

                document.querySelectorAll('.admin-nav a').forEach(item => item.classList.remove('active'));
                link.classList.add('active');
            });
        </script>
        <?php
    }
}
