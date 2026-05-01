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
            --accent-hover:  #f40612;
            --yellow:        #ffd60a;
            --bg:            #000000;
            --surface:       #141414;
            --surface2:      #1f1f1f;
            --surface3:      #2a2a2a;
            --text-pure:     #ffffff;
            --text-primary:  #e2e8f0;
            --text-secondary:#94a3b8;
            --text-muted:    #64748b;
            --border:        #2a2a2a;
            --border-strong: #3d3d3d;
            --radius:        6px;
            --radius-lg:     10px;
            --shadow-lg:     0 12px 40px rgba(0,0,0,.85);
            --transition:    .2s ease;
        }

        html, body {
            width: 100%; height: 100%;
            background: var(--bg);
            color: var(--text-primary);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── NAVBAR ──────────────────────────────────────────────────── */
        #pplayer-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 200;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            background: linear-gradient(to bottom, rgba(0,0,0,.9) 0%, transparent 100%);
            pointer-events: none;
            transition: opacity .3s;
        }
        #pplayer-nav.hidden { opacity: 0; }
        #pplayer-nav > * { pointer-events: auto; }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            background: rgba(0,0,0,.4);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 20px;
            padding: 6px 14px;
            backdrop-filter: blur(8px);
            transition: background var(--transition), color var(--transition);
        }
        .nav-back-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
        .nav-back-btn:hover { background: rgba(255,255,255,.15); color: var(--text-pure); }

        .nav-title-block {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .nav-series-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-pure);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        .nav-ep-label {
            font-size: 11px;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        /* ─── WRAP DO PLAYER ──────────────────────────────────────────── */
        #player-wrap {
            position: relative;
            width: 100%;
            background: #000;
            aspect-ratio: 16/9;
            max-height: 100vh;
            overflow: hidden;
            cursor: none;
        }
        #player-wrap.cursor-visible { cursor: default; }

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
            background: rgba(0,0,0,.75);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 10;
            transition: opacity .3s;
        }
        #pip-loader-overlay.hidden { opacity: 0; pointer-events: none; }

        .loader-spinner {
            width: 48px; height: 48px;
            border: 3px solid rgba(255,255,255,.15);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .loader-text {
            font-size: 13px;
            color: var(--text-secondary);
            letter-spacing: .03em;
        }

        /* ─── ERRO OVERLAY ────────────────────────────────────────────── */
        #pip-error-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.88);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 15;
            padding: 32px;
            text-align: center;
        }
        #pip-error-overlay.visible { display: flex; }

        .error-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: rgba(229,9,20,.12);
            border: 1px solid rgba(229,9,20,.3);
            display: flex; align-items: center; justify-content: center;
        }
        .error-icon svg { width: 28px; height: 28px; color: var(--accent); }
        .error-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-pure);
        }
        .error-message {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 420px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 8px;
        }
        .btn-retry {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            padding: 10px 20px;
            cursor: pointer;
            transition: background var(--transition);
        }
        .btn-retry:hover { background: var(--accent-hover); }
        .btn-back-err {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: var(--radius);
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition);
        }
        .btn-back-err:hover { background: rgba(255,255,255,.18); }

        /* ─── CONTROLES CUSTOMIZADOS ──────────────────────────────────── */
        #pip-controls {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            z-index: 20;
            padding: 0 20px 16px;
            background: linear-gradient(to top, rgba(0,0,0,.85) 0%, transparent 100%);
            transition: opacity .3s, transform .3s;
            transform: translateY(0);
        }
        #pip-controls.hidden {
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
        }

        /* Progress bar */
        .progress-wrap {
            position: relative;
            height: 4px;
            background: rgba(255,255,255,.2);
            border-radius: 2px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: height .15s;
        }
        .progress-wrap:hover { height: 6px; }
        .progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 2px;
            pointer-events: none;
            transition: width .1s linear;
        }
        .progress-buffer {
            position: absolute;
            top: 0; left: 0;
            height: 100%;
            background: rgba(255,255,255,.25);
            border-radius: 2px;
            pointer-events: none;
        }
        .progress-thumb {
            position: absolute;
            top: 50%;
            width: 12px; height: 12px;
            background: var(--accent);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            pointer-events: none;
            transition: transform .15s;
        }
        .progress-wrap:hover .progress-thumb { transform: translate(-50%, -50%) scale(1); }

        /* Botões de controle */
        .controls-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .controls-left  { flex: 1; display: flex; align-items: center; gap: 8px; }
        .controls-right { display: flex; align-items: center; gap: 8px; }

        .ctrl-btn {
            background: none;
            border: none;
            color: var(--text-pure);
            cursor: pointer;
            padding: 6px;
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            opacity: .85;
            transition: opacity var(--transition), background var(--transition);
        }
        .ctrl-btn:hover { opacity: 1; background: rgba(255,255,255,.1); }
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
            width: 70px;
            height: 3px;
            background: rgba(255,255,255,.3);
            border-radius: 2px;
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
            color: var(--text-secondary);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            letter-spacing: .02em;
        }

        /* Audio badge */
        .audio-badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 3px 7px;
            border-radius: 4px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            color: var(--text-secondary);
        }

        /* ─── BOTÃO PRÓXIMO EPISÓDIO ──────────────────────────────────── */
        #btn-next-episode {
            display: none;
            position: absolute;
            bottom: 90px;
            right: 24px;
            z-index: 25;
            align-items: center;
            gap: 10px;
            background: rgba(20,20,20,.92);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: var(--radius-lg);
            padding: 14px 20px;
            color: var(--text-pure);
            cursor: pointer;
            backdrop-filter: blur(12px);
            transition: background var(--transition), transform var(--transition), opacity .3s;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            animation: slideInRight .35s ease-out;
        }
        #btn-next-episode.visible { display: flex; }
        #btn-next-episode:hover {
            background: rgba(229,9,20,.85);
            transform: translateY(-2px);
        }
        #btn-next-episode svg { width: 18px; height: 18px; }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ─── TOAST INFORMATIVO ────────────────────────────────────────── */
        #pip-toast {
            position: absolute;
            top: 70px;
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
            background: rgba(0,0,0,.8);
            border: 1px solid rgba(255,255,255,.12);
            color: var(--text-pure);
            font-size: 13px;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity .25s, transform .25s;
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
            padding: 32px 24px 64px;
        }

        .info-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 28px;
        }

        .info-poster {
            width: 80px;
            height: 120px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }

        .info-text { flex: 1; min-width: 0; }

        .info-series-name {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .info-main-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-pure);
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .info-ep-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .info-synopsis {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.65;
            max-width: 680px;
        }

        /* Audio toggle */
        .audio-toggle-wrap {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
        }
        .audio-tab {
            padding: 7px 18px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            border: 1px solid var(--border-strong);
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
            border-color: rgba(255,255,255,.3);
            color: var(--text-primary);
        }

        @media (max-width: 600px) {
            #pplayer-nav { padding: 0 14px; }
            .nav-series-name { max-width: 180px; }
            #player-info-section { padding: 20px 16px 48px; }
            .info-main-title { font-size: 18px; }
            #btn-next-episode { bottom: 80px; right: 12px; font-size: 13px; padding: 12px 16px; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ──────────────────────────────────────────────────────────── -->
<nav id="pplayer-nav">
    <div class="nav-left">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="nav-back-btn" id="nav-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Voltar
        </a>
        <div class="nav-title-block">
            <span class="nav-series-name"><?php echo htmlspecialchars($title); ?></span>
            <?php if ($isSerie): ?>
            <span class="nav-ep-label">
                Temporada <?php echo $season; ?> &mdash; Episódio <?php echo $episode; ?>
                <?php if ($episodeName): ?>&mdash; <?php echo htmlspecialchars($episodeName); ?><?php endif; ?>
            </span>
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
                    <svg id="icon-fs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ─── INFO ABAIXO DO PLAYER ──────────────────────────────────────────── -->
<section id="player-info-section">
    <?php if ($isSerie): ?>
    <!-- Audio toggle (duplicado para facilitar acesso) -->
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
    const API_URL      = <?php echo json_encode($apiUrl); ?>;
    const IS_SERIE     = <?php echo $isSerie ? 'true' : 'false'; ?>;
    const TMDB_ID      = <?php echo $tmdbId; ?>;
    const CONTENT_SLUG = <?php echo json_encode($slug); ?>;
    const CONTENT_TYPE = <?php echo json_encode($contentType); ?>;
    const CURRENT_S    = <?php echo $season; ?>;
    const CURRENT_E    = <?php echo $episode; ?>;
    let   AUDIO        = <?php echo json_encode($audio); ?>;

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
        if (!document.fullscreenElement) {
            playerWrap.requestFullscreen?.() || playerWrap.webkitRequestFullscreen?.();
        } else {
            document.exitFullscreen?.() || document.webkitExitFullscreen?.();
        }
    };

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
    playerWrap.addEventListener('touchstart', showControls, { passive: true });
    playerWrap.addEventListener('click', (e) => {
        if (e.target === video) togglePlay();
    });
    video.addEventListener('pause', showControls);

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

    // ─── Init ─────────────────────────────────────────────────────────────
    loadVideo();

})();
</script>
</body>
</html>
