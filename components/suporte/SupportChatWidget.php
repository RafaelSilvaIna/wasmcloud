<?php
declare(strict_types=1);

$isAuthenticated = isset($_SESSION['user_id']);
$userDisplayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? null;
?>

<!-- Error toast -->
<p id="sp-error-msg" role="alert" style="display:none;color:#fca5a5;font-size:.8rem;text-align:center;padding:4px 0;"></p>

<!-- ================================================================
     START SECTION — shown when no active chat
     ================================================================ -->
<section id="sp-start-section" class="sp-chat-wrap">
    <div class="sp-chat-header">
        <div class="sp-chat-header-left">
            <span class="sp-status-dot" id="sp-status-dot-start"></span>
            <div>
                <div class="sp-chat-title">Suporte Pipocine</div>
                <div class="sp-chat-sub">Atendimento disponivel das 12:00 as 21:30</div>
            </div>
        </div>
    </div>

    <div class="sp-chat-start">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
             stroke="#e50914" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <h3>Como podemos ajudar?</h3>
        <p>Descreva brevemente o assunto e nossa equipe responder&aacute; assim que possivel.</p>

        <?php if (!$isAuthenticated): ?>
        <input
            id="sp-guest-name"
            type="text"
            class="sp-input-subject"
            placeholder="Seu nome (opcional)"
            maxlength="80"
            autocomplete="name"
        >
        <?php endif; ?>

        <input
            id="sp-subject-input"
            type="text"
            class="sp-input-subject"
            placeholder="Assunto — ex: Problema no pagamento"
            maxlength="180"
        >

        <button id="sp-start-btn" type="button" class="sp-btn sp-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Iniciar atendimento
        </button>
    </div>
</section>

<!-- ================================================================
     CHAT SECTION — shown when chat is active
     ================================================================ -->
<section id="sp-chat-section" class="sp-chat-wrap" style="display:none">

    <!-- Header -->
    <div class="sp-chat-header">
        <div class="sp-chat-header-left">
            <span class="sp-status-dot" id="sp-status-dot"></span>
            <div>
                <div class="sp-chat-title">
                    Atendimento
                    <span id="sp-chat-subject" style="font-weight:400;color:#94a3b8;font-size:.82rem"></span>
                </div>
                <div class="sp-chat-sub">Suporte Pipocine</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <span id="sp-status-label" class="sp-chat-status-label">Aberto</span>
            <button id="sp-new-chat-btn" type="button" class="sp-btn sp-btn--ghost"
                    style="height:32px;padding:0 10px;font-size:.78rem">
                Novo chat
            </button>
        </div>
    </div>

    <!-- Reply bar -->
    <div id="sp-reply-bar" class="sp-reply-bar" role="status" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="#e50914" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
        </svg>
        <span id="sp-reply-text"></span>
        <button id="sp-reply-bar-close" type="button" class="sp-reply-bar-close" aria-label="Cancelar resposta">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- Messages -->
    <div id="sp-messages" class="sp-messages" role="log" aria-live="polite" aria-relevant="additions">
        <div class="sp-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                 stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>Aguardando a primeira mensagem...</p>
        </div>

        <!-- Typing indicator -->
        <div id="sp-typing" class="sp-typing" aria-label="Atendente digitando">
            <div class="sp-msg-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            </div>
            <div class="sp-typing-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <!-- Closed banner (hidden unless status=closed) -->
    <div id="sp-chat-closed" class="sp-chat-closed" style="display:none">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span>Atendimento encerrado. Se precisar de mais ajuda, abra um novo chat.</span>
        <button id="sp-new-chat-btn-2" type="button" class="sp-btn sp-btn--ghost"
                style="height:32px;padding:0 12px;font-size:.82rem">
            Novo atendimento
        </button>
    </div>

    <!-- Input bar -->
    <div id="sp-input-bar" class="sp-input-bar">
        <!-- Image preview -->
        <div id="sp-img-preview" class="sp-img-preview">
            <img id="sp-img-preview-img" src="" alt="Preview">
            <button id="sp-img-remove" type="button" class="sp-img-preview-rm" aria-label="Remover imagem">&times;</button>
        </div>

        <div class="sp-input-row">
            <textarea
                id="sp-textarea"
                class="sp-textarea"
                rows="1"
                placeholder="Escreva sua mensagem..."
                maxlength="2000"
                aria-label="Mensagem de suporte"
            ></textarea>
            <div class="sp-input-actions">
                <!-- Attach image -->
                <button id="sp-attach-btn" type="button" class="sp-icon-btn" title="Anexar imagem" aria-label="Anexar imagem">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </button>
                <input id="sp-file-input" type="file" accept="image/*" style="display:none" aria-hidden="true">

                <!-- Send -->
                <button id="sp-send-btn" type="button" class="sp-send-btn" title="Enviar" aria-label="Enviar mensagem">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Lightbox -->
<div id="sp-lightbox" class="sp-lightbox" role="dialog" aria-modal="true" aria-label="Imagem ampliada">
    <button id="sp-lightbox-close" class="sp-lightbox-close" aria-label="Fechar imagem">&times;</button>
    <img src="" alt="Imagem em tela cheia">
</div>

<script>
// Wire second new-chat button
document.getElementById('sp-new-chat-btn-2')?.addEventListener('click', function() {
    document.getElementById('sp-new-chat-btn')?.click();
});
</script>
