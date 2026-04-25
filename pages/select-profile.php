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
    <title>PipoCine - Quem está assistindo?</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <link rel="stylesheet" href="/assets/css/profiles.css">
</head>
<body>
    <div class="profiles-wrapper">
        <div class="logo-container">
            <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
        </div>
        
        <h1 class="main-title">Quem está assistindo?</h1>
        
        <div id="pipo-profiles-root">
            <div class="profiles-grid" id="profiles-grid">
                <div class="loader-pipo" style="grid-column: 1 / -1; margin: 50px auto;"></div>
            </div>
        </div>
    </div>

    <div class="profile-modal" id="pipo-profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Criar Novo Perfil</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form class="modal-form" id="profile-form">
                <div class="avatar-picker-trigger" id="avatar-picker-trigger">
                    <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo" alt="Avatar" class="avatar-wrapper" id="current-avatar-img">
                    <input type="hidden" id="selected-avatar-url" name="image" value="https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo">
                    <div class="edit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </div>
                </div>

                <div class="input-group">
                    <label>Nome do Perfil</label>
                    <input type="text" name="name" id="pro_name" placeholder="Ex: Maria" required autocomplete="off">
                </div>

                <div class="input-group username-check-wrapper">
                    <label>Nome de Usuário (@)</label>
                    <input type="text" name="username" id="username" placeholder="maria_cine" required autocomplete="off">
                    <span class="username-status" id="username-status"></span>
                </div>

                <div class="input-group">
                    <label>Tipo de Conta</label>
                    <select name="type" id="pro_type">
                        <option value="standard">Conta Padrão (Livre)</option>
                        <option value="kids">Conta Kids (Restrita)</option>
                    </select>
                </div>

                <div class="pin-toggle-wrapper">
                    <label>Proteger perfil com PIN?</label>
                    <div class="pipo-switch">
                        <input type="checkbox" id="pin-toggle" name="lock_profile">
                        <span class="pipo-slider"></span>
                    </div>
                </div>

                <div class="input-group pin-input-box" id="pin-input-box">
                    <label>PIN de Acesso (4 dígitos)</label>
                    <input type="password" name="pin" id="pin_input" maxlength="4" placeholder="••••" inputmode="numeric">
                </div>

                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary" style="flex: 1;" id="btn-save-profile">Salvar Perfil</button>
                    <button type="button" class="btn-primary pipo-modal-cancel" style="background-color: rgba(255,255,255,0.1); flex: 1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="profile-modal" id="pipo-pin-modal">
        <div class="modal-content" style="max-width: 350px; text-align: center;">
            <h2 style="margin-bottom: 10px; color: var(--text-pure);">Perfil Bloqueado</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 0.95rem;">Digite o PIN para acessar este perfil.</p>
            <input type="password" id="access-pin-input" maxlength="4" inputmode="numeric" style="width: 100%; text-align: center; font-size: 2rem; letter-spacing: 15px; padding: 15px; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-primary" id="btn-confirm-pin" style="flex: 1;">Entrar</button>
                <button type="button" class="btn-primary pipo-modal-cancel" id="btn-cancel-pin" style="background-color: rgba(255,255,255,0.1); flex: 1;">Voltar</button>
            </div>
        </div>
    </div>

    <div class="avatar-modal" id="pipo-avatar-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Escolha seu Avatar</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="avatar-categories" id="avatar-categories">
                <button class="category-btn active" data-category="adventurer">Aventureiro</button>
                <button class="category-btn" data-category="open-peeps">Pessoas</button>
                <button class="category-btn" data-category="bottts">Robôs</button>
                <button class="category-btn" data-category="pixel-art">Pixel</button>
                <button class="category-btn" data-category="notionists">Notion</button>
            </div>
            <div class="avatar-grid" id="avatar-grid"></div>
        </div>
    </div>

    <div class="profile-action-menu" id="pipo-profile-action-menu">
        <div class="menu-header">
            <img src="" alt="Avatar" class="menu-avatar" id="menu-avatar-img">
            <div class="menu-info">
                <h3 id="menu-profile-name">Perfil</h3>
                <p id="menu-username">@username</p>
            </div>
        </div>
        <div class="menu-options">
            <div class="menu-item" id="trigger-edit-profile">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Editar Perfil
            </div>
            <div class="menu-item delete" id="trigger-delete-profile">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                Excluir Perfil
            </div>
        </div>
    </div>

    <div class="press-overlay" id="pipo-press-overlay"></div>

    <script src="/assets/js/notification.js"></script>
    <script src="/assets/js/profiles.js"></script>
</body>
</html>