<?php
declare(strict_types=1);

final class AdminAdsReviewPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .adr-wrap {
                display: grid;
                gap: 14px;
            }

            .adr-head {
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 18px;
            }

            .adr-head h2 {
                margin: 0 0 6px;
                color: #fff;
            }

            .adr-head p {
                margin: 0;
                color: var(--admin-muted);
            }

            .adr-live {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 36px;
                border: 1px solid rgba(34, 197, 94, .24);
                border-radius: 999px;
                padding: 0 12px;
                color: #bbf7d0;
                background: rgba(34, 197, 94, .08);
                font-size: .8rem;
                font-weight: 750;
            }

            .adr-live::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #22c55e;
                box-shadow: 0 0 0 0 rgba(34,197,94,.4);
                animation: adr-pulse 1.8s infinite;
            }

            @keyframes adr-pulse {
                0% { box-shadow: 0 0 0 0 rgba(34,197,94,.45); }
                70% { box-shadow: 0 0 0 10px rgba(34,197,94,0); }
                100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
            }

            .adr-kpis {
                display: grid;
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: 10px;
            }

            .adr-kpi,
            .adr-shell,
            .adr-queue-item,
            .adr-detail {
                border: 1px solid var(--admin-border);
                border-radius: 14px;
                background: var(--admin-surface);
            }

            .adr-kpi {
                padding: 14px;
            }

            .adr-kpi span {
                display: block;
                color: var(--admin-muted);
                font-size: .75rem;
            }

            .adr-kpi strong {
                display: block;
                margin-top: 8px;
                color: #fff;
                font-size: 1.35rem;
            }

            .adr-tools {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .adr-tools input {
                min-height: 42px;
                min-width: min(320px, 100%);
                border: 1px solid var(--admin-border);
                border-radius: 10px;
                color: #fff;
                background: #090c12;
                padding: 0 12px;
            }

            .adr-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 7px;
            }

            .adr-filter,
            .adr-btn {
                min-height: 38px;
                border: 1px solid var(--admin-border);
                border-radius: 10px;
                padding: 0 12px;
                color: var(--admin-text);
                background: transparent;
                cursor: pointer;
            }

            .adr-filter.active {
                color: #fff;
                border-color: rgba(229,9,20,.38);
                background: rgba(229,9,20,.12);
            }

            .adr-shell {
                display: grid;
                grid-template-columns: 360px minmax(0, 1fr);
                overflow: hidden;
                min-height: 620px;
            }

            .adr-queue {
                display: grid;
                align-content: start;
                gap: 9px;
                border-right: 1px solid var(--admin-border);
                padding: 12px;
                overflow-y: auto;
            }

            .adr-queue-item {
                width: 100%;
                display: grid;
                gap: 8px;
                padding: 14px;
                color: inherit;
                text-align: left;
                cursor: pointer;
            }

            .adr-queue-item.active {
                border-color: rgba(229,9,20,.4);
                background: rgba(229,9,20,.09);
            }

            .adr-queue-top,
            .adr-detail-top,
            .adr-actions {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                align-items: center;
            }

            .adr-queue-title {
                color: #fff;
                font-weight: 750;
            }

            .adr-muted {
                color: var(--admin-muted);
                font-size: .84rem;
            }

            .adr-badge {
                display: inline-flex;
                width: fit-content;
                border-radius: 999px;
                padding: 5px 9px;
                font-size: .72rem;
                font-weight: 800;
            }

            .adr-badge.info { color: #bfdbfe; background: rgba(59,130,246,.14); }
            .adr-badge.warning { color: #fde68a; background: rgba(245,158,11,.14); }
            .adr-badge.success { color: #bbf7d0; background: rgba(34,197,94,.14); }
            .adr-badge.danger { color: #fecaca; background: rgba(239,68,68,.14); }
            .adr-badge.muted, .adr-badge.neutral { color: #cbd5e1; background: rgba(148,163,184,.12); }

            .adr-detail {
                border: 0;
                border-radius: 0;
                display: grid;
                gap: 16px;
                align-content: start;
                padding: 18px;
            }

            .adr-preview {
                overflow: hidden;
                border: 1px solid var(--admin-border);
                border-radius: 14px;
                aspect-ratio: 16/9;
                background: #05070d;
            }

            .adr-preview img,
            .adr-preview video {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
            }

            .adr-meta-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .adr-meta {
                border: 1px solid var(--admin-border);
                border-radius: 12px;
                padding: 12px;
                background: rgba(255,255,255,.02);
            }

            .adr-meta span {
                display: block;
                color: var(--admin-muted);
                font-size: .74rem;
                margin-bottom: 5px;
            }

            .adr-meta strong,
            .adr-meta a {
                color: #fff;
                word-break: break-word;
            }

            .adr-note-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .adr-note-grid textarea {
                width: 100%;
                min-height: 92px;
                resize: vertical;
                border: 1px solid var(--admin-border);
                border-radius: 12px;
                color: #fff;
                background: #090c12;
                padding: 12px;
                font: inherit;
            }

            .adr-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .adr-btn.primary {
                border-color: rgba(229,9,20,.4);
                background: var(--admin-red);
                color: #fff;
            }

            .adr-btn.success {
                border-color: rgba(34,197,94,.35);
                background: rgba(34,197,94,.14);
                color: #bbf7d0;
            }

            .adr-btn.warning {
                border-color: rgba(245,158,11,.35);
                background: rgba(245,158,11,.12);
                color: #fde68a;
            }

            .adr-btn.danger {
                border-color: rgba(239,68,68,.35);
                background: rgba(239,68,68,.12);
                color: #fecaca;
            }

            .adr-timeline {
                display: grid;
                gap: 10px;
            }

            .adr-event {
                display: grid;
                gap: 4px;
                border-left: 2px solid rgba(148,163,184,.22);
                padding-left: 12px;
            }

            .adr-empty {
                display: grid;
                place-items: center;
                min-height: 240px;
                color: var(--admin-muted);
                text-align: center;
            }

            @media (max-width: 1100px) {
                .adr-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .adr-shell { grid-template-columns: 1fr; }
                .adr-queue { border-right: 0; border-bottom: 1px solid var(--admin-border); max-height: 320px; }
            }

            @media (max-width: 700px) {
                .adr-head,
                .adr-detail-top { flex-direction: column; align-items: flex-start; }
                .adr-kpis,
                .adr-meta-grid,
                .adr-note-grid { grid-template-columns: 1fr; }
            }
        </style>

        <section data-admin-route="ads-review" class="admin-route-panel" hidden>
            <div class="adr-wrap">
                <header class="adr-head">
                    <div>
                        <h2>Revisão de anúncios</h2>
                        <p>Fila viva, decisões auditáveis e sincronização com o anunciante.</p>
                    </div>
                    <span class="adr-live">Sincronização ativa</span>
                </header>

                <section class="adr-kpis">
                    <article class="adr-kpi"><span>Fila</span><strong id="adr-count-pending_review">0</strong></article>
                    <article class="adr-kpi"><span>Em análise</span><strong id="adr-count-in_review">0</strong></article>
                    <article class="adr-kpi"><span>Aprovados</span><strong id="adr-count-approved">0</strong></article>
                    <article class="adr-kpi"><span>Ativos</span><strong id="adr-count-active">0</strong></article>
                    <article class="adr-kpi"><span>Ajustes</span><strong id="adr-count-changes_requested">0</strong></article>
                    <article class="adr-kpi"><span>Rejeitados</span><strong id="adr-count-rejected">0</strong></article>
                </section>

                <div class="adr-tools">
                    <input id="adr-search" type="search" placeholder="Buscar anúncio, marca ou email">
                    <div class="adr-filters">
                        <button class="adr-filter active" data-adr-filter="queue" type="button">Fila</button>
                        <button class="adr-filter" data-adr-filter="approved" type="button">Aprovados</button>
                        <button class="adr-filter" data-adr-filter="active" type="button">Ativos</button>
                        <button class="adr-filter" data-adr-filter="changes_requested" type="button">Ajustes</button>
                        <button class="adr-filter" data-adr-filter="rejected" type="button">Rejeitados</button>
                        <button class="adr-filter" data-adr-filter="all" type="button">Todos</button>
                    </div>
                </div>

                <section class="adr-shell">
                    <div class="adr-queue" id="adr-queue"></div>
                    <article class="adr-detail" id="adr-detail">
                        <div class="adr-empty">Selecione um anúncio para iniciar a revisão.</div>
                    </article>
                </section>
            </div>
        </section>
        <script src="/assets/js/admin-ads-review.js"></script>
        <?php
    }
}
