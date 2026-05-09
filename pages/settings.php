<?php
require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pinValidated = $_SESSION['pin_validated'] ?? false;
$pinValidateTime = $_SESSION['pin_validate_time'] ?? 0;
$isValid = $pinValidated && (time() - $pinValidateTime) < 300;

if (!$isValid) {
    unset($_SESSION['pin_validated'], $_SESSION['pin_validate_time']);
    header('Location: /select-profile?needs_pin=1');
    exit;
}

unset($_SESSION['pin_validated'], $_SESSION['pin_validate_time']);

$displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Conta Pipocine';
$displayIdentifier = $_SESSION['user_email'] ?? $_SESSION['user_phone'] ?? $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0b0d">
    <title>PipoCine - Configuracoes</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <style>
        :root {
            --bg: #0b0b0d;
            --panel: #141416;
            --panel-soft: #19191c;
            --line: rgba(255,255,255,.09);
            --line-strong: rgba(255,255,255,.16);
            --text: #fff;
            --muted: #9ca3af;
            --muted-2: #6b7280;
            --accent: #e50914;
            --green: #22c55e;
            --yellow: #f59e0b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(229,9,20,.08), transparent 34%),
                var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        button, input { font: inherit; }

        .shell {
            margin: 0 auto;
            max-width: 1120px;
            padding: 28px 22px 72px;
        }

        .topbar {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 34px;
        }

        .back-link {
            align-items: center;
            color: var(--muted);
            display: inline-flex;
            gap: 8px;
            font-size: .92rem;
            font-weight: 650;
            text-decoration: none;
        }

        .back-link:hover { color: var(--text); }

        .page-label {
            color: var(--muted-2);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .hero {
            align-items: end;
            border-bottom: 1px solid var(--line);
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr auto;
            margin-bottom: 34px;
            padding-bottom: 30px;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 4rem);
            letter-spacing: 0;
            line-height: 1.02;
            margin: 10px 0 12px;
        }

        .hero p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.5;
            margin: 0;
        }

        .account-pill {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            padding: 10px 14px;
            white-space: nowrap;
        }

        .grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .section {
            display: grid;
            gap: 12px;
        }

        .section.wide { grid-column: 1 / -1; }

        .section-title {
            color: var(--muted-2);
            font-size: .78rem;
            font-weight: 850;
            letter-spacing: .08em;
            margin: 12px 0 0;
            text-transform: uppercase;
        }

        .card {
            background: linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.02)), var(--panel);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .row {
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            min-height: 78px;
            padding: 18px;
        }

        .row:last-child { border-bottom: 0; }

        .row h3 {
            font-size: .98rem;
            font-weight: 760;
            margin: 0 0 5px;
        }

        .row p {
            color: var(--muted);
            font-size: .86rem;
            line-height: 1.45;
            margin: 0;
        }

        .actions {
            align-items: center;
            display: flex;
            flex: 0 0 auto;
            gap: 10px;
        }

        .btn {
            align-items: center;
            background: rgba(255,255,255,.06);
            border: 1px solid var(--line-strong);
            border-radius: 6px;
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            font-size: .86rem;
            font-weight: 760;
            justify-content: center;
            min-height: 38px;
            padding: 0 14px;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }

        .btn:hover { background: rgba(255,255,255,.1); }
        .btn.primary { background: var(--accent); border-color: var(--accent); }
        .btn.danger { border-color: rgba(229,9,20,.55); color: #ffb4b8; }
        .btn.ghost { background: transparent; }

        .badge {
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            display: inline-flex;
            font-size: .74rem;
            font-weight: 800;
            gap: 7px;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .dot {
            background: var(--muted-2);
            border-radius: 50%;
            height: 7px;
            width: 7px;
        }

        .badge.enabled { color: #bbf7d0; border-color: rgba(34,197,94,.28); }
        .badge.enabled .dot { background: var(--green); }
        .badge.warning { color: #fde68a; border-color: rgba(245,158,11,.28); }
        .badge.warning .dot { background: var(--yellow); }

        .toggle {
            background: rgba(255,255,255,.16);
            border: 0;
            border-radius: 999px;
            cursor: pointer;
            height: 28px;
            padding: 3px;
            position: relative;
            transition: background .18s ease;
            width: 52px;
        }

        .toggle::after {
            background: #fff;
            border-radius: 50%;
            content: '';
            display: block;
            height: 22px;
            transition: transform .18s ease;
            width: 22px;
        }

        .toggle.active { background: var(--green); }
        .toggle.active::after { transform: translateX(24px); }

        .subgrid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr 1fr;
            padding: 16px;
        }

        .subcard {
            background: rgba(0,0,0,.18);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 8px;
            min-height: 180px;
            padding: 14px;
        }

        .subcard h4 {
            font-size: .88rem;
            margin: 0 0 12px;
        }

        .list {
            display: grid;
            gap: 8px;
            max-height: 270px;
            overflow: auto;
        }

        .list-item {
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 0;
        }

        .list-item:last-child { border-bottom: 0; }

        .list-item strong {
            display: block;
            font-size: .86rem;
            margin-bottom: 3px;
        }

        .list-item small {
            color: var(--muted);
            display: block;
            font-size: .76rem;
            line-height: 1.4;
        }

        .empty {
            color: var(--muted-2);
            font-size: .84rem;
            padding: 12px 0;
        }

        .modal-layer {
            align-items: center;
            background: rgba(0,0,0,.76);
            display: none;
            inset: 0;
            justify-content: center;
            padding: 20px;
            position: fixed;
            z-index: 20000;
        }

        .modal-layer.open { display: flex; }

        .modal {
            background: #161618;
            border: 1px solid var(--line-strong);
            border-radius: 12px;
            box-shadow: 0 30px 90px rgba(0,0,0,.62);
            max-width: 390px;
            padding: 26px;
            width: 100%;
        }

        .modal h2 {
            font-size: 1.22rem;
            margin: 0 0 8px;
        }

        .modal p {
            color: var(--muted);
            font-size: .9rem;
            line-height: 1.5;
            margin: 0 0 18px;
        }

        .pin-inputs {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, 1fr);
            margin: 18px 0 12px;
        }

        .pin-inputs input {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            color: #fff;
            font-size: 1.4rem;
            height: 58px;
            outline: none;
            text-align: center;
        }

        .pin-inputs input:focus { border-color: #fff; }

        .modal-error {
            color: #ffb4b8;
            font-size: .82rem;
            min-height: 18px;
        }

        .modal-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr;
            margin-top: 18px;
        }

        .toast {
            background: #f8fafc;
            border-radius: 8px;
            bottom: 20px;
            color: #0f172a;
            display: none;
            font-size: .9rem;
            font-weight: 750;
            left: 50%;
            max-width: min(92vw, 520px);
            padding: 12px 14px;
            position: fixed;
            transform: translateX(-50%);
            z-index: 30000;
        }

        .toast.show { display: block; }

        @media (max-width: 860px) {
            .hero, .grid, .subgrid { grid-template-columns: 1fr; }
            .hero { align-items: start; }
            .account-pill { white-space: normal; }
        }

        @media (max-width: 560px) {
            .shell { padding: 20px 14px 56px; }
            .row { align-items: flex-start; flex-direction: column; }
            .actions { flex-wrap: wrap; width: 100%; }
            .btn { flex: 1; }
            .modal-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <nav class="topbar">
            <a href="/select-profile" class="back-link">
                <span aria-hidden="true">←</span>
                Voltar
            </a>
            <span class="page-label">Configuracoes</span>
        </nav>

        <header class="hero">
            <div>
                <span class="page-label">Seguranca da conta</span>
                <h1>Controle seus acessos.</h1>
                <p>Gerencie PIN, verificacao em duas etapas, login por QR Code e dispositivos conectados.</p>
            </div>
            <div class="account-pill"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?><?= $displayIdentifier ? ' · ' . htmlspecialchars($displayIdentifier, ENT_QUOTES, 'UTF-8') : '' ?></div>
        </header>

        <div class="grid">
            <section class="section">
                <h2 class="section-title">Protecao</h2>
                <div class="card">
                    <div class="row">
                        <div>
                            <h3>PIN de seguranca</h3>
                            <p id="pin-copy">Use um PIN para proteger areas sensiveis.</p>
                        </div>
                        <div class="actions">
                            <span id="pin-badge" class="badge"><span class="dot"></span> Carregando</span>
                            <button class="btn" id="btn-pin-create">Criar</button>
                            <button class="btn" id="btn-pin-change">Alterar</button>
                            <button class="btn danger" id="btn-pin-remove">Remover</button>
                        </div>
                    </div>
                    <div class="row">
                        <div>
                            <h3>Verificacao em duas etapas</h3>
                            <p>Adicione codigos do autenticador ao login da sua conta.</p>
                        </div>
                        <div class="actions">
                            <span id="twofa-badge" class="badge"><span class="dot"></span> Carregando</span>
                            <button class="btn primary" id="btn-2fa">Ativar</button>
                        </div>
                    </div>
                    <div class="row" id="backup-row" style="display:none;">
                        <div>
                            <h3>Codigos de backup</h3>
                            <p>Gere novos codigos para recuperar acesso sem autenticador.</p>
                        </div>
                        <div class="actions">
                            <button class="btn" id="btn-backup">Regenerar</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Login por QR Code</h2>
                <div class="card">
                    <div class="row">
                        <div>
                            <h3>Permitir QR Code</h3>
                            <p>Autorize novos dispositivos escaneando um QR Code com uma sessao ativa.</p>
                        </div>
                        <div class="actions">
                            <span id="qr-badge" class="badge"><span class="dot"></span> Carregando</span>
                            <button class="toggle" id="qr-toggle" aria-label="Alternar QR Code"></button>
                        </div>
                    </div>
                    <div class="subgrid">
                        <div class="subcard">
                            <h4>Dispositivos conectados</h4>
                            <div class="list" id="qr-devices"></div>
                        </div>
                        <div class="subcard">
                            <h4>Logs recentes</h4>
                            <div class="list" id="qr-logs"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section wide">
                <h2 class="section-title">Dispositivos confiaveis 2FA</h2>
                <div class="card">
                    <div class="row">
                        <div>
                            <h3>Dispositivos lembrados</h3>
                            <p id="trusted-copy">Dispositivos que podem entrar sem solicitar codigo 2FA.</p>
                        </div>
                        <div class="actions">
                            <button class="btn" id="btn-refresh">Atualizar</button>
                            <button class="btn danger" id="btn-remove-all">Remover todos</button>
                        </div>
                    </div>
                    <div class="subgrid" style="grid-template-columns:1fr;">
                        <div class="subcard">
                            <div class="list" id="trusted-devices"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section wide">
                <h2 class="section-title">Conta</h2>
                <div class="card">
                    <div class="row">
                        <div>
                            <h3>Sair de todos os dispositivos</h3>
                            <p>Encerra sessoes ativas e exige novo login nos dispositivos.</p>
                        </div>
                        <div class="actions">
                            <button class="btn danger" disabled>Em breve</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div class="modal-layer" id="pin-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="pin-modal-title">
            <h2 id="pin-modal-title">Confirmar PIN</h2>
            <p id="pin-modal-copy">Digite seu PIN de 4 digitos para continuar.</p>
            <div class="pin-inputs" id="pin-inputs">
                <input type="password" inputmode="numeric" maxlength="1" autocomplete="off">
                <input type="password" inputmode="numeric" maxlength="1" autocomplete="off">
                <input type="password" inputmode="numeric" maxlength="1" autocomplete="off">
                <input type="password" inputmode="numeric" maxlength="1" autocomplete="off">
            </div>
            <div class="modal-error" id="pin-modal-error"></div>
            <div class="modal-actions">
                <button class="btn ghost" id="pin-cancel">Cancelar</button>
                <button class="btn primary" id="pin-confirm">Continuar</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <?php
    require_once __DIR__ . '/../components/TwoFactorSetupModal.php';
    TwoFactorSetupModal::render();
    ?>

    <script>
        const API_BASE = '/api/v4';
        const $ = (id) => document.getElementById(id);

        const state = {
            hasPin: false,
            twofaEnabled: false,
            qrEnabled: false
        };

        const els = {
            pinBadge: $('pin-badge'),
            pinCopy: $('pin-copy'),
            pinCreate: $('btn-pin-create'),
            pinChange: $('btn-pin-change'),
            pinRemove: $('btn-pin-remove'),
            twofaBadge: $('twofa-badge'),
            twofaButton: $('btn-2fa'),
            backupRow: $('backup-row'),
            backupButton: $('btn-backup'),
            qrBadge: $('qr-badge'),
            qrToggle: $('qr-toggle'),
            qrDevices: $('qr-devices'),
            qrLogs: $('qr-logs'),
            trustedCopy: $('trusted-copy'),
            trustedDevices: $('trusted-devices'),
            refresh: $('btn-refresh'),
            removeAll: $('btn-remove-all'),
            toast: $('toast')
        };

        function toast(message) {
            els.toast.textContent = message;
            els.toast.classList.add('show');
            clearTimeout(window.__settingsToast);
            window.__settingsToast = setTimeout(() => els.toast.classList.remove('show'), 3200);
        }

        async function api(path, options = {}) {
            const response = await fetch(`${API_BASE}${path}`, {
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(options.headers || {}) },
                ...options
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                throw new Error(data.error || data.message || 'Nao foi possivel concluir a acao.');
            }
            return data;
        }

        function setBadge(el, enabled, labelEnabled = 'Ativado', labelDisabled = 'Desativado') {
            el.className = `badge ${enabled ? 'enabled' : ''}`;
            el.innerHTML = `<span class="dot"></span> ${enabled ? labelEnabled : labelDisabled}`;
        }

        function empty(text) {
            return `<div class="empty">${escapeHtml(text)}</div>`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = String(text ?? '');
            return div.innerHTML;
        }

        function formatDate(value) {
            if (!value) return 'Data indisponivel';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('pt-BR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        }

        function deviceName(userAgent = '') {
            const ua = userAgent.toLowerCase();
            const browser = ua.includes('edg') ? 'Edge' : ua.includes('chrome') ? 'Chrome' : ua.includes('firefox') ? 'Firefox' : ua.includes('safari') ? 'Safari' : 'Navegador';
            const os = ua.includes('windows') ? 'Windows' : ua.includes('android') ? 'Android' : ua.includes('iphone') || ua.includes('ipad') ? 'iOS' : ua.includes('mac') ? 'macOS' : 'Dispositivo';
            return `${browser} em ${os}`;
        }

        function qrAction(action, status) {
            const labels = {
                approve_qrcode: 'QR Code aprovado',
                login_qrcode: 'Login por QR Code',
                settings_qrcode: 'Configuracao alterada'
            };
            return `${labels[action] || action} (${status})`;
        }

        function requestPin({ title = 'Confirmar PIN', copy = 'Digite seu PIN de 4 digitos para continuar.' } = {}) {
            return new Promise((resolve) => {
                const modal = $('pin-modal');
                const inputs = Array.from(document.querySelectorAll('#pin-inputs input'));
                const error = $('pin-modal-error');
                const confirm = $('pin-confirm');
                const cancel = $('pin-cancel');
                let done = false;

                $('pin-modal-title').textContent = title;
                $('pin-modal-copy').textContent = copy;
                error.textContent = '';
                inputs.forEach(input => input.value = '');
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');

                const cleanup = (value) => {
                    if (done) return;
                    done = true;
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                    confirm.removeEventListener('click', onConfirm);
                    cancel.removeEventListener('click', onCancel);
                    inputs.forEach(input => {
                        input.removeEventListener('input', onInput);
                        input.removeEventListener('keydown', onKeydown);
                        input.removeEventListener('paste', onPaste);
                    });
                    resolve(value);
                };

                const getPin = () => inputs.map(input => input.value).join('');

                const onConfirm = () => {
                    const pin = getPin();
                    if (!/^\d{4}$/.test(pin)) {
                        error.textContent = 'Digite os 4 digitos do PIN.';
                        return;
                    }
                    cleanup(pin);
                };

                const onCancel = () => cleanup(null);

                const onInput = (event) => {
                    event.target.value = event.target.value.replace(/\D/g, '').slice(0, 1);
                    error.textContent = '';
                    if (event.target.value) {
                        const index = inputs.indexOf(event.target);
                        if (inputs[index + 1]) inputs[index + 1].focus();
                    }
                };

                const onKeydown = (event) => {
                    if (event.key === 'Backspace' && !event.target.value) {
                        const index = inputs.indexOf(event.target);
                        if (inputs[index - 1]) inputs[index - 1].focus();
                    }
                    if (event.key === 'Enter') onConfirm();
                };

                const onPaste = (event) => {
                    event.preventDefault();
                    const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
                    pasted.split('').forEach((char, index) => { if (inputs[index]) inputs[index].value = char; });
                    if (pasted.length === 4) onConfirm();
                };

                confirm.addEventListener('click', onConfirm);
                cancel.addEventListener('click', onCancel);
                inputs.forEach(input => {
                    input.addEventListener('input', onInput);
                    input.addEventListener('keydown', onKeydown);
                    input.addEventListener('paste', onPaste);
                });

                setTimeout(() => inputs[0].focus(), 80);
            });
        }

        async function requestNewPin() {
            const pin = await requestPin({ title: 'Criar PIN', copy: 'Digite um PIN de 4 digitos.' });
            if (!pin) return null;
            const confirmation = await requestPin({ title: 'Confirmar PIN', copy: 'Repita o mesmo PIN para confirmar.' });
            if (!confirmation) return null;
            if (pin !== confirmation) {
                toast('Os PINs nao conferem.');
                return null;
            }
            return pin;
        }

        async function loadPin() {
            try {
                const data = await api('/pin/check');
                state.hasPin = !!(data.has_pin ?? data.hasPin ?? data.exists);
            } catch (error) {
                state.hasPin = false;
            }

            setBadge(els.pinBadge, state.hasPin, 'Criado', 'Nao criado');
            els.pinCopy.textContent = state.hasPin ? 'PIN ativo para confirmar alteracoes sensiveis.' : 'Crie um PIN para proteger areas sensiveis.';
            els.pinCreate.style.display = state.hasPin ? 'none' : '';
            els.pinChange.style.display = state.hasPin ? '' : 'none';
            els.pinRemove.style.display = state.hasPin ? '' : 'none';
        }

        async function load2FA() {
            const data = await api('/2fa/status');
            const status = data.data || {};
            state.twofaEnabled = !!status.enabled;
            setBadge(els.twofaBadge, state.twofaEnabled);
            els.twofaButton.textContent = state.twofaEnabled ? 'Desativar' : 'Ativar';
            els.twofaButton.classList.toggle('danger', state.twofaEnabled);
            els.twofaButton.classList.toggle('primary', !state.twofaEnabled);
            els.backupRow.style.display = state.twofaEnabled ? 'flex' : 'none';
            els.trustedCopy.textContent = status.trusted_devices_count > 0
                ? `${status.trusted_devices_count} dispositivo(s) lembrado(s).`
                : 'Nenhum dispositivo lembrado para pular 2FA.';
        }

        async function loadTrustedDevices() {
            const data = await api('/2fa/devices');
            const devices = data.data || [];
            els.removeAll.style.display = devices.length ? '' : 'none';
            els.trustedDevices.innerHTML = devices.length ? devices.map(device => `
                <div class="list-item">
                    <div>
                        <strong>${escapeHtml(device.device_name || 'Dispositivo confiavel')}</strong>
                        <small>${escapeHtml(device.ip_address || 'IP desconhecido')} · ${formatDate(device.created_at)}</small>
                    </div>
                </div>
            `).join('') : empty('Nenhum dispositivo confiavel registrado.');
        }

        async function loadQr() {
            const data = await api('/qr-login/settings');
            state.qrEnabled = !!data.settings?.enabled;
            setBadge(els.qrBadge, state.qrEnabled);
            els.qrToggle.classList.toggle('active', state.qrEnabled);

            const devices = data.devices || [];
            els.qrDevices.innerHTML = devices.length ? devices.map(device => `
                <div class="list-item">
                    <div>
                        <strong>${escapeHtml(deviceName(device.user_agent || ''))}</strong>
                        <small>${escapeHtml(device.ip_address || 'IP desconhecido')} · ${formatDate(device.created_at)}</small>
                    </div>
                </div>
            `).join('') : empty('Nenhuma sessao Pipocine ativa.');

            const logs = data.logs || [];
            els.qrLogs.innerHTML = logs.length ? logs.map(log => `
                <div class="list-item">
                    <div>
                        <strong>${escapeHtml(qrAction(log.action, log.status))}</strong>
                        <small>${escapeHtml(log.ip_address || 'IP desconhecido')} · ${formatDate(log.created_at)}</small>
                    </div>
                </div>
            `).join('') : empty('Nenhum evento de QR Code registrado.');
        }

        async function refreshAll() {
            await Promise.allSettled([loadPin(), load2FA(), loadTrustedDevices(), loadQr()]);
        }

        els.pinCreate.addEventListener('click', async () => {
            const pin = await requestNewPin();
            if (!pin) return;
            try {
                await api('/pin/create', { method: 'POST', body: JSON.stringify({ pin }) });
                toast('PIN criado com sucesso.');
                loadPin();
            } catch (error) { toast(error.message); }
        });

        els.pinChange.addEventListener('click', async () => {
            const current = await requestPin({ title: 'PIN atual', copy: 'Digite o PIN atual para continuar.' });
            if (!current) return;
            const next = await requestNewPin();
            if (!next) return;
            try {
                await api('/pin/change', { method: 'POST', body: JSON.stringify({ current_pin: current, new_pin: next }) });
                toast('PIN alterado com sucesso.');
                loadPin();
            } catch (error) { toast(error.message); }
        });

        els.pinRemove.addEventListener('click', async () => {
            const pin = await requestPin({ title: 'Remover PIN', copy: 'Digite seu PIN atual para remover esta protecao.' });
            if (!pin) return;
            try {
                await api('/pin/remove', { method: 'POST', body: JSON.stringify({ pin }) });
                toast('PIN removido.');
                loadPin();
            } catch (error) { toast(error.message); }
        });

        els.twofaButton.addEventListener('click', async () => {
            if (!state.twofaEnabled) {
                if (window.TwoFactorSetupModal) window.TwoFactorSetupModal.show();
                return;
            }

            const pin = await requestPin({ title: 'Desativar 2FA', copy: 'Confirme seu PIN para desativar a verificacao em duas etapas.' });
            if (!pin) return;
            try {
                await api('/2fa/disable', { method: 'POST', body: JSON.stringify({ pin }) });
                toast('2FA desativado.');
                await refreshAll();
            } catch (error) { toast(error.message); }
        });

        els.backupButton.addEventListener('click', async () => {
            const pin = await requestPin({ title: 'Codigos de backup', copy: 'Confirme seu PIN para gerar novos codigos.' });
            if (!pin) return;
            try {
                const data = await api('/2fa/backup-codes', { method: 'POST', body: JSON.stringify({ pin }) });
                toast(`Novos codigos: ${(data.backup_codes || []).join(' ')}`);
            } catch (error) { toast(error.message); }
        });

        els.qrToggle.addEventListener('click', async () => {
            try {
                await api('/qr-login/settings', { method: 'POST', body: JSON.stringify({ enabled: !state.qrEnabled }) });
                toast(!state.qrEnabled ? 'Login por QR Code ativado.' : 'Login por QR Code desativado.');
                loadQr();
            } catch (error) { toast(error.message); }
        });

        els.refresh.addEventListener('click', refreshAll);

        els.removeAll.addEventListener('click', async () => {
            const pin = await requestPin({ title: 'Remover dispositivos', copy: 'Confirme seu PIN para remover todos os dispositivos confiaveis.' });
            if (!pin) return;
            try {
                await api('/2fa/devices', { method: 'DELETE', body: JSON.stringify({ device_token: 'ALL' }) });
                toast('Dispositivos removidos.');
                await refreshAll();
            } catch (error) { toast(error.message); }
        });

        window.addEventListener('twofactor-enabled', refreshAll);
        document.addEventListener('DOMContentLoaded', refreshAll);
    </script>
</body>
</html>
