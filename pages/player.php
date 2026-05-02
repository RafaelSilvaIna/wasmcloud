<?php
/**
 * ARQUIVO: pages/player.php
 * DESCRIÇÃO: Página de player de vídeo — estilo Netflix
 * Suporta: HLS (m3u8), MP4, WebM, MKV
 * Rotas esperadas:
 *   /assistir/filme/<tmdb_id>
 *   /assistir/serie/<tmdb_id>/<temporada>/<episodio>
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    header('Location: /login?redirect=' . urlencode($currentUrl));
    exit;
}

require_once __DIR__ . '/../database/db.php';

// ─── Parâmetros ───────────────────────────────────────────────────────────────
$contentType = strtolower(trim($_GET['type'] ?? 'filme'));
$tmdbId      = (int) ($_GET['id'] ?? 0);
$season      = (int) ($_GET['s']  ?? 1);
$episode     = (int) ($_GET['e']  ?? 1);
$audio       = strtolower(trim($_GET['audio'] ?? 'dub'));

$isSerie = in_array($contentType, ['serie', 'series', 'tv'], true);

if ($tmdbId <= 0) {
    header('Location: /');
    exit;
}

// ─── Metadados do conteúdo (título, capa, poster) — banco cineveo ────────────
$content = null;
if ($pdoCineveo) {
    try {
        $tipoDB = $isSerie ? 'serie' : 'filme';
        $stmt = $pdoCineveo->prepare(
            "SELECT titulo, sinopse, poster, capa, slug, data_lancamento, nota
             FROM conteudo WHERE id_tmdb = ? AND tipo = ? LIMIT 1"
        );
        $stmt->execute([$tmdbId, $tipoDB]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}
}

if (!$content) {
    header('Location: /');
    exit;
}

// Normaliza imagens
$backdropImg = $content['capa'] ?? '';
if ($backdropImg && strpos($backdropImg, 'http') !== 0) {
    $backdropImg = 'https://image.tmdb.org/t/p/original/' . ltrim($backdropImg, '/');
}

$posterImg = $content['poster'] ?? '';
if ($posterImg && strpos($posterImg, 'http') !== 0) {
    $posterImg = 'https://image.tmdb.org/t/p/w500/' . ltrim($posterImg, '/');
}

$title = $content['titulo'] ?? 'Sem título';
$slug  = $content['slug'] ?? '';

// Título do episódio (série) — banco cineveo
$episodeName = '';
$episodeSynopsis = '';
if ($isSerie && $pdoCineveo) {
    try {
        $stmtEp = $pdoCineveo->prepare(
            "SELECT nome, sinopse FROM episodios
             WHERE id_tmdb = ? AND temporada = ? AND episodio = ? LIMIT 1"
        );
        $stmtEp->execute([$tmdbId, $season, $episode]);
        $ep = $stmtEp->fetch(PDO::FETCH_ASSOC);
        if ($ep) {
            $episodeName    = $ep['nome'] ?? '';
            $episodeSynopsis = $ep['sinopse'] ?? '';
        }
    } catch (Throwable $e) {}
}

$pageTitle = $isSerie
    ? htmlspecialchars("{$title} — T{$season}E{$episode}" . ($episodeName ? ": {$episodeName}" : '') . ' — Pipocine')
    : htmlspecialchars("{$title} — Pipocine");

// URL da API de vídeo (chamada pelo JS no carregamento)
$apiUrl = "/api/v2/episode-url?id={$tmdbId}&type={$contentType}&s={$season}&e={$episode}&audio={$audio}";

// Link de voltar: para série vai para a página de episódios; para filme vai para /info=<id>
$backUrl = $isSerie
    ? ($slug ? "/serie/{$slug}" : "/info={$tmdbId}")
    : "/info={$tmdbId}";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="robots" content="noindex,nofollow">

    <!-- HLS.js para streams M3U8 -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js" defer></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:        #e50914;
            --accent-hover:  #b20610;
            --bg:            #141414;
            --surface:       #1f1f1f;
            --surface2:      #2a2a2a;
            --text-pure:     #ffffff;
            --text-primary:  #e5e5e5;
            --text-secondary:#b3b3b3;
            --text-muted:    #737373;
            --border:        #2a2a2a;
            --border-strong: #404040;
            --radius:        4px;
            --transition:    .15s ease;
        }

        html, body {
            width: 100%;
            background: var(--bg);
            color: var(--text-primary);
            font-family: 'Netflix Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ─── NAVBAR ──────────────────────────────────────────────────── */
        #pplayer-nav {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 200;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            background: linear-gradient(to bottom, rgba(0,0,0,.9) 0%, transparent 100%);
            pointer-events: none;
            transition: opacity .3s;
        }
        #pplayer-nav.hidden { opacity: 0; }
        #pplayer-nav > * { pointer-events: auto; }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-pure);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            opacity: .85;
            transition: opacity var(--transition);
            padding: 4px 0;
        }
        .nav-back-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
        .nav-back-btn:hover { opacity: 1; }

        .nav-divider {
            width: 1px;
            height: 20px;
            background: rgba(255,255,255,.2);
        }

        .nav-title-block {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .nav-series-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-pure);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 340px;
            letter-spacing: .01em;
        }
        .nav-ep-label {
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        /* ─── WRAP DO PLAYER ──────────────────────────────────────────── */
        #player-wrap {
            position: relative;
            width: 100%;
            background: #000;
            aspect-ratio: 16/9;
            overflow: hidden;
            cursor: none;
        }
        #player-wrap.cursor-visible { cursor: default; }

        /* Em tela cheia o player ocupa tudo */
        #player-wrap:fullscreen,
        #player-wrap:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
            aspect-ratio: unset;
        }

        #pip-video {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            background: #000;
        }

        /* ─── LOADER OVERLAY ──────────────────────────────────────────── */
        #pip-loader-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            z-index: 10;
            transition: opacity .25s;
        }
        #pip-loader-overlay.hidden { opacity: 0; pointer-events: none; }

        /* Spinner estilo Netflix — anel fino + ponto vermelho */
        .loader-spinner {
            position: relative;
            width: 44px; height: 44px;
        }
        .loader-spinner::before {
            content: '';
            position: absolute; inset: 0;
            border: 3px solid rgba(255,255,255,.12);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .loader-text {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* ─── ERRO OVERLAY ────────────────────────────────────────────── */
        #pip-error-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.9);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            z-index: 15;
            padding: 32px;
            text-align: center;
        }
        #pip-error-overlay.visible { display: flex; }

        .error-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: rgba(229,9,20,.1);
            border: 1px solid rgba(229,9,20,.25);
            display: flex; align-items: center; justify-content: center;
        }
        .error-icon svg { width: 26px; height: 26px; color: var(--accent); }
        .error-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-pure);
        }
        .error-message {
            font-size: 13px;
            color: var(--text-secondary);
            max-width: 380px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 4px;
        }
        .btn-retry {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            padding: 9px 22px;
            cursor: pointer;
            letter-spacing: .02em;
            transition: background var(--transition);
        }
        .btn-retry:hover { background: var(--accent-hover); }
        .btn-back-err {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: var(--radius);
            padding: 9px 22px;
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition);
        }
        .btn-back-err:hover { background: rgba(255,255,255,.15); }

        /* ─── CONTROLES CUSTOMIZADOS ──────────────────────────────────── */
        #pip-controls {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            z-index: 20;
            padding: 0 24px 20px;
            background: linear-gradient(to top,
                rgba(0,0,0,.95) 0%,
                rgba(0,0,0,.5) 50%,
                transparent 100%);
            transition: opacity .25s;
        }
        #pip-controls.hidden {
            opacity: 0;
            pointer-events: none;
        }

        /* Progress bar — estilo Netflix: track fino, fill vermelho, thumb no hover */
        .progress-wrap {
            position: relative;
            height: 3px;
            background: rgba(255,255,255,.3);
            border-radius: 0;
            cursor: pointer;
            margin-bottom: 16px;
            transition: height .12s;
        }
        .progress-wrap:hover { height: 5px; }
        .progress-fill {
            position: absolute;
            top: 0; left: 0;
            height: 100%;
            background: var(--accent);
            pointer-events: none;
        }
        .progress-buffer {
            position: absolute;
            top: 0; left: 0;
            height: 100%;
            background: rgba(255,255,255,.2);
            pointer-events: none;
        }
        .progress-thumb {
            position: absolute;
            top: 50%;
            width: 13px; height: 13px;
            background: #fff;
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            pointer-events: none;
            transition: transform .1s;
            box-shadow: 0 0 0 2px rgba(255,255,255,.2);
        }
        .progress-wrap:hover .progress-thumb { transform: translate(-50%, -50%) scale(1); }

        /* Linha de botões */
        .controls-row {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .controls-left  { flex: 1; display: flex; align-items: center; gap: 2px; }
        .controls-right { display: flex; align-items: center; gap: 2px; }

        .ctrl-btn {
            background: none;
            border: none;
            color: var(--text-pure);
            cursor: pointer;
            padding: 8px;
            display: flex; align-items: center; justify-content: center;
            opacity: .85;
            transition: opacity var(--transition), transform var(--transition);
            -webkit-tap-highlight-color: transparent;
        }
        .ctrl-btn:hover { opacity: 1; }
        .ctrl-btn:active { transform: scale(.92); }
        .ctrl-btn svg { width: 20px; height: 20px; }
        .ctrl-btn.lg svg { width: 26px; height: 26px; }

        /* Volume */
        .volume-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .volume-slider {
            -webkit-appearance: none;
            width: 72px;
            height: 3px;
            background: rgba(255,255,255,.3);
            border-radius: 0;
            outline: none;
            cursor: pointer;
        }
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px; height: 12px;
            background: #fff;
            border-radius: 50%;
        }

        /* Tempo */
        .time-label {
            font-size: 12px;
            color: rgba(255,255,255,.7);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            letter-spacing: .02em;
            padding: 0 6px;
        }

        /* Audio badge */
        .audio-badge {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 3px 7px;
            border-radius: 2px;
            background: transparent;
            border: 1px solid rgba(255,255,255,.3);
            color: rgba(255,255,255,.6);
        }

        /* ─── BOTÃO PRÓXIMO EPISÓDIO ────���─────────────────────────────── */
        #btn-next-episode {
            display: none;
            position: absolute;
            bottom: 96px;
            right: 24px;
            z-index: 25;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.1);
            border: 1.5px solid rgba(255,255,255,.6);
            border-radius: var(--radius);
            padding: 10px 20px;
            color: var(--text-pure);
            cursor: pointer;
            backdrop-filter: blur(10px);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .02em;
            transition: background var(--transition), border-color var(--transition);
            animation: slideInRight .3s ease-out;
        }
        #btn-next-episode.visible { display: flex; }
        #btn-next-episode:hover {
            background: rgba(255,255,255,.2);
            border-color: #fff;
        }
        #btn-next-episode svg { width: 18px; height: 18px; }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(16px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ─── TOAST INFORMATIVO ────────────────────────────────────────── */
        #pip-toast {
            position: absolute;
            top: 72px;
            left: 50%;
            transform: translateX(-50%) translateY(-6px);
            background: rgba(0,0,0,.8);
            color: rgba(255,255,255,.9);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 2px;
            letter-spacing: .03em;
            opacity: 0;
            transition: opacity .18s, transform .18s;
            pointer-events: none;
            z-index: 30;
            white-space: nowrap;
        }
        #pip-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ─── SEÇÃO DE INFO (abaixo do player) ────────────────────────── */
        #player-info-section {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 28px 72px;
        }

        /* Linha divisória Netflix */
        .info-divider {
            height: 1px;
            background: var(--border);
            margin-bottom: 28px;
        }

        /* Audio toggle — acima do bloco de info */
        .audio-toggle-wrap {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
        }
        .audio-tab {
            padding: 5px 18px;
            border-radius: 2px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            cursor: pointer;
            border: 1.5px solid var(--border-strong);
            background: transparent;
            color: var(--text-muted);
            transition: all var(--transition);
        }
        .audio-tab.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .audio-tab:hover:not(.active) {
            border-color: rgba(255,255,255,.5);
            color: var(--text-primary);
        }

        /* Bloco de metadados */
        .info-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .info-poster {
            width: 78px;
            height: 117px;
            border-radius: 2px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .info-text { flex: 1; min-width: 0; }

        .info-series-name {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .info-main-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-pure);
            line-height: 1.2;
            margin-bottom: 5px;
            letter-spacing: -.015em;
        }

        .info-ep-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 14px;
            font-weight: 500;
        }

        .info-synopsis {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.65;
            max-width: 580px;
        }

        /* ─── MOBILE ──────────────────────────────────────────────────── */
        @media (max-width: 640px) {
            /* Navbar */
            #pplayer-nav { padding: 0 16px; height: 56px; }
            .nav-series-name { max-width: 200px; font-size: 13px; }
            .nav-ep-label { font-size: 11px; }
            .nav-divider { display: none; }

            /* Player ocupa 100% da largura, mantém 16:9 */
            #player-wrap { aspect-ratio: 16/9; }

            /* Controles mais tocáveis */
            #pip-controls { padding: 0 14px 16px; }
            .progress-wrap { margin-bottom: 12px; }
            .ctrl-btn { padding: 10px; }
            .ctrl-btn svg { width: 22px; height: 22px; }
            .ctrl-btn.lg svg { width: 28px; height: 28px; }
            .volume-wrap { display: none; } /* ocultar volume no mobile */
            .time-label { font-size: 11px; padding: 0 4px; }
            .audio-badge { font-size: 9px; padding: 2px 5px; }

            /* Botão próximo ep */
            #btn-next-episode { bottom: 80px; right: 14px; font-size: 13px; padding: 9px 16px; }

            /* Seção de info */
            #player-info-section { padding: 24px 16px 56px; }
            .info-divider { margin-bottom: 20px; }
            .info-main-title { font-size: 18px; }
            .info-ep-label { font-size: 12px; }
            .info-synopsis { font-size: 13px; }
            .info-poster { width: 64px; height: 96px; }
        }

        /* ─── FULLSCREEN / LANDSCAPE no mobile ───────────────────────── */
        @media (max-width: 640px) and (orientation: landscape) {
            #player-wrap {
                aspect-ratio: unset;
                height: 100svh;
            }
        }

        /* Ícone fullscreen — troca para sair */
        #icon-fs-exit { display: none; }
        #player-wrap:fullscreen #icon-fs-exit,
        #player-wrap:-webkit-full-screen #icon-fs-exit { display: block; }
        #player-wrap:fullscreen #icon-fs,
        #player-wrap:-webkit-full-screen #icon-fs { display: none; }
    </style>
</head>
<body>

<!-- ─── NAVBAR ──────────────────────────────────────────────────────────── -->
<nav id="pplayer-nav">
    <div class="nav-left">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="nav-back-btn" id="nav-back" aria-label="Voltar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/></svg>
            <span>Voltar</span>
        </a>
        <div class="nav-divider"></div>
        <div class="nav-title-block">
            <span class="nav-series-name"><?php echo htmlspecialchars($title); ?></span>
            <?php if ($isSerie): ?>
            <span class="nav-ep-label">T<?php echo $season; ?> &bull; Ep. <?php echo $episode; ?><?php if ($episodeName): ?> &mdash; <?php echo htmlspecialchars($episodeName); ?><?php endif; ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ─── PLAYER ──────────────────────────────────────────────────────────── -->
<main>
<div id="player-wrap">

    <!-- Loader -->
    <div id="pip-loader-overlay">
        <div class="loader-spinner"></div>
        <span class="loader-text">Carregando vídeo&hellip;</span>
    </div>

    <!-- Erro / sem link -->
    <div id="pip-error-overlay">
        <div class="error-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <div class="error-title" id="err-title">Vídeo indisponível</div>
        <div class="error-message" id="err-message">Este conteúdo ainda não possui link de vídeo disponível.</div>
        <div class="error-actions">
            <button class="btn-retry" id="btn-retry" onclick="loadVideo()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 9A8 8 0 0119.9 15M20 15a8 8 0 01-15.9-6"/></svg>
                Tentar novamente
            </button>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn-back-err">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Voltar
            </a>
        </div>
    </div>

    <!-- Elemento de vídeo -->
    <video id="pip-video" preload="metadata" playsinline></video>

    <!-- Toast feedback -->
    <div id="pip-toast"></div>

    <!-- Botão próximo episódio (séries) -->
    <?php if ($isSerie): ?>
    <a id="btn-next-episode" href="#">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        Próximo episódio
    </a>
    <?php endif; ?>

    <!-- Controles customizados -->
    <div id="pip-controls" class="hidden">
        <!-- Barra de progresso -->
        <div class="progress-wrap" id="progress-bar" role="slider" aria-label="Progresso do vídeo" tabindex="0">
            <div class="progress-buffer" id="progress-buffer" style="width:0%"></div>
            <div class="progress-fill"   id="progress-fill"  style="width:0%"></div>
            <div class="progress-thumb"  id="progress-thumb" style="left:0%"></div>
        </div>
        <!-- Linha de botões -->
        <div class="controls-row">
            <div class="controls-left">
                <!-- Play/Pause -->
                <button class="ctrl-btn lg" id="btn-play-pause" aria-label="Play/Pause" onclick="togglePlay()">
                    <svg id="icon-play" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg id="icon-pause" fill="currentColor" viewBox="0 0 24 24" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <!-- Rewind 10s -->
                <button class="ctrl-btn" aria-label="Voltar 10 segundos" onclick="skip(-10)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                </button>
                <!-- Forward 10s -->
                <button class="ctrl-btn" aria-label="Avançar 10 segundos" onclick="skip(10)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>
                </button>
                <!-- Volume -->
                <div class="volume-wrap">
                    <button class="ctrl-btn" id="btn-mute" aria-label="Mudo" onclick="toggleMute()">
                        <svg id="icon-vol" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-4.5-9.5L12 6v12l-4.5-3.5H4a1 1 0 01-1-1v-3a1 1 0 011-1h3.5z"/></svg>
                        <svg id="icon-mute" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                    </button>
                    <input type="range" class="volume-slider" id="volume-slider" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                </div>
                <!-- Tempo -->
                <span class="time-label" id="time-label">0:00 / 0:00</span>
            </div>
            <div class="controls-right">
                <!-- Badge áudio -->
                <span class="audio-badge" id="audio-badge-ctrl"><?php echo strtoupper($audio) === 'DUB' ? 'DUB' : 'LEG'; ?></span>
                <!-- PiP -->
                <button class="ctrl-btn" id="btn-pip" aria-label="Picture-in-Picture" onclick="togglePiP()" style="display:none">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9z"/><rect stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x="2" y="4" width="20" height="16" rx="2"/></svg>
                </button>
                <!-- Fullscreen -->
                <button class="ctrl-btn" id="btn-fs" aria-label="Tela cheia" onclick="toggleFullscreen()">
                    <svg id="icon-fs" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <svg id="icon-fs-exit" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4M9 9H4m5 0L3 3m12 6V4m0 5h5m-5 0l6-6M9 15v5m0-5H4m5 0l-6 6m12-6v5m0-5h5m-5 0l6 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ─── INFO ABAIXO DO PLAYER ──────────────────────────────────────────── -->
<section id="player-info-section">
    <div class="info-divider"></div>

    <?php if ($isSerie): ?>
    <div class="audio-toggle-wrap" id="audio-toggle-below"></div>
    <?php endif; ?>

    <div class="info-header">
        <?php if ($posterImg): ?>
        <img src="<?php echo htmlspecialchars($posterImg); ?>" class="info-poster" alt="Poster">
        <?php endif; ?>
        <div class="info-text">
            <?php if ($isSerie): ?>
            <div class="info-series-name"><?php echo htmlspecialchars($title); ?></div>
            <div class="info-main-title">
                <?php echo $episodeName ? htmlspecialchars($episodeName) : "Episódio {$episode}"; ?>
            </div>
            <div class="info-ep-label">Temporada <?php echo $season; ?> &bull; Episódio <?php echo $episode; ?></div>
            <?php else: ?>
            <div class="info-main-title"><?php echo htmlspecialchars($title); ?></div>
            <?php endif; ?>
            <?php if ($episodeSynopsis || $content['sinopse']): ?>
            <p class="info-synopsis"><?php echo htmlspecialchars($isSerie ? $episodeSynopsis : $content['sinopse']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
</main>

<script>
(function () {
    'use strict';

    // ─── Config ──────────────────────────────────────────────────────────
    const API_URL        = <?php echo json_encode($apiUrl); ?>;
    const IS_SERIE       = <?php echo $isSerie ? 'true' : 'false'; ?>;
    const TMDB_ID        = <?php echo $tmdbId; ?>;
    const CONTENT_SLUG   = <?php echo json_encode($slug); ?>;
    const CONTENT_TYPE   = <?php echo json_encode($contentType); ?>;
    const CONTENT_TITLE  = <?php echo json_encode($title); ?>;
    const CONTENT_POSTER = <?php echo json_encode($posterImg); ?>;
    const CONTENT_YEAR   = <?php echo json_encode((int) substr($content['data_lancamento'] ?? '0', 0, 4) ?: null); ?>;
    const CURRENT_S      = <?php echo $season; ?>;
    const CURRENT_E      = <?php echo $episode; ?>;
    let   AUDIO          = <?php echo json_encode($audio); ?>;

    // Progresso inicial: lê ?t= da URL (link "Continua Assistindo") ou usa a API
    const _urlT = parseInt(new URLSearchParams(window.location.search).get('t') || '0', 10);
    let RESUME_TIME = _urlT > 5 ? _urlT : 0;

    // ─── Elementos ───────────────────────────────────────────────────────
    const video          = document.getElementById('pip-video');
    const loaderOverlay  = document.getElementById('pip-loader-overlay');
    const errorOverlay   = document.getElementById('pip-error-overlay');
    const errTitle       = document.getElementById('err-title');
    const errMessage     = document.getElementById('err-message');
    const controls       = document.getElementById('pip-controls');
    const btnPlayPause   = document.getElementById('btn-play-pause');
    const iconPlay       = document.getElementById('icon-play');
    const iconPause      = document.getElementById('icon-pause');
    const progressBar    = document.getElementById('progress-bar');
    const progressFill   = document.getElementById('progress-fill');
    const progressBuffer = document.getElementById('progress-buffer');
    const progressThumb  = document.getElementById('progress-thumb');
    const volumeSlider   = document.getElementById('volume-slider');
    const iconVol        = document.getElementById('icon-vol');
    const iconMute       = document.getElementById('icon-mute');
    const timeLabel      = document.getElementById('time-label');
    const playerWrap     = document.getElementById('player-wrap');
    const nav            = document.getElementById('pplayer-nav');
    const btnNextEp      = document.getElementById('btn-next-episode');
    const toast          = document.getElementById('pip-toast');
    const audioBadge     = document.getElementById('audio-badge-ctrl');
    const btnPiP         = document.getElementById('btn-pip');
    const audioToggleBelow = document.getElementById('audio-toggle-below');

    let hls           = null;
    let nextEpData    = null;
    let controlsTimer = null;
    let isDragging    = false;
    let toastTimer    = null;
    let isSeeking     = false;

    // ─── Utilitários ─────────────────────────────────────────────────────
    function formatTime(sec) {
        sec = Math.floor(sec || 0);
        const h = Math.floor(sec / 3600);
        const m = Math.floor((sec % 3600) / 60);
        const s = sec % 60;
        if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        return `${m}:${String(s).padStart(2,'0')}`;
    }

    function showToast(msg) {
        clearTimeout(toastTimer);
        toast.textContent = msg;
        toast.classList.add('show');
        toastTimer = setTimeout(() => toast.classList.remove('show'), 1800);
    }

    // ─── Carregar vídeo via API ───────────────────────────────────────────
    function loadVideo() {
        // Reset estado
        errorOverlay.classList.remove('visible');
        loaderOverlay.classList.remove('hidden');
        controls.classList.add('hidden');
        if (hls) { hls.destroy(); hls = null; }
        video.src = '';

        const url = AUDIO === AUDIO ? API_URL.replace(/audio=[^&]+/, 'audio=' + AUDIO) : API_URL;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showError(data.error || 'Vídeo indisponível', data.message || '');
                    return;
                }
                // Atualiza badge de áudio
                if (data.audio) {
                    AUDIO = data.audio;
                    audioBadge.textContent = data.audio === 'dub' ? 'DUB' : 'LEG';
                    syncAudioTabs(data.audio);
                }
                // Próximo episódio
                if (IS_SERIE && data.next_episode) {
                    nextEpData = data.next_episode;
                    buildNextEpLink(nextEpData);
                }
                // Inicia player
                startPlayer(data.url, data.media_type);
            })
            .catch(() => showError('Erro de conexão', 'Não foi possível conectar ao servidor de vídeo.'));
    }

    function showError(title, message) {
        loaderOverlay.classList.add('hidden');
        errTitle.textContent   = title   || 'Vídeo indisponível';
        errMessage.textContent = message || '';
        errorOverlay.classList.add('visible');
    }

    function startPlayer(url, mediaType) {
        if (!url) { showError('URL inválida', 'O link de vídeo retornado é inválido.'); return; }

        const isHLS = mediaType === 'm3u8' || url.includes('.m3u8');

        if (isHLS) {
            if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                hls = new Hls({ enableWorker: false, maxBufferLength: 30 });
                hls.loadSource(url);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    loaderOverlay.classList.add('hidden');
                    controls.classList.remove('hidden');
                    if (RESUME_TIME > 5 && video.duration && RESUME_TIME < video.duration - 10) {
                        video.currentTime = RESUME_TIME;
                        RESUME_TIME = 0;
                    }
                    video.play().catch(() => {});
                });
                hls.on(Hls.Events.ERROR, (_e, d) => {
                    if (d.fatal) showError('Erro ao carregar stream', 'O vídeo HLS encontrou um erro fatal.');
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari nativo
                video.src = url;
                video.load();
                video.addEventListener('canplay', onReady, { once: true });
                video.addEventListener('error', onVideoError, { once: true });
            } else {
                showError('Formato não suportado', 'Seu navegador não suporta streams HLS.');
            }
        } else {
            // MP4/WebM: usa canplay — dispara assim que o buffer inicial está pronto
            // sem esperar loadedmetadata que pode travar em servidores HTTP/1.0
            video.src = url;
            video.load();
            // Timeout de segurança: se canplay não disparar em 8s, tenta loadedmetadata
            const fallbackTimer = setTimeout(() => {
                video.removeEventListener('canplay', onReady);
                if (video.readyState >= 1) {
                    onReady();
                } else {
                    video.addEventListener('loadedmetadata', onReady, { once: true });
                }
            }, 8000);
            video.addEventListener('canplay', () => {
                clearTimeout(fallbackTimer);
                onReady();
            }, { once: true });
            video.addEventListener('error', () => {
                clearTimeout(fallbackTimer);
                onVideoError();
            }, { once: true });
        }
    }

    function onReady() {
        loaderOverlay.classList.add('hidden');
        controls.classList.remove('hidden');
        // Restaura ponto exato onde o usuário parou
        if (RESUME_TIME > 5 && video.duration && RESUME_TIME < video.duration - 10) {
            video.currentTime = RESUME_TIME;
            RESUME_TIME = 0; // aplica apenas uma vez
        }
        video.play().catch(() => {});
    }

    function onVideoError() {
        showError('Erro ao carregar vídeo', 'Não foi possível reproduzir este arquivo de vídeo.');
    }

    // ─── Controles de vídeo ──────────────────────────────────────────────
    window.togglePlay = function () {
        if (video.paused) {
            video.play();
            showToast('Reproduzindo');
        } else {
            video.pause();
            showToast('Pausado');
        }
    };

    window.skip = function (sec) {
        video.currentTime = Math.max(0, Math.min(video.duration || 0, video.currentTime + sec));
        showToast(sec > 0 ? `+${sec}s` : `${sec}s`);
    };

    window.toggleMute = function () {
        video.muted = !video.muted;
        iconVol.style.display  = video.muted ? 'none'  : '';
        iconMute.style.display = video.muted ? ''      : 'none';
        volumeSlider.value = video.muted ? 0 : video.volume;
    };

    window.toggleFullscreen = function () {
        const isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) || window.innerWidth < 768;
        const isFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;

        if (!isFs) {
            const req = playerWrap.requestFullscreen || playerWrap.webkitRequestFullscreen;
            if (req) req.call(playerWrap);
            // No mobile, força landscape após entrar em fullscreen
            if (isMobile && screen.orientation?.lock) {
                screen.orientation.lock('landscape').catch(() => {});
            }
        } else {
            const exit = document.exitFullscreen || document.webkitExitFullscreen;
            if (exit) exit.call(document);
            // Libera a orientação ao sair
            if (isMobile && screen.orientation?.unlock) {
                screen.orientation.unlock();
            }
        }
    };

    // Sincroniza quando o usuário sai do fullscreen via Esc ou gesto do sistema
    document.addEventListener('fullscreenchange',       syncFsIcon);
    document.addEventListener('webkitfullscreenchange', syncFsIcon);
    function syncFsIcon() {
        const isFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
        document.getElementById('icon-fs').style.display      = isFs ? 'none' : '';
        document.getElementById('icon-fs-exit').style.display = isFs ? '' : 'none';
        // Libera orientação se saiu do fullscreen sem passar pelo botão
        if (!isFs && screen.orientation?.unlock) screen.orientation.unlock();
    }

    window.togglePiP = async function () {
        try {
            if (document.pictureInPictureElement) {
                await document.exitPictureInPicture();
            } else {
                await video.requestPictureInPicture();
            }
        } catch (e) {}
    };

    // Volume
    volumeSlider.addEventListener('input', () => {
        video.volume = parseFloat(volumeSlider.value);
        video.muted = video.volume === 0;
        iconVol.style.display  = video.muted ? 'none' : '';
        iconMute.style.display = video.muted ? ''     : 'none';
    });

    // Eventos do vídeo
    video.addEventListener('play',  () => { iconPlay.style.display = 'none'; iconPause.style.display = ''; });
    video.addEventListener('pause', () => { iconPlay.style.display = '';     iconPause.style.display = 'none'; });

    video.addEventListener('timeupdate', () => {
        if (isDragging || isSeeking) return;
        updateProgress();
        // Mostra botão próximo episódio quando faltam 60s
        if (IS_SERIE && nextEpData && video.duration > 0) {
            const remaining = video.duration - video.currentTime;
            if (remaining <= 60) showNextEpBtn();
        }
    });

    video.addEventListener('progress', () => {
        if (!video.duration) return;
        let buff = 0;
        for (let i = 0; i < video.buffered.length; i++) {
            if (video.buffered.start(i) <= video.currentTime) {
                buff = (video.buffered.end(i) / video.duration) * 100;
            }
        }
        progressBuffer.style.width = buff + '%';
    });

    video.addEventListener('waiting',  () => loaderOverlay.classList.remove('hidden'));
    video.addEventListener('playing',  () => loaderOverlay.classList.add('hidden'));
    video.addEventListener('ended',    () => {
        if (IS_SERIE && nextEpData) showNextEpBtn();
    });

    // PiP disponibilidade
    if ('pictureInPictureEnabled' in document && document.pictureInPictureEnabled) {
        if (btnPiP) btnPiP.style.display = '';
    }

    function updateProgress() {
        if (!video.duration) return;
        const pct = (video.currentTime / video.duration) * 100;
        progressFill.style.width  = pct + '%';
        progressThumb.style.left  = pct + '%';
        timeLabel.textContent = formatTime(video.currentTime) + ' / ' + formatTime(video.duration);
    }

    // ─── Progress bar drag ────────────────────────────────────────────────
    function seekTo(e) {
        const rect = progressBar.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
        const pct = Math.max(0, Math.min(1, x / rect.width));
        if (video.duration) {
            video.currentTime = pct * video.duration;
            progressFill.style.width = (pct * 100) + '%';
            progressThumb.style.left = (pct * 100) + '%';
        }
    }

    progressBar.addEventListener('mousedown',  (e) => { isDragging = true; seekTo(e); });
    progressBar.addEventListener('touchstart', (e) => { isDragging = true; seekTo(e); }, { passive: true });
    document.addEventListener('mousemove',  (e) => { if (isDragging) seekTo(e); });
    document.addEventListener('touchmove',  (e) => { if (isDragging) seekTo(e); }, { passive: true });
    document.addEventListener('mouseup',    ()  => { isDragging = false; });
    document.addEventListener('touchend',   ()  => { isDragging = false; });

    // ─── Auto-hide controles ──────────────────────────────────────────────
    function showControls() {
        controls.classList.remove('hidden');
        nav.classList.remove('hidden');
        playerWrap.classList.add('cursor-visible');
        clearTimeout(controlsTimer);
        if (!video.paused) {
            controlsTimer = setTimeout(hideControls, 3000);
        }
    }

    function hideControls() {
        if (video.paused) return;
        controls.classList.add('hidden');
        nav.classList.add('hidden');
        playerWrap.classList.remove('cursor-visible');
    }

    playerWrap.addEventListener('mousemove',  showControls);
    video.addEventListener('pause', showControls);

    // Toque único: mostra/oculta controles. Toque duplo: avança/volta 10s (Netflix)
    let tapCount = 0;
    let tapTimer  = null;
    playerWrap.addEventListener('touchstart', (e) => {
        showControls();
        tapCount++;
        clearTimeout(tapTimer);
        tapTimer = setTimeout(() => {
            if (tapCount === 1) {
                // toque único — apenas mostra controles (já feito acima)
            } else if (tapCount >= 2) {
                const x = e.changedTouches[0].clientX;
                const mid = playerWrap.getBoundingClientRect().width / 2;
                skip(x > mid ? 10 : -10);
            }
            tapCount = 0;
        }, 280);
    }, { passive: true });

    // Click no desktop
    playerWrap.addEventListener('click', (e) => {
        if (e.target === video || e.target === playerWrap) togglePlay();
    });

    // ─── Teclas de atalho ─────────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
        switch (e.code) {
            case 'Space': case 'KeyK': e.preventDefault(); togglePlay(); break;
            case 'ArrowRight': e.preventDefault(); skip(10);  break;
            case 'ArrowLeft':  e.preventDefault(); skip(-10); break;
            case 'ArrowUp':    e.preventDefault(); video.volume = Math.min(1, video.volume + .1); volumeSlider.value = video.volume; break;
            case 'ArrowDown':  e.preventDefault(); video.volume = Math.max(0, video.volume - .1); volumeSlider.value = video.volume; break;
            case 'KeyM':  e.preventDefault(); toggleMute(); break;
            case 'KeyF':  e.preventDefault(); toggleFullscreen(); break;
        }
    });

    // ─── Próximo episódio ─────────────────────────────────────────────────
    function buildNextEpLink(ep) {
        if (!btnNextEp) return;
        // Usa o mesmo padrão do cineveo: /assistir/{slug}-t{s}-ep{e}.html
        const base = CONTENT_SLUG
            ? `/assistir/${CONTENT_SLUG}-t${ep.temporada}-ep${ep.episodio}.html`
            : `/assistir/serie/${TMDB_ID}/${ep.temporada}/${ep.episodio}`;
        btnNextEp.href = base + `?audio=${AUDIO}`;
    }

    function showNextEpBtn() {
        if (!btnNextEp || !nextEpData) return;
        btnNextEp.classList.add('visible');
    }

    // ─── Audio toggle (seção abaixo) ──────────────────────────────────────
    function buildAudioTabs(hasDub, hasLeg) {
        if (!audioToggleBelow) return;
        audioToggleBelow.innerHTML = '';
        if (hasDub) {
            const btn = document.createElement('button');
            btn.className = 'audio-tab' + (AUDIO === 'dub' ? ' active' : '');
            btn.dataset.audio = 'dub';
            btn.textContent = 'Dublado';
            btn.onclick = () => switchAudio('dub');
            audioToggleBelow.appendChild(btn);
        }
        if (hasLeg) {
            const btn = document.createElement('button');
            btn.className = 'audio-tab' + (AUDIO === 'leg' ? ' active' : '');
            btn.dataset.audio = 'leg';
            btn.textContent = 'Legendado';
            btn.onclick = () => switchAudio('leg');
            audioToggleBelow.appendChild(btn);
        }
    }

    function syncAudioTabs(audio) {
        document.querySelectorAll('.audio-tab').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.audio === audio);
        });
    }

    window.switchAudio = function (newAudio) {
        if (newAudio === AUDIO) return;
        const pos = video.currentTime;
        AUDIO = newAudio;
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('audio', newAudio);
        history.replaceState(null, '', newUrl.toString());
        audioBadge.textContent = newAudio === 'dub' ? 'DUB' : 'LEG';
        syncAudioTabs(newAudio);
        // Recarrega o vídeo no tempo atual
        loadVideoWithCallback(pos);
    };

    function loadVideoWithCallback(resumeAt) {
        errorOverlay.classList.remove('visible');
        loaderOverlay.classList.remove('hidden');
        controls.classList.add('hidden');
        if (hls) { hls.destroy(); hls = null; }
        video.src = '';

        const url = API_URL.replace(/audio=[^&]+/, 'audio=' + AUDIO);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showError(data.error, data.message); return; }
                if (data.audio) { AUDIO = data.audio; audioBadge.textContent = data.audio === 'dub' ? 'DUB' : 'LEG'; }
                if (IS_SERIE && data.next_episode) { nextEpData = data.next_episode; buildNextEpLink(nextEpData); }
                startPlayerAt(data.url, data.media_type, resumeAt);
            })
            .catch(() => showError('Erro de conexão', ''));
    }

    function startPlayerAt(url, mediaType, resumeAt) {
        const isHLS = mediaType === 'm3u8' || url.includes('.m3u8');
        if (isHLS && typeof Hls !== 'undefined' && Hls.isSupported()) {
            hls = new Hls({ enableWorker: false, maxBufferLength: 30 });
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                if (resumeAt) video.currentTime = resumeAt;
                loaderOverlay.classList.add('hidden');
                controls.classList.remove('hidden');
                video.play().catch(() => {});
            });
        } else {
            video.src = url;
            video.load();
            const onReadyAt = () => {
                if (resumeAt) video.currentTime = resumeAt;
                loaderOverlay.classList.add('hidden');
                controls.classList.remove('hidden');
                video.play().catch(() => {});
            };
            const fallbackAt = setTimeout(() => {
                video.removeEventListener('canplay', onReadyAt);
                if (video.readyState >= 1) { onReadyAt(); }
                else { video.addEventListener('loadedmetadata', onReadyAt, { once: true }); }
            }, 8000);
            video.addEventListener('canplay', () => { clearTimeout(fallbackAt); onReadyAt(); }, { once: true });
        }
    }

    // ─── Verificação de disponibilidade de áudio (para mostrar tabs) ─────
    // Executado apenas UMA vez, após o vídeo começar a reproduzir, para não
    // competir com o carregamento inicial do vídeo.
    let audioCheckDone = false;
    function checkAvailableAudio() {
        if (audioCheckDone) return;
        audioCheckDone = true;
        const baseParams = `id=${TMDB_ID}&type=${CONTENT_TYPE}&s=${CURRENT_S}&e=${CURRENT_E}`;
        Promise.all([
            fetch(`/api/v2/episode-url?${baseParams}&audio=dub`).then(r => r.json()).catch(() => ({ success: false })),
            fetch(`/api/v2/episode-url?${baseParams}&audio=leg`).then(r => r.json()).catch(() => ({ success: false })),
        ]).then(([dub, leg]) => {
            if (IS_SERIE) buildAudioTabs(dub.success, leg.success);
        });
    }

    // Dispara a verificação de áudio apenas após o vídeo começar (não concorre com o carregamento)
    video.addEventListener('playing', () => { setTimeout(checkAvailableAudio, 2000); }, { once: true });

    // ─── WatchProgress ───────────────────────────────────────────────────
    // Salva o progresso periodicamente e ao fechar/trocar de página.
    // Restaura o ponto exato em que o usuário parou ao abrir o player.
    const WatchProgress = {
        _saveInterval: null,
        _lastSaved: -1,

        _payload() {
            return {
                content_id:     TMDB_ID,
                content_type:   IS_SERIE ? 'serie' : 'filme',
                season:         CURRENT_S,
                episode:        CURRENT_E,
                progress_time:  Math.floor(video.currentTime || 0),
                duration:       Math.floor(video.duration    || 0),
                content_title:  CONTENT_TITLE,
                content_poster: CONTENT_POSTER,
                content_year:   CONTENT_YEAR,
                audio:          AUDIO,
            };
        },

        save(force = false) {
            const t = Math.floor(video.currentTime || 0);
            // Só salva se moveu pelo menos 5s desde o último save
            if (!force && Math.abs(t - this._lastSaved) < 5) return;
            if (t < 5) return; // ignora os primeiros 5s
            this._lastSaved = t;
            const body = JSON.stringify(this._payload());
            // Usa sendBeacon quando disponível (garante envio mesmo ao fechar)
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: 'application/json' });
                navigator.sendBeacon('/api/v3/watch-progress/save', blob);
            } else {
                fetch('/api/v3/watch-progress/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body,
                    keepalive: true,
                }).catch(() => {});
            }
        },

        startInterval() {
            this.stopInterval();
            this._saveInterval = setInterval(() => this.save(), 15000);
        },

        stopInterval() {
            if (this._saveInterval) {
                clearInterval(this._saveInterval);
                this._saveInterval = null;
            }
        },

        async init() {
            // Busca o progresso existente para retomar
            try {
                const params = new URLSearchParams({
                    content_id:   TMDB_ID,
                    content_type: IS_SERIE ? 'serie' : 'filme',
                    season:       CURRENT_S,
                    episode:      CURRENT_E,
                });
                const res  = await fetch('/api/v3/watch-progress/get?' + params);
                const json = await res.json();
                if (json.sucesso && json.dados && json.dados.progress_time > 5) {
                    RESUME_TIME = parseFloat(json.dados.progress_time);
                }
            } catch (_) {}
        },
    };

    // Salva ao sair da página (fechou aba, navegou para outro lugar)
    window.addEventListener('beforeunload',   () => WatchProgress.save(true));
    window.addEventListener('pagehide',       () => WatchProgress.save(true));
    // Pausa/retoma intervalo junto com o vídeo
    video.addEventListener('play',  () => WatchProgress.startInterval());
    video.addEventListener('pause', () => { WatchProgress.save(true); WatchProgress.stopInterval(); });
    video.addEventListener('ended', () => { WatchProgress.save(true); WatchProgress.stopInterval(); });

    // ─── Init ─────────────────────────────────────────────────────────────
    // Primeiro busca o progresso, depois carrega o vídeo para poder retomar
    WatchProgress.init().then(() => loadVideo());

})();
</script>
</body>
</html>
