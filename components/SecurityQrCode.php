<?php
/**
 * COMPONENTE: SecurityQrCode
 *
 * Seção "Login via QR Code" dentro do painel de Segurança.
 * Explica o funcionamento e exibe um QR Code de demonstração/uso.
 * CSS e JS são injetados inline (componente auto-contido).
 *
 * Uso:
 *   require_once __DIR__ . '/../components/SecurityQrCode.php';
 *   SecurityQrCode::render();
 */

declare(strict_types=1);

class SecurityQrCode
{
    public static function render(): void
    {
        ?>
<!-- ════════════════════════════════════════════════════════════════════
     COMPONENTE: Login via QR Code
     ═══════════════════════════════════════════════════════════════════ -->
<div class="sec-card" id="sec-qr-card">

    <!-- Cabeçalho -->
    <div class="sec-card-header">
        <div class="sec-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
                <rect x="14" y="14" width="3" height="3"/>
                <rect x="18" y="14" width="3" height="3"/>
                <rect x="14" y="18" width="3" height="3"/>
                <rect x="18" y="18" width="3" height="3"/>
            </svg>
        </div>
        <div class="sec-card-title-group">
            <h2 class="sec-card-title">Login via QR Code</h2>
            <p class="sec-card-desc">
                Escaneie o QR Code com seu dispositivo já autenticado no PipoCine para entrar automaticamente.
            </p>
        </div>
        <div class="sec-card-badge badge-active">
            <span class="badge-dot" style="background:#ffd60a"></span>
            <span class="badge-label" style="color:#ffd60a">Disponível</span>
        </div>
    </div>

    <!-- Corpo -->
    <div class="sec-card-body">

        <!-- Como funciona -->
        <div class="qr-how-it-works">
            <p class="qr-section-label">Como funciona</p>
            <ol class="qr-steps">
                <li>
                    <span class="qr-step-num">1</span>
                    <span>Na página de login, clique em <strong>Mais opções</strong> e selecione <strong>Entrar com QR Code</strong>.</span>
                </li>
                <li>
                    <span class="qr-step-num">2</span>
                    <span>Um QR Code será exibido e ficará válido por <strong>5 minutos</strong>.</span>
                </li>
                <li>
                    <span class="qr-step-num">3</span>
                    <span>Com seu celular já logado no PipoCine, escaneie o QR Code.</span>
                </li>
                <li>
                    <span class="qr-step-num">4</span>
                    <span>A sessão é iniciada automaticamente. O QR Code é invalidado após o uso.</span>
                </li>
            </ol>
        </div>

        <!-- QR Code demo / geração -->
        <div class="qr-demo-section">
            <p class="qr-section-label">Testar agora</p>
            <p class="qr-demo-desc">
                Gere um QR Code de teste para verificar se o seu dispositivo consegue escanear e autenticar corretamente.
            </p>

            <!-- Área do QR Code -->
            <div class="qr-area" id="qr-area">

                <!-- Estado: idle -->
                <div class="qr-state" id="qr-idle">
                    <div class="qr-placeholder" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </div>
                    <button type="button" class="sec-btn sec-btn-primary" id="qr-btn-generate">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 .49-3.85"/>
                        </svg>
                        Gerar QR Code
                    </button>
                </div>

                <!-- Estado: QR ativo -->
                <div class="qr-state" id="qr-active" hidden>
                    <div class="qr-frame" id="qr-frame">
                        <!-- QR Code SVG gerado pela lib qrcode.js -->
                        <canvas id="qr-canvas" width="200" height="200" aria-label="QR Code para autenticação"></canvas>
                    </div>
                    <div class="qr-meta">
                        <div class="qr-timer-row">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 width="14" height="14" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span>Expira em <strong id="qr-countdown">5:00</strong></span>
                        </div>
                        <button type="button" class="sec-btn sec-btn-ghost qr-btn-small" id="qr-btn-renew">
                            Gerar novo
                        </button>
                    </div>
                    <!-- Barra de progresso -->
                    <div class="qr-progress-bar">
                        <div class="qr-progress-fill" id="qr-progress-fill"></div>
                    </div>
                </div>

                <!-- Estado: autenticado -->
                <div class="qr-state" id="qr-success" hidden>
                    <div class="qr-success-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <p class="qr-success-text">QR Code escaneado com sucesso!</p>
                    <button type="button" class="sec-btn sec-btn-ghost qr-btn-small" id="qr-btn-new-after-success">
                        Gerar outro
                    </button>
                </div>

                <!-- Estado: expirado -->
                <div class="qr-state" id="qr-expired" hidden>
                    <div class="qr-expired-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <p class="qr-expired-text">QR Code expirado.</p>
                    <button type="button" class="sec-btn sec-btn-primary qr-btn-small" id="qr-btn-retry">
                        Gerar novo
                    </button>
                </div>

            </div><!-- /.qr-area -->
        </div><!-- /.qr-demo-section -->

    </div><!-- /.sec-card-body -->
</div><!-- /#sec-qr-card -->

<!-- ════════════════════════════════════════════════════════════════════
     CSS DO COMPONENTE
     ═══════════════════════════════════════════════════════════════════ -->
<style>
/* ── Garante que [hidden] não seja sobrescrito pelo display:flex dos estados ── */
#sec-qr-card [hidden] {
    display: none !important;
}

/* ── Como funciona ───────────────────────────────────────────────────── */
.qr-section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--set-text-muted, #4a5568);
    margin: 0 0 12px;
}

.qr-how-it-works {
    margin-bottom: 28px;
}

.qr-steps {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.qr-steps li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 13.5px;
    color: var(--set-text-secondary, #94a3b8);
    line-height: 1.55;
}

.qr-step-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background-color: var(--set-accent-dim, rgba(255,214,10,0.1));
    color: var(--set-accent, #ffd60a);
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.qr-steps li strong {
    color: var(--set-text-primary, #e2e8f0);
    font-weight: 600;
}

/* ── Área do QR Code ─────────────────────────────────────────────────── */
.qr-demo-desc {
    font-size: 13px;
    color: var(--set-text-secondary, #94a3b8);
    line-height: 1.55;
    margin: 0 0 16px;
}

.qr-area {
    background-color: var(--set-elevated, #1a1e28);
    border: 1px solid var(--set-border, rgba(255,255,255,0.07));
    border-radius: var(--set-radius, 10px);
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

/* Estados */
.qr-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    width: 100%;
}

/* Placeholder */
.qr-placeholder {
    width: 80px;
    height: 80px;
    opacity: 0.12;
}
.qr-placeholder svg {
    width: 100%;
    height: 100%;
    color: var(--set-text-primary, #e2e8f0);
}

/* QR frame */
.qr-frame {
    padding: 12px;
    background-color: #fff;
    border-radius: 10px;
    line-height: 0;
}
.qr-frame canvas {
    display: block;
    border-radius: 4px;
}

/* Meta / countdown */
.qr-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
}

.qr-timer-row {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12.5px;
    color: var(--set-text-secondary, #94a3b8);
}
.qr-timer-row svg { color: var(--set-text-muted, #4a5568); }
.qr-timer-row strong { color: var(--set-text-primary, #e2e8f0); }
.qr-timer-row strong.expiring-soon { color: #e50914; }

.qr-btn-small {
    padding: 6px 12px;
    font-size: 12.5px;
}

/* Barra de progresso */
.qr-progress-bar {
    width: 200px;
    max-width: 100%;
    height: 3px;
    border-radius: 2px;
    background-color: var(--set-border, rgba(255,255,255,0.07));
    overflow: hidden;
}
.qr-progress-fill {
    height: 100%;
    background-color: var(--set-accent, #ffd60a);
    width: 100%;
    transition: width 1s linear, background-color 0.3s;
    transform-origin: left;
}
.qr-progress-fill.expiring { background-color: #e50914; }

/* Sucesso */
.qr-success-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background-color: rgba(16,185,129,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #10b981;
    animation: qr-pop 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
.qr-success-icon svg { width: 28px; height: 28px; }

@keyframes qr-pop {
    from { transform: scale(0.5); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.qr-success-text {
    font-size: 14px;
    font-weight: 500;
    color: #10b981;
    margin: 0;
}

/* Expirado */
.qr-expired-icon {
    width: 52px; height: 52px;
    border-radius: 50%;
    background-color: rgba(229,9,20,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e50914;
}
.qr-expired-icon svg { width: 26px; height: 26px; }

.qr-expired-text {
    font-size: 13.5px;
    color: var(--set-text-secondary, #94a3b8);
    margin: 0;
}

@media (max-width: 480px) {
    .qr-frame canvas { width: 160px !important; height: 160px !important; }
}
</style>

<!-- ════════════════════════════════════════════════════════════════════
     JS DO COMPONENTE
     Depende: qrcode.min.js (carregado dinamicamente via ES module)
     ═══════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // ── Elementos ───────────────────────────────────────────────────────────
    const stateIdle    = document.getElementById('qr-idle');
    const stateActive  = document.getElementById('qr-active');
    const stateSuccess = document.getElementById('qr-success');
    const stateExpired = document.getElementById('qr-expired');

    const btnGenerate  = document.getElementById('qr-btn-generate');
    const btnRenew     = document.getElementById('qr-btn-renew');
    const btnRetry     = document.getElementById('qr-btn-retry');
    const btnNewAfter  = document.getElementById('qr-btn-new-after-success');
    const canvas       = document.getElementById('qr-canvas');
    const countdown    = document.getElementById('qr-countdown');
    const progressFill = document.getElementById('qr-progress-fill');

    // ── Estado ──────────────────────────────────────────────────────────────
    let currentToken    = null;
    let expiresAt       = null;
    let pollInterval    = null;
    let countdownTimer  = null;
    let totalTtl        = 300;

    // ── Mostrar estado ───────────────────────────────────────────────────────
    function showState(name) {
        stateIdle.hidden    = name !== 'idle';
        stateActive.hidden  = name !== 'active';
        stateSuccess.hidden = name !== 'success';
        stateExpired.hidden = name !== 'expired';
    }

    // ── Carregar qrcode.js dinamicamente ────────────────────────────────────
    function loadQRLib() {
        return new Promise((resolve, reject) => {
            if (window.QRCode) { resolve(); return; }

            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
            script.crossOrigin = 'anonymous';
            script.onload  = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // ── Renderizar QR Code no canvas ─────────────────────────────────────────
    function renderQR(url) {
        if (!window.QRCode) return;

        // Limpa canvas anterior
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Remove QRCode anterior se existir
        const old = document.getElementById('qr-canvas-inner');
        if (old) old.remove();

        // Cria div temporária para QRCode (ele usa innerHTML)
        const tmp = document.createElement('div');
        tmp.id = 'qr-canvas-inner';
        tmp.style.display = 'none';
        document.body.appendChild(tmp);

        new window.QRCode(tmp, {
            text       : url,
            width      : 200,
            height     : 200,
            colorDark  : '#000000',
            colorLight : '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.M,
        });

        // Aguarda o QR ser renderizado e copia para nosso canvas
        requestAnimationFrame(() => {
            const img = tmp.querySelector('img');
            const qrCanvas = tmp.querySelector('canvas');

            if (qrCanvas) {
                const ctx2 = canvas.getContext('2d');
                ctx2.drawImage(qrCanvas, 0, 0);
            } else if (img) {
                img.onload = () => {
                    const ctx2 = canvas.getContext('2d');
                    ctx2.drawImage(img, 0, 0, 200, 200);
                };
                img.src = img.src; // trigger onload if already loaded
            }
            tmp.remove();
        });
    }

    // ── Gerar QR Code ────────────────────────────────────────────────────────
    async function generateQr() {
        stopAll();
        btnGenerate.disabled = true;
        btnGenerate.querySelector('span, svg') && null;

        try {
            await loadQRLib();

            const res  = await fetch('/api/v3/auth/qr/generate', { method: 'POST' });
            const json = await res.json();

            if (!json.success) throw new Error(json.message || 'Erro');

            currentToken = json.token;
            expiresAt    = new Date(json.expires_at.replace(' ', 'T'));
            totalTtl     = json.ttl || 300;

            renderQR(json.confirm_url);
            showState('active');
            startCountdown();
            startPolling();

        } catch (err) {
            showState('idle');
        } finally {
            btnGenerate.disabled = false;
        }
    }

    // ── Countdown ─────────────────────────────────────────────────────────────
    function startCountdown() {
        updateCountdown();
        countdownTimer = setInterval(updateCountdown, 1000);
    }

    function updateCountdown() {
        const now  = new Date();
        const diff = Math.max(0, Math.floor((expiresAt - now) / 1000));
        const m    = Math.floor(diff / 60);
        const s    = diff % 60;

        countdown.textContent = m + ':' + String(s).padStart(2, '0');

        const ratio = diff / totalTtl;
        progressFill.style.width = (ratio * 100) + '%';

        const expiring = diff <= 60;
        countdown.classList.toggle('expiring-soon', expiring);
        progressFill.classList.toggle('expiring', expiring);

        if (diff <= 0) {
            stopAll();
            showState('expired');
        }
    }

    // ── Polling ───────────────────────────────────────────────────────────────
    function startPolling() {
        pollInterval = setInterval(pollStatus, 2000);
    }

    async function pollStatus() {
        if (!currentToken) { stopAll(); return; }

        try {
            const res  = await fetch('/api/v3/auth/qr/poll?token=' + encodeURIComponent(currentToken));
            const json = await res.json();

            if (json.status === 'authenticated') {
                stopAll();
                showState('success');
                // A sessão já foi iniciada no servidor — redireciona após breve feedback
                setTimeout(() => { window.location.href = json.redirect || '/home'; }, 1500);

            } else if (json.status === 'expired' || json.status === 'not_found') {
                stopAll();
                showState('expired');
            }
            // 'pending' — continua a fazer polling
        } catch { /* ignora erros de rede transitórios */ }
    }

    // ── Limpar timers ─────────────────────────────────────────────────────────
    function stopAll() {
        clearInterval(pollInterval);
        clearInterval(countdownTimer);
        pollInterval = countdownTimer = null;
        currentToken = null;
    }

    // ── Eventos de botões ────────────────────────────────────────────────────
    btnGenerate.addEventListener('click', generateQr);
    btnRenew.addEventListener('click', generateQr);
    btnRetry.addEventListener('click', generateQr);
    btnNewAfter.addEventListener('click', () => { showState('idle'); });

    // Limpa ao sair da página
    window.addEventListener('beforeunload', stopAll);

    // ── Init ─────────────────────────────────────────────────────────────────
    showState('idle');
}());
</script>
<?php
    }
}
