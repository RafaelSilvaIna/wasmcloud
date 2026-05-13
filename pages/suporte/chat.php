<?php
declare(strict_types=1);

$isAuthenticated = isset($_SESSION['user_id']);
$userDisplayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? null;
$userAvatar      = $_SESSION['profile_pic_url'] ?? null;
$chatId          = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$urlToken        = $_GET['token'] ?? null; // guest fallback token from URL
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Chamado #<?= $chatId ?> — Suporte Pipocine</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/support.css">
</head>
<body class="sp-page sp-chat-page"
    data-chat-id="<?= $chatId ?>"
    data-auth="<?= $isAuthenticated ? '1' : '0' ?>"
    data-url-token="<?= htmlspecialchars($urlToken ?? '', ENT_QUOTES) ?>"
    <?php if ($isAuthenticated): ?>data-user-id="<?= (int) $_SESSION['user_id'] ?>"<?php endif; ?>
>

<!-- ====== NAVBAR ====== -->
<nav class="sp-nav" role="navigation" aria-label="Navegacao principal">
    <a href="/suporte" class="sp-nav-brand" aria-label="Voltar ao suporte">
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="sp-nav-logo">
        <span class="sp-nav-brand-name">Pipocine</span>
        <span class="sp-nav-breadcrumb">
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                 fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            Suporte
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                 fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            <span id="sp-nav-chat-label">Chamado #<?= $chatId ?></span>
        </span>
    </a>

    <div class="sp-nav-end">
        <button type="button" class="sp-user-btn" id="sp-user-btn" aria-label="Menu do usuario" aria-haspopup="true" aria-expanded="false">
            <?php if ($userAvatar): ?>
                <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES) ?>" alt="" class="sp-user-avatar-img">
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            <?php endif; ?>
        </button>

        <div class="sp-user-menu" id="sp-user-menu" role="menu" aria-hidden="true">
            <?php if ($isAuthenticated && $userDisplayName): ?>
            <div class="sp-user-menu-name"><?= htmlspecialchars($userDisplayName, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($isAuthenticated): ?>
            <a href="/" class="sp-user-menu-item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Voltar a home
            </a>
            <?php else: ?>
            <a href="/login" class="sp-user-menu-item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Voltar ao login
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ====== CHAT LAYOUT ====== -->
<main class="sp-chat-main" id="sp-chat-main">

    <!-- Access denied / loading state — shown until JS resolves token -->
    <div id="sp-chat-loading" class="sp-chat-loading">
        <div class="sp-spinner" aria-hidden="true"></div>
        <p>Carregando chamado...</p>
    </div>

    <!-- Chat UI (hidden until token confirmed) -->
    <div id="sp-chat-ui" class="sp-chat-ui" style="display:none">

        <!-- Sidebar / info panel -->
        <aside class="sp-chat-sidebar" id="sp-chat-sidebar">
            <div class="sp-sidebar-inner">
                <div class="sp-sidebar-header">
                    <p class="sp-sidebar-label">Chamado</p>
                    <p class="sp-sidebar-id">#<?= $chatId ?></p>
                </div>
                <div class="sp-sidebar-status">
                    <span class="sp-status-dot" id="sp-status-dot-chat"></span>
                    <span id="sp-sidebar-status-text" class="sp-sidebar-status-text">Aberto</span>
                </div>
                <p class="sp-sidebar-subject" id="sp-sidebar-subject">Carregando...</p>
                <hr class="sp-sidebar-divider">
                <p class="sp-sidebar-label">Atendimento</p>
                <p class="sp-sidebar-hours">12:00 – 21:30 diariamente</p>
                <hr class="sp-sidebar-divider">
                <a href="/suporte?view=novo" class="sp-btn sp-btn--ghost sp-sidebar-new-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Novo chamado
                </a>
                <a href="/suporte" class="sp-btn sp-btn--ghost sp-sidebar-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Todos os chamados
                </a>
            </div>
        </aside>

        <!-- Main chat panel -->
        <div class="sp-chat-panel">

            <!-- Chat header -->
            <div class="sp-chat-panel-header">
                <!-- Mobile back -->
                <a href="/suporte" class="sp-mobile-back" aria-label="Voltar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </a>
                <div class="sp-chat-panel-header-info">
                    <p class="sp-chat-panel-title" id="sp-panel-title">Suporte Pipocine</p>
                    <p class="sp-chat-panel-sub">Atendimento &mdash; responderemos em breve</p>
                </div>
                <div class="sp-chat-panel-header-actions">
                    <span id="sp-status-label-chat" class="sp-chat-status-label">Aberto</span>
                </div>
            </div>

            <!-- Reply bar -->
            <div id="sp-reply-bar" class="sp-reply-bar" role="status" aria-live="polite">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="#e50914" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                </svg>
                <span id="sp-reply-text"></span>
                <button id="sp-reply-bar-close" type="button" class="sp-reply-bar-close" aria-label="Cancelar resposta">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Messages -->
            <div id="sp-messages" class="sp-messages" role="log" aria-live="polite" aria-relevant="additions">
                <div class="sp-empty" id="sp-msgs-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"
                         fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p>Aguardando a primeira mensagem...</p>
                </div>
                <!-- Typing -->
                <div id="sp-typing" class="sp-typing" aria-label="Atendente digitando">
                    <div class="sp-msg-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                        </svg>
                    </div>
                    <div class="sp-typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>

            <!-- Closed banner -->
            <div id="sp-chat-closed" class="sp-chat-closed" style="display:none">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span>Atendimento encerrado. Abra um novo chamado se precisar de mais ajuda.</span>
                <a href="/suporte?view=novo" class="sp-btn sp-btn--ghost" style="height:32px;padding:0 12px;font-size:.82rem">
                    Novo chamado
                </a>
            </div>

            <!-- Input -->
            <div id="sp-input-bar" class="sp-input-bar">
                <div id="sp-img-preview" class="sp-img-preview">
                    <img id="sp-img-preview-img" src="" alt="Preview">
                    <button id="sp-img-remove" type="button" class="sp-img-preview-rm" aria-label="Remover imagem">&times;</button>
                </div>
                <div class="sp-input-row">
                    <textarea
                        id="sp-textarea"
                        class="sp-textarea"
                        rows="1"
                        placeholder="Escreva sua mensagem..."
                        maxlength="2000"
                        aria-label="Mensagem de suporte"
                    ></textarea>
                    <div class="sp-input-actions">
                        <button id="sp-attach-btn" type="button" class="sp-icon-btn" aria-label="Anexar imagem">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </button>
                        <input id="sp-file-input" type="file" accept="image/*" style="display:none" aria-hidden="true">
                        <button id="sp-send-btn" type="button" class="sp-send-btn" aria-label="Enviar mensagem">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <p id="sp-error-msg" role="alert" class="sp-form-error" style="display:none"></p>
            </div>

        </div><!-- /.sp-chat-panel -->
    </div><!-- /#sp-chat-ui -->

    <!-- Access denied -->
    <div id="sp-chat-denied" class="sp-chat-denied" style="display:none">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
             fill="none" stroke="#e50914" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <p>Voce nao tem acesso a este chamado.</p>
        <a href="/suporte" class="sp-btn sp-btn--ghost">Voltar ao suporte</a>
    </div>

</main>

<!-- Lightbox -->
<div id="sp-lightbox" class="sp-lightbox" role="dialog" aria-modal="true" aria-label="Imagem ampliada">
    <button id="sp-lightbox-close" class="sp-lightbox-close" aria-label="Fechar">&times;</button>
    <img src="" alt="Imagem em tela cheia">
</div>

<script src="/assets/js/support-client.js" defer></script>
<script>
(function () {
    // User menu
    const userBtn  = document.getElementById('sp-user-btn');
    const userMenu = document.getElementById('sp-user-menu');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = userMenu.classList.toggle('open');
            userBtn.setAttribute('aria-expanded', String(open));
            userMenu.setAttribute('aria-hidden', String(!open));
        });
        document.addEventListener('click', function () {
            userMenu.classList.remove('open');
            userBtn.setAttribute('aria-expanded', 'false');
            userMenu.setAttribute('aria-hidden', 'true');
        });
        userMenu.addEventListener('click', e => e.stopPropagation());
    }
})();
</script>
</body>
</html>
