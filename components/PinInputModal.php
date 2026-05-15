<?php
/**
 * PinInputModal Component
 *
 * Modal minimalista de entrada de PIN de 4 dígitos.
 * O usuario digita com o teclado do proprio dispositivo — sem numpad na tela.
 *
 * Uso:
 *   require_once __DIR__ . '/../components/PinInputModal.php';
 *   PinInputModal::render();
 *
 * API JS (window.PinInputModal):
 *   .open(options)    — abre o modal
 *   .close()          — fecha e limpa
 *
 * options = {
 *   title?       : string,       // default "Digite o PIN"
 *   subtitle?    : string,       // default "Insira os 4 digitos"
 *   onConfirm    : (pin) => {},  // chamado quando PIN completo e confirmado
 *   onCancel?    : () => {},
 *   confirmLabel?: string,       // texto do botao principal
 *   cancelLabel? : string,       // texto do botao cancelar
 *   autoSubmit?  : bool,         // true = confirma ao preencher 4 digitos automaticamente
 * }
 */

class PinInputModal
{
    public static function render(): void
    {
        echo self::styles();
        echo self::html();
        echo self::scripts();
    }

    // ── CSS ───────────────────────────────────────────────────────────────────

    private static function styles(): string
    {
        return <<<'HTML'
<style id="pim-styles">

/* ── PinInputModal ── */

.pim-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .82);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 10500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: opacity .22s ease, visibility .22s ease;
}

.pim-overlay.open {
    opacity: 1;
    visibility: visible;
}

.pim-card {
    background: #111;
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 16px;
    width: 100%;
    max-width: 340px;
    padding: 28px 24px 24px;
    display: flex;
    flex-direction: column;
    gap: 0;
    transform: translateY(12px) scale(.97);
    transition: transform .22s ease;
}

.pim-overlay.open .pim-card {
    transform: translateY(0) scale(1);
}

/* Header */
.pim-header {
    text-align: center;
    margin-bottom: 24px;
}

.pim-title {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 6px;
    line-height: 1.3;
}

.pim-subtitle {
    color: rgba(255, 255, 255, .45);
    font-size: .83rem;
    margin: 0;
    line-height: 1.5;
}

/* Inputs */
.pim-inputs {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 20px;
}

.pim-input {
    width: 52px;
    height: 60px;
    background: rgba(255, 255, 255, .05);
    border: 1.5px solid rgba(255, 255, 255, .1);
    border-radius: 12px;
    color: #fff;
    font-size: 1.4rem;
    font-weight: 700;
    text-align: center;
    outline: none;
    transition: border-color .15s, background .15s;
    -webkit-appearance: none;
    appearance: none;
    caret-color: transparent;
}

.pim-input:focus {
    border-color: rgba(255, 255, 255, .35);
    background: rgba(255, 255, 255, .08);
}

.pim-input.filled {
    border-color: rgba(255, 255, 255, .25);
    background: rgba(255, 255, 255, .07);
}

.pim-input.pim-error {
    border-color: #ef4444;
    animation: pim-shake .35s ease;
}

@keyframes pim-shake {
    0%, 100% { transform: translateX(0); }
    25%       { transform: translateX(-5px); }
    75%       { transform: translateX(5px); }
}

/* Mensagem */
.pim-msg {
    min-height: 18px;
    font-size: .8rem;
    text-align: center;
    color: #ef4444;
    margin-bottom: 16px;
    transition: opacity .15s;
}

.pim-msg:empty { opacity: 0; }

/* Acoes */
.pim-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pim-btn {
    width: 100%;
    padding: 13px 20px;
    border-radius: 10px;
    font-size: .92rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .15s, background .15s;
    line-height: 1;
}

.pim-btn:disabled { opacity: .35; cursor: not-allowed; }

.pim-btn-confirm {
    background: #fff;
    color: #000;
}

.pim-btn-confirm:hover:not(:disabled) { opacity: .88; }

.pim-btn-cancel {
    background: transparent;
    color: rgba(255, 255, 255, .45);
    border: 1px solid rgba(255, 255, 255, .1);
}

.pim-btn-cancel:hover { color: rgba(255, 255, 255, .75); border-color: rgba(255, 255, 255, .2); }

</style>
HTML;
    }

    // ── HTML ──────────────────────────────────────────────────────────────────

    private static function html(): string
    {
        return <<<'HTML'
<div id="pim-overlay" class="pim-overlay" role="dialog" aria-modal="true" aria-label="Inserir PIN">

    <div class="pim-card">

        <div class="pim-header">
            <h2 class="pim-title" id="pim-title">Digite o PIN</h2>
            <p class="pim-subtitle" id="pim-subtitle">Insira os 4 digitos de seguranca</p>
        </div>

        <div class="pim-inputs" id="pim-inputs">
            <input class="pim-input" type="password" inputmode="numeric" pattern="[0-9]*"
                   maxlength="1" autocomplete="one-time-code" data-idx="0" aria-label="Digito 1">
            <input class="pim-input" type="password" inputmode="numeric" pattern="[0-9]*"
                   maxlength="1" autocomplete="off" data-idx="1" aria-label="Digito 2">
            <input class="pim-input" type="password" inputmode="numeric" pattern="[0-9]*"
                   maxlength="1" autocomplete="off" data-idx="2" aria-label="Digito 3">
            <input class="pim-input" type="password" inputmode="numeric" pattern="[0-9]*"
                   maxlength="1" autocomplete="off" data-idx="3" aria-label="Digito 4">
        </div>

        <p class="pim-msg" id="pim-msg" role="alert"></p>

        <div class="pim-actions">
            <button class="pim-btn pim-btn-confirm" id="pim-confirm" disabled>Confirmar</button>
            <button class="pim-btn pim-btn-cancel"  id="pim-cancel">Cancelar</button>
        </div>

    </div>

</div>
HTML;
    }

    // ── JS ────────────────────────────────────────────────────────────────────

    private static function scripts(): string
    {
        return <<<'HTML'
<script id="pim-scripts">
(function () {
    'use strict';

    const overlay  = document.getElementById('pim-overlay');
    const card     = overlay?.querySelector('.pim-card');
    const titleEl  = document.getElementById('pim-title');
    const subEl    = document.getElementById('pim-subtitle');
    const msgEl    = document.getElementById('pim-msg');
    const confirmBtn = document.getElementById('pim-confirm');
    const cancelBtn  = document.getElementById('pim-cancel');
    const inputs   = Array.from(overlay?.querySelectorAll('.pim-input') ?? []);

    if (!overlay) return;

    // Estado interno
    let _opts = {};

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getPin() {
        return inputs.map(i => i.value).join('');
    }

    function syncButton() {
        const pin = getPin();
        confirmBtn.disabled = pin.length !== 4;
        inputs.forEach(i => {
            i.classList.toggle('filled', i.value !== '');
        });
    }

    function clearAll() {
        inputs.forEach(i => {
            i.value = '';
            i.classList.remove('filled', 'pim-error');
        });
        msgEl.textContent = '';
        confirmBtn.disabled = true;
    }

    function showError(msg) {
        msgEl.textContent = msg;
        inputs.forEach(i => i.classList.add('pim-error'));
        setTimeout(() => inputs.forEach(i => i.classList.remove('pim-error')), 400);
    }

    function focusFirst() {
        requestAnimationFrame(() => inputs[0]?.focus());
    }

    // ── API publica ───────────────────────────────────────────────────────────

    window.PinInputModal = {

        open(opts = {}) {
            _opts = opts;
            clearAll();

            titleEl.textContent   = opts.title        ?? 'Digite o PIN';
            subEl.textContent     = opts.subtitle      ?? 'Insira os 4 digitos de seguranca';
            confirmBtn.textContent = opts.confirmLabel  ?? 'Confirmar';
            cancelBtn.textContent  = opts.cancelLabel   ?? 'Cancelar';

            overlay.classList.add('open');
            focusFirst();
        },

        close() {
            overlay.classList.remove('open');
            clearAll();
        },

        showError(msg) {
            showError(msg);
        }
    };

    // ── Inputs ────────────────────────────────────────────────────────────────

    inputs.forEach((input, idx) => {

        // Apenas numeros
        input.addEventListener('keypress', e => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });

        input.addEventListener('input', () => {
            const val = input.value.replace(/\D/g, '').slice(0, 1);
            input.value = val;

            if (val && idx < inputs.length - 1) inputs[idx + 1].focus();

            syncButton();

            // Auto-submit quando preenchido e opcao ativa
            if (_opts.autoSubmit && getPin().length === 4) {
                setTimeout(() => handleConfirm(), 80);
            }
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].value = '';
                syncButton();
            }
            if (e.key === 'Enter' && getPin().length === 4) handleConfirm();
        });

        // Colar o PIN completo (ex: copiar do SMS)
        input.addEventListener('paste', e => {
            e.preventDefault();
            const text = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
            text.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
            syncButton();
            const next = inputs[Math.min(text.length, inputs.length - 1)];
            next?.focus();
            if (_opts.autoSubmit && getPin().length === 4) setTimeout(() => handleConfirm(), 80);
        });
    });

    // ── Confirmar ─────────────────────────────────────────────────────────────

    function handleConfirm() {
        const pin = getPin();
        if (pin.length !== 4) return;
        if (typeof _opts.onConfirm === 'function') _opts.onConfirm(pin);
    }

    confirmBtn.addEventListener('click', handleConfirm);

    // ── Cancelar ──────────────────────────────────────────────────────────────

    cancelBtn.addEventListener('click', () => {
        window.PinInputModal.close();
        if (typeof _opts.onCancel === 'function') _opts.onCancel();
    });

    // Fechar ao clicar fora do card
    overlay.addEventListener('click', e => {
        if (e.target === overlay) {
            window.PinInputModal.close();
            if (typeof _opts.onCancel === 'function') _opts.onCancel();
        }
    });

    // ESC
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            window.PinInputModal.close();
            if (typeof _opts.onCancel === 'function') _opts.onCancel();
        }
    });

})();
</script>
HTML;
    }
}
