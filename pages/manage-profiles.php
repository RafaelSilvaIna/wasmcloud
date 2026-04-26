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
    <title>PipoCine — Gerenciar Perfis</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <link rel="stylesheet" href="/assets/css/profiles.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        /* Estilo específico para indicar que estamos em modo de edição */
        .profile-item.edit-mode .avatar-wrapper::after {
            content: ''; position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.6);
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>');
            background-repeat: no-repeat; background-position: center; transition: opacity 0.2s; opacity: 0.8;
        }
        .profile-item.edit-mode:hover .avatar-wrapper::after { opacity: 1; transform: scale(1.05); }
    </style>
</head>
<body>

<div class="profiles-wrapper">
    <div class="logo-container">
        <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
    </div>

    <h1 class="main-title">Gerenciar Perfis</h1>

    <div id="pipo-profiles-root">
        <div class="profiles-grid" id="profiles-grid">
            <div class="loader-pipo" style="grid-column: 1 / -1; margin: 50px auto;"></div>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <button class="btn-ghost" onclick="window.location.href='/select-profile'" style="padding: 12px 32px; font-size: 1rem;">
            Concluído
        </button>
    </div>
</div>

<div class="profile-modal" id="pipo-edit-modal" role="dialog" aria-modal="true">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Editar Perfil</h2>
            <button class="modal-close" aria-label="Fechar"><i data-lucide="x" width="16" height="16"></i></button>
        </div>

        <form class="modal-form" id="edit-profile-form" novalidate>
            <input type="hidden" name="id" id="edit_profile_id">
            
            <div class="modal-avatar-section">
                <div class="avatar-picker-trigger" id="avatar-picker-trigger">
                    <div class="avatar-wrapper">
                        <img src="" alt="Avatar atual" class="modal-avatar-img" id="current-avatar-img">
                    </div>
                    <input type="hidden" id="selected-avatar-url" name="image">
                    <div class="edit-icon"><i data-lucide="pencil" width="13" height="13"></i></div>
                </div>
            </div>

            <div class="input-group" style="margin-top: 15px;">
                <label for="edit_pro_name">Nome do Perfil</label>
                <div class="input-with-icon">
                    <i data-lucide="user" width="15" height="15" class="input-icon"></i>
                    <input type="text" name="name" id="edit_pro_name" required autocomplete="off">
                </div>
            </div>

            <div class="modal-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-primary" id="btn-save-edit">
                    <i data-lucide="check" width="16" height="16"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="avatar-modal" id="pipo-avatar-modal" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i data-lucide="image" width="20" height="20"></i> Escolha seu Avatar</h2>
            <button class="modal-close"><i data-lucide="x" width="16" height="16"></i></button>
        </div>
        <div class="avatar-categories" id="avatar-categories" role="tablist">
            <button class="category-btn active" data-category="adventurer">Aventureiro</button>
            <button class="category-btn" data-category="open-peeps">Pessoas</button>
            <button class="category-btn" data-category="bottts">Robôs</button>
        </div>
        <div class="avatar-grid" id="avatar-grid"></div>
    </div>
</div>

<script src="/assets/js/notification.js"></script>
<script src="/assets/js/manage-profiles.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>