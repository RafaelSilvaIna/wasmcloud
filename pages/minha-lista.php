<?php
/**
 * ARQUIVO: pages/minha-lista.php
 * Biblioteca do perfil: histórico, salvos e curtidos.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode('/minha-lista'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0c10">
    <title>Minha Lista — Pipocine</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:         #e50914;
            --accent-dim:     rgba(229, 9, 20, 0.12);
            --bg:             #0a0c10;
            --surface:        #111318;
            --surface2:       #181b22;
            --text-pure:      #ffffff;
            --text-primary:   #e2e8f0;
            --text-secondary: #8892a4;
            --text-muted:     #4a5568;
            --border:         rgba(255,255,255,0.06);
            --border-strong:  rgba(255,255,255,0.10);
            --max:            1200px;
        }

        html { background: var(--bg); }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            padding-top: 72px;
            padding-bottom: 80px;
        }

        /* ── PAGE HEADER ────────────────────────────────────────── */
        .lib-header {
            max-width: var(--max);
            margin: 0 auto;
            padding: 56px 40px 0;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 32px;
            flex-wrap: wrap;
        }

        .lib-header-left {}

        .lib-eyebrow {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .lib-title {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 700;
            color: var(--text-pure);
            letter-spacing: -.025em;
            line-height: 1.1;
        }

        .lib-stats {
            display: flex;
            gap: 32px;
            padding-bottom: 4px;
        }

        .lib-stat {}

        .lib-stat-num {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-pure);
            letter-spacing: -.02em;
            line-height: 1;
        }

        .lib-stat-label {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-top: 5px;
        }

        /* ── DIVIDER ─────────────────────────────────────────────── */
        .lib-divider {
            max-width: var(--max);
            margin: 28px auto 0;
            padding: 0 40px;
        }

        .lib-divider-line {
            height: 1px;
            background: var(--border);
        }

        /* ── TABS ────────────────────────────────────────────────── */
        .lib-tabs-wrap {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 40px;
        }

        .lib-tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
        }

        .lib-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 16px 20px 18px;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: .01em;
            transition: color .18s ease, border-color .18s ease;
            margin-bottom: -1px;
        }

        .lib-tab svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            opacity: .6;
            transition: opacity .18s ease;
        }

        .lib-tab:hover {
            color: var(--text-secondary);
        }

        .lib-tab:hover svg { opacity: .8; }

        .lib-tab.active {
            color: var(--text-pure);
            border-bottom-color: var(--accent);
            font-weight: 600;
        }

        .lib-tab.active svg { opacity: 1; }

        .lib-tab-count {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            background: rgba(255,255,255,0.06);
            padding: 2px 7px;
            border-radius: 20px;
            line-height: 1.5;
            transition: background .18s ease, color .18s ease;
        }

        .lib-tab.active .lib-tab-count {
            background: var(--accent-dim);
            color: var(--accent);
        }

        /* ── CONTENT ─────────────────────────────────────────────── */
        .lib-content {
            max-width: var(--max);
            margin: 40px auto 0;
            padding: 0 40px;
        }

        .lib-panel { display: none; }
        .lib-panel.active { display: block; }

        /* ── HISTORY SECTION LABEL ───────────────────────────────── */
        .lib-section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 18px;
            margin-top: 32px;
        }

        .lib-section-label:first-child { margin-top: 0; }

        /* ── GRID ─────────────────────────────────────────────────── */
        .lib-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px 16px;
            align-items: start;
        }

        /* ── CARD ─────────────────────────────────────────────────── */
        .lib-card {
            cursor: pointer;
            width: 100%;
        }

        .lib-card-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 2 / 3;
            border-radius: 6px;
            overflow: hidden;
            background: var(--surface2);
            transition: transform .22s ease, box-shadow .22s ease;
        }

        .lib-card:hover .lib-card-thumb {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,.6);
        }

        .lib-card-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .lib-card-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .18s ease;
        }

        .lib-card:hover .lib-card-overlay { opacity: 1; }

        .lib-card-play {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,.92);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lib-card-play svg {
            width: 18px;
            height: 18px;
            margin-left: 2px;
        }

        .lib-card-type {
            position: absolute;
            top: 8px;
            left: 8px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255,255,255,.75);
            background: rgba(0,0,0,.55);
            backdrop-filter: blur(4px);
            padding: 3px 7px;
            border-radius: 3px;
        }

        .lib-card-info {
            margin-top: 10px;
        }

        .lib-card-title {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
        }

        .lib-card-year {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── BARRA DE PROGRESSO (Continua Assistindo) ────────────── */
        .lib-card-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(255,255,255,0.15);
        }

        .lib-card-progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 0 0 0 6px;
        }

        .lib-card-ep-label {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── EMPTY STATE ─────────────────────────────────────────── */
        .lib-empty {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 64px 0;
            gap: 12px;
        }

        .lib-empty-icon {
            width: 32px;
            height: 32px;
            color: var(--text-muted);
            opacity: .4;
            margin-bottom: 4px;
        }

        .lib-empty-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .lib-empty-sub {
            font-size: 13px;
            color: var(--text-muted);
            max-width: 280px;
            line-height: 1.6;
        }

        .lib-empty-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-pure);
            background: var(--accent);
            border: none;
            border-radius: 5px;
            padding: 9px 18px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 8px;
            letter-spacing: .01em;
            transition: opacity .18s ease;
        }

        .lib-empty-btn:hover { opacity: .85; }

        /* ── LOADER ─────────────────────────────────────────────── */
        .lib-loader {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 64px 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .lib-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-strong);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .65s linear infinite;
            flex-shrink: 0;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── RESPONSIVE ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .lib-header      { padding: 40px 20px 0; }
            .lib-divider     { padding: 0 20px; }
            .lib-tabs-wrap   { padding: 0 20px; }
            .lib-content     { padding: 0 20px; margin-top: 32px; }
            .lib-stats       { gap: 24px; }
            .lib-stat-num    { font-size: 20px; }
            .lib-grid        { grid-template-columns: repeat(4, 1fr); gap: 20px 12px; }
            .lib-tab         { padding: 14px 14px 16px; font-size: 12px; }
        }

        @media (max-width: 480px) {
            .lib-header      { gap: 24px; }
            .lib-stats       { gap: 20px; }
            .lib-grid        { grid-template-columns: repeat(3, 1fr) !important; gap: 16px 10px; }
            .lib-tabs        { overflow-x: auto; scrollbar-width: none; }
            .lib-tabs::-webkit-scrollbar { display: none; }
            .lib-tab         { white-space: nowrap; }
            .lib-empty       { align-items: center; text-align: center; }
            .lib-empty-sub   { max-width: 240px; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../components/Header.php'; ?>

<!-- ── PAGE HEADER ──────────────────────────────────────────── -->
<div class="lib-header">
    <div class="lib-header-left">
        <div class="lib-eyebrow">Biblioteca</div>
        <h1 class="lib-title">Minha Lista</h1>
    </div>
    <div class="lib-stats">
        <div class="lib-stat">
            <div class="lib-stat-num" id="stat-history">—</div>
            <div class="lib-stat-label">Assistidos</div>
        </div>
        <div class="lib-stat">
            <div class="lib-stat-num" id="stat-saved">—</div>
            <div class="lib-stat-label">Salvos</div>
        </div>
        <div class="lib-stat">
            <div class="lib-stat-num" id="stat-liked">—</div>
            <div class="lib-stat-label">Curtidos</div>
        </div>
    </div>
</div>

<div class="lib-divider">
    <div class="lib-divider-line"></div>
</div>

<!-- ── TABS ─────────────────────────────────────────────────── -->
<div class="lib-tabs-wrap">
    <nav class="lib-tabs" role="tablist">
        <button class="lib-tab active" role="tab" aria-selected="true"
                onclick="Library.switchTab('continue', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            Continua Assistindo
            <span class="lib-tab-count" id="cnt-continue">0</span>
        </button>
        <button class="lib-tab" role="tab" aria-selected="false"
                onclick="Library.switchTab('history', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Histórico
            <span class="lib-tab-count" id="cnt-history">0</span>
        </button>
        <button class="lib-tab" role="tab" aria-selected="false"
                onclick="Library.switchTab('saved', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            Salvos
            <span class="lib-tab-count" id="cnt-saved">0</span>
        </button>
        <button class="lib-tab" role="tab" aria-selected="false"
                onclick="Library.switchTab('liked', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
            </svg>
            Curtidos
            <span class="lib-tab-count" id="cnt-liked">0</span>
        </button>
    </nav>
</div>

<!-- ── CONTENT ─────────────────────────────��──────────���──────── -->
<div class="lib-content">

    <!-- Loader -->
    <div class="lib-loader" id="lib-loader">
        <div class="lib-spinner"></div>
        <span>Carregando sua biblioteca...</span>
    </div>

    <!-- Continua Assistindo -->
    <div class="lib-panel active" id="panel-continue" role="tabpanel"></div>

    <!-- Histórico -->
    <div class="lib-panel" id="panel-history" role="tabpanel"></div>

    <!-- Salvos -->
    <div class="lib-panel" id="panel-saved" role="tabpanel">
        <div class="lib-grid" id="grid-saved"></div>
    </div>

    <!-- Curtidos -->
    <div class="lib-panel" id="panel-liked" role="tabpanel">
        <div class="lib-grid" id="grid-liked"></div>
    </div>

</div>

<?php require_once __DIR__ . '/../components/ContentCard.php'; ?>

<script src="/assets/js/header.js"></script>
<script>
class Library {
    static data = { history: [], saved: [], liked: [], continueWatching: [] };
    static currentTab = 'continue';

    static async init() {
        try {
            // Carrega biblioteca e "continua assistindo" em paralelo
            const [libRes, contRes] = await Promise.all([
                fetch('/api/v3/library/all'),
                fetch('/api/v3/watch-progress/continue?limit=20'),
            ]);
            const libJson  = await libRes.json();
            const contJson = await contRes.json();

            if (!libJson.sucesso) throw new Error('Falha ao carregar biblioteca.');

            this.data = {
                ...libJson.dados,
                continueWatching: contJson.sucesso ? (contJson.dados || []) : [],
            };

            document.getElementById('lib-loader').style.display = 'none';

            this.updateStats();
            this.renderAll();

        } catch (err) {
            document.getElementById('lib-loader').style.display = 'none';
            document.getElementById('panel-continue').innerHTML = this.emptyState(
                'Não foi possível carregar sua lista.',
                'Tente novamente mais tarde.', false
            );
        }
    }

    static updateStats() {
        document.getElementById('stat-history').textContent      = this.data.history.length;
        document.getElementById('stat-saved').textContent        = this.data.saved.length;
        document.getElementById('stat-liked').textContent        = this.data.liked.length;
        document.getElementById('cnt-continue').textContent      = this.data.continueWatching.length;
        document.getElementById('cnt-history').textContent       = this.data.history.length;
        document.getElementById('cnt-saved').textContent         = this.data.saved.length;
        document.getElementById('cnt-liked').textContent         = this.data.liked.length;
    }

    static renderAll() {
        this.renderContinue(this.data.continueWatching);
        this.renderGrid('history', this.data.history,  'Você não assistiu nada ainda.', 'Explore o catálogo e comece a assistir.');
        this.renderGrid('saved',   this.data.saved,    'Nenhum conteúdo salvo.',         'Salve filmes e séries para assistir depois.');
        this.renderGrid('liked',   this.data.liked,    'Nenhum conteúdo curtido.',        'Curta os conteúdos que você amou.');
    }

    // ── Renderiza "Continua Assistindo" ──────────────────────────────────
    static renderContinue(items) {
        const panel = document.getElementById('panel-continue');
        if (!items.length) {
            panel.innerHTML = this.emptyState(
                'Nada para continuar.',
                'Quando você pausar ou fechar um conteúdo no meio, ele aparece aqui.', true
            );
            return;
        }
        panel.innerHTML =
            `<div class="lib-grid">` +
            items.map(item => this.continueCardHtml(item)).join('') +
            `</div>`;
    }

    // ── Card com barra de progresso e link para retomar exato ────────────
    static continueCardHtml(item) {
        const id          = item.content_id;
        const isSerie     = item.content_type === 'serie';
        const ct          = isSerie ? 'serie' : 'filme';
        const season      = item.season   || 1;
        const episode     = item.episode  || 1;
        const audio       = item.audio    || 'dub';
        const progress    = parseFloat(item.progress_time || 0);
        const duration    = parseFloat(item.duration      || 0);
        const pct         = duration > 0 ? Math.min(100, Math.round((progress / duration) * 100)) : 0;
        const badge       = isSerie ? 'Série' : 'Filme';

        // URL do player apontando para o ponto exato via parâmetro &t=
        const href = isSerie
            ? `/assistir/serie/${id}/${season}/${episode}?audio=${audio}&t=${Math.floor(progress)}`
            : `/assistir/filme/${id}?audio=${audio}&t=${Math.floor(progress)}`;

        const poster = item.content_poster
            ? this.esc(item.content_poster)
            : `https://via.placeholder.com/140x210/111318/4a5568?text=${this.esc(item.content_title?.slice(0,2) || '?')}`;

        const year    = item.content_year ? `<div class="lib-card-year">${item.content_year}</div>` : '';
        const epLabel = isSerie
            ? `<div class="lib-card-ep-label">T${season} &bull; Ep. ${episode}</div>`
            : '';

        return `
            <div class="lib-card" onclick="window.location.href='${href}'" role="button" tabindex="0"
                 onkeydown="if(event.key==='Enter')window.location.href='${href}'"
                 aria-label="Continuar assistindo ${this.esc(item.content_title)}">
                <div class="lib-card-thumb">
                    <img src="${poster}"
                         alt="${this.esc(item.content_title)}"
                         class="lib-card-img"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/140x210/181b22/4a5568?text=?'">
                    <div class="lib-card-overlay" aria-hidden="true">
                        <div class="lib-card-play">
                            <svg viewBox="0 0 24 24" fill="black"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <span class="lib-card-type">${badge}</span>
                    ${pct > 0 ? `
                    <div class="lib-card-progress" aria-hidden="true">
                        <div class="lib-card-progress-fill" style="width:${pct}%"></div>
                    </div>` : ''}
                </div>
                <div class="lib-card-info">
                    <div class="lib-card-title">${this.esc(item.content_title)}</div>
                    ${epLabel}${year}
                </div>
            </div>`;
    }

    static renderGrid(type, items, emptyTitle, emptySub) {
        if (type === 'history') {
            const panel = document.getElementById('panel-history');
            if (!items.length) {
                panel.innerHTML = this.emptyState(emptyTitle, emptySub, true);
            } else {
                panel.innerHTML = this.renderHistoryGrouped(items);
            }
            return;
        }

        const grid = document.getElementById(`grid-${type}`);
        if (!items.length) {
            grid.closest('.lib-panel').innerHTML = this.emptyState(emptyTitle, emptySub, true);
            return;
        }
        grid.innerHTML = items.map(item => this.cardHtml(item)).join('');
    }

    static renderHistoryGrouped(items) {
        const groups = {};
        items.forEach(item => {
            const label = this.dateGroupLabel(item.watched_at);
            if (!groups[label]) groups[label] = [];
            groups[label].push(item);
        });

        return Object.entries(groups).map(([label, group]) =>
            `<div class="lib-section-label">${this.esc(label)}</div>
             <div class="lib-grid" style="margin-bottom: 40px">` +
            group.map(item => this.cardHtml(item, 'history')).join('') +
            `</div>`
        ).join('');
    }

    static cardHtml(item, type = '') {
        const id     = item.content_id;
        const ct     = item.content_type === 'serie' ? 'serie' : 'movie';
        const href   = `/view?id=${id}&type=${ct}`;
        const badge  = ct === 'serie' ? 'Série' : 'Filme';
        const poster = item.content_poster
            ? item.content_poster
            : `https://via.placeholder.com/140x210/111318/4a5568?text=${this.esc(item.content_title?.slice(0,2) || '?')}`;
        const year   = item.content_year
            ? `<div class="lib-card-year">${item.content_year}</div>`
            : '';

        return `
            <div class="lib-card" onclick="window.location.href='${href}'" role="button" tabindex="0"
                 onkeydown="if(event.key==='Enter')window.location.href='${href}'"
                 aria-label="${this.esc(item.content_title)}">
                <div class="lib-card-thumb">
                    <img src="${this.esc(poster)}"
                         alt="${this.esc(item.content_title)}"
                         class="lib-card-img"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/140x210/181b22/4a5568?text=?'">
                    <div class="lib-card-overlay" aria-hidden="true">
                        <div class="lib-card-play">
                            <svg viewBox="0 0 24 24" fill="black"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <span class="lib-card-type">${badge}</span>
                </div>
                <div class="lib-card-info">
                    <div class="lib-card-title">${this.esc(item.content_title)}</div>
                    ${year}
                </div>
            </div>`;
    }

    static switchTab(tab, btn) {
        this.currentTab = tab;

        document.querySelectorAll('.lib-tab').forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');

        document.querySelectorAll('.lib-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(`panel-${tab}`).classList.add('active');
    }

    static emptyState(title, sub, showBtn = true) {
        const btn = showBtn
            ? `<a href="/home" class="lib-empty-btn">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                       <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                   </svg>
                   Explorar catálogo
               </a>`
            : '';
        return `
            <div class="lib-empty">
                <svg class="lib-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <div class="lib-empty-title">${this.esc(title)}</div>
                <div class="lib-empty-sub">${this.esc(sub)}</div>
                ${btn}
            </div>`;
    }

    static dateGroupLabel(dateStr) {
        if (!dateStr) return 'Sem data';
        const d    = new Date(dateStr.replace(' ', 'T'));
        const now  = new Date();
        const tod  = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const itemD = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const diff = Math.floor((tod - itemD) / 86400000);

        if (diff === 0) return 'Hoje';
        if (diff === 1) return 'Ontem';
        if (diff <= 7)  return 'Esta semana';
        if (diff <= 30) return 'Este mês';
        return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    }

    static esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

document.addEventListener('DOMContentLoaded', () => Library.init());
</script>

</body>
</html>
