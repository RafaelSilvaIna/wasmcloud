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
<div id="pip-device-overlay" role="dialog" aria-modal="true" aria-labelledby="pip-device-title">
    <div id="pip-device-card">

        <p class="pdm-label" aria-hidden="true">Acesso restrito</p>
        <h1 class="pdm-title" id="pip-device-title">Esta conta ja esta em uso</h1>
        <p class="pdm-body">Outro dispositivo esta usando esta conta no momento. Assim que ele sair, o acesso sera liberado automaticamente.</p>

        <a href="/select-profile" class="pdm-btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>

    </div>
</div>

<style>
#pip-device-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, .92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    animation: pdm-fade-in .2s ease both;
}
@keyframes pdm-fade-in {
    from { opacity: 0; }
    to   { opacity: 1; }
}

#pip-device-card {
    background: #111113;
    border: 1px solid rgba(255, 255, 255, .07);
    border-radius: 16px;
    padding: 40px 32px;
    max-width: 360px;
    width: 100%;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    animation: pdm-up .25s cubic-bezier(.22, 1, .36, 1) both;
}
@keyframes pdm-up {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.pdm-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #555562;
    margin: 0 0 16px;
}

.pdm-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #f0f0f2;
    margin: 0 0 12px;
    line-height: 1.3;
    letter-spacing: -.01em;
}

.pdm-body {
    font-size: .875rem;
    color: #6b6b7a;
    line-height: 1.65;
    margin: 0 0 32px;
}

.pdm-btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 11px 22px;
    border: 1px solid rgba(255, 255, 255, .12);
    border-radius: 8px;
    color: #c8c8d0;
    font-size: .875rem;
    font-weight: 500;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.pdm-btn-back svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}
.pdm-btn-back:hover {
    background: rgba(255, 255, 255, .06);
    border-color: rgba(255, 255, 255, .2);
    color: #fff;
}

#pip-device-overlay.pdm-released {
    animation: pdm-fade-out .3s ease forwards;
}
@keyframes pdm-fade-out {
    to { opacity: 0; pointer-events: none; }
}

@media (max-width: 480px) {
    #pip-device-card { padding: 32px 20px; }
}
</style>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════════════
     HEARTBEAT JS — Sempre injetado em páginas protegidas
     ════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var HEARTBEAT_INTERVAL = 30000;
    var isBlocked          = <?= $_deviceIsBlocked ? 'true' : 'false' ?>;
    var hbTimer            = null;

    // ── Heartbeat principal ───────────────────────────────────────────────
    function sendHeartbeat() {
        fetch('/api/devices/heartbeat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
        }).catch(function () {});
    }

    // ── Inicializacao ─────────────────────────────────────────────────────
    if (!isBlocked) {
        sendHeartbeat();
        hbTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
    }

    // ── Release ao sair — sendBeacon e instantaneo (melhor esforco) ──────
    // pagehide dispara ao fechar a aba/navegar, antes de qualquer unload.
    // beforeunload e mantido como fallback para browsers mais antigos.
    function releaseDevice() {
        clearInterval(hbTimer);
        navigator.sendBeacon('/api/devices/release');
    }

    window.addEventListener('pagehide', releaseDevice);
    window.addEventListener('beforeunload', releaseDevice);

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
