<?php
/**
 * ARQUIVO: pages/busca.php
 * Pagina de busca de conteudo — exige login.
 */

// ─── DIAGNOSTICO: capturar erro real em vez de 500 generico ───
$__diag_errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$__diag_errors) {
    $__diag_errors[] = "[$errno] $errstr in $errfile:$errline";
    return true;
});
set_exception_handler(function($e) use (&$__diag_errors) {
    $__diag_errors[] = "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
});

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode('/busca'));
    exit;
}

// Query inicial via GET (ex.: /busca?q=matrix)
$queryInicial = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0c10">
    <title><?= $queryInicial ? htmlspecialchars($queryInicial) . ' — Busca' : 'Buscar' ?> — Pipocine</title>
    <meta name="description" content="Busque filmes e series no Pipocine.">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/content-card.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:         #e50914;
            --accent-glow:    rgba(229,9,20,0.25);
            --bg:             #0a0c10;
            --surface:        #111318;
            --surface2:       #181b22;
            --text-pure:      #ffffff;
            --text-primary:   #e2e8f0;
            --text-secondary: #8892a4;
            --text-muted:     #4a5568;
            --border:         rgba(255,255,255,0.06);
            --border-hover:   rgba(255,255,255,0.12);
            --max:            1280px;
            --radius:         10px;
            --radius-sm:      6px;
        }

        html { background: var(--bg); scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            padding-top: 64px;
            padding-bottom: 80px;
        }

        /* ── SEARCH HERO ─────────────────────────────────────────────── */
        .search-hero {
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            padding: 24px 0 0;
            position: sticky;
            top: 64px;
            z-index: 90;
            transition: box-shadow .2s;
        }

        .search-hero.scrolled {
            box-shadow: 0 4px 24px rgba(0,0,0,.4);
        }

        .search-hero-inner {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 24px 16px;
        }

        /* Barra de pesquisa — pill */
        .search-bar-wrap {
            position: relative;
            max-width: 640px;
        }

        .search-bar-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            transition: color .2s;
        }

        .search-bar-input {
            width: 100%;
            height: 48px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 100px;
            color: var(--text-pure);
            font-family: inherit;
            font-size: 15px;
            font-weight: 400;
            padding: 0 44px 0 44px;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
            caret-color: var(--accent);
        }

        .search-bar-input::placeholder { color: var(--text-muted); }

        .search-bar-input:focus {
            border-color: var(--accent);
            background: var(--surface2);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .search-bar-input:focus ~ .search-bar-icon { color: var(--accent); }

        .search-bar-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--surface2);
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s, color .15s, background .15s;
        }

        .search-bar-clear.visible { opacity: 1; pointer-events: all; }
        .search-bar-clear:hover { color: var(--text-primary); background: var(--border-hover); }

        /* ── FILTROS ─────────────────────────────────────────────────── */
        .filters-row {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 12px;
        }

        .filters-row::-webkit-scrollbar { display: none; }

        .filter-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            white-space: nowrap;
            margin-right: 2px;
            flex-shrink: 0;
        }

        .filter-sep {
            width: 1px;
            height: 14px;
            background: var(--border);
            flex-shrink: 0;
            margin: 0 2px;
        }

        .filter-chip {
            height: 30px;
            padding: 0 12px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 100px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .filter-chip:hover {
            border-color: var(--border-hover);
            color: var(--text-secondary);
        }

        .filter-chip.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .filter-select {
            height: 30px;
            padding: 0 28px 0 12px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 100px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            appearance: none;
            -webkit-appearance: none;
            outline: none;
            transition: all .15s;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .filter-select:hover {
            border-color: var(--border-hover);
            color: var(--text-secondary);
        }

        .filter-select option {
            background: var(--surface2);
            color: var(--text-primary);
        }

        .filter-select.active {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ── BODY AREA ───────────────────────────────────────────────── */
        .search-body {
            max-width: var(--max);
            margin: 0 auto;
            padding: 24px 24px 0;
        }

        /* ── META ────────────────────────────────────────────────────── */
        .results-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .results-count {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .results-count strong {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .results-sort {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sort-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .sort-select {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            padding: 2px 18px 2px 0;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 2px center;
        }

        .sort-select option {
            background: var(--surface2);
            color: var(--text-primary);
        }

        /* ── GRID — FLUID AUTO-FILL ──────────────────────────────────── */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 16px 10px;
        }

        /* ── CARD — MINIMALISTA (poster only, info on hover) ─────────── */
        .result-card {
            cursor: pointer;
            border-radius: var(--radius-sm);
            overflow: hidden;
            position: relative;
            transition: transform .2s cubic-bezier(.4,0,.2,1), box-shadow .2s;
            outline: none;
        }

        .result-card:hover {
            transform: scale(1.04);
            box-shadow: 0 8px 30px rgba(0,0,0,.5);
            z-index: 2;
        }

        .result-card:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .result-card-thumb {
            position: relative;
            aspect-ratio: 2/3;
            background: var(--surface);
            overflow: hidden;
        }

        .result-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            opacity: 0;
            transition: opacity .35s ease;
        }

        .result-card-img.loaded { opacity: 1; }

        /* Hover overlay — gradient + title + meta */
        .result-card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.3) 40%, transparent 60%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 10px;
            opacity: 0;
            transition: opacity .2s;
        }

        .result-card:hover .result-card-overlay { opacity: 1; }

        .result-card-overlay-title {
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .result-card-overlay-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
            font-size: 10px;
            color: rgba(255,255,255,.6);
        }

        .result-card-overlay-meta .nota-star {
            color: #f5c518;
            fill: currentColor;
            width: 9px;
            height: 9px;
            flex-shrink: 0;
        }

        /* Play icon — centered */
        .result-card-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(.7);
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s, transform .2s;
        }

        .result-card-play svg {
            width: 14px;
            height: 14px;
            margin-left: 2px;
        }

        .result-card:hover .result-card-play {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        /* Badge */
        .result-card-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 3px;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 1;
        }

        .result-card-badge.serie {
            background: rgba(229,9,20,.8);
            color: #fff;
        }

        .result-card-badge.filme {
            background: rgba(255,255,255,.1);
            color: rgba(255,255,255,.85);
            border: 1px solid rgba(255,255,255,.12);
        }

        /* Indisponivel */
        .result-card-unavail {
            position: absolute;
            inset: 0;
            background: rgba(10,12,16,.55);
            display: flex;
            align-items: flex-end;
            padding: 8px;
        }

        .result-card-unavail-tag {
            font-size: 8px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
        }

        /* ── CARD FADE-IN ────────────────────────────────────────────── */
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .result-card {
            animation: cardIn .35s ease both;
        }

        .results-grid .result-card:nth-child(1)  { animation-delay: .00s; }
        .results-grid .result-card:nth-child(2)  { animation-delay: .02s; }
        .results-grid .result-card:nth-child(3)  { animation-delay: .04s; }
        .results-grid .result-card:nth-child(4)  { animation-delay: .06s; }
        .results-grid .result-card:nth-child(5)  { animation-delay: .08s; }
        .results-grid .result-card:nth-child(6)  { animation-delay: .10s; }
        .results-grid .result-card:nth-child(n+7) { animation-delay: .12s; }

        /* ── SKELETON ────────────────────────────────────────────────── */
        @keyframes shimmer {
            0%   { background-position: -300px 0; }
            100% { background-position:  300px 0; }
        }

        .skeleton {
            background: linear-gradient(90deg,
                var(--surface) 25%,
                var(--surface2) 50%,
                var(--surface) 75%);
            background-size: 600px 100%;
            animation: shimmer 1.2s infinite linear;
        }

        .skeleton-card { border-radius: var(--radius-sm); overflow: hidden; }
        .skeleton-thumb { aspect-ratio: 2/3; }
        .skeleton-line { height: 8px; border-radius: 4px; margin-top: 8px; }
        .skeleton-line-short { height: 8px; border-radius: 4px; width: 50%; margin-top: 5px; }

        /* ── EMPTY / ERROR ───────────────────────────────────────────── */
        .empty-state {
            padding: 60px 0 40px;
            text-align: center;
        }

        .empty-icon {
            width: 40px;
            height: 40px;
            color: var(--text-muted);
            margin: 0 auto 16px;
            opacity: .3;
        }

        .empty-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: -.01em;
        }

        .empty-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 6px;
            line-height: 1.5;
        }

        /* ── INITIAL STATE ───────────────────────────────────────────── */
        .initial-state {
            padding: 60px 0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .initial-hint {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .trending-terms {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .trending-term {
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all .15s;
        }

        .trending-term:hover {
            border-color: var(--border-hover);
            color: var(--text-primary);
            background: var(--surface);
        }

        /* ── PAGINACAO ───────────────────────────────────────────────── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .page-btn {
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            padding: 0 8px;
            transition: all .15s;
        }

        .page-btn:hover {
            border-color: var(--border-hover);
            color: var(--text-primary);
        }

        .page-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .page-btn:disabled { opacity: .25; cursor: not-allowed; }

        .page-ellipsis {
            font-size: 12px;
            color: var(--text-muted);
            padding: 0 3px;
        }

        /* ── RESPONSIVE ─────────────────────────────────────────────── */
        @media (min-width: 1400px) {
            .results-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); gap: 20px 14px; }
        }

        @media (max-width: 768px) {
            body { padding-top: 56px; }
            .search-hero { top: 56px; padding: 16px 0 0; }
            .search-hero-inner { padding: 0 16px 12px; }
            .filters-row { padding: 0 16px 10px; gap: 5px; }
            .search-body { padding: 16px 16px 0; }
            .search-bar-input { height: 44px; font-size: 14px; padding: 0 40px 0 40px; }
            .search-bar-icon { left: 14px; width: 16px; height: 16px; }
            .results-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px 8px; }
            .filter-chip { height: 28px; padding: 0 10px; font-size: 11px; }
            .filter-select { height: 28px; padding: 0 24px 0 10px; font-size: 11px; }
            .result-card-badge { font-size: 7px; padding: 1px 5px; }
            .result-card-overlay { padding: 8px; }
            .result-card-overlay-title { font-size: 11px; }
            .result-card-overlay-meta { font-size: 9px; }
            .result-card-play { width: 30px; height: 30px; }
            .result-card-play svg { width: 12px; height: 12px; }
        }

        @media (max-width: 400px) {
            .results-grid { grid-template-columns: repeat(3, 1fr); gap: 10px 6px; }
            .search-bar-input { height: 42px; font-size: 13px; border-radius: 100px; }
            .filter-label { display: none; }
            .filter-sep { display: none; }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/search.css">
</head>
<body>

<?php require_once __DIR__ . '/../components/Header.php'; ?>
<?php require_once __DIR__ . '/../components/SessionModal.php'; ?>
<?php require_once __DIR__ . '/../components/ContentCard.php'; ?>

<main>

    <!-- ── Search Hero — barra + filtros ──────────────────────────────── -->
    <div class="search-hero" id="search-hero">
        <div class="search-hero-inner">
            <div class="search-bar-wrap">
                <svg class="search-bar-icon" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>

                <input
                    id="search-input"
                    type="text"
                    class="search-bar-input"
                    placeholder="Buscar filmes e series..."
                    autocomplete="off"
                    autocorrect="off"
                    spellcheck="false"
                    aria-label="Campo de busca"
                    value="<?= $queryInicial ?>"
                />

                <button
                    id="search-clear"
                    class="search-bar-clear<?= $queryInicial ? ' visible' : '' ?>"
                    aria-label="Limpar busca"
                    type="button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="filters-header">
            <button id="filters-toggle" class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-panel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6h16"/>
                    <path d="M7 12h10"/>
                    <path d="M10 18h4"/>
                </svg>
                <span>Filtros</span>
                <strong id="filters-count" class="filters-count" hidden>0</strong>
            </button>
        </div>

        <!-- Filtros -->
        <div class="filters-row" id="filters-panel" role="group" aria-label="Filtros de busca">
            <span class="filter-label">Filtrar</span>

            <!-- Tipo -->
            <button class="filter-chip active" data-filter="tipo" data-value="" type="button">Todos</button>
            <button class="filter-chip" data-filter="tipo" data-value="filme" type="button">Filmes</button>
            <button class="filter-chip" data-filter="tipo" data-value="serie" type="button">Series</button>

            <div class="filter-sep" aria-hidden="true"></div>

            <!-- Genero -->
            <select class="filter-select" id="filter-genero" aria-label="Filtrar por genero">
                <option value="">Genero</option>
            </select>

            <!-- Ano -->
            <select class="filter-select" id="filter-ano" aria-label="Filtrar por ano">
                <option value="">Ano</option>
            </select>
        </div>
    </div>

    <!-- ── Corpo dos resultados ─────────────────────────────────────── -->
    <div class="search-body">
        <div id="results-meta" class="results-meta" style="display:none">
            <span class="results-count" id="results-count"></span>
            <div class="results-sort">
                <span class="sort-label">Ordenar por</span>
                <select class="sort-select" id="sort-select" aria-label="Ordenar resultados">
                    <option value="relevancia">Relevancia</option>
                    <option value="nota">Melhor nota</option>
                    <option value="recente">Mais recente</option>
                    <option value="antigo">Mais antigo</option>
                </select>
            </div>
        </div>

        <div id="results-container"></div>
        <div id="pagination" class="pagination" style="display:none"></div>
    </div>

</main>

<script src="/assets/js/header.js"></script>
<script>
// ─────────────────────────────────────────────────────────────────────────────
// Estado global da busca
// ─────────────────────────────────────────────────────────────────────────────
const Search = {
    query:       '',
    tipo:        '',
    genero:      '',
    ano:         '',
    ordem:       'relevancia',
    pagina:      1,
    totalPaginas: 0,
    total:       0,
    debounceTimer: null,

    // Elementos DOM
    el: {
        input:     document.getElementById('search-input'),
        clear:     document.getElementById('search-clear'),
        container: document.getElementById('results-container'),
        meta:      document.getElementById('results-meta'),
        count:     document.getElementById('results-count'),
        pagination:document.getElementById('pagination'),
        sort:      document.getElementById('sort-select'),
        generoSel: document.getElementById('filter-genero'),
        anoSel:    document.getElementById('filter-ano'),
    },

    // ── Inicializacao ───────────────────────────────────────────────────────
    async init() {
        await this.loadFilters();
        this.bindEvents();

        const initialQuery = '<?= $queryInicial ?>';
        if (initialQuery) {
            this.query = initialQuery;
            this.run();
        } else {
            this.renderInitial();
        }
    },

    // ── Carrega generos e anos do banco ─────────────────────────────────────
    async loadFilters() {
        try {
            const res  = await fetch('/api/v2/busca/generos');
            const json = await res.json();
            if (json.sucesso && json.dados) {
                json.dados.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g;
                    opt.textContent = g;
                    this.el.generoSel.appendChild(opt);
                });
            }
        } catch (_) {}

        // Anos de 2025 ate 1950
        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= 1950; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            this.el.anoSel.appendChild(opt);
        }
    },

    // ── Eventos ─────────────────────────────────────────────────────────────
    bindEvents() {
        // Input com debounce
        this.el.input.addEventListener('input', () => {
            const val = this.el.input.value.trim();
            this.el.clear.classList.toggle('visible', val.length > 0);
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.query  = val;
                this.pagina = 1;
                if (val.length >= 1) this.run();
                else                  this.renderInitial();
            }, 320);
        });

        // Enter imediato
        this.el.input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                clearTimeout(this.debounceTimer);
                this.query  = this.el.input.value.trim();
                this.pagina = 1;
                if (this.query) this.run();
            }
        });

        // Limpar
        this.el.clear.addEventListener('click', () => {
            this.el.input.value = '';
            this.el.clear.classList.remove('visible');
            this.query  = '';
            this.pagina = 1;
            this.el.input.focus();
            this.renderInitial();
            this.updateUrl();
        });

        // Chips de tipo
        document.querySelectorAll('[data-filter="tipo"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-filter="tipo"]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.tipo   = btn.dataset.value;
                this.pagina = 1;
                if (this.query) this.run();
            });
        });

        // Selects de genero e ano
        this.el.generoSel.addEventListener('change', () => {
            this.genero = this.el.generoSel.value;
            this.el.generoSel.classList.toggle('active', !!this.genero);
            this.pagina = 1;
            if (this.query) this.run();
        });

        this.el.anoSel.addEventListener('change', () => {
            this.ano = this.el.anoSel.value;
            this.el.anoSel.classList.toggle('active', !!this.ano);
            this.pagina = 1;
            if (this.query) this.run();
        });

        // Ordenacao
        this.el.sort.addEventListener('change', () => {
            this.ordem  = this.el.sort.value;
            this.pagina = 1;
            if (this.query) this.run();
        });

        // Scroll shadow on search hero
        const hero = document.getElementById('search-hero');
        window.addEventListener('scroll', () => {
            hero.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    },

    // ── Executa a busca ─────────────────────────────────────────────────────
    async run() {
        this.renderSkeleton();
        this.updateUrl();

        const params = new URLSearchParams({ q: this.query, ordem: this.ordem, pagina: this.pagina });
        if (this.tipo)   params.set('tipo',   this.tipo);
        if (this.genero) params.set('genero', this.genero);
        if (this.ano)    params.set('ano',    this.ano);

        try {
            const res  = await fetch('/api/v2/busca?' + params);
            const json = await res.json();

            if (!json.sucesso) {
                this.renderError();
                return;
            }

            this.total        = json.total;
            this.totalPaginas = json.total_paginas;

            if (!json.dados || json.dados.length === 0) {
                this.renderEmpty();
                return;
            }

            this.renderResults(json.dados);
            this.renderMeta();
            this.renderPagination();

        } catch (err) {
            this.renderError();
        }
    },

    // ── URL sincrona ────────────────────────────────────────────────────────
    updateUrl() {
        const url = new URL(window.location);
        if (this.query) url.searchParams.set('q', this.query);
        else            url.searchParams.delete('q');
        history.replaceState(null, '', url);
    },

    // ── Renders ─────────────────────────────────────────────────────────────
    renderInitial() {
        this.el.meta.style.display      = 'none';
        this.el.pagination.style.display = 'none';

        const terms = ['Ação', 'Suspense', 'Comédia', 'Drama', 'Terror', 'Animação', 'Documentário', 'Romance'];
        this.el.container.innerHTML = `
            <div class="initial-state">
                <p class="initial-hint">Termos populares</p>
                <div class="trending-terms">
                    ${terms.map(t => `<button class="trending-term" type="button">${this.esc(t)}</button>`).join('')}
                </div>
            </div>`;

        // Clique nos termos populares
        this.el.container.querySelectorAll('.trending-term').forEach(btn => {
            btn.addEventListener('click', () => {
                this.el.input.value = btn.textContent;
                this.el.clear.classList.add('visible');
                this.query  = btn.textContent;
                this.pagina = 1;
                this.run();
            });
        });
    },

    renderSkeleton() {
        this.el.meta.style.display      = 'none';
        this.el.pagination.style.display = 'none';

        const cards = Array.from({length: 18}, () => `
            <div class="skeleton-card">
                <div class="skeleton skeleton-thumb"></div>
            </div>`).join('');

        this.el.container.innerHTML = `<div class="results-grid">${cards}</div>`;
    },

    renderResults(items) {
        const cards = items.map(item => {
            const isSerie  = item.tipo === 'serie';
            const href     = isSerie
                ? `/view?id=${encodeURIComponent(item.id_tmdb)}&type=serie&s=1&e=1`
                : `/view?id=${encodeURIComponent(item.id_tmdb)}&type=movie`;
            const badge    = isSerie ? 'serie' : 'filme';
            const label    = isSerie ? 'Serie' : 'Filme';
            const nota     = parseFloat(item.nota || 0);
            const notaStr  = nota > 0 ? nota.toFixed(1) : '';
            const ano      = item.ano || '';
            const poster   = item.poster || '';
            const unavail  = !item.disponivel ? `
                <div class="result-card-unavail" aria-hidden="true">
                    <span class="result-card-unavail-tag">Em breve</span>
                </div>` : '';

            const metaParts = [];
            if (ano) metaParts.push(ano);
            if (notaStr) metaParts.push(`<svg class="nota-star" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>${notaStr}`);

            return `
                <div class="result-card" onclick="window.location.href='${this.esc(href)}'"
                     role="button" tabindex="0"
                     onkeydown="if(event.key==='Enter')window.location.href='${this.esc(href)}'"
                     aria-label="${this.esc(item.titulo)}">
                    <div class="result-card-thumb">
                        ${poster ? `<img
                            class="result-card-img"
                            src="${this.esc(poster)}"
                            alt="${this.esc(item.titulo)}"
                            loading="lazy"
                            onerror="this.style.display='none'"
                            onload="this.classList.add('loaded')">` : ''}
                        <div class="result-card-overlay">
                            <div class="result-card-play">
                                <svg viewBox="0 0 24 24" fill="black">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <div class="result-card-overlay-title">${this.esc(item.titulo)}</div>
                            ${metaParts.length ? `<div class="result-card-overlay-meta">${metaParts.join(' · ')}</div>` : ''}
                        </div>
                        <span class="result-card-badge ${badge}">${label}</span>
                        ${unavail}
                    </div>
                </div>`;
        }).join('');

        this.el.container.innerHTML = `<div class="results-grid">${cards}</div>`;
    },

    renderMeta() {
        const q = this.esc(this.query);
        this.el.count.innerHTML = `<strong>${this.total.toLocaleString('pt-BR')}</strong> resultado${this.total !== 1 ? 's' : ''} para <strong>"${q}"</strong>`;
        this.el.meta.style.display = 'flex';
        this.el.sort.value = this.ordem;
    },

    renderPagination() {
        if (this.totalPaginas <= 1) {
            this.el.pagination.style.display = 'none';
            return;
        }

        const p   = this.pagina;
        const max = this.totalPaginas;

        let btns = '';

        // Prev
        btns += `<button class="page-btn" onclick="Search.goPage(${p-1})" ${p===1?'disabled':''} aria-label="Pagina anterior">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>`;

        // Paginas
        const range = this.pageRange(p, max);
        let prev = null;
        for (const n of range) {
            if (prev !== null && n - prev > 1) btns += `<span class="page-ellipsis">...</span>`;
            btns += `<button class="page-btn${n===p?' active':''}"
                onclick="Search.goPage(${n})"
                aria-label="Pagina ${n}" aria-current="${n===p?'page':'false'}">${n}</button>`;
            prev = n;
        }

        // Next
        btns += `<button class="page-btn" onclick="Search.goPage(${p+1})" ${p===max?'disabled':''} aria-label="Proxima pagina">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>`;

        this.el.pagination.innerHTML     = btns;
        this.el.pagination.style.display = 'flex';
    },

    renderEmpty() {
        this.el.meta.style.display      = 'none';
        this.el.pagination.style.display = 'none';
        this.el.container.innerHTML = `
            <div class="empty-state">
                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p class="empty-title">Nenhum resultado para "${this.esc(this.query)}"</p>
                <p class="empty-sub">Tente outro termo ou remova os filtros.</p>
            </div>`;
    },

    renderError() {
        this.el.meta.style.display      = 'none';
        this.el.pagination.style.display = 'none';
        this.el.container.innerHTML = `
            <div class="empty-state">
                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p class="empty-title">Algo deu errado</p>
                <p class="empty-sub">Nao foi possivel carregar os resultados. Tente novamente.</p>
            </div>`;
    },

    // ── Helpers ─────────────────────────────────────────────────────────────
    goPage(n) {
        if (n < 1 || n > this.totalPaginas) return;
        this.pagina = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        this.run();
    },

    pageRange(current, total) {
        const delta  = 2;
        const pages  = [];
        const left   = Math.max(1, current - delta);
        const right  = Math.min(total, current + delta);

        if (left > 1)  pages.push(1);
        for (let i = left; i <= right; i++) pages.push(i);
        if (right < total) pages.push(total);

        return pages;
    },

    esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },
};

// Inicia
document.addEventListener('DOMContentLoaded', () => Search.init());
</script>
<script src="/assets/js/search-page.js"></script>

<?php if (!empty($__diag_errors)): ?>
<div style="position:fixed;bottom:0;left:0;right:0;background:#c00;color:#fff;padding:12px;z-index:99999;font-family:monospace;font-size:12px;max-height:200px;overflow:auto;">
<strong>DIAGNÓSTICO — <?= count($__diag_errors) ?> erro(s):</strong><br>
<?php foreach ($__diag_errors as $err): ?>
<div style="border-bottom:1px solid rgba(255,255,255,.2);padding:4px 0;"><?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</body>
</html>
