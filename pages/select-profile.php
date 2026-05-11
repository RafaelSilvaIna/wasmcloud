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
    <meta name="theme-color" content="#000000">
    <title>PipoCine — Quem está assistindo?</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>

    <style>
        /* ============================================================
           PROFILES — Design System Minimalista & Escuro
           ============================================================ */
        :root {
            --profile-bg-base: #000000;
            --profile-bg-modal: #0a0a0c;
            --profile-bg-input: #1c1c1e;
            --profile-text-pure: #ffffff;
            --profile-text-muted: #8e8e93;
            --profile-accent: #0a7aff;
            /* Azul estilo iOS */
            --profile-success: #34c759;
            --profile-error: #ff3b30;
            --profile-card-size: 130px;
        }

        body {
            background-color: var(--profile-bg-base);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            overflow-x: hidden;
        }

        /* ============================================================
           PÁGINA PRINCIPAL E GRELHA
           ============================================================ */
        .profiles-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        .main-title {
            color: var(--profile-text-pure);
            font-size: clamp(2rem, 5vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 50px;
            text-align: center;
        }

        #pipo-profiles-root {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .profiles-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            max-width: 800px;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .profile-item:hover {
            transform: scale(1.05);
        }

        .avatar-wrapper {
            width: var(--profile-card-size);
            height: var(--profile-card-size);
            border-radius: 50%;
            overflow: hidden;
            background-color: #222;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            color: var(--profile-text-pure);
            font-size: 1.1rem;
            font-weight: 500;
            text-align: center;
        }

        /* Botão Adicionar na Grelha (Círculo com Borda) */
        .add-profile-btn {
            justify-content: flex-start;
        }

        .add-icon-wrapper {
            width: var(--profile-card-size);
            height: var(--profile-card-size);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
            border: 2px solid var(--profile-accent);
            color: var(--profile-accent);
            transition: background-color 0.2s;
        }

        .add-profile-btn:hover .add-icon-wrapper {
            background-color: rgba(10, 122, 255, 0.1);
        }

        /* Botão Gerenciar Perfis */
        .manage-profiles-container {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .btn-manage {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--profile-text-muted);
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-manage:hover {
            border-color: var(--profile-text-pure);
            color: var(--profile-text-pure);
        }

        /* ============================================================
           MODAL DARK MINIMALISTA (Para Criação/Edição e Avatar)
           ============================================================ */
        .profile-modal,
        .avatar-modal {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.90);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
            padding: 20px;
        }

        .profile-modal.open,
        .avatar-modal.open {
            opacity: 1;
            visibility: visible;
        }

        .modal-content-dark {
            background: var(--profile-bg-modal);
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            padding: 30px 24px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Header */
        .modal-header-dark {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn-cancel-dark {
            background: transparent;
            border: none;
            color: var(--profile-text-pure);
            font-size: 1rem;
            cursor: pointer;
            padding: 0;
            opacity: 0.8;
        }

        .btn-cancel-dark:hover {
            opacity: 1;
        }

        .title-dark {
            color: var(--profile-text-pure);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        /* Avatar Selection */
        .avatar-section-dark {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            cursor: pointer;
        }

        .avatar-circle-dark {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--profile-bg-input);
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }

        .avatar-circle-dark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-section-dark:hover .avatar-circle-dark {
            border-color: rgba(255, 255, 255, 0.3);
        }

        .avatar-hint-dark {
            color: var(--profile-text-muted);
            font-size: 0.9rem;
        }

        /* Inputs */
        .inputs-container-dark {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 30px;
        }

        .input-group-dark {
            position: relative;
            width: 100%;
        }

        .input-dark {
            width: 100%;
            background-color: var(--profile-bg-input);
            border: 1px solid transparent;
            padding: 16px;
            border-radius: 12px;
            color: var(--profile-text-pure);
            font-size: 1rem;
            box-sizing: border-box;
        }

        .input-dark::placeholder {
            color: var(--profile-text-muted);
        }

        .input-dark:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Validação do Username */
        .username-status-dark {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .username-status-dark.available {
            color: var(--profile-success);
        }

        .username-status-dark.taken {
            color: var(--profile-error);
        }

        .username-status-dark.loading {
            color: var(--profile-text-muted);
        }

        /* Toggle Row */
        .toggle-row-dark {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--profile-bg-input);
            padding: 16px;
            border-radius: 12px;
        }

        .toggle-texts-dark {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .toggle-title-dark {
            color: var(--profile-text-pure);
            font-size: 1rem;
            font-weight: 500;
        }

        .toggle-desc-dark {
            color: var(--profile-text-muted);
            font-size: 0.8rem;
        }

        /* Switch Toggle Elemento */
        .switch-dark {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 30px;
            flex-shrink: 0;
        }

        .switch-dark input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider-dark {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #39393d;
            border-radius: 30px;
            transition: .3s;
        }

        .slider-dark:before {
            content: "";
            position: absolute;
            height: 26px;
            width: 26px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            border-radius: 50%;
            transition: .3s;
        }

        .switch-dark input:checked+.slider-dark {
            background-color: var(--profile-accent);
        }

        .switch-dark input:checked+.slider-dark:before {
            transform: translateX(20px);
        }

        /* Botão Salvar (Azul) */
        .btn-save-dark {
            width: 100%;
            background-color: var(--profile-accent);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-save-dark:hover {
            opacity: 0.9;
        }

        .btn-save-dark:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Grid de Avatares (Modal) */
        .avatar-categories {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 20px;
            scrollbar-width: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .avatar-categories::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            background: transparent;
            border: none;
            color: var(--profile-text-muted);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            white-space: nowrap;
            transition: color 0.2s;
        }

        .category-btn.active {
            color: var(--profile-text-pure);
            font-weight: 600;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
            gap: 16px;
            max-height: 400px;
            overflow-y: auto;
            padding: 4px;
        }

        .avatar-option {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
            object-fit: cover;
        }

        .avatar-option:hover {
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* ============================================================
           MENU DA CONTA
           ============================================================ */
        .account-header {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .account-settings-btn,
        .account-menu-toggle {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s;
        }

        .account-settings-btn:hover,
        .account-menu-toggle:hover,
        .account-menu-toggle[aria-expanded="true"] {
            border-color: rgba(255, 255, 255, 0.42);
            background: rgba(255, 255, 255, 0.08);
        }

        .account-settings-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .account-settings-btn i {
            width: 20px;
            height: 20px;
        }

        .account-menu-wrap {
            position: relative;
        }

        .account-menu-toggle {
            min-height: 44px;
            max-width: min(360px, calc(100vw - 96px));
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px 5px 6px;
        }

        .account-user-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(255, 255, 255, 0.10);
            color: #fff;
            overflow: hidden;
        }

        .account-user-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .account-owner-name {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1;
        }

        .account-chevron {
            width: 16px;
            height: 16px;
            color: rgba(255, 255, 255, 0.68);
            transition: transform 0.2s;
        }

        .account-menu-toggle[aria-expanded="true"] .account-chevron {
            transform: rotate(180deg);
        }

        .account-menu-content {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: min(292px, calc(100vw - 32px));
            max-height: calc(100vh - 92px);
            overflow-y: auto;
            padding: 8px;
            background: rgba(10, 10, 12, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            box-shadow: 0 18px 54px rgba(0, 0, 0, 0.62);
            backdrop-filter: blur(18px);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
            box-sizing: border-box;
        }

        .account-menu-content.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .account-menu-heading {
            padding: 10px 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .account-menu-kicker {
            color: var(--profile-text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .account-menu-name {
            color: var(--profile-text-pure);
            font-size: 1rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .account-menu-section {
            margin-top: 2px;
            padding-top: 7px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .account-menu-label {
            padding: 4px 10px 7px;
            color: var(--profile-text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .account-menu-item {
            width: 100%;
            min-height: 40px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--profile-text-pure);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            text-decoration: none;
            font: inherit;
            font-size: 0.94rem;
            cursor: pointer;
            box-sizing: border-box;
            transition: background-color 0.16s;
        }

        .account-menu-item:hover,
        .account-menu-item:focus-visible {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(255, 255, 255, 0.06);
            outline: none;
        }

        .account-menu-item i,
        .account-menu-item svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            color: rgba(255, 255, 255, 0.72);
        }

        @media (max-width: 560px) {
            .account-header {
                top: 14px;
                right: 14px;
                gap: 8px;
            }

            .account-owner-name {
                display: none;
            }

            .account-menu-toggle {
                padding-right: 8px;
                max-width: none;
            }

            .account-menu-content {
                position: fixed;
                top: 66px;
                right: 14px;
                width: min(292px, calc(100vw - 28px));
                max-height: calc(100vh - 86px);
                border-radius: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- Header com ícone de configurações -->
    <?php
    $ownerName = trim($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Dono da conta');
    $ownerAvatar = trim($_SESSION['profile_pic_url'] ?? '');
    ?>

    <div class="account-header" aria-label="Menu da conta">
        <button id="btn-settings" class="account-settings-btn" type="button" aria-label="Abrir configurações">
            <i data-lucide="settings" aria-hidden="true"></i>
        </button>

        <div class="account-menu-wrap">
            <button id="account-menu-toggle" class="account-menu-toggle" type="button" aria-haspopup="true"
                aria-expanded="false" aria-controls="account-menu-content">
                <span class="account-user-icon" aria-hidden="true">
                    <?php if ($ownerAvatar !== ''): ?>
                        <img src="<?= htmlspecialchars($ownerAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <i data-lucide="user-round"></i>
                    <?php endif; ?>
                </span>
                <span class="account-owner-name"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></span>
                <i data-lucide="chevron-down" class="account-chevron" aria-hidden="true"></i>
            </button>

            <nav id="account-menu-content" class="account-menu-content" aria-label="Caminhos da conta">
                <div class="account-menu-heading">
                    <span class="account-menu-kicker">Dono da conta</span>
                    <span class="account-menu-name"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="account-menu-section" aria-label="Principal">
                    <div class="account-menu-label">Configuração</div>
                    <button type="button" class="account-menu-item" id="btn-settings-menu">
                        <i data-lucide="settings" aria-hidden="true"></i>
                        <span>Configurações</span>
                    </button>
                </div>

                <div class="account-menu-section" aria-label="Conta">
                    <div class="account-menu-label">Conta</div>
                    <a class="account-menu-item" href="/plan/me">
                        <i data-lucide="credit-card" aria-hidden="true"></i>
                        <span>Minha Assinatura</span>
                    </a>
                    <a class="account-menu-item" href="/settings?section=support">
                        <i data-lucide="life-buoy" aria-hidden="true"></i>
                        <span>Suporte</span>
                    </a>
                    <a class="account-menu-item" href="/settings?section=data">
                        <i data-lucide="database" aria-hidden="true"></i>
                        <span>Dados</span>
                    </a>
                    <a class="account-menu-item" href="/settings?section=analytics">
                        <i data-lucide="chart-no-axes-combined" aria-hidden="true"></i>
                        <span>Analytics</span>
                    </a>
                </div>
            </nav>
        </div>
    </div>

    <div class="profiles-wrapper">
        <h1 class="main-title">Quem está assistindo?</h1>

        <div id="pipo-profiles-root">
            <div class="profiles-grid" id="profiles-grid">

                <div class="profile-item">
                    <div class="avatar-wrapper">
                        <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo" alt="Pipo" class="avatar-img">
                    </div>
                    <span class="profile-name">Pipo</span>
                </div>

                <div class="profile-item">
                    <div class="avatar-wrapper">
                        <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Ana" alt="Ana" class="avatar-img">
                    </div>
                    <span class="profile-name">Ana</span>
                </div>

                <div class="profile-item add-profile-btn"
                    onclick="document.getElementById('pipo-profile-modal').classList.add('open')">
                    <div class="add-icon-wrapper">
                        <i data-lucide="plus" style="width: 48px; height: 48px;"></i>
                    </div>
                    <span class="profile-name">Adicionar</span>
                </div>

            </div>
        </div>

        <div class="manage-profiles-container">
            <button class="btn-manage" id="btn-manage-profiles"
                onclick="window.location.href='/manage-profiles'">Gerenciar Perfis</button>
        </div>
    </div>

    <div class="profile-modal" id="pipo-profile-modal" role="dialog" aria-modal="true">
        <div class="modal-content-dark">

            <div class="modal-header-dark">
                <button type="button" class="btn-cancel-dark pipo-modal-cancel"
                    onclick="document.getElementById('pipo-profile-modal').classList.remove('open')">Cancelar</button>
                <h2 class="title-dark">Criar Perfil</h2>
                <div style="width: 65px;"></div>
            </div>

            <form id="profile-form" class="form-dark" novalidate>

                <div class="avatar-section-dark" id="avatar-picker-trigger" tabindex="0"
                    onclick="document.getElementById('pipo-avatar-modal').classList.add('open')">
                    <div class="avatar-circle-dark">
                        <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo" alt="Avatar atual"
                            id="current-avatar-img">
                    </div>
                    <input type="hidden" id="selected-avatar-url" name="image"
                        value="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo">
                    <span class="avatar-hint-dark">Toque para escolher um avatar</span>
                </div>

                <div class="inputs-container-dark">
                    <div class="input-group-dark">
                        <input type="text" name="name" id="pro_name" class="input-dark" placeholder="Nome do perfil"
                            required autocomplete="off">
                    </div>

                    <div class="input-group-dark">
                        <input type="text" name="username" id="username" class="input-dark"
                            placeholder="Nome de usuário (ex: joao_123)" required autocomplete="off" maxlength="30"
                            pattern="[a-zA-Z0-9_]+">
                        <span class="username-status-dark" id="username-status"></span>
                    </div>

                    <div class="toggle-row-dark">
                        <div class="toggle-texts-dark">
                            <span class="toggle-title-dark">Perfil Infantil</span>
                            <span class="toggle-desc-dark">Restringe conteúdo apenas para crianças</span>
                        </div>
                        <label class="switch-dark">
                            <input type="checkbox" id="kids-toggle">
                            <span class="slider-dark"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save-dark" id="btn-save-profile">Salvar</button>
            </form>
        </div>
    </div>

    <div class="avatar-modal" id="pipo-avatar-modal" role="dialog" aria-modal="true">
        <div class="modal-content-dark">
            <div class="modal-header-dark" style="margin-bottom: 20px;">
                <button type="button" class="btn-cancel-dark modal-close"
                    onclick="document.getElementById('pipo-avatar-modal').classList.remove('open')">Voltar</button>
                <h2 class="title-dark">Avatar</h2>
                <div style="width: 50px;"></div>
            </div>

            <div class="avatar-categories" id="avatar-categories" role="tablist">
                <button class="category-btn active" data-category="adventurer">Aventureiro</button>
                <button class="category-btn" data-category="open-peeps">Pessoas</button>
                <button class="category-btn" data-category="bottts">Robôs</button>
                <button class="category-btn" data-category="pixel-art">Pixel</button>
            </div>

            <div class="avatar-grid" id="avatar-grid" role="listbox">
                <img class="avatar-option" src="https://api.dicebear.com/7.x/adventurer/svg?seed=1" alt="Avatar">
                <img class="avatar-option" src="https://api.dicebear.com/7.x/adventurer/svg?seed=2" alt="Avatar">
                <img class="avatar-option" src="https://api.dicebear.com/7.x/adventurer/svg?seed=3" alt="Avatar">
                <img class="avatar-option" src="https://api.dicebear.com/7.x/adventurer/svg?seed=4" alt="Avatar">
            </div>
        </div>
    </div>

    <script src="/assets/js/notification.js"></script>
    <script src="/assets/js/profiles.js"></script>
    <script>
        // Inicializar os ícones do Lucide
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>

    <?php
    // Componente de aviso de PIN de segurança
    require_once __DIR__ . '/../components/PinWarning.php';
    PinWarning::render();
    
    // Modal de validação de PIN para acessar configurações
    require_once __DIR__ . '/../components/PinValidateModal.php';
    PinValidateModal::render();
    ?>

    <script>
        // Evento do botão de configurações
        const accountMenuToggle = document.getElementById('account-menu-toggle');
        const accountMenuContent = document.getElementById('account-menu-content');

        function setAccountMenu(open) {
            if (!accountMenuToggle || !accountMenuContent) return;
            accountMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            accountMenuContent.classList.toggle('open', open);
        }

        accountMenuToggle?.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = accountMenuToggle.getAttribute('aria-expanded') === 'true';
            setAccountMenu(!isOpen);
        });

        accountMenuContent?.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function() {
            setAccountMenu(false);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') setAccountMenu(false);
        });

        function openSettingsPinModal() {
            setAccountMenu(false);
            if (window.PinValidateModal) {
                window.PinValidateModal.show();
            }
        }

        document.getElementById('btn-settings')?.addEventListener('click', openSettingsPinModal);
        document.getElementById('btn-settings-menu')?.addEventListener('click', openSettingsPinModal);
        
        // Verifica se veio de redirecionamento por falta de PIN
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('needs_pin') === '1') {
            // Remove o parâmetro da URL
            window.history.replaceState({}, document.title, window.location.pathname);
            // Abre o modal automaticamente
            setTimeout(() => {
                if (window.PinValidateModal) {
                    window.PinValidateModal.show();
                }
            }, 500);
        }
    </script>

</body>

</html>
