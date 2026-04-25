<?php
require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0c10">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>PipoCine — Quem está assistindo?</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <link rel="stylesheet" href="/assets/css/profiles.css">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     PAGE SHELL
     ═══════════════════════════════════════════════════════════ -->
<div class="profiles-wrapper">

    <!-- Logo -->
    <div class="logo-container">
        <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
    </div>

    <h1 class="main-title">Quem está assistindo?</h1>

    <!-- Profiles Grid -->
    <div id="pipo-profiles-root">
        <div class="profiles-grid" id="profiles-grid">
            <div class="loader-pipo" style="grid-column: 1 / -1; margin: 50px auto;"></div>
        </div>
    </div>

</div><!-- /.profiles-wrapper -->


<!-- ═══════════════════════════════════════════════════════════
     MODAL — Criar / Editar Perfil
     ═══════════════════════════════════════════════════════════ -->
<div class="profile-modal" id="pipo-profile-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-content modal-content--wide">

        <!-- Coluna esquerda: avatar -->
        <div class="modal-left">
            <div class="modal-avatar-section">
                <div class="avatar-picker-trigger" id="avatar-picker-trigger" role="button" tabindex="0"
                     aria-label="Trocar avatar">
                    <div class="avatar-wrapper">
                        <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo"
                             alt="Avatar atual"
                             class="modal-avatar-img"
                             id="current-avatar-img">
                    </div>
                    <input type="hidden" id="selected-avatar-url" name="image"
                           value="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo">
                    <div class="edit-icon" aria-hidden="true">
                        <i data-lucide="pencil" width="13" height="13"></i>
                    </div>
                </div>
                <p class="avatar-hint">Clique para trocar<br>seu avatar</p>
            </div>
        </div>

        <!-- Coluna direita: formulário -->
        <div class="modal-right">
            <div class="modal-header">
                <h2 id="modal-title">Criar Novo Perfil</h2>
                <button class="modal-close" aria-label="Fechar modal">
                    <i data-lucide="x" width="16" height="16"></i>
                </button>
            </div>

            <form class="modal-form" id="profile-form" novalidate>

                <!-- Nome do Perfil -->
                <div class="input-group">
                    <label for="pro_name">Nome do Perfil</label>
                    <div class="input-with-icon">
                        <i data-lucide="user" width="15" height="15" class="input-icon"></i>
                        <input type="text" name="name" id="pro_name"
                               placeholder="Ex: Maria"
                               required autocomplete="off">
                    </div>
                </div>

                <!-- Nome de Usuário -->
                <div class="input-group username-check-wrapper">
                    <label for="username">Nome de Usuário</label>
                    <div class="input-with-icon">
                        <i data-lucide="at-sign" width="15" height="15" class="input-icon"></i>
                        <input type="text" name="username" id="username"
                               placeholder="maria_cine"
                               required autocomplete="off"
                               maxlength="30"
                               pattern="[a-zA-Z0-9_]+">
                    </div>
                    <span class="username-status" id="username-status" aria-live="polite"></span>
                    <span class="username-hint">Somente letras, números e _</span>
                </div>

                <!-- Tipo de Conta -->
                <div class="input-group">
                    <label>Tipo de Conta</label>
                    <input type="hidden" name="type" id="pro_type" value="standard">
                    <div class="account-type-picker">
                        <button type="button"
                                class="account-type-btn active"
                                data-type="standard"
                                id="btn-type-standard"
                                aria-pressed="true">
                            <span class="type-icon-wrap">
                                <i data-lucide="clapperboard" width="20" height="20"></i>
                            </span>
                            <span class="type-name">Padrão</span>
                            <span class="type-desc">Livre</span>
                        </button>
                        <button type="button"
                                class="account-type-btn"
                                data-type="kids"
                                id="btn-type-kids"
                                aria-pressed="false">
                            <span class="type-icon-wrap type-icon-wrap--kids">
                                <i data-lucide="baby" width="20" height="20"></i>
                            </span>
                            <span class="type-name">Kids</span>
                            <span class="type-desc">Restrita</span>
                        </button>
                        <button type="button"
                                class="account-type-info-btn"
                                id="btn-type-info"
                                aria-label="Saiba mais sobre os tipos de conta">
                            <i data-lucide="info" width="18" height="18"></i>
                        </button>
                    </div>
                </div>

                <!-- PIN Section -->
                <div class="pin-section">
                    <div class="pin-toggle-wrapper">
                        <label for="pin-toggle" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <i data-lucide="lock" width="15" height="15" style="color:var(--profile-text-muted);"></i>
                            Proteger com PIN
                        </label>
                        <div class="pipo-switch">
                            <input type="checkbox" id="pin-toggle" name="lock_profile">
                            <span class="pipo-slider"></span>
                        </div>
                    </div>

                    <div class="pin-dots-wrapper" id="pin-input-box">
                        <p class="pin-dots-label">
                            <i data-lucide="keyboard" width="14" height="14"></i>
                            Digite um PIN de 4 dígitos
                        </p>
                        <div class="pin-dots" id="pin-dots" role="group" aria-label="Indicador de PIN">
                            <span class="pin-dot" data-index="0"></span>
                            <span class="pin-dot" data-index="1"></span>
                            <span class="pin-dot" data-index="2"></span>
                            <span class="pin-dot" data-index="3"></span>
                        </div>
                        <input type="hidden" name="pin" id="pin_input" maxlength="4">
                        <div class="pin-numpad" id="pin-numpad" role="group" aria-label="Teclado numérico">
                            <?php for ($d = 1; $d <= 9; $d++): ?>
                            <button type="button" class="pin-key" data-digit="<?= $d ?>"
                                    aria-label="Dígito <?= $d ?>"><?= $d ?></button>
                            <?php endfor; ?>
                            <button type="button" class="pin-key pin-key--empty" tabindex="-1" aria-hidden="true"></button>
                            <button type="button" class="pin-key" data-digit="0" aria-label="Dígito 0">0</button>
                            <button type="button" class="pin-key pin-key--del" id="pin-del" aria-label="Apagar">
                                <i data-lucide="delete" width="18" height="18"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="modal-actions">
                    <button type="submit" class="btn-primary btn-save" id="btn-save-profile">
                        <i data-lucide="check" width="16" height="16"></i>
                        Salvar Perfil
                    </button>
                    <button type="button" class="btn-ghost pipo-modal-cancel">
                        <i data-lucide="x" width="16" height="16"></i>
                        Cancelar
                    </button>
                </div>

            </form>
        </div><!-- /.modal-right -->

    </div><!-- /.modal-content--wide -->
</div><!-- /#pipo-profile-modal -->


<!-- ═══════════════════════════════════════════════════════════
     MODAL — PIN de Acesso
     ═══════════════════════════════════════════════════════════ -->
<div class="profile-modal" id="pipo-pin-modal" role="dialog" aria-modal="true" aria-labelledby="pin-modal-title">
    <div class="modal-content modal-content--pin">

        <div class="pin-access-header">
            <div class="pin-access-lock" aria-hidden="true">
                <i data-lucide="lock-keyhole" width="40" height="40"></i>
            </div>
            <h2 id="pin-modal-title">Perfil Bloqueado</h2>
            <p>Digite o PIN de 4 dígitos para acessar.</p>
        </div>

        <div class="pin-dots" id="access-pin-dots" role="group" aria-label="Indicador de PIN">
            <span class="pin-dot" data-index="0"></span>
            <span class="pin-dot" data-index="1"></span>
            <span class="pin-dot" data-index="2"></span>
            <span class="pin-dot" data-index="3"></span>
        </div>
        <input type="hidden" id="access-pin-input">

        <div class="pin-numpad" role="group" aria-label="Teclado numérico">
            <?php for ($d = 1; $d <= 9; $d++): ?>
            <button type="button" class="pin-key" data-digit="<?= $d ?>"
                    aria-label="Dígito <?= $d ?>"><?= $d ?></button>
            <?php endfor; ?>
            <button type="button" class="pin-key pin-key--empty" tabindex="-1" aria-hidden="true"></button>
            <button type="button" class="pin-key" data-digit="0" aria-label="Dígito 0">0</button>
            <button type="button" class="pin-key pin-key--del" id="access-pin-del" aria-label="Apagar">
                <i data-lucide="delete" width="18" height="18"></i>
            </button>
        </div>

        <button type="button" class="btn-ghost pipo-modal-cancel" id="btn-cancel-pin">
            <i data-lucide="arrow-left" width="15" height="15"></i>
            Voltar
        </button>

    </div>
</div><!-- /#pipo-pin-modal -->


<!-- ═══════════════════════════════════════════════════════════
     MODAL — Tipos de Conta
     ═══════════════════════════════════════════════════════════ -->
<div class="profile-modal" id="pipo-account-type-modal" role="dialog" aria-modal="true" aria-labelledby="account-type-title">
    <div class="modal-content modal-content--account-type">

        <div class="modal-header">
            <h2 id="account-type-title">
                <i data-lucide="shield-check" width="20" height="20"></i>
                Tipos de Conta
            </h2>
            <button class="modal-close" id="close-account-type-modal" aria-label="Fechar modal">
                <i data-lucide="x" width="16" height="16"></i>
            </button>
        </div>

        <div class="account-type-cards">

            <!-- Card Padrão -->
            <div class="account-type-card" data-type="standard">
                <div class="atc-icon-wrap">
                    <i data-lucide="clapperboard" width="28" height="28"></i>
                </div>
                <h3>Conta Padrão</h3>
                <span class="atc-badge atc-badge--free">
                    <i data-lucide="unlock" width="11" height="11"></i>
                    Livre
                </span>
                <ul class="atc-features">
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Acesso a todos os filmes e séries
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Conteúdo adulto e infantil
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Todas as categorias disponíveis
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Sem restrições de conteúdo
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Histórico completo de visualização
                    </li>
                </ul>
                <button type="button" class="btn-primary atc-select-btn" data-select-type="standard">
                    <i data-lucide="check" width="15" height="15"></i>
                    Selecionar Padrão
                </button>
            </div>

            <!-- Card Kids -->
            <div class="account-type-card" data-type="kids">
                <div class="atc-icon-wrap atc-icon-wrap--kids">
                    <i data-lucide="baby" width="28" height="28"></i>
                </div>
                <h3>Conta Kids</h3>
                <span class="atc-badge atc-badge--kids">
                    <i data-lucide="shield" width="11" height="11"></i>
                    Restrita
                </span>
                <ul class="atc-features">
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Conteúdo exclusivo infantil
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Filmes e séries para crianças
                    </li>
                    <li>
                        <i data-lucide="lock" width="15" height="15" class="atc-lock"></i>
                        Conteúdo adulto bloqueado
                    </li>
                    <li>
                        <i data-lucide="lock" width="15" height="15" class="atc-lock"></i>
                        Ações limitadas no app
                    </li>
                    <li>
                        <i data-lucide="check-circle" width="15" height="15" class="atc-check"></i>
                        Ambiente seguro e monitorado
                    </li>
                </ul>
                <button type="button" class="btn-primary atc-select-btn atc-select-btn--kids" data-select-type="kids">
                    <i data-lucide="check" width="15" height="15"></i>
                    Selecionar Kids
                </button>
            </div>

        </div><!-- /.account-type-cards -->

    </div>
</div><!-- /#pipo-account-type-modal -->


<!-- ═══════════════════════════════════════════════════════════
     MODAL — Avatares
     ═══════════════════════════════════════════════════════════ -->
<div class="avatar-modal" id="pipo-avatar-modal" role="dialog" aria-modal="true" aria-labelledby="avatar-modal-title">
    <div class="modal-content modal-content--avatars">

        <div class="modal-header">
            <h2 id="avatar-modal-title">
                <i data-lucide="image" width="20" height="20"></i>
                Escolha seu Avatar
            </h2>
            <button class="modal-close" aria-label="Fechar">
                <i data-lucide="x" width="16" height="16"></i>
            </button>
        </div>

        <div class="avatar-categories" id="avatar-categories" role="tablist">
            <button class="category-btn active" data-category="adventurer" role="tab" aria-selected="true">
                <i data-lucide="compass" width="13" height="13"></i>
                Aventureiro
            </button>
            <button class="category-btn" data-category="open-peeps" role="tab" aria-selected="false">
                <i data-lucide="users" width="13" height="13"></i>
                Pessoas
            </button>
            <button class="category-btn" data-category="bottts" role="tab" aria-selected="false">
                <i data-lucide="bot" width="13" height="13"></i>
                Robôs
            </button>
            <button class="category-btn" data-category="pixel-art" role="tab" aria-selected="false">
                <i data-lucide="grid-2x2" width="13" height="13"></i>
                Pixel
            </button>
            <button class="category-btn" data-category="notionists" role="tab" aria-selected="false">
                <i data-lucide="sparkles" width="13" height="13"></i>
                Notion
            </button>
        </div>

        <div class="avatar-grid" id="avatar-grid" role="listbox" aria-label="Avatares disponíveis"></div>

    </div>
</div><!-- /#pipo-avatar-modal -->


<!-- ═══════════════════════════════════════════════════════════
     ACTION MENU — Bottom sheet (mobile long-press)
     ═══════════════════════════════════════════════════════════ -->
<div class="profile-action-menu" id="pipo-profile-action-menu" role="menu">

    <div class="menu-header">
        <img src="" alt="Avatar do perfil" class="menu-avatar" id="menu-avatar-img">
        <div class="menu-info">
            <h3 id="menu-profile-name">Perfil</h3>
            <p id="menu-username">@username</p>
        </div>
    </div>

    <div class="menu-options">
        <div class="menu-item" id="trigger-edit-profile" role="menuitem" tabindex="0">
            <i data-lucide="pencil" width="18" height="18"></i>
            Editar Perfil
        </div>
        <div class="menu-item delete" id="trigger-delete-profile" role="menuitem" tabindex="0">
            <i data-lucide="trash-2" width="18" height="18"></i>
            Excluir Perfil
        </div>
    </div>

</div><!-- /#pipo-profile-action-menu -->

<div class="press-overlay" id="pipo-press-overlay"></div>


<!-- ═══════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════ -->
<script src="/assets/js/notification.js"></script>
<script src="/assets/js/profiles.js"></script>

<!-- Inicializa os ícones Lucide após o DOM carregar -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // Re-renderiza ícones quando novos elementos são injetados via JS (ex: profiles grid)
    window.pipoCineRenderIcons = function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };
</script>

</body>
</html>
