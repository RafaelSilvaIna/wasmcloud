<?php
require_once __DIR__ . '/../database/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PipoCine - Entre com suas credenciais para acessar a melhor experiência de cinema em casa.">
    <title>PipoCine - Entrar</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <style>
        /* ── Botão "Mais opções" ──────────────────────────────────────── */
        .btn-more-options {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            width: 100%;
            padding: 11px 16px;
            margin-top: 10px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: rgba(255,255,255,0.55);
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s, background 0.2s;
        }
        .btn-more-options:hover {
            border-color: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.85);
            background: rgba(255,255,255,0.04);
        }
        .btn-more-options svg { width: 15px; height: 15px; flex-shrink: 0; }

        /* ── Modal overlay ────────────────────────────────────────────── */
        .auth-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 500;
            padding: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .auth-modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .auth-modal {
            background: #12151c;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 14px;
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            transform: translateY(20px) scale(0.97);
            transition: transform 0.25s cubic-bezier(0.16,1,0.3,1);
        }
        .auth-modal-overlay.open .auth-modal {
            transform: translateY(0) scale(1);
        }

        .auth-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .auth-modal-title {
            font-size: 15px;
            font-weight: 600;
            color: #e2e8f0;
            margin: 0;
        }
        .auth-modal-close {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s, background 0.15s;
        }
        .auth-modal-close:hover {
            color: #fff;
            background: rgba(255,255,255,0.07);
        }
        .auth-modal-close svg { width: 16px; height: 16px; }

        /* ── Tabs do modal ────────────────────────────────────────────── */
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .auth-tab {
            flex: 1;
            padding: 11px 8px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: color 0.15s, border-color 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .auth-tab svg { width: 14px; height: 14px; }
        .auth-tab.active {
            color: #ffd60a;
            border-bottom-color: #ffd60a;
        }
        .auth-tab:hover:not(.active) {
            color: rgba(255,255,255,0.75);
        }

        .auth-tab-panel { display: none; padding: 22px; }
        .auth-tab-panel.active { display: block; }

        /* ── Painel código de 4 dígitos ────────────────────────────────── */
        .code-panel-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.55;
            margin: 0 0 18px;
        }
        .code-panel-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 8px;
        }
        .code-panel-digit {
            width: 58px;
            height: 68px;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.1);
            background: #1a1e28;
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            caret-color: transparent;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .code-panel-digit:focus {
            border-color: #ffd60a;
            box-shadow: 0 0 0 3px rgba(255,214,10,0.12);
        }
        .code-panel-digit.filled  { border-color: rgba(255,214,10,0.4); }
        .code-panel-digit.cp-error { border-color: #e50914; animation: shake-digit 0.3s ease; }

        .code-panel-hint {
            font-size: 12.5px;
            min-height: 18px;
            text-align: center;
            margin: 8px 0 16px;
        }
        .code-panel-hint.err { color: #e50914; }
        .code-panel-hint.ok  { color: #10b981; }

        .btn-code-submit {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: #ffd60a;
            color: #000;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-code-submit:hover:not(:disabled) { background: #ffe033; }
        .btn-code-submit:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ── Painel QR Code ────────────────────────────────────────────── */
        .qr-login-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.55;
            margin: 0 0 16px;
            text-align: center;
        }
        .qr-login-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            min-height: 200px;
        }
        .qr-login-frame {
            background: #fff;
            border-radius: 10px;
            padding: 10px;
            line-height: 0;
        }
        .qr-login-frame canvas { display: block; border-radius: 4px; }

        .qr-login-timer {
            font-size: 12.5px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .qr-login-timer svg  { color: #4a5568; width: 13px; height: 13px; }
        .qr-login-timer strong { color: #e2e8f0; }
        .qr-login-timer strong.exp { color: #e50914; }

        .qr-login-progress {
            width: 180px;
            height: 3px;
            border-radius: 2px;
            background: rgba(255,255,255,0.07);
            overflow: hidden;
        }
        .qr-login-progress-fill {
            height: 100%;
            background: #ffd60a;
            width: 100%;
            transition: width 1s linear, background-color 0.3s;
        }
        .qr-login-progress-fill.exp { background: #e50914; }

        .qr-login-state-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }
        .qr-login-state-placeholder svg {
            width: 70px; height: 70px;
            opacity: 0.1;
            color: #e2e8f0;
        }
        .btn-qr-generate {
            padding: 10px 20px;
            border-radius: 8px;
            background: #ffd60a;
            color: #000;
            font-size: 13.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background 0.15s;
        }
        .btn-qr-generate svg { width: 15px; height: 15px; }
        .btn-qr-generate:hover:not(:disabled) { background: #ffe033; }
        .btn-qr-generate:disabled { opacity: 0.5; cursor: not-allowed; }

        .qr-login-success { text-align: center; color: #10b981; font-size: 14px; font-weight: 500; }
        .qr-login-expired { text-align: center; color: #94a3b8; font-size: 13px; }

        @media (max-width: 400px) {
            .code-panel-digit { width: 50px; height: 58px; font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-side-image">
            <div class="hero-text-wrapper">
                <h2>A melhor experiência<br>de cinema em casa.</h2>
            </div>
        </div>
        
        <div class="login-side-form">
            <div class="form-box">
                <div class="logo-box">
                    <img src="/assets/img/logo-pipocine.png" alt="PipoCine Logo">
                </div>
                
                <h1>Entrar</h1>
                <p class="subtitle">Use suas credenciais Cineveo para entrar.</p>

                <div id="error-alert" class="error-msg" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span id="error-text"></span>
                </div>

                <form id="login-form" autocomplete="on">
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder=" " required autocomplete="email">
                        <label for="email">E-mail</label>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
                        <label for="password">Senha</label>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="btn-submit">
                        <span id="btn-text">Entrar na Plataforma</span>
                        <span class="loader" id="btn-loader"></span>
                    </button>

                    <!-- Mais opções de autenticação -->
                    <button type="button" class="btn-more-options" id="btn-more-options">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
                        </svg>
                        Mais opções de autenticação
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Modal de autenticação alternativa ─────────────────────────────── -->
    <div class="auth-modal-overlay" id="auth-modal-overlay" role="dialog"
         aria-modal="true" aria-labelledby="auth-modal-title" aria-hidden="true">
        <div class="auth-modal">

            <div class="auth-modal-header">
                <h2 class="auth-modal-title" id="auth-modal-title">Mais opções de acesso</h2>
                <button type="button" class="auth-modal-close" id="auth-modal-close" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab active" role="tab" data-tab="code"
                        aria-selected="true" aria-controls="tab-panel-code" id="tab-code">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M12 2a4 4 0 0 0-4 4v2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-3V6a4 4 0 0 0-4-4z"/>
                        <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                    Código de acesso
                </button>
                <button class="auth-tab" role="tab" data-tab="qr"
                        aria-selected="false" aria-controls="tab-panel-qr" id="tab-qr">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                        <rect x="14" y="14" width="3" height="3"/>
                        <rect x="18" y="18" width="3" height="3"/>
                    </svg>
                    QR Code
                </button>
            </div>

            <!-- Painel: Código de 4 dígitos -->
            <div class="auth-tab-panel active" id="tab-panel-code" role="tabpanel" aria-labelledby="tab-code">
                <p class="code-panel-desc">
                    Digite o código de 4 dígitos vinculado à sua conta para entrar sem senha.
                </p>
                <div class="code-panel-inputs">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-panel-digit" data-idx="0" aria-label="Dígito 1" autocomplete="one-time-code">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-panel-digit" data-idx="1" aria-label="Dígito 2">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-panel-digit" data-idx="2" aria-label="Dígito 3">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-panel-digit" data-idx="3" aria-label="Dígito 4">
                </div>
                <p class="code-panel-hint" id="cp-hint" aria-live="assertive"></p>
                <button type="button" class="btn-code-submit" id="btn-code-login" disabled>
                    Entrar com código
                </button>
            </div>

            <!-- Painel: QR Code -->
            <div class="auth-tab-panel" id="tab-panel-qr" role="tabpanel" aria-labelledby="tab-qr">
                <p class="qr-login-desc">
                    Escaneie o QR Code com seu dispositivo já autenticado no PipoCine.
                </p>

                <!-- idle -->
                <div class="qr-login-area" id="qrl-idle">
                    <div class="qr-login-state-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <button type="button" class="btn-qr-generate" id="qrl-btn-gen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"/>
                                <path d="M3.51 15a9 9 0 1 0 .49-3.85"/>
                            </svg>
                            Gerar QR Code
                        </button>
                    </div>
                </div>

                <!-- active -->
                <div class="qr-login-area" id="qrl-active" style="display:none">
                    <div class="qr-login-frame">
                        <canvas id="qrl-canvas" width="180" height="180" aria-label="QR Code de login"></canvas>
                    </div>
                    <div class="qr-login-timer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Expira em <strong id="qrl-countdown">5:00</strong>
                    </div>
                    <div class="qr-login-progress">
                        <div class="qr-login-progress-fill" id="qrl-fill"></div>
                    </div>
                </div>

                <!-- success -->
                <div class="qr-login-area" id="qrl-success" style="display:none">
                    <p class="qr-login-success">QR Code escaneado! Redirecionando…</p>
                </div>

                <!-- expired -->
                <div class="qr-login-area" id="qrl-expired" style="display:none">
                    <p class="qr-login-expired">QR Code expirado.</p>
                    <button type="button" class="btn-qr-generate" id="qrl-btn-retry">Gerar novo</button>
                </div>

            </div>
        </div>
    </div>

    <script src="/assets/js/login.js"></script>
    <script>
    (function () {
        'use strict';

        // ── Modal abrir/fechar ──────────────────────────────────────────────
        const overlay = document.getElementById('auth-modal-overlay');
        const btnOpen  = document.getElementById('btn-more-options');
        const btnClose = document.getElementById('auth-modal-close');

        function openModal() {
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
            stopQrlAll();
        }

        btnOpen.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        // ── Tabs ────────────────────────────────────────────────────────────
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('.auth-tab-panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
                document.getElementById('tab-panel-' + tab.dataset.tab).classList.add('active');
            });
        });

        // ── Código de 4 dígitos (login) ─────────────────────────────────────
        const cpDigits  = document.querySelectorAll('.code-panel-digit');
        const cpHint    = document.getElementById('cp-hint');
        const btnCodeLg = document.getElementById('btn-code-login');

        function cpGetCode() {
            return Array.from(cpDigits).map(d => d.value).join('');
        }

        cpDigits.forEach((input, i) => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && i > 0) {
                    cpDigits[i - 1].focus();
                    cpDigits[i - 1].value = '';
                    cpDigits[i - 1].classList.remove('filled');
                    e.preventDefault();
                }
            });
            input.addEventListener('input', () => {
                const v = input.value.replace(/\D/g, '');
                input.value = v ? v[v.length - 1] : '';
                input.classList.toggle('filled', !!input.value);
                input.classList.remove('cp-error');
                cpHint.textContent = '';
                cpHint.className = 'code-panel-hint';
                if (input.value && i < 3) cpDigits[i + 1].focus();
                btnCodeLg.disabled = cpGetCode().length < 4;
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const p = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,4);
                p.split('').forEach((c, idx) => {
                    if (cpDigits[idx]) { cpDigits[idx].value = c; cpDigits[idx].classList.add('filled'); }
                });
                btnCodeLg.disabled = cpGetCode().length < 4;
                cpDigits[Math.min(p.length, 3)].focus();
            });
        });

        btnCodeLg.addEventListener('click', async () => {
            const code = cpGetCode();
            btnCodeLg.disabled = true;
            btnCodeLg.textContent = 'Verificando…';
            cpHint.textContent = '';
            cpHint.className = 'code-panel-hint';

            try {
                const res  = await fetch('/api/v3/auth/code/login', {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body   : JSON.stringify({ code }),
                });
                const json = await res.json();

                if (json.success) {
                    cpHint.textContent = 'Acesso confirmado! Redirecionando…';
                    cpHint.className   = 'code-panel-hint ok';
                    setTimeout(() => { window.location.href = '/home'; }, 800);
                } else {
                    cpHint.textContent = json.message || 'Código incorreto.';
                    cpHint.className   = 'code-panel-hint err';
                    cpDigits.forEach(d => d.classList.add('cp-error'));
                    btnCodeLg.disabled = false;
                    btnCodeLg.textContent = 'Entrar com código';
                }
            } catch {
                cpHint.textContent = 'Erro de conexão.';
                cpHint.className   = 'code-panel-hint err';
                btnCodeLg.disabled = false;
                btnCodeLg.textContent = 'Entrar com código';
            }
        });

        // ── QR Code (login) ──────────────────────────────────────────────────
        const qrlIdle    = document.getElementById('qrl-idle');
        const qrlActive  = document.getElementById('qrl-active');
        const qrlSuccess = document.getElementById('qrl-success');
        const qrlExpired = document.getElementById('qrl-expired');
        const qrlCanvas  = document.getElementById('qrl-canvas');
        const qrlCountdown = document.getElementById('qrl-countdown');
        const qrlFill    = document.getElementById('qrl-fill');
        const btnQrlGen  = document.getElementById('qrl-btn-gen');
        const btnQrlRtry = document.getElementById('qrl-btn-retry');

        let qrlToken = null, qrlExpAt = null, qrlTtl = 300;
        let qrlPoll = null, qrlTimer = null;

        function qrlShow(s) {
            qrlIdle.style.display    = s === 'idle'    ? 'flex' : 'none';
            qrlActive.style.display  = s === 'active'  ? 'flex' : 'none';
            qrlSuccess.style.display = s === 'success' ? 'flex' : 'none';
            qrlExpired.style.display = s === 'expired' ? 'flex' : 'none';
        }

        function stopQrlAll() {
            clearInterval(qrlPoll); clearInterval(qrlTimer);
            qrlPoll = qrlTimer = qrlToken = null;
        }

        function loadQRLib() {
            return new Promise((res, rej) => {
                if (window.QRCode) { res(); return; }
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
                s.crossOrigin = 'anonymous';
                s.onload = res; s.onerror = rej;
                document.head.appendChild(s);
            });
        }

        function renderQRLogin(url) {
            if (!window.QRCode) return;
            const tmp = document.createElement('div');
            tmp.style.display = 'none';
            document.body.appendChild(tmp);
            new window.QRCode(tmp, { text: url, width: 180, height: 180, colorDark: '#000', colorLight: '#fff' });
            requestAnimationFrame(() => {
                const cv = tmp.querySelector('canvas');
                if (cv) qrlCanvas.getContext('2d').drawImage(cv, 0, 0);
                tmp.remove();
            });
        }

        async function qrlGenerate() {
            stopQrlAll();
            try {
                await loadQRLib();
                const res  = await fetch('/api/v3/auth/qr/generate', { method: 'POST' });
                const json = await res.json();
                if (!json.success) throw new Error();

                qrlToken = json.token;
                qrlExpAt = new Date(json.expires_at.replace(' ', 'T'));
                qrlTtl   = json.ttl || 300;

                qrlCanvas.getContext('2d').clearRect(0,0,180,180);
                renderQRLogin(json.confirm_url);
                qrlShow('active');

                qrlTimer = setInterval(() => {
                    const diff = Math.max(0, Math.floor((qrlExpAt - new Date()) / 1000));
                    const m = Math.floor(diff / 60), s = diff % 60;
                    qrlCountdown.textContent = m + ':' + String(s).padStart(2, '0');
                    qrlFill.style.width = ((diff / qrlTtl) * 100) + '%';
                    const exp = diff <= 60;
                    qrlCountdown.classList.toggle('exp', exp);
                    qrlFill.classList.toggle('exp', exp);
                    if (diff <= 0) { stopQrlAll(); qrlShow('expired'); }
                }, 1000);

                qrlPoll = setInterval(async () => {
                    if (!qrlToken) { stopQrlAll(); return; }
                    try {
                        const r = await fetch('/api/v3/auth/qr/poll?token=' + encodeURIComponent(qrlToken));
                        const j = await r.json();
                        if (j.status === 'authenticated') {
                            stopQrlAll(); qrlShow('success');
                            setTimeout(() => { window.location.href = j.redirect || '/home'; }, 1200);
                        } else if (j.status === 'expired' || j.status === 'not_found') {
                            stopQrlAll(); qrlShow('expired');
                        }
                    } catch {}
                }, 2000);

            } catch { qrlShow('idle'); }
        }

        btnQrlGen.addEventListener('click', qrlGenerate);
        btnQrlRtry.addEventListener('click', qrlGenerate);
        window.addEventListener('beforeunload', stopQrlAll);

    }());
    </script>
</body>
</html>
