<?php
/**
 * TwoFactorSetupModal Component
 * 
 * Modal para configuração de 2FA (Google Authenticator)
 * CSS e JS embutidos no próprio componente
 * 
 * Uso: <?php require_once __DIR__ . '/../components/TwoFactorSetupModal.php'; TwoFactorSetupModal::render(); ?>
 */

class TwoFactorSetupModal
{
    /**
     * Renderiza o componente completo
     */
    public static function render(): void
    {
        echo self::getStyles();
        echo self::getHtml();
        echo self::getScripts();
    }

    /**
     * CSS do componente
     */
    private static function getStyles(): string
    {
        return '
<style id="twofactor-setup-styles">
/* ============================================================
   2FA SETUP MODAL - Modal de Configuração 2FA
   Design Netflix: Minimalista, Moderno, Escuro
   ============================================================ */

.twofactor-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.84);
    backdrop-filter: blur(18px);
    z-index: 11000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    padding: 24px;
    overflow-y: auto;
}

.twofactor-overlay.active {
    opacity: 1;
    visibility: visible;
}

.twofactor-modal {
    background: #151515;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    max-width: 560px;
    width: 100%;
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    box-shadow: 0 28px 80px rgba(0, 0, 0, 0.68);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s ease;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.22) transparent;
}

.twofactor-modal::-webkit-scrollbar {
    width: 8px;
}

.twofactor-modal::-webkit-scrollbar-track {
    background: transparent;
}

.twofactor-modal::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    border: 2px solid #151515;
}

.twofactor-overlay.active .twofactor-modal {
    transform: scale(1) translateY(0);
}

/* Header */
.twofactor-header {
    padding: 28px 32px 14px;
}

.twofactor-title {
    color: #ffffff;
    font-size: 1.35rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    line-height: 1.2;
}

.twofactor-title svg {
    flex: 0 0 auto;
    width: 22px;
    height: 22px;
    color: #22c55e;
}

.twofactor-subtitle {
    color: #a1a1aa;
    font-size: 0.9rem;
    margin-top: 8px;
    line-height: 1.5;
}

/* Content */
.twofactor-content {
    padding: 18px 32px 24px;
}

/* Steps indicator */
.twofactor-steps {
    display: flex;
    justify-content: center;
    gap: 6px;
    padding: 0 32px;
    margin: 4px 0 18px;
}

.twofactor-step {
    width: 42px;
    height: 3px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    transition: all 0.3s ease;
}

.twofactor-step.active {
    background: #ffffff;
}

.twofactor-step.completed {
    background: #22c55e;
}

/* Step content */
.twofactor-step-content {
    display: none;
}

.twofactor-step-content.active {
    display: block;
    animation: twofactor-fadeIn 0.3s ease;
}

@keyframes twofactor-fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* QR Code section */
.twofactor-qr-section {
    text-align: center;
    margin-bottom: 20px;
}

.twofactor-qr-title {
    color: #f4f4f5;
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 18px;
}

.twofactor-qr-code {
    width: 196px;
    height: 196px;
    margin: 0 auto;
    padding: 10px;
    background: #fff;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 16px 44px rgba(0, 0, 0, 0.32);
}

.twofactor-qr-code img {
    max-width: 100%;
    max-height: 100%;
}

.twofactor-qr-loading {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-top-color: #e50914;
    border-radius: 50%;
    animation: twofactor-spin 0.8s linear infinite;
}

@keyframes twofactor-spin {
    to { transform: rotate(360deg); }
}

/* Secret key */
.twofactor-secret {
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
}

.twofactor-secret-label {
    color: #a1a1aa;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 8px;
}

.twofactor-secret-value {
    color: #fff;
    font-family: "SF Mono", Monaco, monospace;
    font-size: 0.98rem;
    letter-spacing: 2px;
    word-break: break-all;
    user-select: all;
    line-height: 1.5;
}

.twofactor-secret-hint {
    color: #71717a;
    font-size: 0.75rem;
    margin-top: 6px;
}

/* Instructions */
.twofactor-instructions {
    background: rgba(255, 255, 255, 0.025);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 0;
}

.twofactor-instructions-title {
    color: #f4f4f5;
    font-size: 0.86rem;
    font-weight: 500;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.twofactor-instructions-title svg {
    color: #22c55e;
    flex: 0 0 auto;
}

.twofactor-instructions-list {
    list-style: none;
    padding: 0;
    margin: 0;
    color: #b5b5bd;
    font-size: 0.82rem;
    line-height: 1.55;
}

.twofactor-instructions-list li {
    padding-left: 16px;
    position: relative;
    margin-bottom: 6px;
}

.twofactor-instructions-list li::before {
    content: "→";
    position: absolute;
    left: 0;
    color: #22c55e;
}

.twofactor-instructions-list li::before {
    content: "";
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #22c55e;
    position: absolute;
    left: 2px;
    top: 0.7em;
}

/* Code verification */
.twofactor-code-section {
    text-align: center;
}

.twofactor-code-title {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 500;
    margin-bottom: 20px;
}

.twofactor-code-inputs {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}

.twofactor-code-input {
    width: 46px;
    height: 52px;
    background: rgba(255, 255, 255, 0.04);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.15s ease;
    outline: none;
}

.twofactor-code-input:focus {
    border-color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}

.twofactor-code-input.filled {
    border-color: #22c55e;
}

.twofactor-code-input.error {
    border-color: #ef4444;
    animation: twofactor-shake 0.4s ease;
}

@keyframes twofactor-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
}

/* Backup codes */
.twofactor-backup-codes {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 0;
}

.twofactor-backup-title {
    color: #fff;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 12px;
    text-align: center;
}

.twofactor-backup-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.twofactor-backup-code {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 10px 8px;
    text-align: center;
    font-family: "SF Mono", Monaco, monospace;
    font-size: 0.85rem;
    color: #ddd;
    letter-spacing: 1px;
}

.twofactor-backup-hint {
    color: #666;
    font-size: 0.75rem;
    text-align: center;
    margin-top: 12px;
    line-height: 1.4;
}

/* Success state */
.twofactor-success {
    text-align: center;
    padding: 32px 20px;
}

.twofactor-success-icon {
    width: 64px;
    height: 64px;
    background: rgba(34, 197, 94, 0.14);
    border: 1px solid rgba(34, 197, 94, 0.34);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.twofactor-success-icon svg {
    width: 32px;
    height: 32px;
    color: #22c55e;
}

.twofactor-success-title {
    color: #fff;
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.twofactor-success-text {
    color: #888;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Message */
.twofactor-message {
    color: #ef4444;
    font-size: 0.85rem;
    text-align: center;
    margin-bottom: 16px;
    min-height: 20px;
}

.twofactor-message.success {
    color: #22c55e;
}

/* Buttons */
.twofactor-btn {
    width: 100%;
    min-height: 46px;
    padding: 13px 20px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    margin-bottom: 0;
}

.twofactor-btn-primary {
    background: #ffffff;
    color: #111111;
}

.twofactor-btn-primary:hover:not(:disabled) {
    background: #e5e5e5;
    transform: translateY(-1px);
}

.twofactor-btn-primary:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
}

.twofactor-btn-secondary {
    background: transparent;
    color: #a1a1aa;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.twofactor-btn-secondary:hover {
    color: #fff;
    border-color: rgba(255, 255, 255, 0.24);
    background: rgba(255, 255, 255, 0.04);
}

.twofactor-btn-danger {
    background: transparent;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.twofactor-btn-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
}

/* Loading spinner */
.twofactor-loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: twofactor-spin 0.8s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

/* Footer */
.twofactor-footer {
    padding: 18px 32px 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.twofactor-step-buttons {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
}

/* Checkbox */
.twofactor-checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 4px 0 0;
    cursor: pointer;
}

.twofactor-checkbox {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.twofactor-checkbox.checked {
    background: #22c55e;
    border-color: #22c55e;
}

.twofactor-checkbox.checked svg {
    width: 12px;
    height: 12px;
    color: #fff;
}

.twofactor-checkbox-label {
    color: #aaa;
    font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 480px) {
    .twofactor-overlay {
        align-items: flex-end;
        padding: 12px;
    }

    .twofactor-modal {
        max-width: 100%;
        max-height: calc(100vh - 24px);
        border-radius: 16px;
    }
    
    .twofactor-header,
    .twofactor-content,
    .twofactor-footer {
        padding-left: 18px;
        padding-right: 18px;
    }

    .twofactor-header {
        padding-top: 22px;
    }

    .twofactor-title {
        font-size: 1.12rem;
    }

    .twofactor-subtitle {
        font-size: 0.82rem;
    }

    .twofactor-steps {
        padding: 0 18px;
        margin-bottom: 14px;
    }

    .twofactor-step {
        width: 34px;
    }
    
    .twofactor-qr-code {
        width: min(184px, 58vw);
        height: min(184px, 58vw);
    }

    .twofactor-instructions {
        padding: 13px 14px;
    }

    .twofactor-code-inputs {
        gap: 7px;
    }
    
    .twofactor-code-input {
        width: clamp(36px, 12vw, 42px);
        height: 48px;
        font-size: 1rem;
    }
}

@media (min-width: 560px) {
    .twofactor-step-buttons {
        grid-template-columns: minmax(0, 1fr) minmax(0, 0.72fr);
    }
}
</style>
';
    }

    /**
     * HTML do componente
     */
    private static function getHtml(): string
    {
        return '
<div id="twofactor-overlay" class="twofactor-overlay">
    <div class="twofactor-modal">
        <!-- Header -->
        <div class="twofactor-header">
            <h2 class="twofactor-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                Verificação em Duas Etapas
            </h2>
            <p class="twofactor-subtitle">Proteja sua conta com uma camada extra de segurança</p>
        </div>
        
        <!-- Steps indicator -->
        <div class="twofactor-steps">
            <div class="twofactor-step active" data-step="1"></div>
            <div class="twofactor-step" data-step="2"></div>
            <div class="twofactor-step" data-step="3"></div>
        </div>
        
        <!-- Content -->
        <div class="twofactor-content">
            
            <!-- Step 1: QR Code -->
            <div class="twofactor-step-content active" data-step-content="1">
                <div class="twofactor-qr-section">
                    <p class="twofactor-qr-title">Escaneie o QR Code com o Google Authenticator</p>
                    <div class="twofactor-qr-code" id="twofactor-qr-container">
                        <div class="twofactor-qr-loading"></div>
                    </div>
                </div>
                
                <div class="twofactor-secret">
                    <div class="twofactor-secret-label">Ou insira manualmente:</div>
                    <div class="twofactor-secret-value" id="twofactor-secret-key">Carregando...</div>
                    <div class="twofactor-secret-hint">Clique para copiar</div>
                </div>
                
                <div class="twofactor-instructions">
                    <div class="twofactor-instructions-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        Como configurar
                    </div>
                    <ul class="twofactor-instructions-list">
                        <li>Baixe o <strong>Google Authenticator</strong> na App Store ou Play Store</li>
                        <li>Toque em "+" ou "Adicionar conta"</li>
                        <li>Escaneie o QR Code acima ou insira a chave manualmente</li>
                        <li>Clique em <strong>Pronto</strong> abaixo quando terminar</li>
                    </ul>
                </div>
            </div>
            
            <!-- Step 2: Verification -->
            <div class="twofactor-step-content" data-step-content="2">
                <div class="twofactor-code-section">
                    <p class="twofactor-code-title">Digite o código de 6 dígitos do app</p>
                    
                    <div class="twofactor-code-inputs">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                        <input type="text" class="twofactor-code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
                    </div>
                    
                    <p class="twofactor-message" id="twofactor-verify-message"></p>
                    
                    <label class="twofactor-checkbox-wrapper">
                        <div class="twofactor-checkbox" id="twofactor-remember-device">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span class="twofactor-checkbox-label">Lembrar este dispositivo por 30 dias</span>
                    </label>
                </div>
            </div>
            
            <!-- Step 3: Backup Codes -->
            <div class="twofactor-step-content" data-step-content="3">
                <div class="twofactor-backup-codes">
                    <p class="twofactor-backup-title">Códigos de Backup</p>
                    <p style="color: #888; font-size: 0.8rem; text-align: center; margin-bottom: 16px;">
                        Salve esses códigos em um local seguro. Use-os se perder acesso ao app.
                    </p>
                    <div class="twofactor-backup-grid" id="twofactor-backup-grid">
                        <!-- Códigos serão inseridos aqui -->
                    </div>
                    <p class="twofactor-backup-hint">
                        Cada código pode ser usado apenas uma vez
                    </p>
                </div>
            </div>
            
            <!-- Step 4: Success -->
            <div class="twofactor-step-content" data-step-content="4">
                <div class="twofactor-success">
                    <div class="twofactor-success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 class="twofactor-success-title">2FA Ativado com Sucesso!</h3>
                    <p class="twofactor-success-text">
                        Sua conta está mais segura. A partir de agora, você precisará do código do app para fazer login.
                    </p>
                </div>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="twofactor-footer">
            <p class="twofactor-message" id="twofactor-footer-message"></p>
            
            <!-- Step 1 buttons -->
            <div class="twofactor-step-buttons" data-step-buttons="1">
                <button class="twofactor-btn twofactor-btn-primary" id="btn-twofactor-next">
                    Pronto, configurei
                </button>
                <button class="twofactor-btn twofactor-btn-secondary" id="btn-twofactor-cancel">
                    Cancelar
                </button>
            </div>
            
            <!-- Step 2 buttons -->
            <div class="twofactor-step-buttons" data-step-buttons="2" style="display: none;">
                <button class="twofactor-btn twofactor-btn-primary" id="btn-twofactor-verify" disabled>
                    Verificar e Ativar
                </button>
                <button class="twofactor-btn twofactor-btn-secondary" id="btn-twofactor-back">
                    Voltar
                </button>
            </div>
            
            <!-- Step 3 buttons -->
            <div class="twofactor-step-buttons" data-step-buttons="3" style="display: none;">
                <button class="twofactor-btn twofactor-btn-primary" id="btn-twofactor-finish">
                    Concluir
                </button>
                <button class="twofactor-btn twofactor-btn-secondary" id="btn-twofactor-download">
                    Baixar Códigos
                </button>
            </div>
            
            <!-- Step 4 buttons -->
            <div class="twofactor-step-buttons" data-step-buttons="4" style="display: none;">
                <button class="twofactor-btn twofactor-btn-primary" id="btn-twofactor-close">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>
';
    }

    /**
     * JavaScript do componente
     */
    private static function getScripts(): string
    {
        return '
<script id="twofactor-setup-scripts">
(function() {
    const API_BASE = "/api/v4";
    let currentStep = 1;
    let setupData = null;
    let verificationCode = "";
    let rememberDevice = false;
    
    // Elementos
    const overlay = document.getElementById("twofactor-overlay");
    const qrContainer = document.getElementById("twofactor-qr-container");
    const secretKeyEl = document.getElementById("twofactor-secret-key");
    const verifyMessage = document.getElementById("twofactor-verify-message");
    const footerMessage = document.getElementById("twofactor-footer-message");
    const backupGrid = document.getElementById("twofactor-backup-grid");
    const rememberCheckbox = document.getElementById("twofactor-remember-device");
    
    // Botões
    const btnNext = document.getElementById("btn-twofactor-next");
    const btnCancel = document.getElementById("btn-twofactor-cancel");
    const btnVerify = document.getElementById("btn-twofactor-verify");
    const btnBack = document.getElementById("btn-twofactor-back");
    const btnFinish = document.getElementById("btn-twofactor-finish");
    const btnDownload = document.getElementById("btn-twofactor-download");
    const btnClose = document.getElementById("btn-twofactor-close");
    
    // Inputs de código
    const codeInputs = document.querySelectorAll(".twofactor-code-input");
    
    // Expõe funções globais
    window.TwoFactorSetupModal = {
        show: function() {
            resetModal();
            overlay.classList.add("active");
            startSetup();
        },
        hide: function() {
            overlay.classList.remove("active");
            resetModal();
        }
    };
    
    // Inicia setup (chama API)
    async function startSetup() {
        qrContainer.innerHTML = \'<div class="twofactor-qr-loading"></div>\';
        secretKeyEl.textContent = "Carregando...";
        
        try {
            const response = await fetch(`${API_BASE}/2fa/setup`, {
                method: "POST",
                headers: { "Content-Type": "application/json" }
            });
            
            const data = await response.json();
            
            if (data.success) {
                setupData = data;
                
                // Mostra QR Code
                qrContainer.innerHTML = `<img src="${data.qr_code_url}" alt="QR Code 2FA" onerror="this.parentElement.innerHTML=\'<p style=color:#888>Erro ao carregar QR</p>\'">`;
                
                // Mostra chave secreta
                secretKeyEl.textContent = data.manual_entry || data.secret;
                
                // Preenche códigos de backup
                backupGrid.innerHTML = data.backup_codes.map(code => 
                    `<div class="twofactor-backup-code">${code}</div>`
                ).join("");
            } else {
                qrContainer.innerHTML = \'<p style="color:#ef4444">Erro ao carregar</p>\';
                showFooterMessage(data.error || "Erro ao iniciar configuração");
            }
        } catch (error) {
            qrContainer.innerHTML = \'<p style="color:#ef4444">Erro de conexão</p>\';
            showFooterMessage("Erro de conexão. Tente novamente.");
        }
    }
    
    // Navegação entre steps
    function goToStep(step) {
        // Atualiza steps indicator
        document.querySelectorAll(".twofactor-step").forEach((el, i) => {
            el.classList.remove("active", "completed");
            if (i + 1 < step) el.classList.add("completed");
            if (i + 1 === step) el.classList.add("active");
        });
        
        // Esconde todos os conteúdos
        document.querySelectorAll(".twofactor-step-content").forEach(el => {
            el.classList.remove("active");
        });
        
        // Esconde todos os botões
        document.querySelectorAll(".twofactor-step-buttons").forEach(el => {
            el.style.display = "none";
        });
        
        // Mostra conteúdo atual
        const content = document.querySelector(`[data-step-content="${step}"]`);
        if (content) content.classList.add("active");
        
        // Mostra botões do step
        const buttons = document.querySelector(`[data-step-buttons="${step}"]`);
        if (buttons) buttons.style.display = "grid";
        
        currentStep = step;
        
        // Foco no primeiro input se for step 2
        if (step === 2) {
            setTimeout(() => codeInputs[0]?.focus(), 100);
        }
    }
    
    // Atualiza botão verify
    function updateVerifyButton() {
        verificationCode = Array.from(codeInputs).map(input => input.value).join("");
        btnVerify.disabled = verificationCode.length !== 6;
        
        codeInputs.forEach(input => {
            input.classList.toggle("filled", input.value.length === 1);
        });
    }
    
    // Verifica código
    async function verifyCode() {
        if (verificationCode.length !== 6) return;
        
        btnVerify.disabled = true;
        btnVerify.innerHTML = \'<span class="twofactor-loading"></span> Verificando...\';
        
        try {
            const response = await fetch(`${API_BASE}/2fa/verify`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    code: verificationCode,
                    remember_device: rememberDevice,
                    device_token: rememberDevice ? generateDeviceToken() : null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage("Código verificado!", true);
                goToStep(3);
            } else {
                showMessage(data.error || "Código inválido");
                
                if (data.blocked) {
                    showMessage("Muitas tentativas. Aguarde 15 minutos.");
                }
                
                // Anima erro
                codeInputs.forEach(input => input.classList.add("error"));
                setTimeout(() => {
                    codeInputs.forEach(input => input.classList.remove("error"));
                }, 400);
                
                // Limpa
                codeInputs.forEach(input => input.value = "");
                updateVerifyButton();
                codeInputs[0]?.focus();
            }
        } catch (error) {
            showMessage("Erro de conexão");
        } finally {
            btnVerify.innerHTML = "Verificar e Ativar";
            updateVerifyButton();
        }
    }
    
    // Gera token de dispositivo
    function generateDeviceToken() {
        return Math.random().toString(36).substring(2) + Date.now().toString(36);
    }
    
    // Mostra mensagem
    function showMessage(text, isSuccess = false) {
        verifyMessage.textContent = text;
        verifyMessage.className = "twofactor-message" + (isSuccess ? " success" : "");
    }
    
    function showFooterMessage(text, isSuccess = false) {
        footerMessage.textContent = text;
        footerMessage.className = "twofactor-message" + (isSuccess ? " success" : "");
    }
    
    // Reseta modal
    function resetModal() {
        currentStep = 1;
        setupData = null;
        verificationCode = "";
        rememberDevice = false;
        
        document.querySelectorAll(".twofactor-step").forEach((el, i) => {
            el.classList.remove("completed");
            el.classList.toggle("active", i === 0);
        });
        
        goToStep(1);
        codeInputs.forEach(input => input.value = "");
        updateVerifyButton();
        showMessage("");
        showFooterMessage("");
        
        rememberCheckbox.classList.remove("checked");
    }
    
    // Event listeners
    btnNext?.addEventListener("click", () => goToStep(2));
    btnCancel?.addEventListener("click", () => window.TwoFactorSetupModal.hide());
    btnBack?.addEventListener("click", () => goToStep(1));
    btnVerify?.addEventListener("click", verifyCode);
    
    btnFinish?.addEventListener("click", () => {
        goToStep(4);
        // Notifica página que 2FA foi ativado
        window.dispatchEvent(new CustomEvent("twofactor-enabled"));
    });
    
    btnDownload?.addEventListener("click", () => {
        if (!setupData?.backup_codes) return;
        
        const content = `CÓDIGOS DE BACKUP - PipoCine
================================

${setupData.backup_codes.join("\\n")}

================================
Guarde esses códigos em segurança!
Cada código pode ser usado apenas uma vez.
Gerado em: ${new Date().toLocaleString()}
`;
        
        const blob = new Blob([content], { type: "text/plain" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "pipocine-backup-codes.txt";
        a.click();
        URL.revokeObjectURL(url);
    });
    
    btnClose?.addEventListener("click", () => {
        window.TwoFactorSetupModal.hide();
        // Recarrega para atualizar status
        window.location.reload();
    });
    
    // Checkbox remember device
    rememberCheckbox?.addEventListener("click", () => {
        rememberDevice = !rememberDevice;
        rememberCheckbox.classList.toggle("checked", rememberDevice);
    });
    
    // Inputs de código
    codeInputs.forEach((input, index) => {
        input.addEventListener("keypress", (e) => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });
        
        input.addEventListener("input", (e) => {
            const value = e.target.value;
            if (value.length === 1 && index < 5) {
                codeInputs[index + 1].focus();
            }
            updateVerifyButton();
        });
        
        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });
        
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const paste = e.clipboardData.getData("text").replace(/\\D/g, "").slice(0, 6);
            paste.split("").forEach((char, i) => {
                if (codeInputs[i]) codeInputs[i].value = char;
            });
            updateVerifyButton();
            if (paste.length === 6) btnVerify?.focus();
            else if (codeInputs[paste.length]) codeInputs[paste.length].focus();
        });
    });
    
    // Copiar chave secreta
    secretKeyEl?.addEventListener("click", () => {
        const text = secretKeyEl.textContent.replace(/\\s/g, "");
        navigator.clipboard.writeText(text).then(() => {
            showFooterMessage("Chave copiada!", true);
            setTimeout(() => showFooterMessage(""), 2000);
        });
    });
    
    // Fechar ao clicar fora
    overlay?.addEventListener("click", (e) => {
        if (e.target === overlay) {
            window.TwoFactorSetupModal.hide();
        }
    });
})();
</script>
';
    }
}
