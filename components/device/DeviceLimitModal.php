<?php
/**
 * components/device/DeviceLimitModal.php
 *
 * Componente visual de bloqueio por limite de dispositivos simultâneos.
 *
 * Renderização automática (app-wide):
 *   Este componente é injetado globalmente pelo DeviceHook via output
 *   buffering (ob_start). Não é necessário incluí-lo manualmente em
 *   nenhuma página — o hook captura toda a saída HTML e insere este
 *   componente imediatamente antes do </body> em todas as páginas
 *   protegidas automaticamente.
 *
 * O componente verifica automaticamente a sessão e:
 *   - Se não houver `$_SESSION['device_limit_exceeded']`, não renderiza
 *     nenhum HTML visível — apenas injeta o script de heartbeat silencioso.
 *   - Se houver, renderiza o modal de bloqueio com:
 *       • Overlay de tela cheia com desfoque
 *       • Animação de entrada suave
 *       • Contador de dispositivos ativos / limite
 *       • Botão de upgrade para o Plano Gold
 *       • Polling automático via JS (a cada 5s) para detectar liberação de vaga
 *       • Ao detectar liberação, oculta o modal sem recarregar a página
 *
 * O heartbeat JS é sempre injetado (independente do bloqueio) para manter
 * o dispositivo ativo enquanto a página estiver aberta.
 */

declare(strict_types=1);

$_deviceLimitData = $_SESSION['device_limit_exceeded'] ?? null;
$_deviceIsBlocked = $_deviceLimitData !== null;
$_deviceActive    = (int) ($_deviceLimitData['active'] ?? 0);
$_deviceLimit     = (int) ($_deviceLimitData['limit']  ?? 1);
$_deviceIsGold    = (bool) ($_deviceLimitData['is_gold'] ?? false);
?>

<?php if ($_deviceIsBlocked): ?>
<!-- ════════════════════════════════════════════════════════════════════════
     DEVICE LIMIT MODAL — Pipocine
     ════════════════════════════════════════════════════════════════════════ -->
<div id="pip-device-overlay" role="dialog" aria-modal="true" aria-labelledby="pip-device-title">

    <div id="pip-device-card">

        <!-- Ícone animado -->
        <div class="pdm-icon-wrap" aria-hidden="true">
            <div class="pdm-icon-ring pdm-ring-1"></div>
            <div class="pdm-icon-ring pdm-ring-2"></div>
            <div class="pdm-icon-center">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                    <path d="M9 8l3 3 3-3"/>
                </svg>
            </div>
        </div>

        <!-- Cabeçalho -->
        <h1 class="pdm-title" id="pip-device-title">Limite de dispositivos atingido</h1>
        <p class="pdm-subtitle">
            Sua conta está sendo usada em
            <strong><?= $_deviceActive ?>&nbsp;<?= $_deviceActive === 1 ? 'dispositivo' : 'dispositivos' ?></strong>
            simultaneamente. O limite do seu plano atual é
            <strong><?= $_deviceLimit ?>&nbsp;<?= $_deviceLimit === 1 ? 'dispositivo' : 'dispositivos' ?></strong>.
        </p>

        <!-- Barra de status -->
        <div class="pdm-status-bar" aria-label="Aguardando liberação de vaga">
            <div class="pdm-status-dot"></div>
            <span class="pdm-status-text">Aguardando outro dispositivo encerrar...</span>
            <div class="pdm-status-loader">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Dispositivos visuais -->
        <div class="pdm-devices" aria-hidden="true">
            <?php for ($i = 0; $i < min($_deviceActive, 4); $i++): ?>
            <div class="pdm-device-chip pdm-chip-occupied">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                </svg>
            </div>
            <?php endfor; ?>
            <div class="pdm-device-chip pdm-chip-waiting">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4l3 3"/>
                </svg>
            </div>
        </div>

        <!-- CTA de upgrade (só aparece para plano gratuito) -->
        <?php if (!$_deviceIsGold): ?>
        <a href="/plan" class="pdm-btn-upgrade">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            Assinar Plano Gold — 4 dispositivos
        </a>
        <p class="pdm-upgrade-hint">Acesso simultâneo em até 4 telas por apenas R$&nbsp;20,90/mês</p>
        <?php endif; ?>

        <!-- Nota de espera automática -->
        <p class="pdm-wait-note">
            Assim que um dispositivo encerrar, o acesso será liberado automaticamente.
        </p>

    </div>
</div>

<style>
/* ─── Reset e base ─────────────────────────────────────────────────────────── */
#pip-device-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, .88);
    backdrop-filter: blur(20px) saturate(1.4);
    -webkit-backdrop-filter: blur(20px) saturate(1.4);
    animation: pdm-fade-in .35s cubic-bezier(.4, 0, .2, 1) both;
}

@keyframes pdm-fade-in {
    from { opacity: 0; }
    to   { opacity: 1; }
}

/* ─── Card principal ───────────────────────────────────────────────────────── */
#pip-device-card {
    background: linear-gradient(160deg, #111113 0%, #0d0d0f 100%);
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 20px;
    padding: 40px 32px 36px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, .04),
        0 24px 80px rgba(0, 0, 0, .7),
        0 8px 24px rgba(0, 0, 0, .5);
    animation: pdm-slide-up .4s cubic-bezier(.34, 1.56, .64, 1) both;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

@keyframes pdm-slide-up {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ─── Ícone animado ────────────────────────────────────────────────────────── */
.pdm-icon-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 28px;
}

.pdm-icon-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 1.5px solid rgba(229, 9, 20, .25);
    animation: pdm-pulse 2.4s ease-in-out infinite;
}
.pdm-ring-2 {
    inset: -10px;
    border-color: rgba(229, 9, 20, .12);
    animation-delay: .4s;
}
@keyframes pdm-pulse {
    0%, 100% { opacity: .6; transform: scale(1); }
    50%       { opacity: .2; transform: scale(1.06); }
}

.pdm-icon-center {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(229, 9, 20, .1);
    border: 1px solid rgba(229, 9, 20, .2);
    border-radius: 50%;
}
.pdm-icon-center svg {
    width: 34px;
    height: 34px;
    color: #e50914;
}

/* ─── Texto ────────────────────────────────────────────────────────────────── */
.pdm-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 12px;
    letter-spacing: -.01em;
    line-height: 1.3;
}

.pdm-subtitle {
    font-size: .9rem;
    color: #8e8e9a;
    line-height: 1.6;
    margin: 0 0 24px;
}
.pdm-subtitle strong {
    color: #c8c8d0;
    font-weight: 600;
}

/* ─── Barra de status ──────────────────────────────────────────────────────── */
.pdm-status-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(255, 255, 255, .04);
    border: 1px solid rgba(255, 255, 255, .07);
    border-radius: 10px;
    padding: 10px 16px;
    margin-bottom: 24px;
}

.pdm-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f59e0b;
    flex-shrink: 0;
    animation: pdm-blink 1.4s ease-in-out infinite;
}
@keyframes pdm-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .3; }
}

.pdm-status-text {
    font-size: .8rem;
    color: #6b6b7a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Loader de três bolinhas */
.pdm-status-loader {
    display: flex;
    gap: 3px;
    flex-shrink: 0;
}
.pdm-status-loader span {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #4b4b5a;
    animation: pdm-bounce .9s ease-in-out infinite;
}
.pdm-status-loader span:nth-child(2) { animation-delay: .15s; }
.pdm-status-loader span:nth-child(3) { animation-delay: .3s; }
@keyframes pdm-bounce {
    0%, 80%, 100% { transform: scale(.6); opacity: .4; }
    40%           { transform: scale(1); opacity: 1; }
}

/* ─── Chips de dispositivos ────────────────────────────────────────────────── */
.pdm-devices {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.pdm-device-chip {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border: 1px solid transparent;
    transition: transform .2s;
}
.pdm-device-chip svg { width: 22px; height: 22px; }

.pdm-chip-occupied {
    background: rgba(229, 9, 20, .12);
    border-color: rgba(229, 9, 20, .25);
    color: #e50914;
}
.pdm-chip-waiting {
    background: rgba(245, 158, 11, .1);
    border-color: rgba(245, 158, 11, .22);
    color: #f59e0b;
    animation: pdm-pulse-chip 1.8s ease-in-out infinite;
}
@keyframes pdm-pulse-chip {
    0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .25); }
    50%       { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
}

/* ─── Botão de upgrade ─────────────────────────────────────────────────────── */
.pdm-btn-upgrade {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, #e50914 0%, #c0060f 100%);
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    letter-spacing: .01em;
    border-radius: 12px;
    text-decoration: none;
    margin-bottom: 10px;
    transition: filter .2s, transform .15s;
    box-shadow: 0 4px 20px rgba(229, 9, 20, .35);
}
.pdm-btn-upgrade svg { width: 16px; height: 16px; flex-shrink: 0; }
.pdm-btn-upgrade:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
}
.pdm-btn-upgrade:active { transform: translateY(0); }

.pdm-upgrade-hint {
    font-size: .78rem;
    color: #555562;
    margin: 0 0 20px;
}

/* ─── Nota de espera ───────────────────────────────────────────────────────── */
.pdm-wait-note {
    font-size: .78rem;
    color: #44444f;
    margin: 0;
    line-height: 1.5;
}

/* ─── Estado liberado ──────────────────────────────────────────────────────── */
#pip-device-overlay.pdm-released {
    animation: pdm-fade-out .4s ease forwards;
}
@keyframes pdm-fade-out {
    to { opacity: 0; pointer-events: none; }
}

/* ─── Responsivo ───────────────────────────────────────────────────────────── */
@media (max-width: 480px) {
    #pip-device-card {
        padding: 32px 20px 28px;
        border-radius: 16px;
    }
    .pdm-title { font-size: 1.1rem; }
    .pdm-status-text { display: none; }
}
</style>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════════════
     HEARTBEAT JS — Sempre injetado em páginas protegidas
     ════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var HEARTBEAT_INTERVAL = 30000; // 30 segundos
    var POLL_INTERVAL      = 5000;  // 5 segundos (quando bloqueado)
    var isBlocked          = <?= $_deviceIsBlocked ? 'true' : 'false' ?>;
    var overlay            = document.getElementById('pip-device-overlay');
    var hbTimer            = null;
    var pollTimer          = null;

    // ── Heartbeat principal ───────────────────────────────────────────────
    function sendHeartbeat() {
        fetch('/api/devices/heartbeat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;

            if (data.allowed && isBlocked && overlay) {
                // Acesso liberado: oculta o modal com animação
                overlay.classList.add('pdm-released');
                isBlocked = false;
                clearInterval(pollTimer);
                setTimeout(function () {
                    if (overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 420);
            }
        })
        .catch(function () { /* silencioso */ });
    }

    // ── Polling de verificação de vaga (quando bloqueado) ─────────────────
    function startPolling() {
        if (!isBlocked) return;
        pollTimer = setInterval(function () {
            fetch('/api/devices/status', {
                method: 'GET',
                credentials: 'same-origin',
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;

                if (data.allowed && isBlocked && overlay) {
                    // Vaga liberada → tenta registrar via heartbeat
                    isBlocked = false;
                    clearInterval(pollTimer);
                    sendHeartbeat();
                }
            })
            .catch(function () { /* silencioso */ });
        }, POLL_INTERVAL);
    }

    // ── Inicialização ─────────────────────────────────────────────────────
    if (!isBlocked) {
        // Dispositivo ativo: envia heartbeat imediato e agenda periódico
        sendHeartbeat();
        hbTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
    } else {
        // Dispositivo bloqueado: inicia polling para aguardar liberação
        startPolling();
    }

    // ── Libera dispositivo ao fechar a aba ───────────────────────────────
    window.addEventListener('beforeunload', function () {
        clearInterval(hbTimer);
        clearInterval(pollTimer);
        navigator.sendBeacon('/api/devices/release');
    });

    // ── Pausa/retoma heartbeat com visibilidade da aba ───────────────────
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearInterval(hbTimer);
        } else {
            sendHeartbeat();
            if (!isBlocked) {
                hbTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
            }
        }
    });
})();
</script>
