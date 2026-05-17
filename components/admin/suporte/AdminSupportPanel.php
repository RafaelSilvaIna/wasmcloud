<?php
declare(strict_types=1);

/**
 * AdminSupportPanel
 *
 * Painel administrativo de atendimento ao cliente.
 * Utiliza support-admin.js para comunicação com /api/suporte/admin/*.
 *
 * Tokens CSS herdados do admin: --admin-bg, --admin-surface, --admin-elevated,
 * --admin-border, --admin-text, --admin-muted, --admin-red.
 */
final class AdminSupportPanel
{
    public static function render(): void
    {
        ?>
        <style>
            /* ============================================================
               AdminSupportPanel — spa-* namespace
               ============================================================ */

            /* -- Layout geral -- */
            .spa-wrap {
                display: flex;
                flex-direction: column;
                height: calc(100vh - 112px);
                min-height: 480px;
                gap: 10px;
            }

            .spa-error-msg {
                margin: 0;
                padding: 10px 14px;
                border-radius: 8px;
                background: rgba(229, 9, 20, .1);
                border: 1px solid rgba(229, 9, 20, .22);
                color: #fca5a5;
                font-size: .82rem;
            }

            .spa-layout {
                display: flex;
                flex: 1;
                min-height: 0;
                border: 1px solid var(--admin-border);
                border-radius: 12px;
                overflow: hidden;
                background: var(--admin-surface);
            }

            /* ---- Sidebar ---- */
            .spa-sidebar {
                width: 290px;
                flex-shrink: 0;
                border-right: 1px solid var(--admin-border);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .spa-sidebar-head {
                padding: 14px 16px;
                border-bottom: 1px solid var(--admin-border);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                flex-shrink: 0;
            }

            .spa-sidebar-head h2 {
                margin: 0;
                font-size: .96rem;
                color: #fff;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .spa-badge-total {
                display: inline-block;
                background: var(--admin-red);
                color: #fff;
                font-size: .67rem;
                font-weight: 800;
                padding: 2px 6px;
                border-radius: 10px;
                min-width: 18px;
                text-align: center;
            }

            .spa-badge-total:empty { display: none; }

            .spa-count-label {
                font-size: .73rem;
                color: var(--admin-muted);
                white-space: nowrap;
            }

            .spa-filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                padding: 10px 10px 4px;
                flex-shrink: 0;
            }

            .spa-ops-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
                padding: 4px 10px 8px;
            }

            .spa-stat-card {
                min-width: 0;
                border: 1px solid var(--admin-border);
                border-radius: 8px;
                padding: 8px 9px;
                background: rgba(255, 255, 255, .02);
            }

            .spa-stat-card strong {
                display: block;
                color: #fff;
                font-size: .92rem;
                line-height: 1;
            }

            .spa-stat-card span {
                display: block;
                margin-top: 4px;
                color: var(--admin-muted);
                font-size: .67rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .spa-search-row {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 0 10px 8px;
                flex-shrink: 0;
            }

            .spa-search-input {
                flex: 1;
                min-width: 0;
                height: 34px;
                border: 1px solid var(--admin-border);
                border-radius: 8px;
                background: var(--admin-elevated);
                color: var(--admin-text);
                padding: 0 10px;
                font: inherit;
                font-size: .8rem;
                outline: none;
            }

            .spa-search-input:focus { border-color: rgba(229, 9, 20, .42); }

            .spa-refresh-btn {
                width: 34px;
                height: 34px;
                border: 1px solid var(--admin-border);
                border-radius: 8px;
                background: transparent;
                color: var(--admin-muted);
                display: grid;
                place-items: center;
                cursor: pointer;
            }

            .spa-refresh-btn:hover { color: #fff; border-color: rgba(148, 163, 184, .3); }

            .spa-filter-btn {
                flex: 1 1 auto;
                min-height: 28px;
                padding: 0 8px;
                border: 1px solid var(--admin-border);
                border-radius: 6px;
                background: transparent;
                color: var(--admin-muted);
                font-size: .74rem;
                font-weight: 650;
                cursor: pointer;
                transition: all .12s;
                white-space: nowrap;
            }

            .spa-filter-btn:hover      { color: #fff; border-color: rgba(148, 163, 184, .3); }
            .spa-filter-btn.active     { background: rgba(229, 9, 20, .13); border-color: rgba(229, 9, 20, .3); color: #f87171; }

            .spa-chat-list {
                flex: 1;
                overflow-y: auto;
                padding: 8px;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .spa-chat-list::-webkit-scrollbar { width: 4px; }
            .spa-chat-list::-webkit-scrollbar-track  { background: transparent; }
            .spa-chat-list::-webkit-scrollbar-thumb  { background: rgba(148, 163, 184, .15); border-radius: 2px; }

            .spa-chat-item {
                width: 100%;
                text-align: left;
                border: 1px solid var(--admin-border);
                border-radius: 8px;
                background: transparent;
                padding: 10px 12px;
                cursor: pointer;
                transition: all .12s;
                color: var(--admin-text);
            }

            .spa-chat-item:hover  { background: rgba(255, 255, 255, .03); border-color: rgba(148, 163, 184, .24); }
            .spa-chat-item.active { background: rgba(229, 9, 20, .09); border-color: rgba(229, 9, 20, .26); }

            .spa-chat-item-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 4px;
            }

            .spa-chat-item-name {
                font-weight: 700;
                font-size: .84rem;
                color: #fff;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .spa-chat-item-time {
                font-size: .71rem;
                color: var(--admin-muted);
                flex-shrink: 0;
            }

            .spa-chat-item-sub {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 6px;
                font-size: .77rem;
                color: var(--admin-muted);
                overflow: hidden;
            }

            .spa-chat-item-sub > span:first-child {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .spa-chat-item-preview {
                margin-top: 7px;
                min-height: 18px;
                color: var(--admin-muted);
                font-size: .75rem;
                line-height: 1.35;
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .spa-chat-item-bottom {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-top: 8px;
            }

            .spa-assignee {
                min-width: 0;
                color: var(--admin-muted);
                font-size: .68rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .spa-unread-badge {
                background: var(--admin-red);
                color: #fff;
                font-size: .65rem;
                font-weight: 800;
                padding: 1px 5px;
                border-radius: 8px;
                min-width: 16px;
                text-align: center;
                flex-shrink: 0;
            }

            .spa-status-pill {
                display: inline-flex;
                align-items: center;
                padding: 2px 7px;
                border-radius: 6px;
                font-size: .69rem;
                font-weight: 700;
                flex-shrink: 0;
            }

            .spa-status-open    { background: rgba(34, 197, 94, .12);  color: #4ade80; border: 1px solid rgba(34, 197, 94, .2); }
            .spa-status-pending { background: rgba(251, 191, 36, .12); color: #fbbf24; border: 1px solid rgba(251, 191, 36, .2); }
            .spa-status-closed  { background: rgba(148, 163, 184, .1); color: var(--admin-muted); border: 1px solid var(--admin-border); }

            .spa-list-empty {
                text-align: center;
                color: var(--admin-muted);
                font-size: .82rem;
                padding: 28px 0;
                margin: 0;
            }

            /* ---- Main ---- */
            .spa-main {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .spa-placeholder {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 12px;
                color: var(--admin-muted);
                font-size: .9rem;
                padding: 32px;
                text-align: center;
            }

            .spa-placeholder p { margin: 0; }

            /* ---- Chat panel ---- */
            .spa-panel {
                flex: 1;
                display: flex;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
            }

            .spa-panel-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 16px;
                border-bottom: 1px solid var(--admin-border);
                background: var(--admin-surface);
                flex-shrink: 0;
                min-height: 52px;
            }

            .spa-panel-title-wrap {
                display: flex;
                align-items: center;
                gap: 8px;
                min-width: 0;
            }

            #spa-chat-title {
                font-weight: 700;
                font-size: .9rem;
                color: #fff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 340px;
            }

            .spa-panel-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
            }

            .spa-action-btn {
                min-height: 30px;
                padding: 0 12px;
                border: 1px solid var(--admin-border);
                border-radius: 7px;
                background: transparent;
                color: var(--admin-muted);
                font-size: .79rem;
                font-weight: 700;
                cursor: pointer;
                transition: all .12s;
            }

            .spa-action-btn:hover               { color: #fff; border-color: rgba(148, 163, 184, .3); }
            .spa-action-btn--danger             { border-color: rgba(229, 9, 20, .3); color: #f87171; }
            .spa-action-btn--danger:hover       { background: rgba(229, 9, 20, .12); }

            /* ---- Tabs ---- */
            .spa-tabs-row {
                display: flex;
                border-bottom: 1px solid var(--admin-border);
                background: var(--admin-surface);
                flex-shrink: 0;
            }

            .spa-tab-btn {
                flex: 1;
                min-height: 36px;
                border: none;
                background: transparent;
                color: var(--admin-muted);
                font-size: .83rem;
                font-weight: 650;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                transition: all .12s;
            }

            .spa-tab-btn:hover  { color: #fff; }
            .spa-tab-btn.active { color: #fff; border-bottom-color: var(--admin-red); }

            /* ---- Messages ---- */
            .spa-messages {
                flex: 1;
                min-height: 0;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                background: var(--admin-bg);
            }

            .spa-messages::-webkit-scrollbar { width: 4px; }
            .spa-messages::-webkit-scrollbar-track { background: transparent; }
            .spa-messages::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .15); border-radius: 2px; }

            .spa-empty-msg {
                text-align: center;
                color: var(--admin-muted);
                font-size: .85rem;
                margin: auto;
                padding: 32px 0;
            }

            .spa-msg {
                display: flex;
                align-items: flex-end;
                gap: 8px;
                max-width: 86%;
            }

            .spa-msg--user   { align-self: flex-end;   flex-direction: row-reverse; }
            .spa-msg--admin  { align-self: flex-start; }
            .spa-msg--system { align-self: center; max-width: 100%; }

            .spa-msg-meta {
                font-size: .7rem;
                color: var(--admin-muted);
                margin-bottom: 3px;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .spa-msg--user .spa-msg-meta { justify-content: flex-end; }

            .spa-msg-bubble {
                padding: 8px 12px;
                border-radius: 10px;
                font-size: .85rem;
                line-height: 1.5;
                word-break: break-word;
                white-space: pre-wrap;
            }

            .spa-msg--admin .spa-msg-bubble {
                background: var(--admin-elevated);
                border: 1px solid var(--admin-border);
                color: var(--admin-text);
                border-bottom-left-radius: 3px;
            }

            .spa-msg--user .spa-msg-bubble {
                background: var(--admin-red);
                color: #fff;
                border-bottom-right-radius: 3px;
            }

            .spa-msg--system .spa-msg-bubble {
                background: transparent;
                border: 1px dashed var(--admin-border);
                color: var(--admin-muted);
                font-size: .78rem;
                font-style: italic;
                text-align: center;
                border-radius: 6px;
            }

            .spa-msg-reply {
                padding: 5px 8px;
                margin-bottom: 4px;
                border-left: 3px solid var(--admin-red);
                border-radius: 4px;
                background: rgba(229, 9, 20, .07);
                font-size: .76rem;
                color: var(--admin-muted);
                cursor: pointer;
            }

            .spa-msg-reply strong { color: var(--admin-text); }

            .spa-reply-btn {
                opacity: 0;
                background: none;
                border: 1px solid var(--admin-border);
                color: var(--admin-muted);
                border-radius: 5px;
                padding: 2px 6px;
                font-size: .7rem;
                cursor: pointer;
                transition: opacity .12s;
                align-self: center;
                flex-shrink: 0;
                white-space: nowrap;
            }

            .spa-msg:hover .spa-reply-btn { opacity: 1; }
            .spa-reply-btn:hover           { color: #fff; }

            .spa-msg-image {
                max-width: 220px;
                border-radius: 6px;
                margin-top: 6px;
                cursor: pointer;
                border: 1px solid var(--admin-border);
                display: block;
            }

            /* Typing indicator */
            .spa-typing-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 16px;
                font-size: .78rem;
                color: var(--admin-muted);
                flex-shrink: 0;
                border-top: 1px solid var(--admin-border);
                background: var(--admin-bg);
            }

            .spa-typing-dots { display: flex; gap: 3px; }

            .spa-typing-dots span {
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: var(--admin-muted);
                animation: spa-typing-bounce 1.4s infinite;
            }

            .spa-typing-dots span:nth-child(2) { animation-delay: .2s; }
            .spa-typing-dots span:nth-child(3) { animation-delay: .4s; }

            @keyframes spa-typing-bounce {
                0%, 80%, 100% { transform: translateY(0); opacity: .4; }
                40%           { transform: translateY(-4px); opacity: 1; }
            }

            /* Reply preview bar */
            .spa-reply-bar {
                display: none;
                align-items: center;
                gap: 8px;
                padding: 7px 14px;
                border-top: 1px solid var(--admin-border);
                background: var(--admin-elevated);
                font-size: .8rem;
                color: var(--admin-muted);
                flex-shrink: 0;
            }

            .spa-reply-bar.active { display: flex; }
            .spa-reply-bar strong { color: var(--admin-text); }

            .spa-icon-sm {
                background: none;
                border: none;
                color: var(--admin-muted);
                cursor: pointer;
                padding: 2px 4px;
                font-size: 1.1rem;
                margin-left: auto;
                line-height: 1;
            }

            .spa-icon-sm:hover { color: #fff; }

            /* Input area */
            .spa-input-bar {
                border-top: 1px solid var(--admin-border);
                background: var(--admin-surface);
                padding: 10px 12px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                flex-shrink: 0;
            }

            .spa-img-preview { display: none; position: relative; width: fit-content; }
            .spa-img-preview.active { display: block; }

            .spa-img-preview img {
                max-height: 70px;
                border-radius: 6px;
                border: 1px solid var(--admin-border);
            }

            #spa-img-remove {
                position: absolute;
                top: -6px;
                right: -6px;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #0f131a;
                border: 1px solid var(--admin-border);
                color: #fff;
                display: grid;
                place-items: center;
                cursor: pointer;
                font-size: .65rem;
                padding: 0;
                line-height: 1;
            }

            .spa-input-row {
                display: flex;
                align-items: flex-end;
                gap: 8px;
            }

            .spa-textarea {
                flex: 1;
                min-height: 38px;
                max-height: 110px;
                resize: none;
                border: 1px solid var(--admin-border);
                border-radius: 8px;
                background: var(--admin-elevated);
                color: var(--admin-text);
                padding: 8px 11px;
                font-size: .85rem;
                line-height: 1.45;
                font-family: inherit;
                outline: none;
                transition: border-color .12s;
                overflow-y: auto;
            }

            .spa-textarea:focus { border-color: rgba(229, 9, 20, .4); }

            .spa-input-actions {
                display: flex;
                align-items: center;
                gap: 5px;
                flex-shrink: 0;
            }

            .spa-icon-btn {
                width: 36px;
                height: 36px;
                border: 1px solid var(--admin-border);
                border-radius: 7px;
                background: transparent;
                color: var(--admin-muted);
                cursor: pointer;
                display: grid;
                place-items: center;
                transition: all .12s;
            }

            .spa-icon-btn:hover { color: #fff; border-color: rgba(148, 163, 184, .3); }
            .spa-icon-btn svg   { width: 15px; height: 15px; }

            .spa-send-btn {
                width: 36px;
                height: 36px;
                background: var(--admin-red);
                border: none;
                border-radius: 7px;
                color: #fff;
                cursor: pointer;
                display: grid;
                place-items: center;
                transition: opacity .12s;
                flex-shrink: 0;
            }

            .spa-send-btn:disabled         { opacity: .45; cursor: not-allowed; }
            .spa-send-btn:hover:not(:disabled) { opacity: .85; }
            .spa-send-btn svg { width: 14px; height: 14px; }

            /* User info tab panel */
            [data-spa-tab-panel="user"] {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                background: var(--admin-bg);
            }

            .spa-user-anon {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                padding: 24px 0;
                color: var(--admin-muted);
                font-size: .85rem;
                text-align: center;
            }

            .spa-user-icon-lg {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: var(--admin-elevated);
                border: 1px solid var(--admin-border);
                display: grid;
                place-items: center;
                color: var(--admin-muted);
                flex-shrink: 0;
            }

            .spa-user-detail {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .spa-user-avatar-wrap {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                overflow: hidden;
                border: 2px solid var(--admin-border);
            }

            .spa-user-avatar { width: 100%; height: 100%; object-fit: cover; }

            .spa-user-name { font-size: 1rem; font-weight: 700; color: #fff; }

            .spa-user-table {
                width: 100%;
                border-collapse: collapse;
                font-size: .81rem;
                text-align: left;
                margin-top: 4px;
            }

            .spa-user-table td {
                padding: 7px 8px;
                border-bottom: 1px solid var(--admin-border);
                color: var(--admin-muted);
                vertical-align: top;
            }

            .spa-user-table td:first-child {
                font-weight: 650;
                color: var(--admin-text);
                width: 110px;
                white-space: nowrap;
            }

            /* Lightbox */
            #spa-lightbox {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .87);
                z-index: 9999;
                align-items: center;
                justify-content: center;
            }

            #spa-lightbox:not([hidden]) { display: flex; }

            #spa-lightbox img {
                max-width: 90vw;
                max-height: 90vh;
                border-radius: 8px;
                object-fit: contain;
            }

            #spa-lightbox-close {
                position: fixed;
                top: 16px;
                right: 16px;
                background: rgba(0, 0, 0, .7);
                border: 1px solid rgba(255, 255, 255, .2);
                border-radius: 50%;
                width: 36px;
                height: 36px;
                color: #fff;
                font-size: 1.1rem;
                cursor: pointer;
                display: grid;
                place-items: center;
            }

            /* Tab panel visibility */
            [data-spa-tab-panel] { display: flex; flex-direction: column; flex: 1; min-height: 0; }
            [data-spa-tab-panel][hidden] { display: none; }

            /* Responsive */
            @media (max-width: 800px) {
                .spa-sidebar { width: 260px; }
                #spa-chat-title { max-width: 220px; }
                .spa-ops-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            @media (max-width: 600px) {
                .spa-layout    { flex-direction: column; }
                .spa-sidebar   { width: 100%; height: 42%; border-right: none; border-bottom: 1px solid var(--admin-border); }
                .spa-main      { height: 56%; }
                .spa-wrap      { height: calc(100vh - 80px); }
                .spa-panel-head { flex-wrap: wrap; align-items: flex-start; }
                #spa-chat-title { max-width: calc(100vw - 48px); }
                .spa-input-row { align-items: stretch; }
                .spa-textarea { min-height: 42px; }
            }
        </style>

        <section data-admin-route="suporte" class="admin-route-panel" hidden>
            <div class="spa-wrap">

                <!-- Error toast -->
                <p id="spa-error" class="spa-error-msg" hidden role="alert"></p>

                <div class="spa-layout">

                    <!-- ====================== SIDEBAR ====================== -->
                    <aside class="spa-sidebar">
                        <div class="spa-sidebar-head">
                            <h2>
                                Atendimentos
                                <span id="spa-unread-badge-total" class="spa-badge-total"></span>
                            </h2>
                            <span id="spa-count-open" class="spa-count-label">0</span>
                        </div>

                        <div class="spa-filter-row">
                            <button type="button" data-sp-admin-filter="open"    class="spa-filter-btn active">Abertos</button>
                            <button type="button" data-sp-admin-filter="pending" class="spa-filter-btn">Pendentes</button>
                            <button type="button" data-sp-admin-filter="closed"  class="spa-filter-btn">Encerrados</button>
                            <button type="button" data-sp-admin-filter=""        class="spa-filter-btn">Todos</button>
                        </div>

                        <div class="spa-ops-row" aria-label="Resumo dos atendimentos">
                            <div class="spa-stat-card">
                                <strong id="spa-stat-open">0</strong>
                                <span>Abertos</span>
                            </div>
                            <div class="spa-stat-card">
                                <strong id="spa-stat-pending">0</strong>
                                <span>Pendentes</span>
                            </div>
                            <div class="spa-stat-card">
                                <strong id="spa-stat-closed">0</strong>
                                <span>Fechados</span>
                            </div>
                        </div>

                        <div class="spa-search-row">
                            <input id="spa-search" class="spa-search-input" type="search"
                                   placeholder="Buscar por usuario, email ou assunto" autocomplete="off">
                            <button id="spa-refresh-btn" type="button" class="spa-refresh-btn"
                                    title="Atualizar" aria-label="Atualizar atendimentos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 12a9 9 0 1 1-3-6.7"/>
                                    <path d="M21 3v6h-6"/>
                                </svg>
                            </button>
                        </div>

                        <div id="spa-chat-list" class="spa-chat-list" role="list">
                            <p class="spa-list-empty">Carregando atendimentos&hellip;</p>
                        </div>
                    </aside>

                    <!-- ====================== MAIN ========================= -->
                    <div class="spa-main">

                        <!-- Placeholder (nenhum chat selecionado) -->
                        <div id="spa-placeholder" class="spa-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="1.5"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <p>Selecione um atendimento para come&ccedil;ar.</p>
                        </div>

                        <!-- Chat panel (aparece ao selecionar um chat) -->
                        <div id="spa-chat-panel" class="spa-panel" style="display:none">

                            <!-- Header do chat -->
                            <div class="spa-panel-head">
                                <div class="spa-panel-title-wrap">
                                    <span id="spa-chat-title">&mdash;</span>
                                    <span id="spa-chat-status" class="spa-status-pill"></span>
                                </div>
                                <div class="spa-panel-actions">
                                    <button id="spa-close-btn"  type="button" class="spa-action-btn spa-action-btn--danger">Encerrar</button>
                                    <button id="spa-reopen-btn" type="button" class="spa-action-btn" hidden>Reabrir</button>
                                </div>
                            </div>

                            <!-- Tabs: Chat | Usuário -->
                            <div class="spa-tabs-row" role="tablist">
                                <button type="button" data-spa-tab="chat" data-spa-tap="chat"
                                        class="spa-tab-btn active" role="tab" aria-selected="true">Chat</button>
                                <button type="button" data-spa-tab="user" data-spa-tap="user"
                                        class="spa-tab-btn" role="tab" aria-selected="false">Usu&aacute;rio</button>
                            </div>

                            <!-- ---- Tab: Chat ---- -->
                            <div data-spa-tab-panel="chat" data-spa-tap-panel="chat" role="tabpanel">

                                <!-- Reply preview bar -->
                                <div id="spa-reply-bar" class="spa-reply-bar" role="status" aria-live="polite">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                                         viewBox="0 0 24 24" fill="none" stroke="#e50914" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="9 17 4 12 9 7"/>
                                        <path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                                    </svg>
                                    <span id="spa-reply-text"></span>
                                    <button id="spa-reply-close" type="button" class="spa-icon-sm" aria-label="Cancelar resposta">&times;</button>
                                </div>

                                <!-- Mensagens -->
                                <div id="spa-messages" class="spa-messages" role="log"
                                     aria-live="polite" aria-relevant="additions">
                                    <p class="spa-empty-msg">Nenhuma mensagem ainda.</p>
                                </div>

                                <!-- Typing indicator -->
                                <div id="spa-typing" class="spa-typing-indicator" hidden
                                     aria-label="Usu&aacute;rio digitando">
                                    <div class="spa-typing-dots" aria-hidden="true">
                                        <span></span><span></span><span></span>
                                    </div>
                                    <span>Usu&aacute;rio digitando&hellip;</span>
                                </div>

                                <!-- Input bar -->
                                <div class="spa-input-bar">
                                    <!-- Preview de imagem -->
                                    <div id="spa-img-preview" class="spa-img-preview">
                                        <img id="spa-img-preview-img" src="" alt="Preview">
                                        <button id="spa-img-remove" type="button" aria-label="Remover imagem">&times;</button>
                                    </div>

                                    <div class="spa-input-row">
                                        <textarea
                                            id="spa-textarea"
                                            class="spa-textarea"
                                            rows="1"
                                            placeholder="Escreva uma resposta ao usu&aacute;rio..."
                                            maxlength="2000"
                                            aria-label="Mensagem de resposta"
                                        ></textarea>
                                        <div class="spa-input-actions">
                                            <!-- Anexar imagem -->
                                            <button id="spa-attach-btn" type="button" class="spa-icon-btn"
                                                    title="Anexar imagem" aria-label="Anexar imagem">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                     fill="none" stroke="currentColor" stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            </button>
                                            <input id="spa-file-input" type="file" accept="image/*"
                                                   style="display:none" aria-hidden="true">

                                            <!-- Enviar -->
                                            <button id="spa-send-btn" type="button" class="spa-send-btn"
                                                    title="Enviar" aria-label="Enviar mensagem">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                     fill="none" stroke="currentColor" stroke-width="2.5"
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ---- Tab: Usuário ---- -->
                            <div id="spa-user-info"
                                 data-spa-tab-panel="user" data-spa-tap-panel="user"
                                 role="tabpanel" hidden>
                            </div>

                        </div><!-- /spa-panel -->
                    </div><!-- /spa-main -->
                </div><!-- /spa-layout -->
            </div><!-- /spa-wrap -->

            <!-- Lightbox -->
            <div id="spa-lightbox" hidden role="dialog" aria-modal="true" aria-label="Imagem ampliada">
                <button id="spa-lightbox-close" aria-label="Fechar imagem">&times;</button>
                <img src="" alt="Imagem em tela cheia">
            </div>

            <script src="/assets/js/support-admin.js" defer></script>
        </section>
        <?php
    }
}
