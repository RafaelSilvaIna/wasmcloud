<?php
/**
 * pages/plataforma.php
 *
 * Página de conteúdo por plataforma de streaming.
 * URL: /plataforma?marca=netflix | prime | disney | max | globoplay | appletv | paramount
 */

require_once __DIR__ . '/../database/db.php';

// Protege a rota
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// ── Definições de marcas suportadas ──────────────────────────────────────────
$BRANDS = [
    'netflix'   => [
        'nome'       => 'Netflix',
        'cor'        => '#e50914',
        'cor_escura' => '#8b0000',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg',
        'descricao'  => 'Filmes e séries originais da Netflix disponíveis no Brasil',
    ],
    'prime'     => [
        'nome'       => 'Prime Video',
        'cor'        => '#00a8e0',
        'cor_escura' => '#004f6e',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
        'descricao'  => 'Catálogo Amazon Prime Video disponível no Brasil',
    ],
    'disney'    => [
        'nome'       => 'Disney+',
        'cor'        => '#0063e5',
        'cor_escura' => '#002d6e',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
        'descricao'  => 'O melhor da Disney, Marvel, Star Wars e National Geographic',
    ],
    'max'       => [
        'nome'       => 'Max',
        'cor'        => '#002be7',
        'cor_escura' => '#001080',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/1/17/HBO_Max_Logo.svg',
        'descricao'  => 'Conteúdo HBO e Max disponível no Brasil',
    ],
    'globoplay' => [
        'nome'       => 'Globoplay',
        'cor'        => '#e30000',
        'cor_escura' => '#7a0000',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/7/7e/Globoplay_logo.svg',
        'descricao'  => 'Produções e séries do Globoplay no Brasil',
    ],
    'appletv'   => [
        'nome'       => 'Apple TV+',
        'cor'        => '#555555',
        'cor_escura' => '#1a1a1a',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/2/28/Apple_TV_Plus_Logo.svg',
        'descricao'  => 'Originais Apple TV+ disponíveis no Brasil',
    ],
    'paramount' => [
        'nome'       => 'Paramount+',
        'cor'        => '#0064ff',
        'cor_escura' => '#002d80',
        'logo'       => 'https://upload.wikimedia.org/wikipedia/commons/a/a5/Paramount_Plus_logo.svg',
        'descricao'  => 'Séries e filmes Paramount+ disponíveis no Brasil',
    ],
];

$marcaSlug = strtolower(trim($_GET['marca'] ?? ''));

// Redireciona para Netflix como padrão se marca for inválida
if (!$marcaSlug || !isset($BRANDS[$marcaSlug])) {
    header('Location: /plataforma?marca=netflix');
    exit;
}

$brand = $BRANDS[$marcaSlug];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0c10">
    <title><?= htmlspecialchars($brand['nome']) ?> — PipoCine</title>
    <meta name="description" content="<?= htmlspecialchars($brand['descricao']) ?>">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/content-card.css">

    <style>
        /* ── Variáveis da plataforma (injetadas via PHP) ───────────────────── */
        :root {
            --brand-cor:        <?= htmlspecialchars($brand['cor']) ?>;
            --brand-cor-escura: <?= htmlspecialchars($brand['cor_escura']) ?>;
        }

        /* ── Hero da plataforma ─────────────────────────────────────────────── */
        .plat-hero {
            position: relative;
            width: 100%;
            min-height: 220px;
            background: linear-gradient(
                135deg,
                var(--brand-cor-escura) 0%,
                #0a0c10 60%
            );
            display: flex;
            align-items: flex-end;
            padding: 80px 5% 40px;
            overflow: hidden;
        }

        /* Glow sutil da cor da marca no canto */
        .plat-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            left: -60px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--brand-cor) 0%, transparent 70%);
            opacity: .12;
            pointer-events: none;
        }

        .plat-hero-inner {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 28px;
            max-width: 1200px;
            width: 100%;
        }

        .plat-logo-wrap {
            width: 96px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            padding: 10px 14px;
            flex-shrink: 0;
        }

        .plat-logo-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        /* Apple TV+ tem logo branca — não inverte */
        .plat-logo-wrap img.no-invert {
            filter: none;
        }

        .plat-hero-text h1 {
            font-size: clamp(1.5rem, 4vw, 2.4rem);
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
            line-height: 1.1;
        }

        .plat-hero-text p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 6px;
            max-width: 480px;
            line-height: 1.5;
        }

        /* Linha colorida da marca na base do hero */
        .plat-hero-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand-cor) 0%, transparent 100%);
        }

        /* ── Barra de filtros ───────────────────────────────────────────────── */
        .plat-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 5% 0;
            max-width: 1260px;
            margin: 0 auto;
        }

        .filter-btn {
            padding: 7px 20px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            border: 1.5px solid rgba(255,255,255,.2);
            background: transparent;
            color: var(--text-secondary);
            transition: all .15s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .filter-btn.active,
        .filter-btn:hover {
            border-color: var(--brand-cor);
            color: #fff;
            background: rgba(var(--brand-cor-rgb, 229,9,20), .15);
        }

        .filter-btn.active {
            background: var(--brand-cor);
            color: #fff;
        }

        /* ── Grade de cards ─────────────────────────────────────────────────── */
        .plat-main {
            max-width: 1260px;
            margin: 0 auto;
            padding: 28px 5% 80px;
        }

        .plat-results-info {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 20px;
            letter-spacing: .02em;
        }

        .plat-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }

        /* Card item na grade de plataforma — mesmo visual do slick-item */
        .plat-grid .slick-item {
            flex: none;
            max-width: none;
            width: 100%;
        }

        /* ── Estado vazio ────────────────────────────────────────────────────── */
        .plat-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            gap: 14px;
            color: var(--text-muted);
            text-align: center;
        }

        .plat-empty svg {
            width: 52px;
            height: 52px;
            opacity: .3;
        }

        .plat-empty p {
            font-size: 15px;
            font-weight: 500;
        }

        .plat-empty span {
            font-size: 13px;
            color: var(--text-muted);
            max-width: 320px;
            line-height: 1.6;
        }

        /* ── Skeleton da grade ──────────────────────────────────────────────── */
        .plat-skeleton-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }

        .plat-skeleton-card {
            aspect-ratio: 2/3;
            background: linear-gradient(90deg, #1a1e28 25%, #232936 50%, #1a1e28 75%);
            background-size: 200% 100%;
            animation: shimmer 1.6s infinite;
            border-radius: 4px;
        }

        .plat-skeleton-title {
            height: 13px;
            background: #1a1e28;
            border-radius: 4px;
            margin-top: 9px;
            width: 75%;
            animation: shimmer 1.6s infinite;
        }

        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ── Loader / carregar mais ─────────────────────────────────────────── */
        .plat-load-wrap {
            display: flex;
            justify-content: center;
            padding: 32px 0 16px;
        }

        .btn-load-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 36px;
            background: rgba(255,255,255,.07);
            border: 1.5px solid rgba(255,255,255,.15);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .15s ease;
        }

        .btn-load-more:hover {
            border-color: var(--brand-cor);
            color: #fff;
        }

        .btn-load-more:disabled {
            opacity: .4;
            cursor: default;
        }

        .spinner-inline {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }

        .btn-load-more.loading .spinner-inline { display: block; }
        .btn-load-more.loading .btn-load-text  { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Responsive ─────────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .plat-grid,
            .plat-skeleton-grid { grid-template-columns: repeat(5, 1fr); }
        }

        @media (max-width: 860px) {
            .plat-grid,
            .plat-skeleton-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (max-width: 640px) {
            .plat-hero { padding: 72px 4% 28px; min-height: 160px; }
            .plat-hero-inner { gap: 16px; }
            .plat-logo-wrap { width: 72px; height: 46px; }
            .plat-hero-text p { display: none; }
            .plat-filters { padding: 16px 4% 0; gap: 8px; flex-wrap: wrap; }
            .filter-btn { padding: 6px 14px; font-size: 11px; }
            .plat-main { padding: 20px 4% 80px; }
            .plat-grid,
            .plat-skeleton-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
        }

        @media (max-width: 420px) {
            .plat-grid,
            .plat-skeleton-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/../components/Header.php'; ?>

    <!-- ── Hero ───────────────────────────────────────────────────────────── -->
    <div class="plat-hero">
        <div class="plat-hero-inner">
            <div class="plat-logo-wrap">
                <img
                    src="<?= htmlspecialchars($brand['logo']) ?>"
                    alt="Logo <?= htmlspecialchars($brand['nome']) ?>"
                    class="<?= $marcaSlug === 'appletv' ? 'no-invert' : '' ?>"
                    onerror="this.style.display='none'"
                >
            </div>
            <div class="plat-hero-text">
                <h1><?= htmlspecialchars($brand['nome']) ?></h1>
                <p><?= htmlspecialchars($brand['descricao']) ?></p>
            </div>
        </div>
        <div class="plat-hero-bar"></div>
    </div>

    <!-- ── Filtros de tipo ────────────────────────────────────────────────── -->
    <nav class="plat-filters" aria-label="Filtrar por tipo">
        <button class="filter-btn active" data-tipo="">Todos</button>
        <button class="filter-btn" data-tipo="filme">Filmes</button>
        <button class="filter-btn" data-tipo="serie">Series</button>
    </nav>

    <!-- ── Grade de resultados ───────────────────────────────────────────── -->
    <main class="plat-main">
        <p class="plat-results-info" id="plat-info" aria-live="polite"></p>

        <!-- Skeletons enquanto carrega -->
        <div class="plat-skeleton-grid" id="plat-skeletons">
            <?php for ($i = 0; $i < 18; $i++): ?>
            <div>
                <div class="plat-skeleton-card"></div>
                <div class="plat-skeleton-title"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Grade real (preenchida via JS) -->
        <div class="plat-grid" id="plat-grid" role="list" aria-label="Conteúdos <?= htmlspecialchars($brand['nome']) ?>"></div>

        <!-- Estado vazio -->
        <div class="plat-empty" id="plat-empty" style="display:none">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"/>
            </svg>
            <p>Nenhum conteudo encontrado</p>
            <span>Nao encontramos titulos da <?= htmlspecialchars($brand['nome']) ?> disponiveis no momento. Tente outro filtro.</span>
        </div>

        <!-- Carregar mais -->
        <div class="plat-load-wrap" id="plat-load-wrap" style="display:none">
            <button class="btn-load-more" id="btn-load-more" aria-label="Carregar mais conteudos">
                <span class="spinner-inline" aria-hidden="true"></span>
                <span class="btn-load-text">Carregar mais</span>
            </button>
        </div>
    </main>

    <!-- Template de card (reutiliza o mesmo da home) -->
    <?php require_once __DIR__ . '/../components/ContentCard.php'; ?>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="/assets/js/header.js"></script>

    <script>
    (function () {
        'use strict';

        // ── Configuração ────────────────────────────────────────────────────
        const MARCA      = <?= json_encode($marcaSlug) ?>;
        const BRAND_COR  = <?= json_encode($brand['cor']) ?>;
        let   currentPage = 1;
        let   currentTipo = '';
        let   isLoading   = false;
        let   hasMore     = true;

        // ── Elementos DOM ───────────────────────────────────────────────────
        const grid       = document.getElementById('plat-grid');
        const skeletons  = document.getElementById('plat-skeletons');
        const emptyState = document.getElementById('plat-empty');
        const infoText   = document.getElementById('plat-info');
        const loadWrap   = document.getElementById('plat-load-wrap');
        const btnLoad    = document.getElementById('btn-load-more');
        const filterBtns = document.querySelectorAll('.filter-btn');

        // ── Template de card ────────────────────────────────────────────────
        const cardTemplate = document.getElementById('pipo-card-template');

        // ── Fetch de dados ──────────────────────────────────────────────────
        async function fetchPlatform(page, tipo) {
            const params = new URLSearchParams({ marca: MARCA, pagina: page, limite: 24 });
            if (tipo) params.set('tipo', tipo);

            const res  = await fetch(`/api/v2/plataforma?${params}`);
            if (!res.ok) throw new Error('Erro na API: ' + res.status);
            return res.json();
        }

        // ── Renderiza um card ────────────────────────────────────────────────
        function renderCard(item) {
            const clone = cardTemplate.content.cloneNode(true);
            const wrap  = clone.querySelector('.slick-item');
            const card  = clone.querySelector('.slick-card');
            const img   = clone.querySelector('.poster');
            const badge = clone.querySelector('.slick-badge');
            const title = clone.querySelector('.card-title-outside');

            // URL de destino
            const dest = item.tipo === 'serie'
                ? `/view?id=${item.id_tmdb}&type=serie`
                : `/view?id=${item.id_tmdb}&type=filme`;

            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-label', item.titulo);
            card.addEventListener('click', () => { window.location.href = dest; });
            card.addEventListener('keydown', e => { if (e.key === 'Enter') window.location.href = dest; });

            img.src     = item.poster || '/assets/img/no-poster.jpg';
            img.alt     = item.titulo;
            img.onerror = function () { this.src = '/assets/img/no-poster.jpg'; };

            badge.textContent = item.tipo === 'serie' ? 'SERIE' : 'FILME';

            title.textContent = item.titulo;
            title.title       = item.titulo;

            wrap.setAttribute('role', 'listitem');
            return wrap;
        }

        // ── Carrega e exibe resultados ───────────────────────────────────────
        async function load(reset = false) {
            if (isLoading || (!hasMore && !reset)) return;
            isLoading = true;

            if (reset) {
                currentPage = 1;
                hasMore     = true;
                grid.innerHTML = '';
                skeletons.style.display  = 'grid';
                emptyState.style.display = 'none';
                loadWrap.style.display   = 'none';
                infoText.textContent     = '';
            }

            if (!reset) {
                btnLoad.classList.add('loading');
                btnLoad.disabled = true;
            }

            try {
                const data = await fetchPlatform(currentPage, currentTipo);

                skeletons.style.display = 'none';

                if (!data.sucesso) throw new Error(data.erro || 'Erro desconhecido');

                const items = data.resultados || [];

                if (items.length === 0 && currentPage === 1) {
                    emptyState.style.display = 'flex';
                    loadWrap.style.display   = 'none';
                    return;
                }

                items.forEach(item => grid.appendChild(renderCard(item)));

                hasMore = !!data.tem_mais;
                infoText.textContent = hasMore
                    ? `Mostrando ${grid.children.length} titulos. Role para ver mais.`
                    : `${grid.children.length} titulos encontrados.`;

                loadWrap.style.display = hasMore ? 'flex' : 'none';
                currentPage++;

                // Inicializa ícones Lucide se disponível
                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                skeletons.style.display = 'none';
                if (currentPage === 1) {
                    emptyState.style.display = 'flex';
                }
                console.error('[Plataforma]', err);
            } finally {
                isLoading = false;
                btnLoad.classList.remove('loading');
                btnLoad.disabled = false;
            }
        }

        // ── Filtros ─────────────────────────────────────────────────────────
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.classList.contains('active')) return;
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentTipo = btn.dataset.tipo;
                load(true);
            });
        });

        // ── Carregar mais ───────────────────────────────────────────────────
        btnLoad.addEventListener('click', () => load(false));

        // ── Inicialização ────────────────────────────────────────────────────
        load(true);

        // ── Inicializa ícones ─────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    })();
    </script>

</body>
</html>
