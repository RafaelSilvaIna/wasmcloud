<?php
declare(strict_types=1);

$_deviceLimitData = $_SESSION['device_limit_exceeded'] ?? null;
$_deviceIsBlocked = $_deviceLimitData !== null;
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
    to { opacity: 1; }
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
    to { opacity: 1; transform: translateY(0); }
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
    letter-spacing: 0;
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

<script>
(function () {
    'use strict';

    var HEARTBEAT_INTERVAL = 20000;
    var STATUS_INTERVAL = 5000;
    var isBlocked = <?= $_deviceIsBlocked ? 'true' : 'false' ?>;
    var hbTimer = null;
    var statusTimer = null;

    function sendHeartbeat() {
        return fetch('/api/devices/heartbeat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            if (data && data.allowed === false && !isBlocked) {
                window.location.reload();
            }
            return data;
        }).catch(function () {});
    }

    function releaseCurrentDevice() {
        if (isBlocked) return;

        try {
            if (navigator.sendBeacon) {
                var payload = new Blob(['{}'], { type: 'application/json' });
                navigator.sendBeacon('/api/devices/release', payload);
                return;
            }
        } catch (e) {}

        try {
            fetch('/api/devices/release', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: '{}',
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
    }

    function startHeartbeat() {
        clearInterval(hbTimer);
        sendHeartbeat();
        hbTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
    }

    function checkBlockedStatus() {
        fetch('/api/devices/status', {
            credentials: 'same-origin',
            headers: { 'Cache-Control': 'no-store' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            if (!data || data.allowed !== true) return;

            clearInterval(statusTimer);
            isBlocked = false;

            var overlay = document.getElementById('pip-device-overlay');
            if (overlay) {
                overlay.classList.add('pdm-released');
                setTimeout(function () { overlay.remove(); }, 320);
            }

            startHeartbeat();
        }).catch(function () {});
    }

    if (isBlocked) {
        checkBlockedStatus();
        statusTimer = setInterval(checkBlockedStatus, STATUS_INTERVAL);
    } else {
        startHeartbeat();
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearInterval(hbTimer);
            return;
        }

        if (isBlocked) {
            checkBlockedStatus();
        } else {
            startHeartbeat();
        }
    });

    window.addEventListener('pagehide', releaseCurrentDevice);
    window.addEventListener('beforeunload', releaseCurrentDevice);
})();
</script>
