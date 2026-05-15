<?php
/**
 * ARQUIVO: pages/create/edit.php
 * Tela de edição de perfil.
 *
 * Acesso: /create/profile/edit=<profileId>
 */
require_once __DIR__ . '/../../database/db.php';

// ── Autenticação ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$profileId = isset($_GET['profile']) ? (int) $_GET['profile'] : 0;

if ($profileId <= 0) {
    header('Location: /select-profile?error=perfil_invalido');
    exit;
}

// ── Carrega dados do perfil ────────────────────────────────────────────────────
$profile = null;
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare(
            'SELECT * FROM profiles WHERE id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$profileId, $userId]);
        $profile = $stmt->fetch(\PDO::FETCH_ASSOC);
    }
} catch (\Throwable $e) {
    $profile = null;
}

if (!$profile) {
    header('Location: /select-profile?error=perfil_invalido');
    exit;
}

// ── Verifica se o usuário é premium (pago ou cortesia) ───────────────────────
$isPremium = false;
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM user_subscriptions
              WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$userId]);
        $isPremium = (int) $stmt->fetchColumn() > 0;
    }
} catch (\Throwable $e) {
    $isPremium = false;
}

if (!$isPremium) {
    try {
        $stmt = $pdo->prepare('SELECT id FROM profiles WHERE user_id = ? ORDER BY id ASC LIMIT 2');
        $stmt->execute([$userId]);
        $allowedProfileIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if (!in_array($profileId, $allowedProfileIds, true)) {
            header('Location: /select-profile?error=plano_gold_necessario');
            exit;
        }
    } catch (\Throwable $e) {
        header('Location: /select-profile?error=plano_gold_necessario');
        exit;
    }
}

$isPremiumJs = $isPremium ? 'true' : 'false';
$profileName = htmlspecialchars($profile['profile_name'] ?? '', ENT_QUOTES, 'UTF-8');
$profileImg  = htmlspecialchars($profile['profile_image'] ?? 'https://api.dicebear.com/9.x/adventurer/svg?seed=Pipo', ENT_QUOTES, 'UTF-8');
$profileUser = htmlspecialchars($profile['profile_username'] ?? '', ENT_QUOTES, 'UTF-8');
$profileIdJs = $profileId;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>PipoCine &mdash; Editar Perfil</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        :root {
            --bg:       #000000;
            --surface:  #111113;
            --surface2: #1c1c1e;
            --border:   rgba(255,255,255,.08);
            --text:     #ffffff;
            --muted:    #8e8e93;
            --accent:   #0a7aff;
            --success:  #34c759;
            --error:    #ff3b30;
            --warning:  #ff9500;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .cp-shell {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 48px 20px 64px;
        }
        .cp-back {
            align-self: flex-start;
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: none;
            color: var(--accent);
            font-size: .95rem;
            cursor: pointer;
            padding: 0;
            margin-bottom: 40px;
        }
        .cp-back svg { width: 18px; height: 18px; }
        .cp-card { width: 100%; max-width: 460px; }
        .cp-title { font-size: 1.75rem; font-weight: 700; text-align: center; margin: 0 0 8px; }
        .cp-subtitle { color: var(--muted); font-size: .95rem; text-align: center; margin: 0 0 36px; }

        /* Avatar */
        .cp-avatar-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-bottom: 32px;
        }
        .cp-avatar-ring { position: relative; width: 108px; height: 108px; border-radius: 50%; }
        .cp-avatar-img {
            width: 108px; height: 108px; border-radius: 50%;
            object-fit: cover; display: block; background: var(--surface2);
        }
        .cp-avatar-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,.55); display: flex;
            align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .cp-avatar-btn:hover .cp-avatar-overlay { opacity: 1; }
        .cp-avatar-overlay svg { width: 24px; height: 24px; color: #fff; }
        .cp-avatar-hint { color: var(--accent); font-size: .88rem; font-weight: 500; }

        /* Upload premium */
        .cp-upload-badge {
            align-items: center;
            gap: 6px;
            background: rgba(10,122,255,.12);
            border: 1px solid rgba(10,122,255,.25);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: .82rem;
            color: var(--accent);
            font-weight: 600;
            cursor: pointer;
            margin: -18px auto 28px;
            width: fit-content;
            display: flex;
        }
        .cp-upload-badge input { display: none; }

        /* Form */
        .cp-form { display: flex; flex-direction: column; gap: 14px; }
        .cp-field { position: relative; }
        .cp-label {
            display: block;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 6px;
        }
        .cp-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
            color: var(--text);
            font-size: .98rem;
            outline: none;
            transition: border-color .2s;
        }
        .cp-input::placeholder { color: var(--muted); }
        .cp-input:focus { border-color: rgba(255,255,255,.22); }
        .cp-input.error   { border-color: var(--error); }
        .cp-input.success { border-color: var(--success); }
        .cp-input:disabled { opacity: .5; cursor: not-allowed; }

        .cp-input-status {
            position: absolute; right: 14px;
            bottom: 14px; font-size: .75rem;
            font-weight: 700; text-transform: uppercase;
        }
        .cp-input-status.available { color: var(--success); }
        .cp-input-status.taken     { color: var(--error); }
        .cp-input-status.loading   { color: var(--muted); }

        .cp-hint {
            font-size: .8rem;
            color: var(--muted);
            margin-top: 4px;
            display: block;
        }

        /* Premium lock */
        .cp-premium-lock {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,149,0,.08);
            border: 1px solid rgba(255,149,0,.2);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: .88rem;
            color: var(--warning);
        }
        .cp-premium-lock svg { width: 16px; height: 16px; flex-shrink: 0; }

        .cp-divider { height: 1px; background: var(--border); margin: 4px 0; }

        /* Zona de perigo */
        .cp-danger-zone {
            border: 1px solid rgba(255,59,48,.2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 8px;
        }
        .cp-danger-title {
            color: var(--error);
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin: 0 0 10px;
        }
        .cp-btn-danger {
            width: 100%;
            background: rgba(255,59,48,.12);
            color: var(--error);
            border: 1px solid rgba(255,59,48,.25);
            border-radius: 10px;
            padding: 13px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }
        .cp-btn-danger:hover { background: rgba(255,59,48,.2); }

        /* Salvar */
        .cp-btn-save {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cp-btn-save:hover { opacity: .9; }
        .cp-btn-save:disabled { opacity: .45; cursor: not-allowed; }
        .cp-spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }
        .cp-btn-save.loading .cp-spinner { display: block; }
        .cp-btn-save.loading .cp-btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Confirm delete overlay */
        .cp-confirm-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.85);
            backdrop-filter: blur(4px);
            z-index: 4000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0; visibility: hidden;
            transition: opacity .2s, visibility .2s;
        }
        .cp-confirm-overlay.open { opacity: 1; visibility: visible; }
        .cp-confirm-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px;
            max-width: 360px;
            width: 100%;
            text-align: center;
        }
        .cp-confirm-icon {
            width: 48px; height: 48px;
            background: rgba(255,59,48,.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--error);
        }
        .cp-confirm-icon svg { width: 24px; height: 24px; }
        .cp-confirm-title { font-size: 1.15rem; font-weight: 700; margin: 0 0 8px; }
        .cp-confirm-desc { color: var(--muted); font-size: .9rem; margin: 0 0 24px; }
        .cp-confirm-actions { display: flex; gap: 10px; }
        .cp-confirm-actions button {
            flex: 1; padding: 13px; border-radius: 10px;
            font-size: .95rem; font-weight: 600; cursor: pointer;
            border: none;
        }
        .cp-btn-cancel-del { background: var(--surface2); color: var(--text); }
        .cp-btn-confirm-del { background: var(--error); color: #fff; }

        /* Modal de avatares */
        .av-modal {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.88);
            backdrop-filter: blur(6px);
            z-index: 3000;
            display: flex;
            align-items: flex-end;
            opacity: 0; visibility: hidden;
            transition: opacity .25s, visibility .25s;
        }
        @media (min-width: 600px) {
            .av-modal { align-items: center; justify-content: center; }
        }
        .av-modal.open { opacity: 1; visibility: visible; }
        .av-sheet {
            background: var(--surface);
            border-radius: 20px 20px 0 0;
            width: 100%; max-height: 80vh;
            display: flex; flex-direction: column;
            padding: 20px; overflow: hidden;
            border: 1px solid var(--border);
        }
        @media (min-width: 600px) {
            .av-sheet { border-radius: 20px; max-width: 500px; max-height: 70vh; }
        }
        .av-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .av-title { font-size: 1.1rem; font-weight: 700; }
        .av-close { background: none; border: none; color: var(--accent); font-size: .95rem; cursor: pointer; padding: 0; }
        .av-tabs {
            display: flex; gap: 6px; overflow-x: auto;
            padding-bottom: 10px; border-bottom: 1px solid var(--border);
            margin-bottom: 16px; scrollbar-width: none;
        }
        .av-tabs::-webkit-scrollbar { display: none; }
        .av-tab {
            background: none; border: none; color: var(--muted);
            font-size: .88rem; padding: 6px 14px;
            border-radius: 20px; cursor: pointer;
            white-space: nowrap; transition: color .2s, background .2s;
        }
        .av-tab.active { color: var(--text); background: var(--surface2); font-weight: 600; }
        .av-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
            gap: 14px; overflow-y: auto; padding: 4px 2px;
        }
        .av-option {
            width: 72px; height: 72px; border-radius: 50%;
            border: 2px solid transparent; cursor: pointer;
            object-fit: cover; transition: border-color .2s, transform .15s;
        }
        .av-option:hover { border-color: rgba(255,255,255,.5); transform: scale(1.07); }
        .av-option.selected { border-color: var(--accent); }
        .av-skeleton {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--surface2);
            animation: pulse 1.4s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }

        @media (max-width: 560px) {
            .cp-shell { padding: 28px 16px 48px; }
            .cp-title { font-size: 1.45rem; }
        }
    </style>
</head>
<body>

<div class="cp-shell">

    <button class="cp-back" onclick="history.back()" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Voltar
    </button>

    <div class="cp-card">

        <h1 class="cp-title">Editar Perfil</h1>
        <p class="cp-subtitle">Altere as informacoes do seu perfil.</p>

        <!-- Avatar -->
        <button type="button" class="cp-avatar-btn" id="btn-avatar-open" aria-label="Alterar avatar">
            <div class="cp-avatar-ring">
                <img id="avatar-preview" src="<?= $profileImg ?>" alt="Avatar atual" class="cp-avatar-img">
                <div class="cp-avatar-overlay">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
            </div>
            <span class="cp-avatar-hint">Alterar avatar</span>
        </button>

        <?php if ($isPremium): ?>
        <label class="cp-upload-badge" id="upload-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Enviar foto
            <input type="file" id="file-upload" accept="image/png,image/jpeg,image/webp">
        </label>
        <?php endif; ?>

        <form class="cp-form" id="edit-form" novalidate>
            <input type="hidden" id="avatar-url" value="<?= $profileImg ?>">
            <input type="hidden" id="profile-id" value="<?= $profileIdJs ?>">

            <!-- Nome -->
            <div class="cp-field">
                <label class="cp-label" for="inp-name">Nome do perfil</label>
                <input type="text" id="inp-name" class="cp-input"
                       value="<?= $profileName ?>"
                       placeholder="Ex: Maria" maxlength="30" required autocomplete="off">
            </div>

            <!-- Username (só premium pode editar) -->
            <div class="cp-field">
                <label class="cp-label" for="inp-username">
                    Username
                    <?php if (!$isPremium): ?>
                    <span class="cp-toggle-badge" style="background:var(--warning);font-size:.65rem;padding:2px 6px;border-radius:4px;margin-left:4px;">Premium</span>
                    <?php endif; ?>
                </label>
                <input type="text" id="inp-username" class="cp-input"
                       value="<?= $profileUser ?>"
                       placeholder="Ex: maria_123"
                       maxlength="30" pattern="[a-zA-Z0-9_]+" autocomplete="off"
                       <?= !$isPremium ? 'disabled title="Apenas assinantes premium podem editar o username"' : '' ?>>
                <span class="cp-input-status" id="username-status"></span>
                <?php if (!$isPremium): ?>
                <span class="cp-hint">Edicao de username disponivel apenas para assinantes Premium.</span>
                <?php endif; ?>
            </div>

            <div class="cp-divider"></div>

            <button type="submit" class="cp-btn-save" id="btn-save">
                <div class="cp-spinner"></div>
                <span class="cp-btn-text">Salvar Alteracoes</span>
            </button>

            <!-- Zona de perigo -->
            <div class="cp-danger-zone">
                <p class="cp-danger-title">Zona de perigo</p>
                <button type="button" class="cp-btn-danger" id="btn-delete">Excluir este perfil</button>
            </div>

        </form>
    </div>
</div>

<!-- Modal de avatares -->
<div class="av-modal" id="av-modal" role="dialog" aria-modal="true">
    <div class="av-sheet">
        <div class="av-header">
            <span class="av-title">Escolher Avatar</span>
            <button type="button" class="av-close" id="av-close">Fechar</button>
        </div>
        <div class="av-tabs" id="av-tabs" role="tablist">
            <button class="av-tab active" data-cat="adventurer">Aventureiro</button>
            <button class="av-tab" data-cat="open-peeps">Pessoas</button>
            <button class="av-tab" data-cat="bottts">Robos</button>
            <button class="av-tab" data-cat="pixel-art">Pixel</button>
            <button class="av-tab" data-cat="lorelei">Lorelei</button>
            <button class="av-tab" data-cat="avataaars">Avataaars</button>
        </div>
        <div class="av-grid" id="av-grid"></div>
    </div>
</div>

<!-- Confirmacao de exclusao -->
<div class="cp-confirm-overlay" id="confirm-overlay">
    <div class="cp-confirm-card">
        <div class="cp-confirm-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"></path>
                <path d="M10 11v6"></path><path d="M14 11v6"></path>
                <path d="M9 6V4h6v2"></path>
            </svg>
        </div>
        <h2 class="cp-confirm-title">Excluir perfil?</h2>
        <p class="cp-confirm-desc">Esta acao e permanente e nao podera ser desfeita. Todo o historico do perfil sera perdido.</p>
        <div class="cp-confirm-actions">
            <button type="button" class="cp-btn-cancel-del" id="btn-cancel-delete">Cancelar</button>
            <button type="button" class="cp-btn-confirm-del" id="btn-confirm-delete">Excluir</button>
        </div>
    </div>
</div>

<script src="/assets/js/notification.js"></script>
<script>
(function () {
    'use strict';

    const IS_PREMIUM = <?= $isPremiumJs ?>;
    const PROFILE_ID = <?= $profileIdJs ?>;
    const IMGBB_KEY  = '538999ea6353b2b12c58af1f65f3cd8c';

    const avatarPreview  = document.getElementById('avatar-preview');
    const avatarUrlInput = document.getElementById('avatar-url');
    const avModal        = document.getElementById('av-modal');
    const avGrid         = document.getElementById('av-grid');
    const avTabs         = document.getElementById('av-tabs');
    const avClose        = document.getElementById('av-close');
    const usernameInp    = document.getElementById('inp-username');
    const usernameStatus = document.getElementById('username-status');
    const fileUpload     = document.getElementById('file-upload');
    const confirmOverlay = document.getElementById('confirm-overlay');

    let currentCat   = 'adventurer';
    let debounce     = null;
    let isUsernameOk = true;
    const origUsername = usernameInp ? usernameInp.value.trim() : '';

    // ── Avatar modal ──────────────────────────────────────────────────────────
    document.getElementById('btn-avatar-open').addEventListener('click', () => {
        avModal.classList.add('open');
        loadAvatars(currentCat);
    });
    avClose.addEventListener('click', () => avModal.classList.remove('open'));
    avModal.addEventListener('click', (e) => { if (e.target === avModal) avModal.classList.remove('open'); });

    avTabs.addEventListener('click', (e) => {
        const tab = e.target.closest('.av-tab');
        if (!tab) return;
        document.querySelectorAll('.av-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentCat = tab.dataset.cat;
        loadAvatars(currentCat);
    });

    async function loadAvatars(cat) {
        avGrid.innerHTML = '';
        for (let i = 0; i < 12; i++) {
            const sk = document.createElement('div');
            sk.className = 'av-skeleton';
            avGrid.appendChild(sk);
        }
        try {
            const res  = await fetch(`/api/profiles/avatars?category=${cat}`);
            const data = await res.json();
            avGrid.innerHTML = '';
            (data.avatars || []).forEach(url => {
                const img = document.createElement('img');
                img.src = url; img.className = 'av-option'; img.alt = 'Avatar'; img.loading = 'lazy';
                if (url === avatarUrlInput.value) img.classList.add('selected');
                img.addEventListener('click', () => {
                    document.querySelectorAll('.av-option').forEach(o => o.classList.remove('selected'));
                    img.classList.add('selected');
                    avatarPreview.src = url;
                    avatarUrlInput.value = url;
                    avModal.classList.remove('open');
                });
                avGrid.appendChild(img);
            });
        } catch (_) {
            avGrid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1;text-align:center">Erro ao carregar.</p>';
        }
    }

    // ── Upload ImgBB ─────────────────────────────────────────────────────────
    if (fileUpload) {
        fileUpload.addEventListener('change', async () => {
            const file = fileUpload.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('Imagem muito grande. Maximo 5 MB.');
                return;
            }
            if (!['image/png','image/jpeg','image/webp'].includes(file.type)) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('Formato invalido.');
                return;
            }
            if (typeof PipoNotification !== 'undefined') PipoNotification.info('Enviando imagem...');
            try {
                const fd = new FormData();
                fd.append('image', file);
                const res  = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_KEY}`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const url = data.data.display_url;
                    avatarPreview.src = url;
                    avatarUrlInput.value = url;
                    if (typeof PipoNotification !== 'undefined') PipoNotification.success('Imagem enviada!');
                } else { throw new Error(); }
            } catch (_) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('Falha ao enviar imagem.');
            }
        });
    }

    // ── Validação de username ─────────────────────────────────────────────────
    if (usernameInp && IS_PREMIUM) {
        usernameInp.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^a-zA-Z0-9_]/g, '');
            clearTimeout(debounce);
            isUsernameOk = false;

            if (e.target.value.length === 0 || e.target.value.trim() === origUsername) {
                usernameStatus.textContent = '';
                usernameStatus.className = 'cp-input-status';
                isUsernameOk = true;
                usernameInp.classList.remove('error', 'success');
                return;
            }
            if (e.target.value.length < 3) {
                usernameStatus.textContent = 'muito curto';
                usernameStatus.className = 'cp-input-status taken';
                usernameInp.classList.add('error');
                usernameInp.classList.remove('success');
                return;
            }
            usernameStatus.textContent = '...';
            usernameStatus.className = 'cp-input-status loading';

            debounce = setTimeout(async () => {
                try {
                    const res  = await fetch(`/api/profiles/check-username?username=${encodeURIComponent(usernameInp.value)}`);
                    const data = await res.json();
                    if (data.available) {
                        usernameStatus.textContent = 'disponivel';
                        usernameStatus.className = 'cp-input-status available';
                        usernameInp.classList.add('success');
                        usernameInp.classList.remove('error');
                        isUsernameOk = true;
                    } else {
                        usernameStatus.textContent = 'em uso';
                        usernameStatus.className = 'cp-input-status taken';
                        usernameInp.classList.add('error');
                        usernameInp.classList.remove('success');
                        isUsernameOk = false;
                    }
                } catch (_) { isUsernameOk = true; }
            }, 500);
        });
    }

    // ── Salvar ────────────────────────────────────────────────────────────────
    const form    = document.getElementById('edit-form');
    const btnSave = document.getElementById('btn-save');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name     = document.getElementById('inp-name').value.trim();
        const username = (usernameInp && IS_PREMIUM) ? usernameInp.value.trim() : '';
        const image    = avatarUrlInput.value;

        if (!name) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('Informe o nome do perfil.');
            return;
        }

        if (username && !isUsernameOk) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('O username nao esta disponivel.');
            return;
        }

        btnSave.disabled = true;
        btnSave.classList.add('loading');

        try {
            const res  = await fetch('/api/profiles/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: PROFILE_ID, name, username, image })
            });
            const data = await res.json();

            if (data.success) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.success('Perfil atualizado!');
                setTimeout(() => window.location.href = '/select-profile', 900);
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message || 'Erro ao salvar.');
                btnSave.disabled = false;
                btnSave.classList.remove('loading');
            }
        } catch (_) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro de conexao.');
            btnSave.disabled = false;
            btnSave.classList.remove('loading');
        }
    });

    // ── Excluir ───────────────────────────────────────────────────────────────
    document.getElementById('btn-delete').addEventListener('click', () => {
        confirmOverlay.classList.add('open');
    });
    document.getElementById('btn-cancel-delete').addEventListener('click', () => {
        confirmOverlay.classList.remove('open');
    });
    document.getElementById('btn-confirm-delete').addEventListener('click', async () => {
        confirmOverlay.classList.remove('open');
        try {
            const res  = await fetch('/api/profiles/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: PROFILE_ID })
            });
            const data = await res.json();
            if (data.success) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.success('Perfil excluido.');
                setTimeout(() => window.location.href = '/select-profile', 1000);
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message || 'Erro ao excluir.');
            }
        } catch (_) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro de conexao.');
        }
    });
})();
</script>

</body>
</html>
