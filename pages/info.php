<?php
// Verificação de segurança: Redireciona para o login se não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Captura o ID do TMDB passado pelo router (via $_GET['tmdb_id'])
$tmdbId = isset($_GET['tmdb_id']) ? (int) $_GET['tmdb_id'] : 0;

if ($tmdbId <= 0) {
    header("Location: /home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0c10">
    <title>Carregando... — PipoCine</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">

    <style>
        /* ══════════════════════════════════════════════════════════════════
           PÁGINA DE INFORMAÇÕES — info.php
           Hierarquia: Hero → Detalhes → Elenco → Temporadas → Recomendados
        ══════════════════════════════════════════════════════════════════ */

        /* ── Resets e Base ─────────────────────────────────────────────── */
        .info-page {
            min-height: 100vh;
            background-color: var(--bg-base);
        }

        /* ── Skeleton Loading ──────────────────────────────────────────── */
        @keyframes shimmer {
            0%   { background-position: -800px 0; }
            100% { background-position: 800px 0; }
        }

        .skeleton {
            background: linear-gradient(90deg,
                var(--bg-surface) 25%,
                var(--bg-elevated) 50%,
                var(--bg-surface) 75%
            );
            background-size: 800px 100%;
            animation: shimmer 1.6s infinite linear;
            border-radius: 6px;
        }

        /* ── Botão Voltar ──────────────────────────────────────────────── */
        .btn-back {
            position: fixed;
            top: 18px;
            left: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(10, 12, 16, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 600;
            padding: 8px 16px 8px 12px;
            border-radius: 50px;
            cursor: pointer;
            transition: background var(--transition-fast), color var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast);
            text-decoration: none;
        }

        .btn-back:hover {
            background: rgba(26, 30, 40, 0.95);
            border-color: var(--border-strong);
            color: var(--text-pure);
            transform: translateX(-2px);
        }

        .btn-back svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            transition: transform var(--transition-fast);
        }

        .btn-back:hover svg {
            transform: translateX(-2px);
        }

        /* ── HERO ──────────────────────────────────────────────────────── */
        .info-hero {
            position: relative;
            width: 100%;
            min-height: 100svh;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }

        .info-hero__backdrop {
            position: absolute;
            inset: 0;
            background-color: var(--bg-base);
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            transition: opacity 0.6s ease;
        }

        /* Gradiente em 3 camadas para imersão cinematográfica */
        .info-hero__backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(to right,  rgba(10,12,16,0.92) 0%, rgba(10,12,16,0.45) 55%, transparent 100%),
                linear-gradient(to top,    var(--bg-base) 0%,   rgba(10,12,16,0.5) 30%, transparent 60%),
                linear-gradient(to bottom, rgba(10,12,16,0.6) 0%, transparent 20%);
        }

        .info-hero__content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 120px 5% 60px;
            display: flex;
            align-items: flex-end;
            gap: 40px;
        }

        /* Poster flutuante no hero */
        .info-hero__poster {
            flex-shrink: 0;
            width: 200px;
            aspect-ratio: 2 / 3;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.7), 0 0 0 1px var(--border-subtle);
            background-color: var(--bg-surface);
        }

        .info-hero__poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .info-hero__meta {
            flex: 1;
            min-width: 0;
        }

        /* Logo do título (imagem) */
        .info-hero__logo {
            max-width: 380px;
            max-height: 100px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: left;
            display: block;
            margin-bottom: 16px;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.6));
        }

        /* Título textual (quando não há logo) */
        .info-hero__title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            color: var(--text-pure);
            line-height: 1.1;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 16px rgba(0,0,0,0.5);
        }

        .info-hero__tagline {
            font-size: 1rem;
            font-style: italic;
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-weight: 400;
        }

        /* Linha de badges de metadados */
        .info-hero__badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge--type {
            background: var(--color-accent);
            color: #fff;
        }

        .badge--year {
            background: var(--bg-elevated);
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
        }

        .badge--runtime {
            background: var(--bg-elevated);
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
        }

        .badge--status {
            background: var(--bg-elevated);
            color: var(--status-success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .badge--status.ended {
            color: var(--text-muted);
            border-color: var(--border-subtle);
        }

        /* Avaliação com estrela */
        .info-hero__rating {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
        }

        .rating-star {
            color: var(--color-primary);
            font-size: 1.2rem;
            line-height: 1;
        }

        .rating-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-pure);
            line-height: 1;
        }

        .rating-max {
            font-size: 0.875rem;
            color: var(--text-muted);
            align-self: flex-end;
            margin-bottom: 1px;
        }

        .rating-votes {
            font-size: 0.8rem;
            color: var(--text-muted);
            align-self: flex-end;
            margin-bottom: 2px;
        }

        /* Géneros */
        .info-hero__genres {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }

        .genre-tag {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border-subtle);
            padding: 4px 12px;
            border-radius: 50px;
            transition: background var(--transition-fast), color var(--transition-fast);
        }

        .genre-tag:hover {
            background: rgba(255,214,10,0.1);
            border-color: rgba(255,214,10,0.3);
            color: var(--color-primary);
        }

        /* Botões de Ação */
        .info-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .btn-watch {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--color-accent);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            padding: 13px 28px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
            box-shadow: 0 4px 16px rgba(229,9,20,0.35);
        }

        .btn-watch:hover {
            background: var(--color-accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(229,9,20,0.5);
        }

        .btn-watch:active {
            transform: translateY(0);
            background: var(--color-accent-active);
        }

        .btn-watch--unavailable {
            background: var(--bg-elevated);
            color: var(--text-muted);
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-trailer {
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 12px 22px;
            border-radius: 6px;
            border: 1px solid var(--border-strong);
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition-fast), border-color var(--transition-fast), color var(--transition-fast);
        }

        .btn-trailer:hover {
            background: var(--bg-elevated);
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        /* ── CONTAINER PRINCIPAL (abaixo do hero) ──────────────────────── */
        .info-body {
            max-width: 1400px;
            margin: 0 auto;
            padding: 48px 5% 80px;
        }

        /* ── LAYOUT DUAS COLUNAS (sinopse + sidebar) ───────────────────── */
        .info-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 48px;
            align-items: start;
            margin-bottom: 56px;
        }

        /* ── Sinopse ───────────────────────────────────────────────────── */
        .section-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--color-primary);
            margin-bottom: 12px;
            display: block;
        }

        .section-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-pure);
            margin-bottom: 16px;
            margin-top: 0;
        }

        .info-overview__text {
            font-size: 1rem;
            line-height: 1.75;
            color: var(--text-secondary);
            margin: 0 0 24px 0;
        }

        /* Equipe / Diretores */
        .crew-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }

        .crew-item__job {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .crew-item__name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ── Sidebar de Metadados ──────────────────────────────────────── */
        .info-sidebar {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            padding: 24px;
        }

        .meta-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-subtle);
        }

        .meta-row:first-child { padding-top: 0; }
        .meta-row:last-child  { border-bottom: none; padding-bottom: 0; }

        .meta-row__key {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .meta-row__val {
            font-size: 0.92rem;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.4;
        }

        /* ── Divisor de Seção ──────────────────────────────────────────── */
        .section-divider {
            border: none;
            border-top: 1px solid var(--border-subtle);
            margin: 0 0 40px 0;
        }

        /* ── ELENCO (carrossel horizontal) ─────────────────────────────── */
        .cast-section {
            margin-bottom: 56px;
        }

        .cast-track-wrapper {
            position: relative;
        }

        .cast-track {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 8px;
        }

        .cast-track::-webkit-scrollbar { display: none; }

        .cast-card {
            flex: 0 0 120px;
            scroll-snap-align: start;
        }

        .cast-card__photo {
            width: 120px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-surface);
            margin-bottom: 8px;
            border: 1px solid var(--border-subtle);
        }

        .cast-card__photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }

        .cast-card:hover .cast-card__photo img {
            transform: scale(1.05);
        }

        .cast-card__photo--placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }

        .cast-card__name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .cast-card__character {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.3;
        }

        /* ── TEMPORADAS ────────────────────────────────────────────────── */
        .seasons-section {
            margin-bottom: 56px;
        }

        .seasons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }

        .season-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: border-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .season-card:hover {
            border-color: var(--color-primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        .season-card__poster {
            width: 100%;
            aspect-ratio: 2 / 3;
            overflow: hidden;
            background: var(--bg-elevated);
        }

        .season-card__poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .season-card__info {
            padding: 10px 12px 12px;
        }

        .season-card__name {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-pure);
            margin-bottom: 3px;
        }

        .season-card__episodes {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ── RECOMENDADOS ──────────────────────────────────────────────── */
        .related-section {
            margin-bottom: 0;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }

        .related-card {
            text-decoration: none;
            display: block;
            cursor: pointer;
        }

        .related-card__poster {
            width: 100%;
            aspect-ratio: 2 / 3;
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-surface);
            margin-bottom: 8px;
            border: 1px solid var(--border-subtle);
            transition: border-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .related-card:hover .related-card__poster {
            border-color: var(--color-primary);
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.5);
        }

        .related-card__poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .related-card__title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.35;
            margin-bottom: 2px;
        }

        .related-card__meta {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* ── Modal de Trailer ──────────────────────────────────────────── */
        .trailer-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,0.88);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .trailer-modal.is-open {
            display: flex;
        }

        .trailer-modal__inner {
            position: relative;
            width: 100%;
            max-width: 900px;
        }

        .trailer-modal__close {
            position: absolute;
            top: -44px;
            right: 0;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color var(--transition-fast);
            padding: 6px;
        }

        .trailer-modal__close:hover { color: var(--text-pure); }

        .trailer-modal__frame-wrap {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            border-radius: 10px;
            overflow: hidden;
            background: #000;
        }

        .trailer-modal__frame-wrap iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            border: none;
        }

        /* ── Estado de Erro ────────────────────────────────────────────── */
        .info-error {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            gap: 12px;
            padding: 40px;
        }

        .info-error.is-visible { display: flex; }

        .info-error__code {
            font-size: 5rem;
            font-weight: 900;
            color: var(--bg-surface);
            line-height: 1;
        }

        .info-error__title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-pure);
            margin: 0;
        }

        .info-error__desc {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 400px;
            margin: 0 0 8px;
        }

        /* ── RESPONSIVO ────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .info-layout {
                grid-template-columns: 1fr 280px;
                gap: 32px;
            }
        }

        @media (max-width: 900px) {
            .info-hero__poster {
                width: 150px;
            }

            .info-hero__content {
                padding: 100px 4% 40px;
                gap: 24px;
            }

            .info-layout {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .info-sidebar {
                /* No mobile a sidebar fica acima da sinopse */
                order: -1;
            }

            .seasons-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }

            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .info-hero {
                min-height: auto;
                align-items: flex-start;
            }

            .info-hero__content {
                flex-direction: column;
                align-items: flex-start;
                padding: 80px 4% 32px;
                gap: 20px;
            }

            .info-hero__poster {
                width: 120px;
            }

            .info-hero__title {
                font-size: 1.75rem;
            }

            .info-hero__logo {
                max-width: 260px;
                max-height: 72px;
            }

            .btn-watch {
                font-size: 0.9rem;
                padding: 11px 22px;
            }

            .info-body {
                padding: 32px 4% 60px;
            }

            .crew-grid {
                grid-template-columns: 1fr 1fr;
            }

            .seasons-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .cast-card {
                flex: 0 0 100px;
            }

            .cast-card__photo {
                width: 100px;
                height: 126px;
            }
        }
    </style>
</head>

<body class="info-page">

    <?php require_once __DIR__ . '/../components/Header.php'; ?>

    <!-- Botão de Voltar fixo -->
    <a href="javascript:history.back()" class="btn-back" aria-label="Voltar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar
    </a>

    <!-- Estado de Erro (oculto inicialmente) -->
    <div class="info-error" id="infoError">
        <div class="info-error__code">404</div>
        <h1 class="info-error__title">Conteúdo não encontrado</h1>
        <p class="info-error__desc">Este título não está disponível na nossa base de dados ou o ID informado é inválido.</p>
        <a href="/home" class="btn-watch">Ir para o Início</a>
    </div>

    <!-- HERO -->
    <section class="info-hero" id="infoHero">
        <div class="info-hero__backdrop" id="heroBackdrop"></div>

        <div class="info-hero__content">
            <!-- Poster flutuante -->
            <div class="info-hero__poster skeleton" id="heroPosterWrap">
                <img src="" alt="" class="poster-img" id="heroPosterImg"
                     style="display:none; opacity:0; transition:opacity 0.4s ease;"
                     loading="eager">
            </div>

            <!-- Metadados -->
            <div class="info-hero__meta">
                <!-- Logo ou Título -->
                <img src="" alt="" class="info-hero__logo" id="heroLogo" style="display:none;">
                <h1 class="info-hero__title" id="heroTitle">
                    <span class="skeleton" style="display:block;height:3rem;width:60%;border-radius:6px;"></span>
                </h1>

                <p class="info-hero__tagline" id="heroTagline"></p>

                <!-- Badges -->
                <div class="info-hero__badges" id="heroBadges"></div>

                <!-- Rating -->
                <div class="info-hero__rating" id="heroRating"></div>

                <!-- Géneros -->
                <div class="info-hero__genres" id="heroGenres"></div>

                <!-- Botões de Ação -->
                <div class="info-hero__actions" id="heroActions"></div>
            </div>
        </div>
    </section>

    <!-- CORPO PRINCIPAL -->
    <main class="info-body" id="infoBody" style="display:none;">

        <!-- Layout: Sinopse + Sidebar -->
        <div class="info-layout" id="infoLayout">
            <!-- Coluna principal -->
            <div class="info-main-col">
                <span class="section-label">Sinopse</span>
                <p class="info-overview__text" id="infoOverview"></p>

                <!-- Equipe/Diretores -->
                <div id="crewSection" style="display:none;">
                    <span class="section-label" style="margin-top:28px; display:block;">Direção &amp; Criação</span>
                    <div class="crew-grid" id="crewGrid"></div>
                </div>
            </div>

            <!-- Sidebar de Metadados -->
            <aside class="info-sidebar" id="infoSidebar">
                <!-- Preenchido via JS -->
            </aside>
        </div>

        <hr class="section-divider">

        <!-- ELENCO -->
        <section class="cast-section" id="castSection" style="display:none;">
            <span class="section-label">Elenco Principal</span>
            <div class="cast-track-wrapper">
                <div class="cast-track" id="castTrack"></div>
            </div>
        </section>

        <!-- TEMPORADAS (apenas séries) -->
        <section class="seasons-section" id="seasonsSection" style="display:none;">
            <hr class="section-divider">
            <span class="section-label">Temporadas</span>
            <div class="seasons-grid" id="seasonsGrid"></div>
        </section>

        <!-- RECOMENDADOS -->
        <section class="related-section" id="relatedSection" style="display:none;">
            <hr class="section-divider">
            <span class="section-label">Você também pode gostar</span>
            <h2 class="section-title">Títulos Relacionados</h2>
            <div class="related-grid" id="relatedGrid"></div>
        </section>

    </main>

    <!-- Modal de Trailer -->
    <div class="trailer-modal" id="trailerModal" role="dialog" aria-modal="true" aria-label="Trailer">
        <div class="trailer-modal__inner">
            <button class="trailer-modal__close" id="trailerClose">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Fechar
            </button>
            <div class="trailer-modal__frame-wrap">
                <iframe id="trailerFrame" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="/assets/js/header.js"></script>

    <script>
    (function () {
        'use strict';

        /* ── Configuração ────────────────────────────────────────────── */
        const TMDB_ID  = <?= (int)$tmdbId ?>;
        const API_BASE = '/api/v2/info';

        /* ── Referências DOM ─────────────────────────────────────────── */
        const $ = id => document.getElementById(id);

        const heroBackdrop  = $('heroBackdrop');
        const heroPosterWrap= $('heroPosterWrap');
        const heroPosterImg = $('heroPosterImg');
        const heroLogo      = $('heroLogo');
        const heroTitle     = $('heroTitle');
        const heroTagline   = $('heroTagline');
        const heroBadges    = $('heroBadges');
        const heroRating    = $('heroRating');
        const heroGenres    = $('heroGenres');
        const heroActions   = $('heroActions');
        const infoBody      = $('infoBody');
        const infoOverview  = $('infoOverview');
        const infoSidebar   = $('infoSidebar');
        const crewSection   = $('crewSection');
        const crewGrid      = $('crewGrid');
        const castSection   = $('castSection');
        const castTrack     = $('castTrack');
        const seasonsSection= $('seasonsSection');
        const seasonsGrid   = $('seasonsGrid');
        const relatedSection= $('relatedSection');
        const relatedGrid   = $('relatedGrid');
        const infoError     = $('infoError');
        const trailerModal  = $('trailerModal');
        const trailerFrame  = $('trailerFrame');
        const trailerClose  = $('trailerClose');

        /* ── Utilitários ─────────────────────────────────────────────── */
        function esc(str) {
            return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('pt-BR', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        function formatYear(dateStr) {
            if (!dateStr) return '';
            return dateStr.substring(0, 4);
        }

        function formatRuntime(mins) {
            if (!mins) return null;
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            if (h > 0 && m > 0) return `${h}h ${m}m`;
            if (h > 0) return `${h}h`;
            return `${m}m`;
        }

        function formatLanguage(code) {
            const map = {
                'pt':'Português','en':'Inglês','es':'Espanhol','fr':'Francês',
                'de':'Alemão','ja':'Japonês','ko':'Coreano','zh':'Chinês',
                'it':'Italiano','ru':'Russo','ar':'Árabe','hi':'Hindi',
                'tr':'Turco','nl':'Holandês','sv':'Sueco','pl':'Polonês',
            };
            return map[code] ?? code?.toUpperCase() ?? '—';
        }

        /* ── Busca os dados da API ────────────────────────────────────── */
        async function fetchInfo() {
            try {
                const res = await fetch(`${API_BASE}?id=${TMDB_ID}`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const json = await res.json();
                if (!json.sucesso) throw new Error(json.erro ?? 'Erro desconhecido');
                render(json.dados);
            } catch (err) {
                console.error('[info] Erro ao buscar dados:', err);
                showError();
            }
        }

        /* ── Exibe estado de erro ────────────────────────────────────── */
        function showError() {
            $('infoHero').style.display = 'none';
            infoError.classList.add('is-visible');
        }

        /* ── Renderização principal ──────────────────────────────────── */
        function render(d) {
            // Atualiza <title>
            document.title = `${d.titulo} — PipoCine`;

            // ── Hero: Backdrop
            if (d.backdrop) {
                heroBackdrop.style.backgroundImage = `url('${esc(d.backdrop)}')`;
            }

            // ── Hero: Poster
            if (d.poster) {
                heroPosterImg.src = d.poster;
                heroPosterImg.alt = d.titulo;
                heroPosterImg.style.display = 'block';
                heroPosterImg.onload = () => {
                    heroPosterWrap.classList.remove('skeleton');
                    heroPosterImg.style.opacity = '1';
                };
                heroPosterImg.onerror = () => heroPosterWrap.classList.remove('skeleton');
            } else {
                heroPosterWrap.classList.remove('skeleton');
            }

            // ── Hero: Logo ou Título
            heroTitle.innerHTML = '';
            if (d.logo) {
                heroLogo.src = d.logo;
                heroLogo.alt = d.titulo;
                heroLogo.style.display = 'block';
            } else {
                heroTitle.textContent = d.titulo;
            }

            // ── Hero: Tagline
            if (d.tagline) {
                heroTagline.textContent = `"${d.tagline}"`;
            }

            // ── Hero: Badges
            const typeLabel = d.tipo === 'serie' ? 'Série' : 'Filme';
            let badgesHTML = `<span class="badge badge--type">${typeLabel}</span>`;
            if (d.data_lancamento) {
                badgesHTML += `<span class="badge badge--year">${formatYear(d.data_lancamento)}</span>`;
            }
            const rt = formatRuntime(d.duracao_minutos);
            if (rt) {
                badgesHTML += `<span class="badge badge--runtime">${rt}</span>`;
            }
            if (d.status) {
                const endedStatuses = ['Encerrado', 'Cancelado', 'Lançado'];
                const isEnded = endedStatuses.includes(d.status);
                badgesHTML += `<span class="badge badge--status${isEnded ? ' ended' : ''}">${esc(d.status)}</span>`;
            }
            heroBadges.innerHTML = badgesHTML;

            // ── Hero: Rating
            if (d.nota > 0) {
                heroRating.innerHTML = `
                    <span class="rating-star">★</span>
                    <span class="rating-value">${d.nota}</span>
                    <span class="rating-max">/10</span>
                    ${d.votos ? `<span class="rating-votes">(${d.votos.toLocaleString('pt-BR')} votos)</span>` : ''}
                `;
            }

            // ── Hero: Géneros
            if (d.generos?.length) {
                heroGenres.innerHTML = d.generos.map(g => `<span class="genre-tag">${esc(g)}</span>`).join('');
            }

            // ── Hero: Botões de Ação
            let actionsHTML = '';
            if (d.disponivel) {
                const watchUrl = d.tipo === 'serie'
                    ? `/view?id=${d.id_tmdb}&type=serie&s=1&e=1`
                    : `/view?id=${d.id_tmdb}&type=movie`;
                actionsHTML += `
                    <a href="${esc(watchUrl)}" class="btn-watch">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Assistir Agora
                    </a>`;
            } else {
                actionsHTML += `
                    <button class="btn-watch btn-watch--unavailable" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Em Breve
                    </button>`;
            }
            if (d.trailer_key) {
                actionsHTML += `
                    <button class="btn-trailer" id="btnTrailer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                        </svg>
                        Ver Trailer
                    </button>`;
            }
            heroActions.innerHTML = actionsHTML;

            // Evento do botão trailer
            const btnTrailer = $('btnTrailer');
            if (btnTrailer) {
                btnTrailer.addEventListener('click', () => openTrailer(d.trailer_key));
            }

            // ── Body: Sinopse
            infoOverview.textContent = d.sinopse || 'Sinopse não disponível.';

            // ── Body: Equipe
            if (d.equipe?.length) {
                crewGrid.innerHTML = d.equipe.slice(0, 6).map(c => `
                    <div class="crew-item">
                        <div class="crew-item__job">${esc(c.job)}</div>
                        <div class="crew-item__name">${esc(c.name)}</div>
                    </div>`).join('');
                crewSection.style.display = 'block';
            }

            // ── Body: Sidebar de Metadados
            renderSidebar(d);

            // ── Body: Elenco
            if (d.elenco?.length) {
                castTrack.innerHTML = d.elenco.map(m => `
                    <div class="cast-card">
                        <div class="cast-card__photo${!m.profile ? ' cast-card__photo--placeholder' : ''}">
                            ${m.profile
                                ? `<img src="${esc(m.profile)}" alt="${esc(m.name)}" loading="lazy">`
                                : `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                       fill="none" stroke="currentColor" stroke-width="1.5"
                                       stroke-linecap="round" stroke-linejoin="round">
                                       <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                       <circle cx="12" cy="7" r="4"/>
                                   </svg>`}
                        </div>
                        <div class="cast-card__name">${esc(m.name)}</div>
                        ${m.character ? `<div class="cast-card__character">${esc(m.character)}</div>` : ''}
                    </div>`).join('');
                castSection.style.display = 'block';
            }

            // ── Body: Temporadas
            if (d.tipo === 'serie' && d.temporadas?.length) {
                seasonsGrid.innerHTML = d.temporadas.map(s => `
                    <div class="season-card" onclick="window.location.href='/view?id=${d.id_tmdb}&type=serie&s=${s.number}&e=1'">
                        <div class="season-card__poster">
                            <img src="${esc(s.poster)}" alt="${esc(s.name)}" loading="lazy">
                        </div>
                        <div class="season-card__info">
                            <div class="season-card__name">${esc(s.name)}</div>
                            <div class="season-card__episodes">${s.episode_count} episódio${s.episode_count !== 1 ? 's' : ''}</div>
                        </div>
                    </div>`).join('');
                seasonsSection.style.display = 'block';
            }

            // ── Body: Recomendados
            if (d.recomendados?.length) {
                relatedGrid.innerHTML = d.recomendados.map(r => `
                    <a class="related-card" href="/info=${r.tmdb_id}">
                        <div class="related-card__poster">
                            <img src="${esc(r.poster)}" alt="${esc(r.title)}" loading="lazy">
                        </div>
                        <div class="related-card__title">${esc(r.title)}</div>
                        ${r.vote > 0 ? `<div class="related-card__meta">★ ${r.vote}</div>` : ''}
                    </a>`).join('');
                relatedSection.style.display = 'block';
            }

            // ── Mostra o corpo
            infoBody.style.display = 'block';
        }

        /* ── Sidebar de Metadados ────────────────────────────────────── */
        function renderSidebar(d) {
            const rows = [];

            if (d.titulo_original && d.titulo_original !== d.titulo) {
                rows.push({ key: 'Título Original', val: d.titulo_original });
            }
            if (d.data_lancamento) {
                rows.push({ key: d.tipo === 'serie' ? 'Estreia' : 'Lançamento', val: formatDate(d.data_lancamento) });
            }
            if (d.tipo === 'serie') {
                if (d.total_temporadas)  rows.push({ key: 'Temporadas',  val: d.total_temporadas });
                if (d.total_episodios)   rows.push({ key: 'Episódios',   val: d.total_episodios });
                if (d.redes_exibicao?.length) rows.push({ key: 'Rede', val: d.redes_exibicao.join(', ') });
            }
            if (d.duracao_minutos) {
                rows.push({ key: 'Duração', val: formatRuntime(d.duracao_minutos) });
            }
            if (d.pais_origem)       rows.push({ key: 'País',    val: esc(d.pais_origem) });
            if (d.idioma_original)   rows.push({ key: 'Idioma',  val: formatLanguage(d.idioma_original) });
            if (d.produtoras?.length) rows.push({ key: 'Produção', val: d.produtoras.map(esc).join(', ') });
            if (d.keywords?.length) {
                rows.push({ key: 'Palavras-chave', val: d.keywords.map(k => `<span class="genre-tag" style="display:inline-block;margin:2px 2px 0 0;">${esc(k)}</span>`).join('') });
            }

            if (rows.length === 0) {
                infoSidebar.style.display = 'none';
                return;
            }

            infoSidebar.innerHTML = rows.map(r => `
                <div class="meta-row">
                    <span class="meta-row__key">${r.key}</span>
                    <span class="meta-row__val">${r.val}</span>
                </div>`).join('');
        }

        /* ── Modal de Trailer ────────────────────────────────────────── */
        function openTrailer(key) {
            trailerFrame.src = `https://www.youtube.com/embed/${key}?autoplay=1&rel=0`;
            trailerModal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeTrailer() {
            trailerModal.classList.remove('is-open');
            trailerFrame.src = '';
            document.body.style.overflow = '';
        }

        trailerClose.addEventListener('click', closeTrailer);
        trailerModal.addEventListener('click', e => { if (e.target === trailerModal) closeTrailer(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTrailer(); });

        /* ── Inicia ──────────────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            fetchInfo();
        });

    })();
    </script>

</body>
</html>
