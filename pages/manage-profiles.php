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
    <title>PipoCine — Gerenciar Perfis</title>
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
            --profile-accent: #0a7aff; /* Azul estilo iOS */
            --profile-success: #34c759;
            --profile-error: #ff3b30;
            --profile-card-size: 130px;
        }

        body { 
            background-color: var(--profile-bg-base); 
            margin: 0; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
        }

        /* ============================================================
           PÁGINA PRINCIPAL E GRELHA
           ============================================================ */
        .profiles-wrapper {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 100vh; padding: 40px 20px; box-sizing: border-box;
        }

        .main-title { 
            color: var(--profile-text-pure); font-size: clamp(2rem, 5vw, 2.5rem); 
            font-weight: 700; margin-bottom: 50px; text-align: center; 
        }

        #pipo-profiles-root { width: 100%; display: flex; justify-content: center; margin-bottom: 40px; }
        .profiles-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; max-width: 800px; }

        .profile-item { display: flex; flex-direction: column; align-items: center; gap: 12px; cursor: pointer; transition: transform 0.2s; position: relative; }
        .profile-item:hover { transform: scale(1.05); }

        .avatar-wrapper { width: var(--profile-card-size); height: var(--profile-card-size); border-radius: 50%; overflow: hidden; background-color: #222; position: relative; }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { color: var(--profile-text-pure); font-size: 1.1rem; font-weight: 500; text-align: center; }

        /* Estilo específico para indicar que estamos em modo de edição (Lápis por cima da foto) */
        .profile-item.edit-mode .avatar-wrapper::after {
            content: ''; position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.6);
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>');
            background-repeat: no-repeat; background-position: center; transition: opacity 0.2s; opacity: 0.8;
        }
        .profile-item.edit-mode:hover .avatar-wrapper::after { opacity: 1; transform: scale(1.05); }

        /* Botão Concluído */
        .manage-profiles-container { display: flex; justify-content: center; width: 100%; }
        .btn-manage {
            background: var(--profile-text-pure); color: var(--profile-bg-base); border: none;
            padding: 12px 32px; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .btn-manage:hover { opacity: 0.8; }

        /* ============================================================
           MODAL DARK MINIMALISTA (Para Edição e Avatar)
           ============================================================ */
        .profile-modal, .avatar-modal {
            position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.90); backdrop-filter: blur(5px);
            z-index: 2000; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: opacity 0.3s; padding: 20px;
        }
        .profile-modal.open, .avatar-modal.open { opacity: 1; visibility: visible; }

        .modal-content-dark {
            background: var(--profile-bg-modal); width: 100%; max-width: 450px;
            border-radius: 20px; padding: 30px 24px; box-sizing: border-box; display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.05);
        }

        /* Header */
        .modal-header-dark { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-cancel-dark { background: transparent; border: none; color: var(--profile-text-pure); font-size: 1rem; cursor: pointer; padding: 0; opacity: 0.8; }
        .btn-cancel-dark:hover { opacity: 1; }
        .title-dark { color: var(--profile-text-pure); font-size: 1.25rem; font-weight: 600; margin: 0; text-align: center; }

        /* Avatar Selection */
        .avatar-section-dark { display: flex; flex-direction: column; align-items: center; gap: 12px; margin-bottom: 30px; cursor: pointer; }
        .avatar-circle-dark { 
            width: 120px; height: 120px; border-radius: 50%; overflow: hidden; 
            background-color: var(--profile-bg-input); border: 2px solid transparent; transition: border-color 0.2s;
        }
        .avatar-circle-dark img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-section-dark:hover .avatar-circle-dark { border-color: rgba(255,255,255,0.3); }
        .avatar-hint-dark { color: var(--profile-text-muted); font-size: 0.9rem; }

        /* Inputs */
        .inputs-container-dark { display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px; }
        .input-group-dark { position: relative; width: 100%; }
        .input-dark {
            width: 100%; background-color: var(--profile-bg-input); border: 1px solid transparent;
            padding: 16px; border-radius: 12px; color: var(--profile-text-pure); font-size: 1rem; box-sizing: border-box; text-align: center;
        }
        .input-dark::placeholder { color: var(--profile-text-muted); }
        .input-dark:focus { outline: none; border-color: rgba(255,255,255,0.2); background-color: rgba(255,255,255,0.05); }

        /* Botão Salvar (Azul) */
        .btn-save-dark {
            width: 100%; background-color: var(--profile-accent); color: white; border: none;
            padding: 16px; border-radius: 12px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s;
        }
        .btn-save-dark:hover { opacity: 0.9; }
        .btn-save-dark:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Grid de Avatares (Modal) */
        .avatar-categories { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 20px; scrollbar-width: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .avatar-categories::-webkit-scrollbar { display: none; }
        .category-btn { background: transparent; border: none; color: var(--profile-text-muted); padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; cursor: pointer; white-space: nowrap; transition: color 0.2s; }
        .category-btn.active { color: var(--profile-text-pure); font-weight: 600; }
        .avatar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(75px, 1fr)); gap: 16px; max-height: 400px; overflow-y: auto; padding: 4px; }
        .avatar-option { width: 75px; height: 75px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s; object-fit: cover;}
        .avatar-option:hover { border-color: rgba(255,255,255,0.5); }
    </style>
</head>
<body>

<div class="profiles-wrapper">
    <h1 class="main-title">Gerenciar Perfis</h1>

    <div id="pipo-profiles-root">
        <div class="profiles-grid" id="profiles-grid">
            <div class="loader-pipo" style="grid-column: 1 / -1; margin: 50px auto;"></div>
        </div>
    </div>

    <div class="manage-profiles-container">
        <button class="btn-manage" onclick="window.location.href='/select-profile'">Concluído</button>
    </div>
</div>

<div class="profile-modal" id="pipo-edit-modal" role="dialog" aria-modal="true">
    <div class="modal-content-dark">
        
        <div class="modal-header-dark">
            <button type="button" class="btn-cancel-dark modal-close" aria-label="Fechar">Cancelar</button>
            <h2 class="title-dark">Editar Perfil</h2>
            <div style="width: 65px;"></div> </div>

        <form id="edit-profile-form" class="form-dark" novalidate>
            <input type="hidden" name="id" id="edit_profile_id">
            
            <div class="avatar-section-dark" id="avatar-picker-trigger" tabindex="0">
                <div class="avatar-circle-dark">
                    <img src="" alt="Avatar atual" id="current-avatar-img">
                </div>
                <input type="hidden" id="selected-avatar-url" name="image">
                <span class="avatar-hint-dark">Toque para alterar o avatar</span>
            </div>

            <div class="inputs-container-dark">
                <div class="input-group-dark">
                    <input type="text" name="name" id="edit_pro_name" class="input-dark" placeholder="Nome do perfil" required autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-save-dark" id="btn-save-edit">Salvar Alterações</button>
        </form>
    </div>
</div>

<div class="avatar-modal" id="pipo-avatar-modal" role="dialog" aria-modal="true">
    <div class="modal-content-dark">
        <div class="modal-header-dark" style="margin-bottom: 20px;">
            <button type="button" class="btn-cancel-dark modal-close">Voltar</button>
            <h2 class="title-dark">Avatar</h2>
            <div style="width: 50px;"></div>
        </div>

        <div class="avatar-categories" id="avatar-categories" role="tablist">
            <button class="category-btn active" data-category="adventurer">Aventureiro</button>
            <button class="category-btn" data-category="open-peeps">Pessoas</button>
            <button class="category-btn" data-category="bottts">Robôs</button>
            <button class="category-btn" data-category="pixel-art">Pixel</button>
        </div>

        <div class="avatar-grid" id="avatar-grid" role="listbox"></div>
    </div>
</div>

<script src="/assets/js/notification.js"></script>
<script src="/assets/js/manage-profiles.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => { 
        if (typeof lucide !== 'undefined') lucide.createIcons(); 
    });
</script>
</body>
</html>