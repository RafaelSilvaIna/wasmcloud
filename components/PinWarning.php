<?php
/**
 * PinWarning Component
 * 
 * Aviso de segurança para criar PIN de 4 dígitos
 * Design inspirado na Netflix - minimalista e moderno
 * CSS e JS embutidos no próprio componente
 * 
 * Uso: <?php require_once __DIR__ . '/../components/PinWarning.php'; PinWarning::render(); ?>
 */

class PinWarning
{
    /**
     * Renderiza o componente completo
     */
    public static function render(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        echo self::getStyles();
        echo self::getHtml($userId);
        echo self::getScripts($userId);
    }

    /**
     * CSS do componente
     */
    private static function getStyles(): string
    {
        return '
<style id="pin-warning-styles">
/* ============================================================
   PIN WARNING - Componente de Aviso de Segurança
   Design Netflix: Minimalista, Moderno, Escuro
   ============================================================ */

.pin-warning-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    padding: 20px;
}

.pin-warning-overlay.active {
    opacity: 1;
    visibility: visible;
}

.pin-warning-modal {
    background: #141414;
    border-radius: 12px;
    max-width: 380px;
    width: 100%;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 
        0 20px 40px -12px rgba(0, 0, 0, 0.9),
        0 0 0 1px rgba(255, 255, 255, 0.06);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.25s ease;
    position: relative;
    overflow: hidden;
}

.pin-warning-overlay.active .pin-warning-modal {
    transform: scale(1) translateY(0);
}

/* Decoração minimalista no topo */
.pin-warning-modal::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: #e50914;
}

/* Ícone de Segurança - mais compacto */
.pin-warning-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    background: #e50914;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pin-warning-icon svg {
    width: 28px;
    height: 28px;
    fill: white;
}

/* Títulos - mais minimalistas */
.pin-warning-title {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 500;
    margin: 0 0 8px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    letter-spacing: -0.3px;
}

.pin-warning-subtitle {
    color: #888;
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0 0 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* PIN Input Container - mais compacto */
.pin-input-container {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 16px;
}

.pin-input {
    width: 44px;
    height: 52px;
    background: rgba(255, 255, 255, 0.04);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.15s ease;
    outline: none;
}

.pin-input:focus {
    border-color: #e50914;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.15);
}

.pin-input.filled {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

/* Mensagens - compactas */
.pin-warning-message {
    color: #22c55e;
    font-size: 0.8rem;
    margin: 8px 0;
    min-height: 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.pin-warning-message.error {
    color: #ef4444;
}

.pin-warning-message.blocked {
    color: #f59e0b;
}

/* Botões - mais compactos e modernos */
.pin-warning-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
}

.pin-btn {
    padding: 12px 20px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.pin-btn-primary {
    background: #e50914;
    color: #ffffff;
}

.pin-btn-primary:hover:not(:disabled) {
    background: #f40612;
}

.pin-btn-primary:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pin-btn-secondary {
    background: transparent;
    color: #888;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.pin-btn-secondary:hover {
    color: #fff;
    border-color: rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.03);
}

/* Checkbox - minimalista */
.pin-warning-checkbox-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    cursor: pointer;
}

.pin-warning-checkbox-wrapper input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: #e50914;
    cursor: pointer;
}

.pin-warning-checkbox-wrapper label {
    color: #666;
    font-size: 0.8rem;
    cursor: pointer;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Status de PIN existente - compacto */
.pin-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 12px;
}

.pin-status.active {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.pin-status.inactive {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

/* Ícone de carregamento - menor */
.pin-loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: #e50914;
    border-radius: 50%;
    animation: pin-spin 0.8s linear infinite;
}

@keyframes pin-spin {
    to { transform: rotate(360deg); }
}

/* Responsivo - ajustado */
@media (max-width: 480px) {
    .pin-warning-modal {
        padding: 24px 20px;
        max-width: 340px;
    }
    
    .pin-warning-title {
        font-size: 1.15rem;
    }
    
    .pin-input {
        width: 40px;
        height: 48px;
        font-size: 1.15rem;
    }
}

/* Animação de shake para erro */
.pin-input.error {
    animation: pin-shake 0.5s ease;
    border-color: #ef4444;
}

@keyframes pin-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-5px); }
    40%, 80% { transform: translateX(5px); }
}
</style>
';
    }

    /**
     * HTML do componente
     */
    private static function getHtml(int $userId): string
    {
        return '
<div id="pin-warning-overlay" class="pin-warning-overlay">
    <div class="pin-warning-modal">
        <!-- Decoração no topo -->
        <div class="pin-warning-decoration"></div>
        
        <!-- Ícone de segurança com SVG -->
        <div class="pin-warning-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <!-- Status do PIN -->
        <div id="pin-status-badge" class="pin-status inactive" style="display: none;">
            <span id="pin-status-icon">⚠️</span>
            <span id="pin-status-text">PIN não configurado</span>
        </div>
        
        <!-- Título e descrição -->
        <h2 class="pin-warning-title">Proteja sua Conta</h2>
        <p class="pin-warning-subtitle">
            Se você é o dono desta conta, crie um PIN de 4 dígitos para ter acesso a funcionalidades extras e proteger suas configurações.
        </p>
        
        <!-- Inputs de PIN -->
        <div id="pin-input-section">
            <div class="pin-input-container">
                <input type="password" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" placeholder="•">
                <input type="password" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1" placeholder="•">
                <input type="password" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2" placeholder="•">
                <input type="password" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3" placeholder="•">
            </div>
            <p id="pin-message" class="pin-warning-message"></p>
        </div>
        
        <!-- Botões -->
        <div class="pin-warning-actions">
            <button id="btn-create-pin" class="pin-btn pin-btn-primary" disabled>
                Criar PIN de Segurança
            </button>
            <button id="btn-skip-pin" class="pin-btn pin-btn-secondary">
                Pular por enquanto
            </button>
        </div>
        
        <!-- Checkbox -->
        <div class="pin-warning-checkbox-wrapper">
            <input type="checkbox" id="pin-hide-again">
            <label for="pin-hide-again">Não exibir novamente</label>
        </div>
    </div>
</div>
';
    }

    /**
     * JavaScript do componente
     */
    private static function getScripts(int $userId): string
    {
        $userIdJson = json_encode($userId);
        
        return '
<script id="pin-warning-scripts">
(function() {
    const userId = ' . $userIdJson . ';
    const API_BASE = "/api/v4";
    
    // Elementos
    const overlay = document.getElementById("pin-warning-overlay");
    const inputs = document.querySelectorAll(".pin-input");
    const btnCreate = document.getElementById("btn-create-pin");
    const btnSkip = document.getElementById("btn-skip-pin");
    const messageEl = document.getElementById("pin-message");
    const checkboxHide = document.getElementById("pin-hide-again");
    const statusBadge = document.getElementById("pin-status-badge");
    const statusText = document.getElementById("pin-status-text");
    const statusIcon = document.getElementById("pin-status-icon");
    
    let hasPin = false;
    let currentPin = "";
    
    // Storage key
    const STORAGE_KEY = "pin_warning_hidden_" + userId;
    
    // Verifica se deve exibir
    function shouldShow() {
        return localStorage.getItem(STORAGE_KEY) !== "true";
    }
    
    // Verifica status do PIN
    async function checkPinStatus() {
        try {
            const response = await fetch(`${API_BASE}/pin/check`);
            const data = await response.json();
            
            hasPin = data.has_pin;
            
            if (hasPin) {
                // Já tem PIN, não mostra o aviso
                hideOverlay();
                return false;
            }
            
            // Mostra badge de PIN não configurado
            statusBadge.style.display = "inline-flex";
            statusBadge.className = "pin-status inactive";
            statusText.textContent = "PIN não configurado";
            statusIcon.textContent = "⚠️";
            
            return true;
        } catch (error) {
            console.error("[PinWarning] Erro ao verificar PIN:", error);
            return true; // Mostra em caso de erro
        }
    }
    
    // Inicializa
    async function init() {
        if (!shouldShow()) {
            hideOverlay();
            return;
        }
        
        const shouldDisplay = await checkPinStatus();
        if (shouldDisplay) {
            showOverlay();
        }
    }
    
    // Mostra overlay
    function showOverlay() {
        setTimeout(() => {
            overlay.classList.add("active");
            inputs[0]?.focus();
        }, 500);
    }
    
    // Esconde overlay
    function hideOverlay() {
        overlay.classList.remove("active");
    }
    
    // Atualiza estado do botão
    function updateButton() {
        currentPin = Array.from(inputs).map(input => input.value).join("");
        btnCreate.disabled = currentPin.length !== 4;
        
        // Atualiza classe filled
        inputs.forEach((input, index) => {
            input.classList.toggle("filled", input.value.length === 1);
        });
    }
    
    // Mostra mensagem
    function showMessage(text, type = "") {
        messageEl.textContent = text;
        messageEl.className = "pin-warning-message " + type;
        
        if (!text) return;
        
        setTimeout(() => {
            messageEl.textContent = "";
            messageEl.className = "pin-warning-message";
        }, 5000);
    }
    
    // Cria o PIN
    async function createPin() {
        if (currentPin.length !== 4) return;
        
        btnCreate.disabled = true;
        btnCreate.innerHTML = \'<span class="pin-loading"></span>\';
        
        try {
            const response = await fetch(`${API_BASE}/pin/create`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ pin: currentPin })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage("PIN criado com sucesso!", "");
                hasPin = true;
                
                // Atualiza badge
                statusBadge.className = "pin-status active";
                statusText.textContent = "PIN ativo";
                statusIcon.textContent = "✓";
                
                setTimeout(() => {
                    hideOverlay();
                }, 1500);
            } else {
                showMessage(data.error || "Erro ao criar PIN", "error");
                btnCreate.innerHTML = "Criar PIN de Segurança";
                updateButton();
            }
        } catch (error) {
            showMessage("Erro de conexão. Tente novamente.", "error");
            btnCreate.innerHTML = "Criar PIN de Segurança";
            updateButton();
        }
    }
    
    // Pula o aviso
    function skipPin() {
        if (checkboxHide.checked) {
            localStorage.setItem(STORAGE_KEY, "true");
        }
        hideOverlay();
    }
    
    // Event listeners dos inputs
    inputs.forEach((input, index) => {
        // Apenas números
        input.addEventListener("keypress", (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        // Navegação
        input.addEventListener("input", (e) => {
            const value = e.target.value;
            
            if (value.length === 1) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
            
            updateButton();
        });
        
        // Backspace
        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // Colar PIN
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const paste = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 4);
            
            paste.split("").forEach((char, i) => {
                if (inputs[i]) {
                    inputs[i].value = char;
                }
            });
            
            updateButton();
            
            if (paste.length === 4) {
                btnCreate.focus();
            } else if (inputs[paste.length]) {
                inputs[paste.length].focus();
            }
        });
    });
    
    // Botões
    btnCreate?.addEventListener("click", createPin);
    btnSkip?.addEventListener("click", skipPin);
    
    // Inicializa
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
</script>
';
    }
}
