<?php
declare(strict_types=1);

final class AdminSidebar
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-sidebar {
                width: 264px;
                min-height: 100vh;
                border-right: 1px solid rgba(148, 163, 184, .14);
                background:
                    radial-gradient(circle at 18% 4%, rgba(229,9,20,.12), transparent 28%),
                    #0a0c10;
                padding: 22px 14px 18px;
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
                gap: 18px;
            }

            .admin-nav-group {
                display: grid;
                gap: 4px;
            }

            .admin-nav-label {
                padding: 0 10px 5px;
                color: rgba(148,163,184,.72);
                text-transform: uppercase;
                letter-spacing: .12em;
                font-size: .68rem;
                font-weight: 800;
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

            .admin-nav-badge {
                margin-left: auto;
                min-width: 18px;
                min-height: 18px;
                display: inline-grid;
                place-items: center;
                border-radius: 999px;
                padding: 0 5px;
                color: #fff;
                background: var(--admin-red);
                font-size: .68rem;
                font-style: normal;
                font-weight: 850;
            }

            .admin-nav-badge:empty { display: none; }

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
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 10px;
                }

                .admin-nav-group {
                    display: flex;
                    overflow-x: auto;
                    padding-bottom: 2px;
                }

                .admin-nav-label {
                    display: none;
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
                <section class="admin-nav-group">
                    <span class="admin-nav-label">Operação</span>
                    <a class="active" href="?route=overview" data-admin-nav="overview"><i data-lucide="layout-dashboard"></i><span>Visão geral</span></a>
                    <a href="?route=users" data-admin-nav="users"><i data-lucide="users"></i><span>Usuários</span></a>
                    <a href="?route=subscriptions" data-admin-nav="subscriptions"><i data-lucide="credit-card"></i><span>Assinaturas</span></a>
                    <a href="?route=box" data-admin-nav="box"><i data-lucide="inbox"></i><span>Box</span></a>
                </section>
                <section class="admin-nav-group">
                    <span class="admin-nav-label">Receita</span>
                    <a href="?route=ads-review" data-admin-nav="ads-review"><i data-lucide="megaphone"></i><span>Ads em revisão</span><em class="admin-nav-badge" id="admin-ads-review-badge"></em></a>
                </section>
                <section class="admin-nav-group">
                    <span class="admin-nav-label">Inteligência</span>
                    <a href="?route=metrics" data-admin-nav="metrics"><i data-lucide="chart-no-axes-combined"></i><span>Métricas de uso</span></a>
                    <a href="?route=api-metrics" data-admin-nav="api-metrics"><i data-lucide="code-xml"></i><span>Métricas de API</span></a>
                    <a href="?route=player-logs" data-admin-nav="player-logs"><i data-lucide="monitor-play"></i><span>Player logs</span></a>
                </section>
                <section class="admin-nav-group">
                    <span class="admin-nav-label">Relacionamento e risco</span>
                    <a href="?route=suporte" data-admin-nav="suporte"><i data-lucide="headphones"></i><span>Suporte</span></a>
                    <a href="?route=security" data-admin-nav="security"><i data-lucide="shield-alert"></i><span>Segurança</span></a>
                    <a href="?route=route-locks" data-admin-nav="route-locks"><i data-lucide="route"></i><span>Rotas</span></a>
                    <a href="?route=status-incidents" data-admin-nav="status-incidents"><i data-lucide="activity"></i><span>Incidentes</span></a>
                </section>
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
