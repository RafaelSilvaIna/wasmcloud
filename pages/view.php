<?php
/**
 * ARQUIVO: pages/view.php
 * DESCRIÇÃO: Página principal de exibição de conteúdo (Filmes e Séries)
 * DESIGN: Estilo Netflix — Pipocine Brand Colors
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    header("Location: /login?redirect=" . urlencode($currentUrl));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistir — Pipocine</title>
    <meta name="description" content="Assista filmes e séries com qualidade premium na Pipocine.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* Pipocine Brand */
            --accent:          #e50914;
            --accent-hover:    #f40612;
            --accent-dim:      rgba(229,9,20,.18);
            --yellow:          #ffd60a;
            --blue:            #0096ff;

            /* Superfícies */
            --bg:              #0a0c10;
            --surface:         #12151c;
            --surface2:        #1a1e28;
            --surface3:        #232936;

            /* Texto */
            --text-pure:       #ffffff;
            --text-primary:    #e2e8f0;
            --text-secondary:  #94a3b8;
            --text-muted:      #64748b;

            /* Bordas */
            --border:          #1e293b;
            --border-strong:   #334155;

            /* Sombras */
            --shadow-sm:       0 1px 3px rgba(0,0,0,.5);
            --shadow-md:       0 4px 16px rgba(0,0,0,.6);
            --shadow-lg:       0 12px 40px rgba(0,0,0,.8);
            --glow-accent:     0 0 20px rgba(229,9,20,.35);

            --radius:          6px;
            --radius-lg:       10px;
            --max:             1200px;
            --transition:      0.2s ease;
        }

        html { scroll-behavior: smooth; background: var(--bg); }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ─── LOADER ───────────────────────────────────── */
        #pip-loader {
            position: fixed; inset: 0; z-index: 9999;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            transition: opacity .4s ease;
        }

        #pip-loader img.loader-logo {
            width: 140px;
            height: auto;
            object-fit: contain;
            opacity: .95;
            animation: pip-pulse 1.8s ease-in-out infinite;
        }

        @keyframes pip-pulse {
            0%, 100% { opacity: .95; transform: scale(1); }
            50%       { opacity: .6;  transform: scale(.97); }
        }

        .pip-loader-progress {
            width: 100px;
            height: 2px;
            background: var(--border);
            border-radius: 2px;
            overflow: hidden;
        }
        .pip-loader-progress::after {
            content: '';
            display: block;
            height: 100%;
            width: 45%;
            background: var(--accent);
            border-radius: 2px;
            animation: progress-slide 1.4s ease-in-out infinite;
        }
        @keyframes progress-slide {
            0%   { transform: translateX(-120%); }
            100% { transform: translateX(350%); }
        }

        /* ─── NAVBAR ───────────────────────────────────── */
        #pip-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: linear-gradient(to bottom, rgba(10,12,16,.95) 0%, transparent 100%);
            transition: background var(--transition);
        }
        #pip-nav.scrolled {
            background: rgba(10,12,16,.97);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .nav-logo {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .nav-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .01em;
            transition: color var(--transition);
        }
        .nav-back svg { width: 16px; height: 16px; flex-shrink: 0; }
        .nav-back:hover { color: var(--text-pure); }

        /* ─── HERO ─────────────────────────────────────── */
        #pip-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }

        .hero-backdrop {
            position: absolute; inset: 0;
        }
        .hero-backdrop-img {
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center 15%;
        }
        .hero-backdrop-gradient {
            position: absolute; inset: 0;
            background:
                linear-gradient(to right,  rgba(10,12,16,.95) 30%, rgba(10,12,16,.2) 70%, transparent 100%),
                linear-gradient(to bottom, transparent 30%, rgba(10,12,16,.7) 65%, var(--bg) 100%);
        }

        .hero-content {
            position: relative; z-index: 2;
            width: 100%;
            max-width: var(--max);
            margin: 0 auto;
            padding: 120px 48px 80px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-width: 620px;
            margin-left: 0;
            padding-left: 48px;
        }

        /* Logo do conteúdo OU título */
        .hero-logo-img {
            max-width: 280px;
            max-height: 100px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: left center;
            margin-bottom: 4px;
            filter: drop-shadow(0 2px 12px rgba(0,0,0,.7));
        }

        .hero-type-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--yellow);
        }
        .hero-type-tag .dot {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: var(--yellow);
        }

        .hero-title {
            font-size: clamp(28px, 5vw, 48px);
            font-weight: 700;
            letter-spacing: -.03em;
            line-height: 1.1;
            color: var(--text-pure);
            text-shadow: 0 2px 20px rgba(0,0,0,.8);
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .meta-badge {
            display: inline-flex;
            align-items: center;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .meta-badge.quality {
            background: var(--yellow);
            color: #000;
        }
        .meta-badge.type {
            background: transparent;
            border: 1px solid var(--border-strong);
            color: var(--text-secondary);
        }
        .meta-badge.year {
            background: transparent;
            color: var(--text-secondary);
            font-weight: 400;
            font-size: 13px;
            letter-spacing: 0;
            padding: 0;
            border: none;
        }
        .meta-sep {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: var(--text-muted);
        }

        .hero-overview {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 480px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            gap: 10px;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .btn-watch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .01em;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            padding: 12px 28px;
            cursor: pointer;
            transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .btn-watch svg { width: 18px; height: 18px; }
        .btn-watch:hover {
            background: var(--accent-hover);
            transform: scale(1.02);
            box-shadow: var(--glow-accent);
        }
        .btn-watch:active { transform: scale(.98); }

        .btn-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: .01em;
            color: var(--text-primary);
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: var(--radius);
            padding: 12px 24px;
            cursor: pointer;
            backdrop-filter: blur(6px);
            transition: background var(--transition), color var(--transition);
        }
        .btn-info svg { width: 16px; height: 16px; }
        .btn-info:hover {
            background: rgba(255,255,255,.18);
            color: var(--text-pure);
        }

        /* ─── CONTEÚDO PRINCIPAL ───────────────────────── */
        #pip-main {
            background: var(--bg);
            padding-bottom: 64px;
        }

        .pip-section {
            max-width: var(--max);
            margin: 0 auto;
            padding: 40px 48px 0;
        }

        @media (max-width: 768px) {
            .pip-section { padding: 28px 20px 0; }
            .hero-content { padding: 100px 20px 64px; }
            #pip-nav { padding: 0 20px; }
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-pure);
            letter-spacing: -.01em;
        }

        .section-count {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* ─── SEASON SELECTOR ──────────────────────────── */
        .season-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .season-select-wrap {
            position: relative;
        }

        .season-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-pure);
            background: var(--surface2);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius);
            padding: 8px 14px;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
            min-width: 160px;
            justify-content: space-between;
        }
        .season-trigger svg {
            width: 14px; height: 14px;
            color: var(--text-muted);
            transition: transform .2s;
            flex-shrink: 0;
        }
        .season-trigger:hover { border-color: var(--border-strong); background: var(--surface3); }
        .season-select-wrap.open .season-trigger svg { transform: rotate(180deg); }
        .season-trigger:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .season-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            min-width: 200px;
            background: var(--surface2);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-lg);
            z-index: 60;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .season-select-wrap.open .season-dropdown { display: block; }

        .season-opt {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: background .12s, color .12s;
            border-bottom: 1px solid var(--border);
        }
        .season-opt:last-child { border-bottom: none; }
        .season-opt:hover { background: var(--surface3); color: var(--text-pure); }
        .season-opt.active {
            color: var(--text-pure);
            font-weight: 600;
        }
        .season-opt.active::after {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
        }
        .season-opt-epcount {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* ─── EPISODES LIST (Netflix row style) ─────────── */
        .episodes-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ep-row {
            display: grid;
            grid-template-columns: 48px 180px 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            cursor: pointer;
            border: 1px solid transparent;
            transition: background var(--transition), border-color var(--transition);
            position: relative;
        }
        .ep-row:hover { background: var(--surface2); border-color: var(--border); }
        .ep-row.ep-active {
            background: var(--surface2);
            border-color: var(--accent-dim);
        }
        .ep-row:focus { outline: none; border-color: var(--accent); }
        .ep-row:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .ep-num {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-muted);
            text-align: center;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .ep-row.ep-active .ep-num { color: var(--accent); }

        .ep-thumb {
            position: relative;
            aspect-ratio: 16/9;
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--surface3);
            flex-shrink: 0;
        }
        .ep-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .3s;
        }
        .ep-row:hover .ep-thumb img { transform: scale(1.04); }

        .ep-thumb-overlay {
            position: absolute; inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0);
            transition: background .2s;
        }
        .ep-row:hover .ep-thumb-overlay { background: rgba(0,0,0,.5); }

        .ep-play-btn {
            opacity: 0;
            transform: scale(.75);
            transition: opacity .2s, transform .2s;
            background: rgba(255,255,255,.15);
            border: 2px solid rgba(255,255,255,.5);
            border-radius: 50%;
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
        }
        .ep-play-btn svg { width: 14px; height: 14px; color: #fff; margin-left: 2px; }
        .ep-row:hover .ep-play-btn { opacity: 1; transform: scale(1); }

        .ep-active-indicator {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px;
            background: var(--accent);
            border-radius: 0 0 var(--radius) var(--radius);
        }

        .ep-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .ep-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-pure);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ep-row.ep-active .ep-title { color: var(--text-pure); }
        .ep-desc {
            font-size: 12px;
            color: var(--text-secondary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.45;
        }

        .ep-duration {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 640px) {
            .ep-row { grid-template-columns: 36px 120px 1fr; }
            .ep-duration { display: none; }
        }
        @media (max-width: 480px) {
            .ep-row { grid-template-columns: 32px 1fr; }
            .ep-thumb { display: none; }
        }

        /* ─── DIVIDER ───────────────────────────────────── */
        .pip-divider {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 48px;
        }
        .pip-divider hr {
            border: none;
            border-top: 1px solid var(--border);
            margin-top: 40px;
        }
        @media (max-width: 768px) {
            .pip-divider { padding: 0 20px; }
        }

        /* ─── CAST ──────────────────────────────────────── */
        .cast-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        .cast-scroll::-webkit-scrollbar { display: none; }

        .cast-card {
            flex-shrink: 0;
            width: 96px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
        }
        .cast-photo {
            width: 96px; height: 96px;
            border-radius: 50%;
            object-fit: cover;
            object-position: top;
            background: var(--surface3);
            border: 2px solid var(--border);
            transition: border-color var(--transition), transform var(--transition);
        }
        .cast-card:hover .cast-photo {
            border-color: var(--border-strong);
            transform: scale(1.04);
        }
        .cast-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }
        .cast-char {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        /* ─── ERROR STATE ───────────────────────────────── */
        .error-state {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 40px 24px;
            text-align: center;
        }
        .error-state img { width: 100px; opacity: .7; margin-bottom: 8px; }
        .error-state h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-pure);
        }
        .error-state p {
            font-size: 13px;
            color: var(--text-secondary);
            max-width: 340px;
            line-height: 1.6;
        }
        .error-home {
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            border-radius: var(--radius);
            padding: 10px 20px;
            text-decoration: none;
            transition: background var(--transition), transform var(--transition);
        }
        .error-home:hover { background: var(--accent-hover); transform: scale(1.02); }

        /* ─── ANIMATIONS ────────────────────────────────── */
        @keyframes pip-fade-up {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .pip-reveal { animation: pip-fade-up .5s cubic-bezier(.16,1,.3,1) both; }
        .pip-reveal-1 { animation-delay: .05s; }
        .pip-reveal-2 { animation-delay: .1s; }
        .pip-reveal-3 { animation-delay: .15s; }

        /* ─── SCROLLBAR ─────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-strong); }
    </style>
</head>
<body>

    <!-- ── LOADER ─────────────────────────────────────── -->
    <div id="pip-loader" role="status" aria-label="Carregando">
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="loader-logo">
        <div class="pip-loader-progress"></div>
    </div>

    <!-- ── NAVBAR ─────────────────────────────────────── -->
    <nav id="pip-nav" aria-label="Navegação principal">
        <a href="/home" class="nav-back" aria-label="Voltar ao início">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            Início
        </a>
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="nav-logo">
        <div style="width:72px"></div><!-- spacer para centralizar logo -->
    </nav>

    <!-- ── PÁGINA PRINCIPAL (oculta até carregar) ───────── -->
    <div id="pip-page" style="display:none;">

        <!-- Hero -->
        <section id="pip-hero" aria-label="Informações do conteúdo">
            <div class="hero-backdrop">
                <img id="hero-backdrop-img" src="" alt="" class="hero-backdrop-img" loading="eager">
                <div class="hero-backdrop-gradient"></div>
            </div>
            <div class="hero-content">
                <!-- Logo do conteúdo (da API) ou título como fallback -->
                <div id="hero-logo-wrap"></div>

                <div id="hero-type-tag" class="hero-type-tag pip-reveal pip-reveal-1">
                    <span class="dot"></span>
                    <span id="hero-type-label"></span>
                </div>

                <div id="hero-meta" class="hero-meta pip-reveal pip-reveal-1"></div>

                <p id="hero-overview" class="hero-overview pip-reveal pip-reveal-2"></p>

                <div class="hero-actions pip-reveal pip-reveal-3">
                    <button class="btn-watch" id="btn-watch" onclick="PipView.startPlayback()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Assistir agora
                    </button>
                    <button class="btn-info" id="btn-episodes" style="display:none;" onclick="PipView.scrollToEpisodes()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h9"/></svg>
                        Episódios
                    </button>
                </div>
            </div>
        </section>

        <!-- Episódios (séries) -->
        <section id="pip-episodes-section" style="display:none;" aria-label="Episódios">
            <div class="pip-section">
                <div class="section-header">
                    <h2 class="section-title">Episódios</h2>
                    <span class="section-count" id="ep-count-label"></span>
                </div>

                <div class="season-controls">
                    <div class="season-select-wrap" id="season-select-wrap">
                        <button class="season-trigger" id="season-trigger-btn" aria-haspopup="listbox" aria-expanded="false">
                            <span id="season-trigger-label">Temporada 1</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="season-dropdown" id="season-dropdown" role="listbox" aria-label="Selecionar temporada"></div>
                    </div>
                </div>

                <div class="episodes-list" id="episodes-list"></div>
            </div>
        </section>

        <!-- Divisor -->
        <div class="pip-divider" id="pip-cast-divider" style="display:none;">
            <hr>
        </div>

        <!-- Elenco -->
        <section id="pip-cast-section" style="display:none;" aria-label="Elenco">
            <div class="pip-section">
                <div class="section-header">
                    <h2 class="section-title">Elenco</h2>
                </div>
                <div class="cast-scroll" id="cast-list"></div>
            </div>
        </section>

    </div><!-- /pip-page -->

    <!-- ── TEMPLATES ──────────────────────────────────── -->

    <template id="tpl-ep-row">
        <div class="ep-row" data-season="${season}" data-ep="${ep_num}"
             role="button" tabindex="0"
             aria-label="Episódio ${ep_num}: ${name}"
             onclick="PipView.goToEpisode(${season}, ${ep_num})">
            <span class="ep-num">${ep_num}</span>
            <div class="ep-thumb">
                <img src="${still}" alt="Ep ${ep_num}" loading="lazy">
                <div class="ep-thumb-overlay">
                    <div class="ep-play-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                ${active_bar}
            </div>
            <div class="ep-info">
                <span class="ep-title">${name}</span>
                <span class="ep-desc">${overview}</span>
            </div>
            <span class="ep-duration">${runtime}</span>
        </div>
    </template>

    <template id="tpl-cast-card">
        <div class="cast-card">
            <img src="${photo}" alt="${name}" class="cast-photo" loading="lazy">
            <span class="cast-name">${name}</span>
            <span class="cast-char">${character}</span>
        </div>
    </template>

    <!-- ── JAVASCRIPT ──────────────────────────────────── -->
    <script>
    class PipView {

        static cfg  = {};
        static data = null;

        /* ── Inicialização ──────────────────────────────── */
        static async init() {
            const p = new URLSearchParams(window.location.search);
            this.cfg = {
                id:   p.get('id'),
                type: p.get('type') || 'movie',
                s:    parseInt(p.get('s')) || 1,
                e:    parseInt(p.get('e')) || 1,
            };

            if (!this.cfg.id) { window.location.href = '/home'; return; }

            // Scroll-aware navbar
            window.addEventListener('scroll', () => {
                document.getElementById('pip-nav')
                    .classList.toggle('scrolled', window.scrollY > 40);
            }, { passive: true });

            await this.load();
        }

        /* ── Busca dados da API ─────────────────────────── */
        static async load() {
            try {
                const { id, type, s, e } = this.cfg;
                const res  = await fetch(`/api/v2/exhibition?id=${id}&type=${type}&s=${s}&e=${e}`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const json = await res.json();
                if (!json.sucesso) throw new Error(json.erro || 'Conteúdo indisponível.');

                this.data = json.dados;

                this.renderHero(this.data.content_info, this.data.playback);

                const isSerie = ['serie', 'series', 'tv'].includes(type);
                if (isSerie) this.renderEpisodes(this.data);

                this.renderCast(id, type);
                this.reveal();

            } catch (err) {
                this.showError(err.message);
            }
        }

        /* ── Hero ───────────────────────────────────────── */
        static renderHero(meta, playback) {
            const isSerie = ['serie', 'series', 'tv'].includes(meta.type);

            // Backdrop
            if (meta.backdrop) {
                document.getElementById('hero-backdrop-img').src =
                    `https://image.tmdb.org/t/p/original${meta.backdrop}`;
                document.getElementById('hero-backdrop-img').alt = meta.title;
            }

            // Logo do conteúdo OU título como fallback
            const logoWrap = document.getElementById('hero-logo-wrap');
            if (meta.logo) {
                // logo vindo da API (ex: "logo": "/oJKousgd...png")
                const logoSrc = meta.logo.startsWith('http')
                    ? meta.logo
                    : `https://image.tmdb.org/t/p/w500${meta.logo}`;
                logoWrap.innerHTML = `
                    <img src="${logoSrc}"
                         alt="${this.esc(meta.title)}"
                         class="hero-logo-img pip-reveal"
                         onerror="this.outerHTML=PipView.titleFallback('${this.esc(meta.title).replace(/'/g,"\\'")}')">
                `;
            } else {
                logoWrap.innerHTML = this.titleFallback(meta.title);
            }

            // Tag tipo
            document.getElementById('hero-type-label').textContent =
                isSerie ? 'Série · Pipocine' : 'Filme · Pipocine';

            // Meta badges
            const metaEl = document.getElementById('hero-meta');
            const parts  = [];
            if (meta.year)      parts.push(`<span class="meta-badge year">${meta.year}</span>`);
            if (meta.genres?.length) {
                const sep = `<span class="meta-sep"></span>`;
                parts.push(sep + meta.genres.slice(0,3)
                    .map(g => `<span class="meta-badge type">${this.esc(g)}</span>`)
                    .join(''));
            }
            parts.push(`<span class="meta-sep"></span><span class="meta-badge quality">${playback?.quality || 'HD'}</span>`);
            metaEl.innerHTML = parts.join('');

            // Overview
            document.getElementById('hero-overview').textContent =
                meta.overview || 'Sinopse não disponível.';

            // Botão episódios
            if (isSerie) document.getElementById('btn-episodes').style.display = 'inline-flex';

            // Title da aba
            document.title = `${meta.title} — Pipocine`;
        }

        static titleFallback(title) {
            return `<h1 class="hero-title pip-reveal">${this.esc(title)}</h1>`;
        }

        /* ── Episódios ──────────────────────────────────── */
        static renderEpisodes(dados) {
            document.getElementById('pip-episodes-section').style.display = 'block';

            // Dropdown de temporadas
            const dropdown  = document.getElementById('season-dropdown');
            const seasons   = dados.seasons_available || [];

            if (seasons.length) {
                dropdown.innerHTML = seasons.map(s => `
                    <div class="season-opt${s.season_number === this.cfg.s ? ' active' : ''}"
                         role="option"
                         aria-selected="${s.season_number === this.cfg.s}"
                         onclick="PipView.changeSeason(${s.season_number})">
                        <span>Temporada ${s.season_number}</span>
                        <span class="season-opt-epcount">${s.episode_count} eps</span>
                    </div>
                `).join('');
                document.getElementById('season-trigger-label').textContent =
                    `Temporada ${this.cfg.s}`;
            }

            // Lista de episódios
            const list      = document.getElementById('episodes-list');
            const tpl       = document.getElementById('tpl-ep-row').innerHTML;
            const episodes  = dados.episodes || [];
            const currentEp = dados.current_episode || this.cfg.e;

            const countLabel = document.getElementById('ep-count-label');
            countLabel.textContent = `${episodes.length} episódio${episodes.length !== 1 ? 's' : ''}`;

            if (!episodes.length) {
                list.innerHTML = `<p style="color:var(--text-muted);font-size:13px;padding:24px 0;text-align:center;">Nenhum episódio disponível nesta temporada.</p>`;
                return;
            }

            list.innerHTML = episodes.map(ep => {
                const isActive  = ep.episode === currentEp;
                const runtime   = ep.runtime ? `${ep.runtime} min` : '';
                const activeBar = isActive ? '<div class="ep-active-indicator"></div>' : '';

                return tpl
                    .replaceAll('${season}',   ep.season)
                    .replaceAll('${ep_num}',   ep.episode)
                    .replaceAll('${still}',    ep.still_path || '')
                    .replaceAll('${name}',     this.esc(ep.name))
                    .replaceAll('${overview}', this.esc(ep.overview || 'Sinopse indisponível.'))
                    .replaceAll('${runtime}',  runtime)
                    .replaceAll('${active_bar}', activeBar)
                    .replace('class="ep-row"',
                        `class="ep-row${isActive ? ' ep-active' : ''}"`)
                    .replace('onclick="PipView.goToEpisode(${season}, ${ep_num})"',
                        `onclick="PipView.goToEpisode(${ep.season}, ${ep.episode})"`);
            }).join('');
        }

        /* ── Elenco ─────────────────────────────────────── */
        static async renderCast(id, type) {
            const castSection = document.getElementById('pip-cast-section');
            const divider     = document.getElementById('pip-cast-divider');
            const list        = document.getElementById('cast-list');
            const tpl         = document.getElementById('tpl-cast-card').innerHTML;
            const mediaType   = ['serie', 'tv'].includes(type) ? 'tv' : 'movie';

            try {
                const res  = await fetch(`https://api.themoviedb.org/3/${mediaType}/${id}/credits?api_key=dc6299fd1adb4e32cf16017eecb33295&language=pt-BR`);
                const data = await res.json();
                const cast = (data.cast || []).filter(a => a.profile_path).slice(0, 16);

                if (!cast.length) return;

                list.innerHTML = cast.map(a =>
                    tpl
                        .replaceAll('${photo}',     `https://image.tmdb.org/t/p/w185${a.profile_path}`)
                        .replaceAll('${name}',      this.esc(a.name))
                        .replaceAll('${character}', this.esc(a.character || ''))
                ).join('');

                castSection.style.display = 'block';
                divider.style.display     = 'block';

            } catch { /* silently fail */ }
        }

        /* ── Navegação ──────────────────────────────────── */
        static goToEpisode(season, ep) {
            const url = new URL(window.location.href);
            url.searchParams.set('s', season);
            url.searchParams.set('e', ep);
            window.location.href = url.toString();
        }

        static changeSeason(s) {
            const url = new URL(window.location.href);
            url.searchParams.set('s', s);
            url.searchParams.set('e', 1);
            window.location.href = url.toString();
        }

        static scrollToEpisodes() {
            document.getElementById('pip-episodes-section')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        static startPlayback() {
            const { id, type, s, e } = this.cfg;
            window.location.href = `/player?id=${id}&type=${type}&s=${s}&e=${e}`;
        }

        /* ── UI ─────────────────────────────────────────── */
        static reveal() {
            const loader = document.getElementById('pip-loader');
            const page   = document.getElementById('pip-page');
            loader.style.opacity    = '0';
            loader.style.transition = 'opacity .35s ease';
            setTimeout(() => {
                loader.style.display = 'none';
                page.style.display   = 'block';
            }, 350);
        }

        static showError(msg) {
            document.getElementById('pip-loader').style.display = 'none';
            document.body.insertAdjacentHTML('afterbegin', `
                <div class="error-state" role="alert">
                    <img src="/assets/img/logo-pipocine.png" alt="Pipocine">
                    <h2>Conteúdo indisponível</h2>
                    <p>${this.esc(msg)}</p>
                    <a href="/home" class="error-home">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        Voltar ao início
                    </a>
                </div>
            `);
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

    /* ── Season dropdown toggle ─────────────────────────── */
    document.addEventListener('click', e => {
        const wrap = document.getElementById('season-select-wrap');
        const btn  = document.getElementById('season-trigger-btn');
        if (!wrap) return;

        if (e.target.closest('#season-trigger-btn')) {
            const isOpen = wrap.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen);
        } else if (!e.target.closest('#season-select-wrap')) {
            wrap.classList.remove('open');
            btn?.setAttribute('aria-expanded', 'false');
        }
    });

    /* ── Keyboard navigation ────────────────────────────── */
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.classList.contains('ep-row')) e.target.click();
        if (e.key === 'Escape') {
            const wrap = document.getElementById('season-select-wrap');
            const btn  = document.getElementById('season-trigger-btn');
            wrap?.classList.remove('open');
            btn?.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('DOMContentLoaded', () => PipView.init());
    </script>
</body>
</html>
