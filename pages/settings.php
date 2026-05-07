<?php
/**
 * Página de Configurações
 * 
 * Protegida por PIN - só pode ser acessada após validação de PIN
 * Acesso direto pela URL é bloqueado, redireciona para select-profile
 */

require_once __DIR__ . '/../database/db.php';

// Verifica se usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Verifica se passou pela validação de PIN
$pinValidated = $_SESSION['pin_validated'] ?? false;
$pinValidateTime = $_SESSION['pin_validate_time'] ?? 0;

// PIN válido por apenas 5 minutos (300 segundos) ou até fechar a página
$maxAge = 300; // 5 minutos
$isValid = $pinValidated && (time() - $pinValidateTime) < $maxAge;

if (!$isValid) {
    // Limpa a sessão de validação
    unset($_SESSION['pin_validated']);
    unset($_SESSION['pin_validate_time']);
    
    // Redireciona para select-profile com mensagem
    header("Location: /select-profile?needs_pin=1");
    exit;
}

// Limpa a flag após uso (single-use)
unset($_SESSION['pin_validated']);
unset($_SESSION['pin_validate_time']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000000">
    <title>PipoCine — Configurações</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <style>
        :root {
            --bg-primary: #000000;
            --bg-secondary: #141414;
            --text-primary: #ffffff;
            --text-secondary: #a3a3a3;
            --accent: #e50914;
            --accent-hover: #f40612;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        
        /* Header */
        .settings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 48px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: var(--text-primary);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 500;
            letter-spacing: -0.3px;
        }
        
        .header-spacer {
            width: 80px;
        }
        
        /* Main Content */
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 48px;
        }
        
        .settings-section {
            margin-bottom: 48px;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 16px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.75rem;
        }
        
        .settings-card {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
        }
        
        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .settings-item:last-child {
            border-bottom: none;
        }
        
        .item-info h3 {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .item-info p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .item-action {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: var(--text-primary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn-danger {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .btn-danger:hover {
            background: var(--accent);
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
        }
        
        .empty-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .empty-icon svg {
            width: 28px;
            height: 28px;
            color: var(--text-secondary);
        }
        
        .empty-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .empty-text {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
            flex-shrink: 0;
        }
        
        .toggle-switch.active {
            background: #22c55e;
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.3s;
        }
        
        .toggle-switch.active::after {
            transform: translateX(20px);
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.enabled {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }
        
        .status-badge.disabled {
            background: rgba(255, 255, 255, 0.08);
            color: #888;
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        
        .status-badge.enabled .status-dot {
            background: #22c55e;
        }
        
        .status-badge.disabled .status-dot {
            background: #666;
        }
        
        /* Devices List */
        .devices-list {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .device-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
        }
        
        .device-item:not(:last-child) {
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        .device-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .device-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .device-icon svg {
            width: 18px;
            height: 18px;
            color: #888;
        }
        
        .device-details h4 {
            font-size: 0.85rem;
            font-weight: 500;
            color: #fff;
        }
        
        .device-details p {
            font-size: 0.75rem;
            color: #666;
            margin-top: 2px;
        }
        
        .btn-remove-device {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            background: transparent;
            color: #ef4444;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-remove-device:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
        }
        
        .btn-remove-all {
            width: 100%;
            margin-top: 12px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            background: transparent;
            color: #ef4444;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-remove-all:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        /* Coming Soon Badge */
        .badge-soon {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(229, 9, 20, 0.15);
            color: #e50914;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .settings-header {
                padding: 16px 24px;
            }
            
            .settings-container {
                padding: 24px;
            }
            
            .settings-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .item-action {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <header class="settings-header">
        <a href="/select-profile" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>
        <h1 class="page-title">Configurações</h1>
        <div class="header-spacer"></div>
    </header>
    
    <main class="settings-container">
        <!-- Seção Segurança e Privacidade -->
        <section class="settings-section">
            <h2 class="section-title">Segurança e Privacidade</h2>
            
            <!-- Verificação em Duas Etapas (2FA) -->
            <div class="settings-card" style="margin-bottom: 16px;">
                <div class="settings-item">
                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                            <h3>Verificação em Duas Etapas</h3>
                            <span id="2fa-status-badge" class="status-badge disabled">
                                <span class="status-dot"></span>
                                Desativado
                            </span>
                        </div>
                        <p>Adicione uma camada extra de segurança com o Google Authenticator</p>
                    </div>
                    <div class="item-action">
                        <button class="btn-action" id="btn-2fa-setup">Ativar</button>
                    </div>
                </div>
                
                <!-- Códigos de Backup (mostrado quando 2FA ativo) -->
                <div id="backup-codes-section" style="display: none; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="font-size: 0.9rem; font-weight: 500; margin-bottom: 4px;">Códigos de Backup</h4>
                            <p style="font-size: 0.8rem; color: #888;">Regenerar códigos de emergência</p>
                        </div>
                        <button class="btn-action" id="btn-regenerate-codes">Regenerar</button>
                    </div>
                </div>
            </div>
            
            <!-- Login via QR Code -->
            <div class="settings-card" style="margin-bottom: 16px;">
                <div class="settings-item">
                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                            <h3>Login via QR Code</h3>
                            <span class="badge-soon">Em Breve</span>
                        </div>
                        <p>Permitir login escaneando um QR Code com outro dispositivo</p>
                    </div>
                    <div class="item-action">
                        <div class="toggle-switch" id="toggle-qr-login" data-soon="true"></div>
                    </div>
                </div>
            </div>
            
            <!-- Login via Código na Caixa de Entrada -->
            <div class="settings-card" style="margin-bottom: 16px;">
                <div class="settings-item">
                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                            <h3>Login via Código por Email</h3>
                            <span class="badge-soon">Em Breve</span>
                        </div>
                        <p>Receber código de login no seu email</p>
                    </div>
                    <div class="item-action">
                        <div class="toggle-switch" id="toggle-email-code" data-soon="true"></div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Seção Locais de Acesso -->
        <section class="settings-section">
            <h2 class="section-title">Controle de Acesso</h2>
            
            <div class="settings-card">
                <div class="settings-item">
                    <div class="item-info">
                        <h3>Dispositivos Confiáveis</h3>
                        <p id="trusted-devices-count">Gerenciar dispositivos que podem acessar sem 2FA</p>
                    </div>
                    <div class="item-action">
                        <button class="btn-action" id="btn-refresh-devices">Atualizar</button>
                    </div>
                </div>
                
                <!-- Lista de Dispositivos -->
                <div class="devices-list" id="devices-list">
                    <div class="device-item" id="no-devices-msg">
                        <p style="color: #666; font-size: 0.85rem; text-align: center; width: 100%;">
                            Nenhum dispositivo confiável registrado
                        </p>
                    </div>
                </div>
                
                <button class="btn-remove-all" id="btn-remove-all-devices" style="display: none;">
                    Remover Todos os Dispositivos
                </button>
            </div>
        </section>
        
        <!-- Seção Conta -->
        <section class="settings-section">
            <h2 class="section-title">Conta</h2>
            <div class="settings-card">
                <div class="settings-item">
                    <div class="item-info">
                        <h3>PIN de Segurança</h3>
                        <p>Alterar ou remover seu PIN de 4 dígitos</p>
                    </div>
                    <div class="item-action">
                        <button class="btn-action" onclick="alert('Em desenvolvimento')">Alterar PIN</button>
                    </div>
                </div>
                <div class="settings-item">
                    <div class="item-info">
                        <h3>Sair de Todos os Dispositivos</h3>
                        <p>Encerrar todas as sessões ativas</p>
                    </div>
                    <div class="item-action">
                        <button class="btn-action btn-danger" onclick="alert('Em desenvolvimento')">Sair de Todos</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php
    // Modal de configuração 2FA
    require_once __DIR__ . '/../components/TwoFactorSetupModal.php';
    TwoFactorSetupModal::render();
    ?>
    
    <script>
        console.log('[Settings] Script iniciado');
        
        const API_BASE = '/api/v4';
        console.log('[Settings] API_BASE:', API_BASE);
        
        // Elementos
        const btn2faSetup = document.getElementById('btn-2fa-setup');
        const badge2fa = document.getElementById('2fa-status-badge');
        const backupSection = document.getElementById('backup-codes-section');
        const btnRegenerateCodes = document.getElementById('btn-regenerate-codes');
        const devicesList = document.getElementById('devices-list');
        const btnRefreshDevices = document.getElementById('btn-refresh-devices');
        const btnRemoveAllDevices = document.getElementById('btn-remove-all-devices');
        const trustedDevicesCount = document.getElementById('trusted-devices-count');
        
        // Toggle switches (Em Breve)
        document.querySelectorAll('.toggle-switch[data-soon="true"]').forEach(toggle => {
            toggle.addEventListener('click', () => {
                alert('Esta funcionalidade estará disponível em breve!');
            });
        });
        
        // Carrega status 2FA
        async function load2FAStatus() {
            console.log('[2FA] Carregando status...');
            try {
                const url = `${API_BASE}/2fa/status`;
                console.log('[2FA] URL:', url);
                
                const response = await fetch(url);
                console.log('[2FA] Response status:', response.status);
                
                const data = await response.json();
                console.log('[2FA] Data:', data);
                
                if (data.success) {
                    update2FAUI(data.data);
                } else {
                    console.error('[2FA] API error:', data.error);
                }
            } catch (error) {
                console.error('[2FA] Erro ao carregar status:', error);
            }
        }
        
        // Atualiza UI do 2FA
        function update2FAUI(status) {
            if (status.enabled) {
                badge2fa.className = 'status-badge enabled';
                badge2fa.innerHTML = '<span class="status-dot"></span> Ativado';
                btn2faSetup.textContent = 'Desativar';
                btn2faSetup.classList.add('btn-danger');
                backupSection.style.display = 'block';
            } else {
                badge2fa.className = 'status-badge disabled';
                badge2fa.innerHTML = '<span class="status-dot"></span> Desativado';
                btn2faSetup.textContent = 'Ativar';
                btn2faSetup.classList.remove('btn-danger');
                backupSection.style.display = 'none';
            }
            
            trustedDevicesCount.textContent = status.trusted_devices_count > 0 
                ? `${status.trusted_devices_count} dispositivo(s) confiável(is)`
                : 'Gerenciar dispositivos que podem acessar sem 2FA';
        }
        
        // Botão Ativar/Desativar 2FA
        btn2faSetup?.addEventListener('click', async () => {
            const isEnabled = btn2faSetup.textContent === 'Desativar';
            
            if (isEnabled) {
                // Desativar - usar prompt simples
                const pin = prompt('Digite seu PIN de segurança para desativar o 2FA:');
                if (!pin) return;
                
                try {
                    const response = await fetch(`${API_BASE}/2fa/disable`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pin })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('2FA desativado com sucesso!');
                        load2FAStatus();
                        loadDevices();
                    } else {
                        alert(data.error || 'Erro ao desativar 2FA');
                    }
                } catch (error) {
                    alert('Erro de conexão');
                }
            } else {
                // Ativar - abre modal
                if (window.TwoFactorSetupModal) {
                    window.TwoFactorSetupModal.show();
                }
            }
        });
        
        // Evento quando 2FA é ativado
        window.addEventListener('twofactor-enabled', () => {
            load2FAStatus();
            loadDevices();
        });
        
        // Regenerar códigos de backup
        btnRegenerateCodes?.addEventListener('click', async () => {
            const pin = prompt('Digite seu PIN de segurança para regenerar códigos:');
            if (!pin) return;
            
            try {
                const response = await fetch(`${API_BASE}/2fa/backup-codes`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pin })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const codesText = data.backup_codes.join('\n');
                    alert(`Novos códigos de backup:\n\n${codesText}\n\nGuarde-os em segurança!`);
                } else {
                    alert(data.error || 'Erro ao regenerar códigos');
                }
            } catch (error) {
                alert('Erro de conexão');
            }
        });
        
        // Carrega dispositivos
        async function loadDevices() {
            console.log('[Devices] Carregando dispositivos...');
            try {
                const response = await fetch(`${API_BASE}/2fa/devices`);
                console.log('[Devices] Response status:', response.status);
                
                const data = await response.json();
                console.log('[Devices] Data:', data);
                
                if (data.success) {
                    renderDevices(data.data);
                } else {
                    console.error('[Devices] API error:', data.error);
                }
            } catch (error) {
                console.error('[Devices] Erro ao carregar dispositivos:', error);
            }
        }
        
        // Renderiza lista de dispositivos
        function renderDevices(devices) {
            if (!devices || devices.length === 0) {
                devicesList.innerHTML = `
                    <div class="device-item" id="no-devices-msg">
                        <p style="color: #666; font-size: 0.85rem; text-align: center; width: 100%;">
                            Nenhum dispositivo confiável registrado
                        </p>
                    </div>
                `;
                btnRemoveAllDevices.style.display = 'none';
                return;
            }
            
            btnRemoveAllDevices.style.display = 'block';
            
            devicesList.innerHTML = devices.map(device => `
                <div class="device-item" data-token="${device.device_token || ''}">
                    <div class="device-info">
                        <div class="device-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                        </div>
                        <div class="device-details">
                            <h4>${escapeHtml(device.device_name || 'Dispositivo Desconhecido')}</h4>
                            <p>${escapeHtml(device.ip_address || 'IP desconhecido')} • ${formatDate(device.created_at)}</p>
                        </div>
                    </div>
                    <button class="btn-remove-device" onclick="removeDevice('${device.device_token || 'ALL'}')">
                        Remover
                    </button>
                </div>
            `).join('');
        }
        
        // Remove dispositivo
        async function removeDevice(token) {
            if (token === 'ALL') return;
            
            if (!confirm('Remover este dispositivo confiável?')) return;
            
            try {
                const response = await fetch(`${API_BASE}/2fa/devices`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ device_token: token })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadDevices();
                    load2FAStatus();
                } else {
                    alert(data.error || 'Erro ao remover dispositivo');
                }
            } catch (error) {
                alert('Erro de conexão');
            }
        }
        
        // Remove todos os dispositivos
        btnRemoveAllDevices?.addEventListener('click', async () => {
            if (!confirm('Remover TODOS os dispositivos confiáveis? Você precisará inserir o código 2FA no próximo login.')) return;
            
            try {
                const response = await fetch(`${API_BASE}/2fa/devices`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ device_token: 'ALL' })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadDevices();
                    load2FAStatus();
                } else {
                    alert(data.error || 'Erro ao remover dispositivos');
                }
            } catch (error) {
                alert('Erro de conexão');
            }
        });
        
        // Botão atualizar
        btnRefreshDevices?.addEventListener('click', () => {
            loadDevices();
            load2FAStatus();
        });
        
        // Helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        
        // Carrega dados ao iniciar quando DOM estiver pronto
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[Settings] DOM pronto, iniciando carregamento...');
            load2FAStatus();
            loadDevices();
            console.log('[Settings] Chamadas de API iniciadas');
        });
    </script>
</body>
</html>
