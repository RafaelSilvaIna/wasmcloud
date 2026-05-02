<?php
/**
 * CommentsSection — Pipocine v3
 *
 * Componente de comentários inspirado no design Netflix.
 * Exporta apenas o HTML/estrutura — sem lógica PHP de negócio.
 * Toda a interação é gerida pelo JS em assets/js/comments.js.
 *
 * Uso na view:
 *   require_once __DIR__ . '/../components/CommentsSection.php';
 *   CommentsSection::render();
 */

class CommentsSection
{
    /**
     * Renderiza o container completo do painel de comentários.
     * Passa os atributos data- para que o JS inicialize com os
     * parâmetros corretos de conteúdo.
     */
    public static function render(): void
    {
?>
<!-- ╔══════════════════════════════════════════════════════════╗
     ║  PIPOCINE — SEÇÃO DE COMENTÁRIOS v3                     ║
     ║  Renderizado pelo componente CommentsSection.php         ║
     ╚══════════════════════════════════════════════════════════╝ -->
<link rel="stylesheet" href="/assets/css/comments.css">

<!-- ── Botão flutuante para abrir/fechar o painel ─────────────── -->
<button
    id="pip-comments-fab"
    class="pip-comments-fab"
    aria-label="Abrir comentários"
    aria-expanded="false"
    aria-controls="pip-comments-panel"
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         aria-hidden="true">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <span class="pip-comments-fab-label">Comentários</span>
    <span class="pip-comments-fab-badge" id="pip-comments-count-badge" aria-label="Total de comentários"></span>
</button>

<!-- ── Painel lateral de comentários ──────────────────────────── -->
<aside
    id="pip-comments-panel"
    class="pip-comments-panel"
    role="complementary"
    aria-label="Comentários"
    aria-hidden="true"
>
    <!-- Cabeçalho do painel -->
    <header class="pip-cp-header">
        <div class="pip-cp-header-left">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="pip-cp-header-icon" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <h2 class="pip-cp-title">Comentários</h2>
            <span class="pip-cp-total" id="pip-cp-total-label"></span>
        </div>
        <button
            class="pip-cp-close"
            id="pip-comments-close"
            aria-label="Fechar comentários"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
    </header>

    <!-- Campo de escrita do comentário -->
    <div class="pip-cp-compose" id="pip-cp-compose">
        <div class="pip-cp-compose-avatar">
            <img
                id="pip-cp-my-avatar"
                src="https://api.dicebear.com/7.x/adventurer/svg?seed=default"
                alt="Seu avatar"
                class="pip-cp-avatar-img"
                loading="lazy"
            >
        </div>
        <div class="pip-cp-compose-field">
            <!-- Indicador de resposta/menção ativa -->
            <div class="pip-cp-reply-indicator" id="pip-cp-reply-indicator" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     style="width:12px;height:12px;flex-shrink:0" aria-hidden="true">
                    <polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                </svg>
                <span id="pip-cp-reply-to-label">Respondendo a @alguém</span>
                <button class="pip-cp-reply-cancel" id="pip-cp-reply-cancel" aria-label="Cancelar resposta">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         style="width:12px;height:12px" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="pip-cp-textarea-wrap">
                <textarea
                    id="pip-cp-textarea"
                    class="pip-cp-textarea"
                    placeholder="Adicionar um comentário... (use @username para mencionar)"
                    rows="1"
                    maxlength="2000"
                    aria-label="Escreva seu comentário"
                    autocomplete="off"
                    spellcheck="true"
                ></textarea>
            </div>

            <div class="pip-cp-compose-actions">
                <span class="pip-cp-char-count" id="pip-cp-char-count" aria-live="polite">0 / 2000</span>
                <button
                    class="pip-cp-submit"
                    id="pip-cp-submit"
                    aria-label="Publicar comentário"
                    disabled
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         style="width:16px;height:16px" aria-hidden="true">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Publicar
                </button>
            </div>

            <!-- Dropdown de sugestões de @menção -->
            <ul
                id="pip-cp-mention-dropdown"
                class="pip-cp-mention-dropdown"
                role="listbox"
                aria-label="Sugestões de menção"
                style="display:none;"
            ></ul>
        </div>
    </div>

    <!-- Lista de comentários -->
    <div class="pip-cp-list-wrap" id="pip-cp-list-wrap">
        <!-- Estado de loading -->
        <div class="pip-cp-loading" id="pip-cp-loading" aria-live="polite" aria-label="Carregando comentários">
            <div class="pip-cp-spinner"></div>
            <span>Carregando comentários...</span>
        </div>

        <!-- Estado vazio -->
        <div class="pip-cp-empty" id="pip-cp-empty" style="display:none;" aria-live="polite">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 class="pip-cp-empty-icon" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p class="pip-cp-empty-title">Nenhum comentário ainda</p>
            <p class="pip-cp-empty-desc">Seja o primeiro a comentar!</p>
        </div>

        <!-- Lista real de comentários -->
        <ul
            class="pip-cp-list"
            id="pip-cp-list"
            style="display:none;"
            aria-label="Lista de comentários"
        ></ul>

        <!-- Botão carregar mais -->
        <button
            class="pip-cp-load-more"
            id="pip-cp-load-more"
            style="display:none;"
            aria-label="Carregar mais comentários"
        >
            Carregar mais
        </button>
    </div>
</aside>

<!-- ── Overlay escurecido (mobile) ────────────────────────────── -->
<div id="pip-comments-overlay" class="pip-comments-overlay" aria-hidden="true"></div>

<!-- ── Templates HTML (clonados pelo JS) ──────────────────────── -->

<!-- Template: Comentário / Resposta -->
<template id="tpl-pip-comment">
    <li class="pip-comment" data-id="" data-profile-id="" data-owner="false">
        <div class="pip-comment-inner">
            <!-- Avatar -->
            <div class="pip-comment-avatar-wrap">
                <img class="pip-comment-avatar" src="" alt="" loading="lazy">
            </div>
            <!-- Corpo -->
            <div class="pip-comment-body-wrap">
                <div class="pip-comment-header">
                    <span class="pip-comment-author"></span>
                    <span class="pip-comment-username"></span>
                    <span class="pip-comment-sep" aria-hidden="true">·</span>
                    <time class="pip-comment-time" datetime=""></time>
                    <span class="pip-comment-edited" style="display:none;">(editado)</span>
                </div>
                <div class="pip-comment-text"></div>
                <div class="pip-comment-actions">
                    <!-- Like -->
                    <button class="pip-comment-action-btn pip-like-btn" aria-label="Curtir comentário" aria-pressed="false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <span class="pip-like-count"></span>
                    </button>
                    <!-- Responder -->
                    <button class="pip-comment-action-btn pip-reply-btn" aria-label="Responder comentário">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                        Responder
                    </button>
                    <!-- Ver respostas -->
                    <button class="pip-comment-action-btn pip-toggle-replies-btn" style="display:none;" aria-label="Ver respostas" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="width:14px;height:14px" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                        <span class="pip-replies-label"></span>
                    </button>
                    <!-- Menu dono (editar/deletar) — visível apenas para autor -->
                    <div class="pip-comment-owner-menu" style="display:none;">
                        <button class="pip-comment-action-btn pip-edit-btn" aria-label="Editar comentário">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </button>
                        <button class="pip-comment-action-btn pip-delete-btn" aria-label="Excluir comentário">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            Excluir
                        </button>
                    </div>
                </div>

                <!-- Sub-lista de respostas -->
                <ul class="pip-replies-list" aria-label="Respostas" style="display:none;"></ul>
            </div>
        </div>
    </li>
</template>

<script src="/assets/js/comments.js" defer></script>
<?php
    }
}
