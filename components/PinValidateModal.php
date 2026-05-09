<?php
/**
 * PinValidateModal Component
 * 
 * Modal para validação de PIN antes de acessar página de configurações
 * CSS e JS embutidos no próprio componente
 * 
 * Uso: <?php require_once __DIR__ . '/../components/PinValidateModal.php'; PinValidateModal::render(); ?>
 */

class PinValidateModal
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
<style id="pin-validate-styles">
/* ============================================================
   PIN VALIDATE MODAL - Modal de Validação de PIN
   Design Netflix: Minimalista, Moderno, Escuro
   ============================================================ */

.pin-validate-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(12px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
    padding: 20px;
}

.pin-validate-overlay.active {
    opacity: 1;
    visibility: visible;
}

.pin-validate-modal {
    background: #141414;
    border-radius: 12px;
    max-width: 360px;
    width: 100%;
    padding: 32px 28px;
    text-align: center;
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.9);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.25s ease;
    position: relative;
}

.pin-validate-overlay.active .pin-validate-modal {
    transform: scale(1) translateY(0);
}

/* Ícone Cadeado */
.pin-validate-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 20px;
    background: #e50914;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pin-validate-icon svg {
    width: 24px;
    height: 24px;
    fill: white;
}

/* Títulos */
.pin-validate-title {
    color: #ffffff;
    font-size: 1.15rem;
    font-weight: 500;
    margin: 0 0 8px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.pin-validate-subtitle {
    color: #888;
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0 0 24px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* PIN Inputs */
.pin-validate-inputs {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}

.pin-validate-input {
    width: 48px;
    height: 56px;
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

.pin-validate-input:focus {
    border-color: #e50914;
    background: rgba(255, 255, 255, 0.08);
}

.pin-validate-input.filled {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.pin-validate-input.error {
    border-color: #ef4444;
    animation: pin-validate-shake 0.4s ease;
}

@keyframes pin-validate-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
}

/* Mensagens */
.pin-validate-message {
    color: #ef4444;
    font-size: 0.8rem;
    margin: 0 0 16px 0;
    min-height: 18px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.pin-validate-message.success {
    color: #22c55e;
}

/* Botões */
.pin-validate-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    margin-bottom: 12px;
}

.pin-validate-btn-primary {
    background: #e50914;
    color: #ffffff;
}

.pin-validate-btn-primary:hover:not(:disabled) {
    background: #f40612;
}

.pin-validate-btn-primary:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pin-validate-btn-secondary {
    background: transparent;
    color: #888;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.pin-validate-btn-secondary:hover {
    color: #fff;
    border-color: rgba(255, 255, 255, 0.25);
}

/* Link criar PIN */
.pin-validate-create {
    color: #e50914;
    font-size: 0.8rem;
    text-decoration: none;
    cursor: pointer;
}

.pin-validate-create:hover {
    text-decoration: underline;
}

/* Ícone de carregamento */
.pin-validate-loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: pin-validate-spin 0.8s linear infinite;
}

@keyframes pin-validate-spin {
    to { transform: rotate(360deg); }
}

/* Tentativas restantes */
.pin-validate-attempts {
    color: #666;
    font-size: 0.75rem;
    margin-top: 12px;
}

/* Responsivo */
@media (max-width: 480px) {
    .pin-validate-modal {
        padding: 28px 24px;
        max-width: 320px;
    }
    
    .pin-validate-input {
        width: 44px;
        height: 52px;
        font-size: 1.1rem;
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
<div id="pin-validate-overlay" class="pin-validate-overlay">
    <div class="pin-validate-modal">
        <!-- Ícone Cadeado -->
        <div class="pin-validate-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
            </svg>
        </div>
        
        <!-- Título dinâmico -->
        <h2 id="pin-validate-title" class="pin-validate-title">Digite seu PIN</h2>
        <p id="pin-validate-subtitle" class="pin-validate-subtitle">
            Insira seu PIN de 4 dígitos para acessar as configurações
        </p>
        
        <!-- Mensagem de erro/info -->
        <p id="pin-validate-message" class="pin-validate-message"></p>
        
        <!-- Inputs de PIN -->
        <div class="pin-validate-inputs">
            <input type="password" class="pin-validate-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0">
            <input type="password" class="pin-validate-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
            <input type="password" class="pin-validate-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
            <input type="password" class="pin-validate-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
        </div>
        
        <!-- Tentativas restantes -->
        <p id="pin-validate-attempts" class="pin-validate-attempts"></p>
        
        <!-- Botões -->
        <button id="btn-validate-pin" class="pin-validate-btn pin-validate-btn-primary" disabled>
            Acessar Configurações
        </button>
        <button id="btn-cancel-validate" class="pin-validate-btn pin-validate-btn-secondary">
            Cancelar
        </button>
        
        <!-- Link para criar PIN (mostrado quando não tem PIN) -->
        <a id="link-create-pin" class="pin-validate-create" style="display: none;" onclick="PinValidateModal.showCreatePin()">
            Criar um PIN de segurança
        </a>
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
<script id="pin-validate-scripts">
(function() {
    const API_BASE = "/api/v4";
    
    // Elementos
    const overlay = document.getElementById("pin-validate-overlay");
    const inputs = document.querySelectorAll(".pin-validate-input");
    const btnValidate = document.getElementById("btn-validate-pin");
    const btnCancel = document.getElementById("btn-cancel-validate");
    const messageEl = document.getElementById("pin-validate-message");
    const titleEl = document.getElementById("pin-validate-title");
    const subtitleEl = document.getElementById("pin-validate-subtitle");
    const attemptsEl = document.getElementById("pin-validate-attempts");
    const linkCreatePin = document.getElementById("link-create-pin");
    
    let currentPin = "";
    let hasPin = true;
    let remainingAttempts = 3;
    
    // Expõe funções globais
    window.PinValidateModal = {
        show: function() {
            checkPinStatus().then(status => {
                if (status.has_pin) {
                    showValidateMode();
                } else {
                    showNoPinMode();
                }
                overlay.classList.add("active");
                inputs[0]?.focus();
            });
        },
        hide: function() {
            overlay.classList.remove("active");
            resetInputs();
        },
        showCreatePin: function() {
            // Fecha este modal e abre o de criação
            overlay.classList.remove("active");
            document.getElementById("pin-warning-overlay")?.classList.add("active");
        }
    };
    
    // Verifica se usuário tem PIN
    async function checkPinStatus() {
        try {
            const response = await fetch(`${API_BASE}/pin/check`);
            const data = await response.json();
            hasPin = data.has_pin;
            return data;
        } catch (error) {
            return { has_pin: true };
        }
    }
    
    // Modo validação (tem PIN)
    function showValidateMode() {
        hasPin = true;
        titleEl.textContent = "Digite seu PIN";
        subtitleEl.textContent = "Insira seu PIN de 4 dígitos para acessar as configurações";
        btnValidate.textContent = "Acessar Configurações";
        btnValidate.disabled = true;
        linkCreatePin.style.display = "none";
        btnValidate.style.display = "block";
        btnCancel.style.display = "block";
        btnCancel.textContent = "Cancelar";
        document.querySelector(".pin-validate-inputs").style.display = "flex";
        attemptsEl.style.display = "block";
    }
    
    // Modo sem PIN
    function showNoPinMode() {
        hasPin = false;
        titleEl.textContent = "PIN Não Configurado";
        subtitleEl.textContent = "Você precisa criar um PIN de segurança para acessar as configurações";
        btnValidate.style.display = "none";
        btnCancel.textContent = "Fechar";
        linkCreatePin.style.display = "inline";
        
        // Esconde inputs
        document.querySelector(".pin-validate-inputs").style.display = "none";
        attemptsEl.style.display = "none";
    }
    
    // Mostra mensagem
    function showMessage(text, isSuccess = false) {
        messageEl.textContent = text;
        messageEl.className = "pin-validate-message" + (isSuccess ? " success" : "");
    }
    
    // Atualiza tentativas
    function updateAttempts(remaining) {
        remainingAttempts = remaining;
        if (remaining < 3 && remaining > 0) {
            attemptsEl.textContent = `Tentativas restantes: ${remaining}`;
        } else if (remaining === 0) {
            attemptsEl.textContent = "Conta bloqueada por 30 minutos";
        } else {
            attemptsEl.textContent = "";
        }
    }
    
    // Reseta inputs
    function resetInputs(clearFeedback = true) {
        inputs.forEach(input => {
            input.value = "";
            input.classList.remove("filled", "error");
        });
        currentPin = "";
        btnValidate.disabled = true;
        resetButton();
        if (clearFeedback) {
            messageEl.textContent = "";
            attemptsEl.textContent = "";
        }
        document.querySelector(".pin-validate-inputs").style.display = "flex";
    }
    
    // Atualiza estado do botão
    function updateButton() {
        currentPin = Array.from(inputs).map(input => input.value).join("");
        btnValidate.disabled = currentPin.length !== 4;
        
        inputs.forEach((input, index) => {
            input.classList.toggle("filled", input.value.length === 1);
        });
    }
    
    // Valida PIN
    async function validatePin() {
        if (currentPin.length !== 4 || !hasPin) return;
        
        btnValidate.disabled = true;
        btnValidate.innerHTML = \'<span class="pin-validate-loading"></span>\';
        
        try {
            const response = await fetch(`${API_BASE}/pin/validate`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ pin: currentPin })
            });
            
            const data = await response.json();
            
            if (data.blocked) {
                const minutes = Math.ceil(data.remaining_seconds / 60);
                showMessage(`Muitas tentativas. Aguarde ${minutes} minutos.`);
                updateAttempts(0);
                resetInputs(false);
                return;
            }
            
            if (data.valid) {
                showMessage("PIN correto!", true);
                // Redireciona para configurações
                setTimeout(() => {
                    window.location.href = "/settings";
                }, 500);
            } else {
                showMessage("PIN incorreto");
                updateAttempts(data.remaining_attempts || 0);
                
                // Anima erro nos inputs
                inputs.forEach(input => input.classList.add("error"));
                setTimeout(() => {
                    inputs.forEach(input => input.classList.remove("error"));
                }, 400);
                
                // Limpa e foca no primeiro
                resetInputs(false);
                inputs[0]?.focus();
            }
        } catch (error) {
            showMessage("Erro de conexão. Tente novamente.");
            resetInputs(false);
        }
    }
    
    function resetButton() {
        btnValidate.innerHTML = "Acessar Configurações";
        updateButton();
    }
    
    // Event listeners dos inputs
    inputs.forEach((input, index) => {
        // Apenas números
        input.addEventListener("keypress", (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        // Input
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
        
        // Colar
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const paste = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 4);
            
            paste.split("").forEach((char, i) => {
                if (inputs[i]) inputs[i].value = char;
            });
            
            updateButton();
            
            if (paste.length === 4) {
                btnValidate.focus();
            } else if (inputs[paste.length]) {
                inputs[paste.length].focus();
            }
        });
    });
    
    // Botões
    btnValidate?.addEventListener("click", validatePin);
    btnCancel?.addEventListener("click", () => {
        window.PinValidateModal.hide();
    });
    
    // Fechar ao clicar fora
    overlay?.addEventListener("click", (e) => {
        if (e.target === overlay) {
            window.PinValidateModal.hide();
        }
    });
})();
</script>
';
    }
}
