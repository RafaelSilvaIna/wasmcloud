<?php
/**
 * ARQUIVO: pages/create/index.php
 * Tela de criação de perfil.
 *
 * Acesso: /create/profile  (protegido pelo flag de sessão can_create_profile)
 */
require_once __DIR__ . '/../../database/db.php';

// ── Autenticação básica ───────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ── Proteção via flag de sessão ───────────────────────────────────────────────
if (empty($_SESSION['can_create_profile'])) {
    header('Location: /select-profile?error=acesso_negado');
    exit;
}
// Consome o flag imediatamente (single-use)
unset($_SESSION['can_create_profile']);

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

$isPremiumJs = $isPremium ? 'true' : 'false';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>PipoCine &mdash; Criar Perfil</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        /* ── Tokens de cor ─────────────────────────────────────────────────── */
        :root {
            --bg:        #000000;
            --surface:   #111113;
            --surface2:  #1c1c1e;
            --border:    rgba(255,255,255,.08);
            --text:      #ffffff;
            --muted:     #8e8e93;
            --accent:    #0a7aff;
            --success:   #34c759;
            --error:     #ff3b30;
            --warning:   #ff9500;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        /* ── Layout ────────────────────────────────────────────────────────── */
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

        .cp-card {
            width: 100%;
            max-width: 460px;
        }

        .cp-title {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin: 0 0 8px;
        }

        .cp-subtitle {
            color: var(--muted);
            font-size: .95rem;
            text-align: center;
            margin: 0 0 36px;
        }

        /* ── Avatar ────────────────────────────────────────────────────────── */
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

        .cp-avatar-ring {
            position: relative;
            width: 108px;
            height: 108px;
            border-radius: 50%;
        }

        .cp-avatar-img {
            width: 108px;
            height: 108px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            background: var(--surface2);
        }

        .cp-avatar-overlay {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(0,0,0,.55);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
        }
        .cp-avatar-btn:hover .cp-avatar-overlay { opacity: 1; }
        .cp-avatar-overlay svg { width: 24px; height: 24px; color: #fff; }

        .cp-avatar-hint {
            color: var(--accent);
            font-size: .88rem;
            font-weight: 500;
        }

        /* Upload premium badge */
        .cp-upload-badge {
            display: none;
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
        }
        .cp-upload-badge.visible { display: flex; }
        .cp-upload-badge input { display: none; }

        /* ── Formulário ────────────────────────────────────────────────────── */
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
        .cp-input.error { border-color: var(--error); }
        .cp-input.success { border-color: var(--success); }

        .cp-input-status {
            position: absolute;
            right: 14px;
            top: 50%;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        /* ajuste quando há label: o top não pode ser 50% do field inteiro */
        .cp-field .cp-input-status { top: auto; bottom: 14px; }

        .cp-input-status.available { color: var(--success); }
        .cp-input-status.taken     { color: var(--error); }
        .cp-input-status.loading   { color: var(--muted); }

        /* Toggle row */
        .cp-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
        }

        .cp-toggle-texts { display: flex; flex-direction: column; gap: 3px; }
        .cp-toggle-title { font-size: .97rem; font-weight: 500; color: var(--text); }
        .cp-toggle-desc  { font-size: .82rem; color: var(--muted); }

        .cp-toggle-badge {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: var(--accent);
            color: #fff;
            border-radius: 4px;
            padding: 2px 6px;
            margin-left: 6px;
        }

        /* Switch */
        .cp-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 28px;
            flex-shrink: 0;
        }
        .cp-switch input { opacity: 0; width: 0; height: 0; }
        .cp-slider {
            position: absolute;
            inset: 0;
            background: #3a3a3c;
            border-radius: 28px;
            cursor: pointer;
            transition: .25s;
        }
        .cp-slider::before {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .25s;
        }
        .cp-switch input:checked + .cp-slider { background: var(--accent); }
        .cp-switch input:checked + .cp-slider::before { transform: translateX(20px); }
        .cp-switch input:disabled + .cp-slider { opacity: .4; cursor: not-allowed; }

        /* Seção PIN */
        .cp-pin-section {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height .3s ease, opacity .3s ease;
        }
        .cp-pin-section.open { max-height: 80px; opacity: 1; }

        .cp-btn-set-pin {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            margin-top: 10px;
            padding: 12px 16px;
            background: rgba(255,255,255,.05);
            border: 1.5px dashed rgba(255,255,255,.15);
            border-radius: 10px;
            color: var(--muted);
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            transition: border-color .15s, color .15s, background .15s;
        }
        .cp-btn-set-pin:hover { border-color: rgba(255,255,255,.3); color: var(--text); background: rgba(255,255,255,.08); }
        .cp-btn-set-pin.pin-ok { border-style: solid; border-color: rgba(34,197,94,.4); color: #4ade80; background: rgba(34,197,94,.05); }
        .cp-btn-set-pin svg { width: 16px; height: 16px; flex-shrink: 0; }

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

        /* Divider */
        .cp-divider {
            height: 1px;
            background: var(--border);
            margin: 4px 0;
        }

        /* Botão salvar */
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

        /* Spinner inline */
        .cp-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }
        .cp-btn-save.loading .cp-spinner { display: block; }
        .cp-btn-save.loading .cp-btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Modal de avatares ─────────────────────────────────────────────── */
        .av-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.88);
            backdrop-filter: blur(6px);
            z-index: 3000;
            display: flex;
            align-items: flex-end;
            opacity: 0;
            visibility: hidden;
            transition: opacity .25s, visibility .25s;
        }
        @media (min-width: 600px) {
            .av-modal { align-items: center; justify-content: center; }
        }
        .av-modal.open { opacity: 1; visibility: visible; }

        .av-sheet {
            background: var(--surface);
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        @media (min-width: 600px) {
            .av-sheet {
                border-radius: 20px;
                max-width: 500px;
                max-height: 70vh;
            }
        }

        .av-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .av-title { font-size: 1.1rem; font-weight: 700; }
        .av-close {
            background: none;
            border: none;
            color: var(--accent);
            font-size: .95rem;
            cursor: pointer;
            padding: 0;
        }

        .av-tabs {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
            scrollbar-width: none;
        }
        .av-tabs::-webkit-scrollbar { display: none; }

        .av-tab {
            background: none;
            border: none;
            color: var(--muted);
            font-size: .88rem;
            padding: 6px 14px;
            border-radius: 20px;
            cursor: pointer;
            white-space: nowrap;
            transition: color .2s, background .2s;
        }
        .av-tab.active {
            color: var(--text);
            background: var(--surface2);
            font-weight: 600;
        }

        .av-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
            gap: 14px;
            overflow-y: auto;
            padding: 4px 2px;
        }

        .av-option {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            object-fit: cover;
            transition: border-color .2s, transform .15s;
        }
        .av-option:hover {
            border-color: rgba(255,255,255,.5);
            transform: scale(1.07);
        }
        .av-option.selected { border-color: var(--accent); }

        /* Skeleton */
        .av-skeleton {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--surface2);
            animation: pulse 1.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; }
            50%      { opacity: .4; }
        }

        /* ── Responsivo ────────────────────────────────────────────────────── */
        @media (max-width: 560px) {
            .cp-shell { padding: 28px 16px 48px; }
            .cp-title { font-size: 1.45rem; }
        }
    </style>
</head>
<body>

<div class="cp-shell">

    <button class="cp-back" onclick="window.location.href='/select-profile'" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Voltar
    </button>

    <div class="cp-card">

        <h1 class="cp-title">Criar Perfil</h1>
        <p class="cp-subtitle">Personalize como quiser. Você pode mudar isso depois.</p>

        <!-- Avatar -->
        <button type="button" class="cp-avatar-btn" id="btn-avatar-open" aria-label="Escolher avatar">
            <div class="cp-avatar-ring">
                <img id="avatar-preview"
                     src="https://api.dicebear.com/9.x/adventurer/svg?seed=Pipo"
                     alt="Avatar selecionado"
                     class="cp-avatar-img">
                <div class="cp-avatar-overlay">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
            </div>
            <span class="cp-avatar-hint">Escolher avatar</span>
        </button>

        <?php if ($isPremium): ?>
        <!-- Upload personalizado (só premium) -->
        <label class="cp-upload-badge visible" id="upload-badge" style="margin: -18px auto 28px; display: flex; width: fit-content;">
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

        <form class="cp-form" id="create-form" novalidate>
            <input type="hidden" id="avatar-url" value="https://api.dicebear.com/9.x/adventurer/svg?seed=Pipo">


            <!-- Nome -->
            <div class="cp-field">
                <label class="cp-label" for="inp-name">Nome do perfil</label>
                <input type="text" id="inp-name" class="cp-input" placeholder="Ex: Maria"
                       maxlength="30" required autocomplete="off">
            </div>

            <!-- Username -->
            <div class="cp-field">
                <label class="cp-label" for="inp-username">Username <span style="color:var(--muted);font-weight:400;text-transform:none">(exclusivo)</span></label>
                <input type="text" id="inp-username" class="cp-input" placeholder="Ex: maria_123"
                       maxlength="30" pattern="[a-zA-Z0-9_]+" autocomplete="off">
                <span class="cp-input-status" id="username-status"></span>
            </div>

            <div class="cp-divider"></div>

            <!-- Perfil infantil -->
            <div class="cp-toggle-row">
                <div class="cp-toggle-texts">
                    <span class="cp-toggle-title">Perfil Infantil</span>
                    <span class="cp-toggle-desc">Restringe a conteudos adequados para criancas</span>
                </div>
                <label class="cp-switch">
                    <input type="checkbox" id="toggle-kids">
                    <span class="cp-slider"></span>
                </label>
            </div>

            <!-- PIN (só premium) -->
            <?php if ($isPremium): ?>
            <div class="cp-toggle-row">
                <div class="cp-toggle-texts">
                    <span class="cp-toggle-title">
                        PIN de seguranca
                        <span class="cp-toggle-badge">Premium</span>
                    </span>
                    <span class="cp-toggle-desc">Exige PIN de 4 digitos para entrar no perfil</span>
                </div>
                <label class="cp-switch">
                    <input type="checkbox" id="toggle-pin">
                    <span class="cp-slider"></span>
                </label>
            </div>

            <div class="cp-pin-section" id="pin-section">
                <input type="hidden" id="pin-value">
                <button type="button" class="cp-btn-set-pin" id="btn-set-pin">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0110 0v4"></path>
                    </svg>
                    <span id="btn-set-pin-label">Definir PIN</span>
                </button>
            </div>
            <?php else: ?>
            <div class="cp-premium-lock">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0110 0v4"></path>
                </svg>
                PIN de seguranca &mdash; disponivel somente para assinantes Premium.
            </div>
            <?php endif; ?>

            <!-- Salvar -->
            <button type="submit" class="cp-btn-save" id="btn-save">
                <div class="cp-spinner"></div>
                <span class="cp-btn-text">Criar Perfil</span>
            </button>
        </form>

    </div>
</div>

<!-- Modal de avatares ─────────────────────────────────────── -->
<div class="av-modal" id="av-modal" role="dialog" aria-modal="true" aria-label="Escolher avatar">
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

<?php
require_once __DIR__ . '/../../components/PinInputModal.php';
PinInputModal::render();
?>
<script src="/assets/js/notification.js"></script>
<script>
(function () {
    'use strict';

    const IS_PREMIUM  = <?= $isPremiumJs ?>;
    const IMGBB_KEY   = '538999ea6353b2b12c58af1f65f3cd8c';

    // ── Elementos ──────────────────────────��──────────────────────────────────
    const avatarPreview  = document.getElementById('avatar-preview');
    const avatarUrlInput = document.getElementById('avatar-url');
    const form           = document.getElementById('create-form');
    const btnSave        = document.getElementById('btn-save');
    const avModal        = document.getElementById('av-modal');
    const avGrid         = document.getElementById('av-grid');
    const avTabs         = document.getElementById('av-tabs');
    const avClose        = document.getElementById('av-close');
    const pinSection     = document.getElementById('pin-section');
    const pinValue       = document.getElementById('pin-value');
    const usernameInp    = document.getElementById('inp-username');
    const usernameStatus = document.getElementById('username-status');
    const fileUpload     = document.getElementById('file-upload');

    let currentCat    = 'adventurer';
    let debounce      = null;
    let isUsernameOk  = true; // username é opcional

    // ── Avatar modal ──────────────────────────────────────────────────────────
    document.getElementById('btn-avatar-open').addEventListener('click', () => {
        avModal.classList.add('open');
        loadAvatars(currentCat);
    });

    avClose.addEventListener('click', () => avModal.classList.remove('open'));
    avModal.addEventListener('click', (e) => {
        if (e.target === avModal) avModal.classList.remove('open');
    });

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
                img.src = url;
                img.className = 'av-option';
                img.alt = 'Avatar';
                img.loading = 'lazy';
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
        } catch (err) {
            avGrid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1;text-align:center">Erro ao carregar avatares.</p>';
        }
    }

    // ── Upload de imagem (ImgBB — apenas premium) ─────────────────────────────
    if (fileUpload) {
        fileUpload.addEventListener('change', async () => {
            const file = fileUpload.files[0];
            if (!file) return;

            const maxMb = 5;
            if (file.size > maxMb * 1024 * 1024) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(`A imagem deve ter no maximo ${maxMb} MB.`);
                return;
            }

            if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('Formato invalido. Use PNG, JPEG ou WEBP.');
                return;
            }

            if (typeof PipoNotification !== 'undefined') PipoNotification.info('Enviando imagem...');

            try {
                const fd = new FormData();
                fd.append('image', file);
                const res = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_KEY}`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    const url = data.data.display_url;
                    avatarPreview.src = url;
                    avatarUrlInput.value = url;
                    if (typeof PipoNotification !== 'undefined') PipoNotification.success('Imagem enviada!');
                } else {
                    throw new Error('Resposta invalida do ImgBB');
                }
            } catch (err) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('Falha ao enviar imagem. Tente novamente.');
            }
        });
    }

    // ── Validação de username ─────────────────────────────────────────────────
    if (usernameInp) {
        usernameInp.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^a-zA-Z0-9_]/g, '');
            clearTimeout(debounce);
            isUsernameOk = false;

            if (e.target.value.length === 0) {
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

    // ── Toggle PIN ────────────────────────────────────────────────────────────
    const togglePin    = document.getElementById('toggle-pin');
    const btnSetPin    = document.getElementById('btn-set-pin');
    const btnSetLabel  = document.getElementById('btn-set-pin-label');

    if (togglePin && pinSection) {
        togglePin.addEventListener('change', () => {
            pinSection.classList.toggle('open', togglePin.checked);
            if (!togglePin.checked) {
                if (pinValue) pinValue.value = '';
                if (btnSetPin) {
                    btnSetPin.classList.remove('pin-ok');
                    btnSetLabel.textContent = 'Definir PIN';
                }
            }
        });
    }

    // Botao "Definir PIN" → abre PinInputModal
    if (btnSetPin && pinValue) {
        btnSetPin.addEventListener('click', () => {
            if (typeof window.PinInputModal === 'undefined') return;
            window.PinInputModal.open({
                title:        'Criar PIN do Perfil',
                subtitle:     'Escolha 4 digitos para proteger este perfil',
                confirmLabel: 'Salvar PIN',
                onConfirm(pin) {
                    pinValue.value = pin;
                    btnSetPin.classList.add('pin-ok');
                    btnSetLabel.textContent = 'PIN definido — clique para alterar';
                    window.PinInputModal.close();
                },
                onCancel() {}
            });
        });
    }

    // ── Submissão do formulário ───────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name     = document.getElementById('inp-name').value.trim();
        const username = usernameInp ? usernameInp.value.trim() : '';
        const kids     = document.getElementById('toggle-kids').checked;
        const pin      = (togglePin && togglePin.checked) ? (pinValue ? pinValue.value : '') : '';
        const image    = avatarUrlInput.value;

        if (!name) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('Informe o nome do perfil.');
            document.getElementById('inp-name').focus();
            return;
        }

        if (username && !isUsernameOk) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('O username escolhido nao esta disponivel.');
            return;
        }

        if (togglePin && togglePin.checked && pin.length < 4) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('O PIN deve ter 4 digitos.');
            return;
        }

        // Loading state
        btnSave.disabled = true;
        btnSave.classList.add('loading');

        try {
            const res = await fetch('/api/profiles/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, username, image, type: kids ? 'kids' : 'standard', pin })
            });
            const data = await res.json();

            if (data.success) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.success('Perfil criado com sucesso!');
                setTimeout(() => window.location.href = '/select-profile', 1000);
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message || 'Erro ao criar perfil.');
                btnSave.disabled = false;
                btnSave.classList.remove('loading');
            }
        } catch (err) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro de conexao. Tente novamente.');
            btnSave.disabled = false;
            btnSave.classList.remove('loading');
        }
    });

    // Init lucide
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
})();
</script>

</body>
</html>
