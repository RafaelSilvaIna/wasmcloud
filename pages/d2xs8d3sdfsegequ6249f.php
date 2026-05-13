<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/admin/AdminJwt.php';
require_once __DIR__ . '/../models/admin/AdminModel.php';
require_once __DIR__ . '/../services/admin/AdminAuthService.php';
require_once __DIR__ . '/../components/admin/AdminHeader.php';
require_once __DIR__ . '/../components/admin/AdminSidebar.php';
require_once __DIR__ . '/../components/admin/AdminUsersPanel.php';
require_once __DIR__ . '/../components/admin/magner/AdminUsageMetricsPanel.php';
require_once __DIR__ . '/../components/admin/magner/AdminApiMetricsPanel.php';
require_once __DIR__ . '/../components/admin/subscriptions/AdminSubscriptionsPanel.php';
require_once __DIR__ . '/../components/admin/suporte/AdminSupportPanel.php';

use Models\Admin\AdminModel;
use Services\Admin\AdminAuthService;

$adminModel = $pdo ? new AdminModel($pdo) : null;
$adminAuth = $adminModel ? new AdminAuthService($adminModel) : null;
$ipAllowed = $adminAuth ? $adminAuth->isRequestAllowed() : false;
$admin = ($ipAllowed && $adminAuth) ? $adminAuth->currentAdmin() : null;
$databaseReady = (bool) $adminAuth;
$detectedIp = $adminAuth ? $adminAuth->requestIp() : 'indisponivel';
$adminRoute = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['route'] ?? 'overview')) ?: 'overview';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>PipoCine Admin</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        :root {
            --admin-bg: #07090d;
            --admin-surface: #0f131a;
            --admin-elevated: #141923;
            --admin-border: rgba(148, 163, 184, .16);
            --admin-text: #e2e8f0;
            --admin-muted: #94a3b8;
            --admin-red: #e50914;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--admin-bg);
            color: var(--admin-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .admin-denied,
        .admin-login-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .admin-login-card,
        .admin-denied-card {
            width: min(420px, 100%);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            background: var(--admin-surface);
            padding: 28px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .4);
        }

        .admin-login-card h1,
        .admin-denied-card h1 {
            margin: 0 0 10px;
            color: #fff;
            font-size: 1.65rem;
        }

        .admin-login-card p,
        .admin-denied-card p {
            margin: 0 0 22px;
            color: var(--admin-muted);
            line-height: 1.55;
        }

        .admin-form {
            display: grid;
            gap: 14px;
        }

        .admin-field {
            display: grid;
            gap: 7px;
        }

        .admin-field label {
            color: var(--admin-muted);
            font-size: .84rem;
            font-weight: 700;
        }

        .admin-field input {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            background: #090c12;
            color: #fff;
            padding: 0 12px;
            font-size: .96rem;
        }

        .admin-btn {
            min-height: 46px;
            border: 0;
            border-radius: 8px;
            background: var(--admin-red);
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }

        .admin-btn:hover {
            background: #f40612;
        }

        .admin-message {
            min-height: 20px;
            color: #fca5a5;
            font-size: .88rem;
        }

        .admin-app {
            min-height: 100vh;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
        }

        .admin-main {
            min-width: 0;
        }

        .admin-content {
            padding: 26px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .admin-card {
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            background: var(--admin-surface);
            padding: 18px;
        }

        .admin-card span {
            display: block;
            color: var(--admin-muted);
            font-size: .78rem;
            font-weight: 750;
        }

        .admin-card strong {
            display: block;
            margin-top: 10px;
            color: #fff;
            font-size: 1.65rem;
        }

        .admin-panel {
            margin-top: 14px;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            background: var(--admin-surface);
            padding: 20px;
        }

        .admin-panel h2 {
            margin: 0 0 8px;
            font-size: 1.1rem;
            color: #fff;
        }

        .admin-panel p {
            margin: 0;
            color: var(--admin-muted);
            line-height: 1.55;
        }

        @media (max-width: 900px) {
            .admin-app {
                grid-template-columns: 1fr;
            }

            .admin-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .admin-content {
                padding: 16px;
            }

            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php if (!$databaseReady): ?>
    <main class="admin-denied">
        <section class="admin-denied-card">
            <h1>Banco indisponivel</h1>
            <p>O painel administrativo precisa da conexao principal do Pipocine para validar acesso.</p>
        </section>
    </main>
<?php elseif (!$ipAllowed): ?>
    <main class="admin-denied">
        <section class="admin-denied-card">
            <h1>Acesso restrito</h1>
            <p>Este painel administrativo esta disponivel apenas para IPs autorizados.</p>
            <p style="margin-bottom:0;font-size:.86rem">IP detectado: <?= htmlspecialchars($detectedIp, ENT_QUOTES, 'UTF-8') ?></p>
        </section>
    </main>
<?php elseif (!$admin): ?>
    <main class="admin-login-shell">
        <section class="admin-login-card">
            <h1>Admin PipoCine</h1>
            <p>Entre com suas credenciais administrativas. A sessao expira em 1 hora.</p>
            <form class="admin-form" id="admin-login-form">
                <div class="admin-field">
                    <label for="admin-email">E-mail</label>
                    <input id="admin-email" name="email" type="email" autocomplete="username" required>
                </div>
                <div class="admin-field">
                    <label for="admin-password">Senha</label>
                    <input id="admin-password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="admin-btn" type="submit">Entrar</button>
                <div class="admin-message" id="admin-login-message" role="status"></div>
            </form>
        </section>
    </main>
<?php else: ?>
    <div class="admin-app">
        <?php AdminSidebar::render(); ?>
        <main class="admin-main">
            <?php AdminHeader::render(); ?>
            <section class="admin-content">
                <section data-admin-route="overview" class="admin-route-panel">
                    <div class="admin-grid" id="admin-stats">
                        <article class="admin-card"><span>Usuarios</span><strong data-stat="users">-</strong></article>
                        <article class="admin-card"><span>Perfis</span><strong data-stat="profiles">-</strong></article>
                        <article class="admin-card"><span>Suspensos</span><strong data-stat="suspended_users">-</strong></article>
                        <article class="admin-card"><span>Banidos</span><strong data-stat="banned_users">-</strong></article>
                        <article class="admin-card"><span>Pagamentos</span><strong data-stat="payments">-</strong></article>
                        <article class="admin-card"><span>Sessoes admin</span><strong data-stat="active_admin_sessions">-</strong></article>
                    </div>
                    <article class="admin-panel">
                        <h2>Central administrativa</h2>
                        <p>Use a rota Usuarios para consultar contas, perfis, logs recentes e aplicar suspensoes ou banimentos com auditoria.</p>
                    </article>
                </section>
                <?php AdminUsersPanel::render(); ?>
                <?php AdminSubscriptionsPanel::render(); ?>
                <?php AdminUsageMetricsPanel::render(); ?>
                <?php AdminApiMetricsPanel::render(); ?>
                <?php AdminSupportPanel::render(); ?>
            </section>
        </main>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    const loginForm = document.getElementById('admin-login-form');
    loginForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = document.getElementById('admin-login-message');
        const button = loginForm.querySelector('button[type="submit"]');
        button.disabled = true;
        message.textContent = '';

        try {
            const payload = Object.fromEntries(new FormData(loginForm).entries());
            const response = await fetch('/api/admin/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || data.error || 'Nao foi possivel entrar.');
            }
            window.location.reload();
        } catch (error) {
            message.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });

    async function loadAdminDashboard() {
        const statsRoot = document.getElementById('admin-stats');
        if (!statsRoot) return;

        const response = await fetch('/api/admin/dashboard');
        const data = await response.json();
        if (!response.ok || !data.success) return;

        document.querySelector('[data-admin-name]')?.replaceChildren(document.createTextNode(data.admin.display_name + ' | ' + data.admin.email));
        Object.entries(data.stats || {}).forEach(([key, value]) => {
            const target = document.querySelector('[data-stat="' + key + '"]');
            if (target) target.textContent = value;
        });
    }

    function setAdminRoute(route) {
        route = route || 'overview';
        document.querySelectorAll('.admin-route-panel').forEach(panel => {
            panel.hidden = panel.dataset.adminRoute !== route;
        });
        document.querySelectorAll('[data-admin-nav]').forEach(link => {
            link.classList.toggle('active', link.dataset.adminNav === route);
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('route', link.dataset.adminNav);
            link.href = url.pathname + url.search;
        });
        if (route === 'users' && window.AdminUsersPanel) {
            window.AdminUsersPanel.load();
        }
        if (route === 'metrics' && window.AdminMetricsPanel) {
            window.AdminMetricsPanel.load();
        }
        if (route === 'api-metrics' && window.AdminApiMetricsPanel) {
            window.AdminApiMetricsPanel.load();
        }
        if (route === 'subscriptions' && window.AdminSubscriptionsPanel) {
            window.AdminSubscriptionsPanel.load();
        }
        if (route === 'suporte' && window.AdminSupportPanel && !window._spaInit) {
            window._spaInit = true;
            window.AdminSupportPanel.init();
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-admin-nav]');
        if (!link) return;
        event.preventDefault();
        const route = link.dataset.adminNav || 'overview';
        const url = new URL(window.location.href);
        url.searchParams.set('route', route);
        history.pushState({ route }, '', url.pathname + url.search);
        setAdminRoute(route);
    });

    window.addEventListener('popstate', function () {
        setAdminRoute(new URL(window.location.href).searchParams.get('route') || 'overview');
    });

    loadAdminDashboard();
    setAdminRoute(<?= json_encode($adminRoute) ?>);
</script>
</body>
</html>
