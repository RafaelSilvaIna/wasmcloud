<?php
/**
 * COMPONENTE: SecurityLoginCode
 *
 * Seção "Código de Acesso" dentro do painel de Segurança.
 * CSS e JS são injetados inline (componente auto-contido).
 *
 * Uso:
 *   require_once __DIR__ . '/../components/SecurityLoginCode.php';
 *   SecurityLoginCode::render();
 */

declare(strict_types=1);

class SecurityLoginCode
{
    public static function render(): void
    {
        ?>
<!-- ════════════════════════════════════════════════════════════════════
     COMPONENTE: Código de Acesso
     ═══════════════════════════════════════════════════════════════════ -->
<div class="sec-card" id="sec-code-card">

    <!-- Cabeçalho -->
    <div class="sec-card-header">
        <div class="sec-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M12 2a4 4 0 0 0-4 4v2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-3V6a4 4 0 0 0-4-4z"/>
                <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
            </svg>
        </div>
        <div class="sec-card-title-group">
            <h2 class="sec-card-title">Código de Acesso</h2>
            <p class="sec-card-desc">
                Crie um código de 4 dígitos para entrar na sua conta sem precisar digitar a senha.
            </p>
        </div>
        <div class="sec-card-badge" id="code-status-badge" aria-live="polite">
            <span class="badge-dot"></span>
            <span class="badge-label">Carregando…</span>
        </div>
    </div>

    <!-- Corpo -->
    <div class="sec-card-body">

        <!-- Estado: sem código cadastrado -->
        <div id="code-state-empty" class="sec-state" hidden>
            <p class="sec-state-text">
                Nenhum código cadastrado. Crie um para habilitar o login rápido.
            </p>
            <button type="button" class="sec-btn sec-btn-primary" id="code-btn-create">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Criar código
            </button>
        </div>

        <!-- Estado: código ativo -->
        <div id="code-state-active" class="sec-state" hidden>
            <div class="code-active-row">
                <div class="code-dots" aria-label="Código de 4 dígitos definido">
                    <span class="code-dot"></span>
                    <span class="code-dot"></span>
                    <span class="code-dot"></span>
                    <span class="code-dot"></span>
                </div>
                <p class="code-changed-at" id="code-changed-at"></p>
            </div>
            <div class="sec-btn-row">
                <button type="button" class="sec-btn sec-btn-ghost" id="code-btn-edit">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Alterar código
                </button>
                <button type="button" class="sec-btn sec-btn-danger" id="code-btn-remove">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                    Remover código
                </button>
            </div>
        </div>

        <!-- Formulário de criação/edição (inicialmente oculto) -->
        <form id="code-form" class="sec-form" hidden novalidate>
            <fieldset class="code-inputs-fieldset">
                <legend class="sr-only">Insira o código de 4 dígitos</legend>
                <div class="code-inputs" role="group" aria-label="Dígitos do código">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-digit" data-index="0" aria-label="Dígito 1"
                           autocomplete="one-time-code">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-digit" data-index="1" aria-label="Dígito 2">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-digit" data-index="2" aria-label="Dígito 3">
                    <input type="password" inputmode="numeric" maxlength="1"
                           class="code-digit" data-index="3" aria-label="Dígito 4">
                </div>
            </fieldset>

            <p class="code-form-hint" id="code-form-hint" aria-live="assertive"></p>

            <div class="sec-btn-row">
                <button type="submit" class="sec-btn sec-btn-primary" id="code-btn-submit">
                    Salvar código
                </button>
                <button type="button" class="sec-btn sec-btn-ghost" id="code-btn-cancel">
                    Cancelar
                </button>
            </div>
        </form>

    </div><!-- /.sec-card-body -->

    <!-- Modal de confirmação de remoção — ancorado no .sec-card com overflow:hidden -->
    <div class="sec-confirm-overlay" id="code-remove-confirm" hidden>
        <div class="sec-confirm-box" role="alertdialog" aria-modal="true"
             aria-labelledby="confirm-remove-title">
            <h3 class="sec-confirm-title" id="confirm-remove-title">Remover código?</h3>
            <p class="sec-confirm-desc">
                Ao remover, o login via código ficará indisponível até você criar um novo.
            </p>
            <div class="sec-btn-row">
                <button type="button" class="sec-btn sec-btn-danger" id="code-confirm-remove">
                    Sim, remover
                </button>
                <button type="button" class="sec-btn sec-btn-ghost" id="code-cancel-remove">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

</div><!-- /#sec-code-card -->

<!-- ═══════════════════════════════════════���════════════════════════════
     CSS DO COMPONENTE (scoped por prefixo .sec-)
     ═══════════════════════════════════════════════════════════════════ -->
<style>
/* ── Garante que [hidden] não seja sobrescrito por regras de display ─────── */
#sec-code-card [hidden] {
    display: none !important;
}

/* ── Variáveis de componente (herdam do settings.css) ────────────────── */
.sec-card {
    background-color: var(--set-surface, #12151c);
    border: 1px solid var(--set-border, rgba(255,255,255,0.07));
    border-radius: var(--set-radius, 10px);
    overflow: hidden;
    margin-bottom: 16px;
    /* Necessário para o overlay de confirmação se ancorar corretamente */
    position: relative;
}

.sec-card-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid var(--set-border, rgba(255,255,255,0.07));
}

.sec-card-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 8px;
    background-color: var(--set-accent-dim, rgba(255,214,10,0.10));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--set-accent, #ffd60a);
    margin-top: 1px;
}
.sec-card-icon svg { width: 18px; height: 18px; }

.sec-card-title-group { flex: 1; min-width: 0; }

.sec-card-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--set-text-pure, #fff);
    margin: 0 0 3px;
}
.sec-card-desc {
    font-size: 13px;
    color: var(--set-text-secondary, #94a3b8);
    line-height: 1.5;
    margin: 0;
}

/* Badge de status */
.sec-card-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.03em;
    flex-shrink: 0;
    border: 1px solid transparent;
    text-transform: uppercase;
    transition: background-color 0.2s, color 0.2s;
}
.sec-card-badge.badge-active {
    background-color: rgba(16,185,129,0.12);
    border-color: rgba(16,185,129,0.25);
    color: #10b981;
}
.sec-card-badge.badge-active .badge-dot { background-color: #10b981; }

.sec-card-badge.badge-inactive {
    background-color: rgba(148,163,184,0.08);
    border-color: rgba(148,163,184,0.15);
    color: var(--set-text-secondary, #94a3b8);
}
.sec-card-badge.badge-inactive .badge-dot { background-color: #94a3b8; }

.sec-card-badge.badge-loading {
    background-color: transparent;
    color: var(--set-text-muted, #4a5568);
}
.badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Corpo */
.sec-card-body {
    padding: 20px 24px 22px;
    position: relative;
}

/* Estados */
.sec-state { }
.sec-state-text {
    font-size: 13.5px;
    color: var(--set-text-secondary, #94a3b8);
    margin: 0 0 16px;
    line-height: 1.55;
}

/* Código ativo */
.code-active-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}
.code-dots {
    display: flex;
    gap: 8px;
    align-items: center;
}
.code-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    background-color: var(--set-accent, #ffd60a);
    opacity: 0.85;
}
.code-changed-at {
    font-size: 12px;
    color: var(--set-text-muted, #4a5568);
    margin: 0;
}

/* Linha de botões */
.sec-btn-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

/* Botões */
.sec-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 7px;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s, opacity 0.15s;
    text-decoration: none;
    white-space: nowrap;
}
.sec-btn svg { width: 15px; height: 15px; flex-shrink: 0; }

.sec-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.sec-btn-primary {
    background-color: var(--set-accent, #ffd60a);
    color: #000;
    border-color: var(--set-accent, #ffd60a);
}
.sec-btn-primary:hover:not(:disabled) {
    background-color: #ffe033;
}

.sec-btn-ghost {
    background-color: var(--set-elevated, #1a1e28);
    color: var(--set-text-primary, #e2e8f0);
    border-color: var(--set-border-strong, rgba(255,255,255,0.13));
}
.sec-btn-ghost:hover:not(:disabled) {
    background-color: var(--set-highlight, #1f2430);
    border-color: rgba(255,255,255,0.22);
}

.sec-btn-danger {
    background-color: rgba(229,9,20,0.1);
    color: #e50914;
    border-color: rgba(229,9,20,0.25);
}
.sec-btn-danger:hover:not(:disabled) {
    background-color: rgba(229,9,20,0.18);
    border-color: rgba(229,9,20,0.4);
}

/* Formulário de código */
.sec-form { margin-top: 4px; }

.code-inputs-fieldset { border: none; padding: 0; margin: 0 0 14px; }

.code-inputs {
    display: flex;
    gap: 12px;
    justify-content: flex-start;
}

.code-digit {
    width: 54px;
    height: 64px;
    border-radius: 10px;
    border: 2px solid var(--set-border-strong, rgba(255,255,255,0.13));
    background-color: var(--set-elevated, #1a1e28);
    color: var(--set-text-pure, #fff);
    font-size: 26px;
    font-weight: 700;
    text-align: center;
    caret-color: transparent;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
    -webkit-appearance: none;
    appearance: none;
}
.code-digit:focus {
    border-color: var(--set-accent, #ffd60a);
    box-shadow: 0 0 0 3px rgba(255,214,10,0.12);
}
.code-digit.filled {
    border-color: rgba(255,214,10,0.4);
}
.code-digit.error {
    border-color: #e50914;
    box-shadow: 0 0 0 3px rgba(229,9,20,0.1);
    animation: shake-digit 0.3s ease;
}

@keyframes shake-digit {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-5px); }
    75%      { transform: translateX(5px); }
}

.code-form-hint {
    font-size: 12.5px;
    min-height: 18px;
    margin: 0 0 14px;
    line-height: 1.4;
}
.code-form-hint.hint-error   { color: #e50914; }
.code-form-hint.hint-success { color: #10b981; }

/* Modal de confirmação overlay — cobre todo o .sec-card */
.sec-confirm-overlay {
    position: absolute;
    inset: 0;
    background-color: rgba(10,12,16,0.90);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 20;
    padding: 16px;
}

.sec-confirm-box {
    background-color: var(--set-surface, #12151c);
    border: 1px solid var(--set-border-strong, rgba(255,255,255,0.13));
    border-radius: 12px;
    padding: 24px 28px;
    max-width: 320px;
    width: 100%;
    text-align: center;
}
.sec-confirm-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--set-text-pure, #fff);
    margin: 0 0 8px;
}
.sec-confirm-desc {
    font-size: 13px;
    color: var(--set-text-secondary, #94a3b8);
    line-height: 1.55;
    margin: 0 0 20px;
}
.sec-confirm-box .sec-btn-row { justify-content: center; }

/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    border: 0;
}

@media (max-width: 600px) {
    .sec-card-header { flex-wrap: wrap; }
    .sec-card-badge  { order: 3; margin-left: 50px; }
    .code-inputs     { gap: 8px; }
    .code-digit      { width: 46px; height: 56px; font-size: 22px; }
}
</style>

<!-- ════════════════════════════════════════════════════════════════════
     JS DO COMPONENTE
     ═══════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // ── Elementos ─────────────────��─────────────────────────────────────────
    const badge        = document.getElementById('code-status-badge');
    const stateEmpty   = document.getElementById('code-state-empty');
    const stateActive  = document.getElementById('code-state-active');
    const form         = document.getElementById('code-form');
    const digits       = document.querySelectorAll('.code-digit');
    const hint         = document.getElementById('code-form-hint');
    const btnCreate    = document.getElementById('code-btn-create');
    const btnEdit      = document.getElementById('code-btn-edit');
    const btnRemove    = document.getElementById('code-btn-remove');
    const btnSubmit    = document.getElementById('code-btn-submit');
    const btnCancel    = document.getElementById('code-btn-cancel');
    const changedAt    = document.getElementById('code-changed-at');
    const removeConf   = document.getElementById('code-remove-confirm');
    const btnConfRm    = document.getElementById('code-confirm-remove');
    const btnCancelRm  = document.getElementById('code-cancel-remove');

    // ── Estado local ────────────────────────────────────────────────────────
    let hasCode = false;

    // ── Helpers ──────────────────────────────────────────────────────────────
    function setBadge(state) {
        const dotEl   = badge.querySelector('.badge-dot');
        const labelEl = badge.querySelector('.badge-label');

        badge.classList.remove('badge-active', 'badge-inactive', 'badge-loading');

        if (state === 'active') {
            badge.classList.add('badge-active');
            labelEl.textContent = 'Ativo';
        } else if (state === 'inactive') {
            badge.classList.add('badge-inactive');
            labelEl.textContent = 'Inativo';
        } else {
            badge.classList.add('badge-loading');
            labelEl.textContent = 'Carregando…';
        }
    }

    function showState(state) {
        stateEmpty.hidden  = state !== 'empty';
        stateActive.hidden = state !== 'active';
        form.hidden        = state !== 'form';
    }

    function setHint(msg, type = '') {
        hint.textContent = msg;
        hint.className   = 'code-form-hint' + (type ? ' hint-' + type : '');
    }

    function getCode() {
        return Array.from(digits).map(d => d.value).join('');
    }

    function clearDigits() {
        digits.forEach(d => { d.value = ''; d.classList.remove('filled', 'error'); });
    }

    function formatDate(isoStr) {
        if (!isoStr) return '';
        const d = new Date(isoStr.replace(' ', 'T'));
        return 'Alterado em ' + d.toLocaleDateString('pt-BR', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
    }

    // ── Carrega status inicial ───────────────────────────────────────────────
    async function fetchStatus() {
        setBadge('loading');
        try {
            const res  = await fetch('/api/v3/security/code/status');
            const json = await res.json();

            if (!json.success) throw new Error();

            hasCode = json.data.has_code;

            if (hasCode) {
                setBadge('active');
                changedAt.textContent = formatDate(json.data.last_changed_at);
                showState('active');
            } else {
                setBadge('inactive');
                showState('empty');
            }
        } catch {
            setBadge('inactive');
            showState('empty');
        }
    }

    // ── Navegação de dígitos ─────────────────────────────────────────────────
    digits.forEach((input, i) => {
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && i > 0) {
                digits[i - 1].focus();
                digits[i - 1].value = '';
                digits[i - 1].classList.remove('filled');
                e.preventDefault();
            }
            if (e.key === 'ArrowLeft' && i > 0) { digits[i - 1].focus(); e.preventDefault(); }
            if (e.key === 'ArrowRight' && i < 3) { digits[i + 1].focus(); e.preventDefault(); }
        });

        input.addEventListener('input', () => {
            const val = input.value.replace(/\D/g, '');
            input.value = val ? val[val.length - 1] : '';
            input.classList.toggle('filled', !!input.value);
            input.classList.remove('error');
            setHint('');

            if (input.value && i < 3) digits[i + 1].focus();
        });

        // Permite colar código completo no primeiro campo
        input.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 4);
            pasted.split('').forEach((ch, idx) => {
                if (digits[idx]) {
                    digits[idx].value = ch;
                    digits[idx].classList.add('filled');
                }
            });
            const focus = Math.min(pasted.length, 3);
            digits[focus].focus();
        });
    });

    // ── Exibir formulário ────────────────────────────────────────────────────
    function openForm() {
        clearDigits();
        setHint('');
        showState('form');
        digits[0].focus();
    }

    btnCreate.addEventListener('click', openForm);
    btnEdit.addEventListener('click', openForm);

    btnCancel.addEventListener('click', () => {
        clearDigits();
        showState(hasCode ? 'active' : 'empty');
    });

    // ── Salvar código ────────────────────────────────────────────────────────
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const code = getCode();

        if (code.length < 4) {
            digits.forEach(d => d.classList.add('error'));
            setHint('Preencha todos os 4 dígitos.', 'error');
            digits.find(d => !d.value)?.focus();
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Salvando…';
        setHint('');

        try {
            const res  = await fetch('/api/v3/security/code/save', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ code }),
            });
            const json = await res.json();

            if (json.success) {
                setHint(json.message, 'success');
                hasCode = true;

                setTimeout(() => {
                    clearDigits();
                    setBadge('active');
                    changedAt.textContent = formatDate(new Date().toISOString());
                    showState('active');
                }, 900);
            } else {
                setHint(json.message || 'Erro ao salvar.', 'error');
                digits.forEach(d => d.classList.add('error'));
            }
        } catch {
            setHint('Erro de conexão. Tente novamente.', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Salvar código';
        }
    });

    // ── Remover código ─────────────────────────────────────��─────────────────
    btnRemove.addEventListener('click', () => {
        removeConf.hidden = false;
    });
    btnCancelRm.addEventListener('click', () => {
        removeConf.hidden = true;
    });
    btnConfRm.addEventListener('click', async () => {
        btnConfRm.disabled = true;
        btnConfRm.textContent = 'Removendo…';

        try {
            const res  = await fetch('/api/v3/security/code/remove', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            const json = await res.json();

            if (json.success) {
                hasCode = false;
                removeConf.hidden = true;
                setBadge('inactive');
                showState('empty');
            }
        } catch {
            // silent
        } finally {
            btnConfRm.disabled = false;
            btnConfRm.textContent = 'Sim, remover';
        }
    });

    // ── Init ─────────────────────────────────────────────────────────────────
    fetchStatus();
}());
</script>
<?php
    }
}
