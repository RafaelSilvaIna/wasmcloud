<?php
/**
 * PAGE: Settings
 * Painel de configurações da conta CineVEO / PipoCine.
 * URL: /settings
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../components/SettingsSidebar.php';
require_once __DIR__ . '/../components/SecurityLoginCode.php';
require_once __DIR__ . '/../components/SecurityQrCode.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$activeTab = htmlspecialchars($_GET['tab'] ?? 'minha-conta', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0c10">
    <title>Configurações — PipoCine</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <!-- style.css: tokens globais PipoCine (bg-base, text-primary, etc.) -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- settings.css: layout do painel, sidebar, cards e responsivo -->
    <link rel="stylesheet" href="/assets/css/settings.css">
</head>

<body class="settings-body">
<div class="settings-layout">

    <!-- SIDEBAR -->
    <?php SettingsSidebar::render($activeTab); ?>

    <!-- CONTEÚDO -->
    <main class="settings-main" id="settings-main">

        <!-- Topbar mobile -->
        <header class="settings-topbar" role="banner">
            <button
                type="button"
                class="topbar-menu-btn"
                id="topbar-menu-btn"
                aria-label="Abrir menu lateral"
                aria-controls="settings-sidebar"
                aria-expanded="false"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <line x1="4" y1="6"  x2="20" y2="6"/>
                    <line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
            </button>
            <span class="topbar-title" id="topbar-title">Minha Conta CineVEO</span>
        </header>

        <div class="settings-content">

            <!-- ── PAINEL: Minha Conta CineVEO ── -->
            <section class="settings-panel" id="panel-minha-conta" data-panel="minha-conta" hidden
                     aria-labelledby="title-minha-conta">

                <div class="settings-section-header">
                    <h1 class="settings-section-title" id="title-minha-conta">Minha Conta CineVEO</h1>
                    <p class="settings-section-desc">Informações da sua conta e plano de assinatura.</p>
                </div>

                <!-- Hero: avatar + nome + plano -->
                <div class="account-hero" id="account-hero">
                    <div class="settings-loader" style="padding:0;flex-direction:row;gap:10px;">
                        <div class="settings-spinner"></div>
                        <span>Carregando...</span>
                    </div>
                </div>

                <!-- Detalhes tabulares -->
                <div class="account-details" id="account-details" style="display:none">
                    <div class="detail-row">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <span class="detail-label">Nome</span>
                        <span class="detail-value" id="detail-name">—</span>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <span class="detail-label">Username</span>
                        <span class="detail-value" id="detail-username">—</span>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                        </div>
                        <span class="detail-label">E-mail</span>
                        <span class="detail-value" id="detail-email">—</span>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <span class="detail-label">Plano</span>
                        <span class="detail-value" id="detail-plan">—</span>
                    </div>
                </div>

                <!-- Perfis PipoCine -->
                <div id="profiles-section" style="display:none">
                    <div class="profiles-section-header">
                        <div>
                            <p class="profiles-section-title">Perfis PipoCine</p>
                            <p class="profiles-section-desc">Perfis vinculados à sua conta CineVEO.</p>
                        </div>
                    </div>
                    <div class="profiles-grid-settings" id="profiles-grid-settings"></div>
                </div>

            </section>

            <!-- ── PAINEL: Gerenciar Perfis ── -->
            <section class="settings-panel" id="panel-perfis" data-panel="perfis" hidden
                     aria-labelledby="title-perfis">
                <div class="settings-section-header">
                    <h1 class="settings-section-title" id="title-perfis">Gerenciar Perfis</h1>
                    <p class="settings-section-desc">Crie, edite ou remova perfis PipoCine vinculados à sua conta.</p>
                </div>
                <div class="settings-section">
                    <a href="/manage-profiles" class="settings-cta-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Ir para Gerenciar Perfis
                    </a>
                </div>
            </section>

            <!-- ── PAINEL: Meu Plano ── -->
            <section class="settings-panel" id="panel-plano" data-panel="plano" hidden
                     aria-labelledby="title-plano">
                <div class="settings-section-header">
                    <h1 class="settings-section-title" id="title-plano">Meu Plano</h1>
                    <p class="settings-section-desc">Veja os detalhes do seu plano de assinatura CineVEO.</p>
                </div>
                <div class="settings-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <p>Em breve — detalhes do plano serão exibidos aqui.</p>
                </div>
            </section>

            <!-- ── PAINEL: Segurança ── -->
            <section class="settings-panel" id="panel-seguranca" data-panel="seguranca" hidden
                     aria-labelledby="title-seguranca">
                <div class="settings-section-header">
                    <h1 class="settings-section-title" id="title-seguranca">Segurança</h1>
                    <p class="settings-section-desc">Gerencie os métodos alternativos de autenticação da sua conta.</p>
                </div>

                <!-- Componente: Código de Acesso (4 dígitos) -->
                <?php SecurityLoginCode::render(); ?>

                <!-- Componente: Login via QR Code -->
                <?php SecurityQrCode::render(); ?>

            </section>

            <!-- ── PAINEL: Notificações ── -->
            <section class="settings-panel" id="panel-notificacoes" data-panel="notificacoes" hidden
                     aria-labelledby="title-notificacoes">
                <div class="settings-section-header">
                    <h1 class="settings-section-title" id="title-notificacoes">Notificações</h1>
                    <p class="settings-section-desc">Controle quais notificações você deseja receber.</p>
                </div>
                <div class="settings-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <p>Em breve — preferências de notificação serão adicionadas aqui.</p>
                </div>
            </section>

        </div><!-- /.settings-content -->
    </main>

</div><!-- /.settings-layout -->

<script src="/assets/js/settings.js"></script>
</body>
</html>
