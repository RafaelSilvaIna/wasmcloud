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
require_once __DIR__ . '/../helpers/player/PlayerPlanHelper.php';
require_once __DIR__ . '/../helpers/player/PlayerCastRegistry.php';
require_once __DIR__ . '/../helpers/player/PlayerFeatureRegistry.php';

// ─── Parâmetros ───────────────────────────────────────────────────────────────
$contentType = strtolower(trim($_GET['type'] ?? 'filme'));
$tmdbId      = (int) ($_GET['id'] ?? 0);
$season      = (int) ($_GET['s']  ?? 1);
$episode     = (int) ($_GET['e']  ?? 1);
$audio       = strtolower(trim($_GET['audio'] ?? 'dub'));

$isSerie = in_array($contentType, ['serie', 'series', 'tv'], true);

if ($tmdbId <= 0) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $lastPlayer = $_SESSION['last_player_request'] ?? null;
    $lastPlayerAge = time() - (int) ($lastPlayer['ts'] ?? 0);

    if ($requestPath === '/player' && is_array($lastPlayer) && $lastPlayerAge >= 0 && $lastPlayerAge <= 86400) {
        $contentType = strtolower(trim((string) ($lastPlayer['type'] ?? 'filme')));
        $tmdbId = (int) ($lastPlayer['id'] ?? 0);
        $season = max(1, (int) ($lastPlayer['s'] ?? 1));
        $episode = max(1, (int) ($lastPlayer['e'] ?? 1));
        $audio = strtolower(trim((string) ($lastPlayer['audio'] ?? 'dub')));
        $isSerie = in_array($contentType, ['serie', 'series', 'tv'], true);
    }
}

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

$_SESSION['last_player_request'] = [
    'id' => $tmdbId,
    'type' => $isSerie ? 'serie' : 'filme',
    's' => max(1, $season),
    'e' => max(1, $episode),
    'audio' => in_array($audio, ['dub', 'leg'], true) ? $audio : 'dub',
    'ts' => time(),
];

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

// Link de voltar: sempre retorna para a view canonica do conteudo.
$backType = $isSerie ? 'serie' : 'filme';
$backUrl = "/view?id={$tmdbId}&type={$backType}";

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$embeddedBrowserPattern = '/instagram|fb_iab|fbav|fban|messenger|telegram|twitter|line\/|micromessenger|tiktok|snapchat|pinterest|linkedinapp|; wv| wv\)/i';
$isEmbeddedBrowser = (bool) preg_match($embeddedBrowserPattern, $userAgent);

$hasPremiumFillAccess = \Helpers\Player\PlayerPlanHelper::hasProAccess($pdoPipocine ?? null, (int) ($_SESSION['user_id'] ?? 0));
$playerFeatures = \Helpers\Player\PlayerFeatureRegistry::build($hasPremiumFillAccess);

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
            min-height: 100%;
            background: var(--bg);
            color: var(--text-primary);
            font-family: 'Netflix Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        main {
            width: 100%;
            height: 100svh;
            min-height: 100vh;
            background:
                radial-gradient(circle at 18% 0%, rgba(229,9,20,.16), transparent 34%),
                linear-gradient(135deg, #030303 0%, #0b0b0d 45%, #050505 100%);
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
            background: linear-gradient(to bottom, rgba(0,0,0,.86) 0%, rgba(0,0,0,.35) 58%, transparent 100%);
            pointer-events: none;
            transition: opacity .3s;
        }
        #pplayer-nav.hidden { opacity: 0; }
        #pplayer-nav > * { pointer-events: auto; }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
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

        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            min-width: 132px;
        }

        .display-menu {
            position: relative;
        }

        .display-menu-btn {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 4px;
            padding: 0 12px;
            background: rgba(12,12,14,.62);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .display-menu-btn svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        .display-menu-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 250px;
            display: none;
            padding: 8px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 6px;
            background: rgba(9,9,11,.94);
            box-shadow: 0 18px 48px rgba(0,0,0,.46);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .display-menu.open .display-menu-panel { display: block; }

        .display-option {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 4px;
            padding: 10px;
            background: transparent;
            color: rgba(255,255,255,.72);
            text-align: left;
            cursor: pointer;
        }

        .display-option:hover,
        .display-option.active {
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        .display-option.locked {
            cursor: not-allowed;
            opacity: .72;
        }

        .cast-launcher-shell {
            position: relative;
        }

        .native-cast-launcher {
            position: absolute;
            inset: 0;
            z-index: 2;
            width: 100%;
            height: 100%;
            border: 0;
            opacity: .01;
            pointer-events: none;
            cursor: pointer;
            --connected-color: #fff;
            --disconnected-color: #fff;
        }

        .cast-launcher-ready .native-cast-launcher {
            pointer-events: auto;
        }

        .display-option-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 4px;
            background: rgba(255,255,255,.08);
        }

        .display-option-icon svg {
            width: 18px;
            height: 18px;
        }

        .display-option-text {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .display-option-text strong {
            font-size: 12px;
            line-height: 1.2;
            color: inherit;
        }

        .display-option-text span {
            font-size: 11px;
            line-height: 1.35;
            color: rgba(255,255,255,.52);
        }

        .settings-menu-panel {
            width: 330px;
            max-height: min(72vh, 560px);
            overflow: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .settings-menu-panel::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .cast-status {
            min-height: 28px;
            padding: 8px 10px 2px 54px;
            color: rgba(255,255,255,.58);
            font-size: 11px;
            line-height: 1.35;
        }

        .cast-status strong {
            color: #fff;
            font-weight: 900;
        }

        .settings-section {
            padding: 6px 0;
        }

        .settings-section + .settings-section {
            border-top: 1px solid rgba(255,255,255,.1);
        }

        .settings-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 10px 5px;
            color: rgba(255,255,255,.9);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .settings-hint {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,.45);
            text-transform: none;
        }

        .settings-option-badge {
            margin-left: auto;
            padding: 3px 6px;
            border-radius: 3px;
            border: 1px solid rgba(255,255,255,.16);
            color: rgba(255,255,255,.58);
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .display-option.active .settings-option-badge {
            border-color: rgba(229,9,20,.5);
            color: #fff;
            background: rgba(229,9,20,.28);
        }

        .display-option.locked .settings-option-badge {
            border-color: rgba(255,255,255,.1);
            color: rgba(255,255,255,.42);
        }

        /* ─── WRAP DO PLAYER ──────────────────────────────────────────── */
        #player-wrap {
            position: relative;
            width: 100%;
            height: 100svh;
            min-height: 100vh;
            background: #000;
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
            object-position: center center;
            background: #000;
        }

        #player-wrap.fit-cover #pip-video {
            object-fit: cover;
        }

        #player-wrap::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(to bottom, rgba(0,0,0,.34) 0%, transparent 20%, transparent 66%, rgba(0,0,0,.72) 100%),
                linear-gradient(to right, rgba(0,0,0,.2), transparent 24%, transparent 76%, rgba(0,0,0,.2));
            z-index: 1;
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
            max-width: 430px;
            line-height: 1.6;
        }
        .error-help-link {
            color: rgba(255,255,255,.54);
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.2);
            transition: color var(--transition), border-color var(--transition);
        }
        .error-help-link:hover {
            color: #fff;
            border-color: rgba(229,9,20,.72);
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
            padding: 0 24px calc(20px + env(safe-area-inset-bottom, 0px));
            background: linear-gradient(to top,
                rgba(0,0,0,.95) 0%,
                rgba(0,0,0,.5) 50%,
                transparent 100%);
            transition: opacity .25s;
            touch-action: manipulation;
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

        .audio-switcher {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 2px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 999px;
            background: rgba(10,10,12,.42);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .audio-switcher .audio-tab {
            min-width: 46px;
            padding: 6px 10px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            cursor: pointer;
            color: rgba(255,255,255,.62);
            transition: background var(--transition), color var(--transition);
        }

        .audio-switcher .audio-tab.active {
            background: rgba(255,255,255,.95);
            color: #08080a;
        }

        .audio-switcher .audio-tab:hover:not(.active) {
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        #browser-block-overlay {
            position: absolute;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 28px;
            background-color: #050505;
            background-image:
                linear-gradient(135deg, rgba(4,4,6,.96), rgba(14,14,18,.9))
                <?php if ($backdropImg): ?>, url('<?php echo htmlspecialchars($backdropImg, ENT_QUOTES); ?>')<?php endif; ?>;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            text-align: center;
        }

        #browser-block-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.72);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        body.embedded-browser #browser-block-overlay { display: flex; }
        body.embedded-browser #pplayer-nav,
        body.embedded-browser #pip-loader-overlay,
        body.embedded-browser #pip-controls,
        body.embedded-browser #btn-next-episode,
        body.embedded-browser #pip-video {
            display: none !important;
        }

        .browser-block-card {
            position: relative;
            z-index: 1;
            width: min(100%, 460px);
            padding: 30px;
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 8px;
            background: rgba(13,13,16,.78);
            box-shadow: 0 24px 80px rgba(0,0,0,.55);
        }

        .browser-block-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(229,9,20,.14);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(229,9,20,.28);
        }

        .browser-block-icon svg { width: 28px; height: 28px; }

        .browser-block-title {
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.18;
            margin-bottom: 10px;
        }

        .browser-block-text {
            color: rgba(255,255,255,.72);
            font-size: 14px;
            line-height: 1.62;
            margin-bottom: 22px;
        }

        .browser-block-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .browser-action-primary,
        .browser-action-secondary {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 4px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .browser-action-primary {
            border: 0;
            background: #fff;
            color: #070707;
        }

        .browser-action-secondary {
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.08);
            color: #fff;
        }

        /* ─── MOBILE ──────────────────────────────────────────────────── */
        @media (max-width: 640px) {
            /* Navbar */
            #pplayer-nav {
                height: 58px;
                gap: 8px;
                padding: 0 max(12px, env(safe-area-inset-right, 0px)) 0 max(12px, env(safe-area-inset-left, 0px));
            }
            .nav-left { gap: 10px; flex: 1; }
            .nav-series-name { max-width: 150px; font-size: 13px; }
            .nav-ep-label { font-size: 11px; }
            .nav-divider { display: none; }
            .nav-right { min-width: auto; }
            .display-menu-btn { min-width: 38px; padding: 0 10px; }
            .display-menu-btn span { display: none; }
            .display-menu-panel {
                width: min(250px, calc(100vw - 24px));
                right: 0;
            }
            .settings-menu-panel {
                width: min(330px, calc(100vw - 24px));
                max-height: calc(100dvh - 76px);
            }
            .cast-menu-panel {
                width: min(330px, calc(100vw - 24px));
                max-height: calc(100dvh - 76px);
            }

            main,
            #player-wrap {
                height: 100dvh;
                min-height: 100dvh;
            }

            /* Controles mais tocáveis */
            #pip-controls {
                z-index: 60;
                padding: 0 max(14px, env(safe-area-inset-right, 0px)) calc(18px + env(safe-area-inset-bottom, 0px)) max(14px, env(safe-area-inset-left, 0px));
            }
            .controls-row { gap: 0; min-width: 0; }
            .controls-left,
            .controls-right { gap: 0; min-width: 0; }
            .progress-wrap {
                height: 5px;
                margin-bottom: 12px;
            }
            .progress-thumb {
                width: 15px;
                height: 15px;
                transform: translate(-50%, -50%) scale(1);
            }
            .ctrl-btn { padding: 9px; min-width: 38px; min-height: 38px; }
            .ctrl-btn svg { width: 22px; height: 22px; }
            .ctrl-btn.lg svg { width: 28px; height: 28px; }
            .volume-wrap { display: none; } /* ocultar volume no mobile */
            .time-label { font-size: 11px; padding: 0 4px; }
            .audio-badge { font-size: 9px; padding: 2px 5px; }
            .audio-switcher { gap: 2px; }
            .audio-switcher .audio-tab { min-width: 38px; padding: 6px 8px; }
            .browser-block-card { padding: 24px 18px; }
            .browser-block-title { font-size: 19px; }

            /* Botão próximo ep */
            #btn-next-episode { bottom: 80px; right: 14px; font-size: 13px; padding: 9px 16px; }
        }

        @media (pointer: coarse) {
            main,
            #player-wrap {
                height: 100dvh;
                min-height: 100dvh;
            }

            #pip-controls {
                z-index: 60;
                padding: 0 max(14px, env(safe-area-inset-right, 0px)) calc(18px + env(safe-area-inset-bottom, 0px)) max(14px, env(safe-area-inset-left, 0px));
            }

            .progress-wrap {
                height: 5px;
                margin-bottom: 12px;
            }

            .progress-thumb {
                width: 15px;
                height: 15px;
                transform: translate(-50%, -50%) scale(1);
            }

            .ctrl-btn {
                min-width: 38px;
                min-height: 38px;
                padding: 9px;
            }

            .volume-wrap { display: none; }
        }

        /* ─── FULLSCREEN / LANDSCAPE no mobile ───────────────────────── */
        @media (max-width: 640px) and (orientation: landscape) {
            #pplayer-nav { height: 50px; }
            .nav-series-name { max-width: 220px; }
            #player-wrap {
                aspect-ratio: unset;
                height: 100dvh;
                min-height: 100dvh;
            }
            #pip-controls {
                padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            }
        }

        @media (pointer: coarse) and (orientation: landscape) {
            #pplayer-nav { height: 50px; }
            .nav-series-name { max-width: 220px; }
            #pip-controls {
                padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            }
            .display-menu-panel,
            .settings-menu-panel,
            .cast-menu-panel {
                max-height: calc(100dvh - 66px);
                overflow: auto;
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
<body class="<?php echo $isEmbeddedBrowser ? 'embedded-browser' : ''; ?>">

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
    <div class="nav-right">
        <div class="display-menu" id="display-menu">
            <button class="display-menu-btn" id="display-menu-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Modo de tela">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8v6H8z"/></svg>
                <span>Tela</span>
            </button>
            <div class="display-menu-panel" id="display-menu-panel" role="menu" aria-label="Modo de exibicao">
                <button class="display-option active" type="button" data-fit-mode="contain" role="menuitemradio" aria-checked="true">
                    <span class="display-option-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="4" y="6" width="16" height="12" rx="2"/><path stroke-linecap="round" d="M8 12h8"/></svg>
                    </span>
                    <span class="display-option-text">
                        <strong>Original</strong>
                        <span>Mostra o video inteiro.</span>
                    </span>
                </button>
                <button class="display-option<?php echo $hasPremiumFillAccess ? '' : ' locked'; ?>" type="button" data-fit-mode="cover" role="menuitemradio" aria-checked="false" aria-disabled="<?php echo $hasPremiumFillAccess ? 'false' : 'true'; ?>">
                    <span class="display-option-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14M16 5v14"/></svg>
                    </span>
                    <span class="display-option-text">
                        <strong>Preencher tela</strong>
                        <span><?php echo $hasPremiumFillAccess ? 'Remove barras sem distorcer.' : 'Disponivel no plano pago ou cortesia.'; ?></span>
                    </span>
                </button>
            </div>
        </div>
        <div class="display-menu player-settings-menu" id="player-settings-menu">
            <button class="display-menu-btn" id="player-settings-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Ajustes do player">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5A3.5 3.5 0 1112 8a3.5 3.5 0 010 7.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.7 1.7 0 00-1.87-.34 1.7 1.7 0 00-1.04 1.56V21a2 2 0 01-4 0v-.08a1.7 1.7 0 00-1.04-1.56 1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.56-1.04H3a2 2 0 010-4h.08A1.7 1.7 0 004.6 8a1.7 1.7 0 00-.34-1.87l-.06-.06a2 2 0 012.83-2.83l.06.06A1.7 1.7 0 008.96 3.6 1.7 1.7 0 0010 2.04V2a2 2 0 014 0v.08a1.7 1.7 0 001.04 1.56 1.7 1.7 0 001.87-.34l.06-.06a2 2 0 012.83 2.83l-.06.06A1.7 1.7 0 0019.4 8c.18.48.62.82 1.14.88H21a2 2 0 010 4h-.46A1.7 1.7 0 0019.4 15z"/></svg>
                <span>Ajustes</span>
            </button>
            <div class="display-menu-panel settings-menu-panel" id="player-settings-panel" role="menu" aria-label="Ajustes de audio e internet">
                <div class="settings-section">
                    <div class="settings-title">
                        <span>Audio</span>
                        <span class="settings-hint">qualidade</span>
                    </div>
                    <?php foreach ($playerFeatures['audio'] as $feature): ?>
                    <button class="display-option player-feature-option<?php echo $feature['enabled'] ? '' : ' locked'; ?><?php echo $feature['id'] === 'standard' ? ' active' : ''; ?>" type="button" data-feature-group="audio" data-feature-id="<?php echo htmlspecialchars($feature['id']); ?>" role="menuitemradio" aria-checked="<?php echo $feature['id'] === 'standard' ? 'true' : 'false'; ?>" aria-disabled="<?php echo $feature['enabled'] ? 'false' : 'true'; ?>">
                        <span class="display-option-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18V6l8 6-8 6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 9v6"/></svg>
                        </span>
                        <span class="display-option-text">
                            <strong><?php echo htmlspecialchars($feature['label']); ?></strong>
                            <span><?php echo htmlspecialchars($feature['enabled'] ? $feature['description'] : 'Disponivel no plano pago ou cortesia.'); ?></span>
                        </span>
                        <span class="settings-option-badge"><?php echo $feature['tier'] === 'free' ? 'Free' : 'Pro'; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="settings-section">
                    <div class="settings-title">
                        <span>Internet</span>
                        <span class="settings-hint">dados</span>
                    </div>
                    <?php foreach ($playerFeatures['data'] as $feature): ?>
                    <button class="display-option player-feature-option<?php echo $feature['enabled'] ? '' : ' locked'; ?><?php echo $feature['id'] === 'standard' ? ' active' : ''; ?>" type="button" data-feature-group="data" data-feature-id="<?php echo htmlspecialchars($feature['id']); ?>" role="menuitemradio" aria-checked="<?php echo $feature['id'] === 'standard' ? 'true' : 'false'; ?>" aria-disabled="<?php echo $feature['enabled'] ? 'false' : 'true'; ?>">
                        <span class="display-option-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 13l3-3 3 3 4-6"/></svg>
                        </span>
                        <span class="display-option-text">
                            <strong><?php echo htmlspecialchars($feature['label']); ?></strong>
                            <span><?php echo htmlspecialchars($feature['enabled'] ? $feature['description'] : 'Disponivel no plano pago ou cortesia.'); ?></span>
                        </span>
                        <span class="settings-option-badge"><?php echo $feature['tier'] === 'free' ? 'Free' : 'Pro'; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="display-menu cast-menu" id="cast-menu">
            <button class="display-menu-btn" id="cast-menu-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Transmissao para TV">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1h-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 17a5 5 0 015 5"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 13a9 9 0 019 9"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21h.01"/></svg>
                <span>Transmitir</span>
            </button>
            <div class="display-menu-panel settings-menu-panel cast-menu-panel" id="cast-menu-panel" role="menu" aria-label="Transmissao de video">
                <div class="settings-section">
                    <div class="settings-title">
                        <span>TV</span>
                        <span class="settings-hint">transmissao</span>
                    </div>
                    <?php foreach ($playerFeatures['cast'] as $feature): ?>
                    <div class="cast-launcher-shell" id="cast-launcher-shell">
                        <button class="display-option player-feature-option<?php echo $feature['enabled'] ? '' : ' locked'; ?> active" id="cast-standard-option" type="button" data-feature-group="cast" data-feature-id="<?php echo htmlspecialchars($feature['id']); ?>" role="menuitemradio" aria-checked="true" aria-disabled="<?php echo $feature['enabled'] ? 'false' : 'true'; ?>">
                            <span class="display-option-icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v10H4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 17a4 4 0 014 4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21h.01"/></svg>
                            </span>
                            <span class="display-option-text">
                                <strong><?php echo htmlspecialchars($feature['label']); ?></strong>
                                <span><?php echo htmlspecialchars($feature['enabled'] ? $feature['description'] : 'Disponivel no plano pago ou cortesia.'); ?></span>
                            </span>
                            <span class="settings-option-badge"><?php echo $feature['tier'] === 'free' ? 'Free' : 'Pro'; ?></span>
                        </button>
                        <google-cast-launcher id="native-cast-launcher" class="native-cast-launcher" aria-label="Abrir permissao de transmissao"></google-cast-launcher>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="cast-status" id="cast-status">Usa AirPlay, Chromecast ou Remote Playback quando o dispositivo permitir.</div>
            </div>
        </div>
    </div>
</nav>

<!-- ─── PLAYER ──────────────────────────────────────────────────────────── -->
<main>
<div id="player-wrap">

    <div id="browser-block-overlay" role="dialog" aria-modal="true" aria-labelledby="browser-block-title">
        <div class="browser-block-card">
            <div class="browser-block-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.36-6.36l-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0l-2.12-2.12M7.76 7.76 5.64 5.64"/>
                    <circle cx="12" cy="12" r="4" stroke-width="2"/>
                </svg>
            </div>
            <h1 class="browser-block-title" id="browser-block-title">Abra em um navegador seguro</h1>
            <p class="browser-block-text">
                Players de vídeo podem falhar dentro de apps como Telegram, Instagram e Facebook. Para assistir sem bloqueios, abra esta página no Google Chrome, Safari ou no navegador padrão do aparelho.
            </p>
            <div class="browser-block-actions">
                <button class="browser-action-primary" type="button" id="open-external-browser">Abrir no Chrome</button>
                <button class="browser-action-secondary" type="button" id="copy-player-link">Copiar link</button>
            </div>
        </div>
    </div>

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
        <div class="error-title" id="err-title">Erro no player</div>
        <div class="error-message" id="err-message">O erro foi reportado automaticamente para a equipe do Pipocine e sera corrigido o mais rapido possivel. Pedimos desculpas pelo transtorno.</div>
        <a class="error-help-link" href="/docs/player-error" target="_blank" rel="noopener">Saiba mais</a>
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
    <video id="pip-video" preload="metadata" playsinline x-webkit-airplay="allow"></video>
    <audio id="pip-audio" preload="metadata"></audio>

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
                <?php if ($isSerie): ?>
                <div class="audio-switcher" id="audio-switcher" aria-label="Selecionar audio">
                    <button class="audio-tab active" type="button"><?php echo strtoupper($audio) === 'DUB' ? 'DUB' : 'LEG'; ?></button>
                </div>
                <span class="audio-badge" id="audio-badge-ctrl" style="display:none"><?php echo strtoupper($audio) === 'DUB' ? 'DUB' : 'LEG'; ?></span>
                <?php else: ?>
                <span class="audio-badge" id="audio-badge-ctrl"><?php echo strtoupper($audio) === 'DUB' ? 'DUB' : 'LEG'; ?></span>
                <?php endif; ?>
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
    const IS_EMBEDDED_BROWSER = <?php echo $isEmbeddedBrowser ? 'true' : 'false'; ?>;
    const CAN_PREMIUM_FILL = <?php echo $hasPremiumFillAccess ? 'true' : 'false'; ?>;
    const PLAYER_FEATURES = <?php echo json_encode($playerFeatures, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const ORIGINAL_PLAYER_URL = window.location.href;
    let   AUDIO          = <?php echo json_encode($audio); ?>;

    // Progresso inicial: lê ?t= da URL (link "Continua Assistindo") ou usa a API
    const _urlT = parseInt(new URLSearchParams(window.location.search).get('t') || '0', 10);
    let RESUME_TIME = _urlT > 5 ? _urlT : 0;

    function maskPlayerUrl() {
        const cleanPath = `${window.location.origin}/player`;
        if (window.location.href !== cleanPath) {
            history.replaceState({ ...(history.state || {}), playerMasked: true }, '', cleanPath);
        }
    }

    maskPlayerUrl();

    const reportedPlayerErrors = new Set();
    const FRIENDLY_PLAYER_ERROR_TITLE = 'Erro no player';
    const FRIENDLY_PLAYER_ERROR_MESSAGE = 'O erro foi reportado automaticamente para a equipe do Pipocine e sera corrigido o mais rapido possivel. Pedimos desculpas pelo transtorno.';

    function browserName() {
        const ua = navigator.userAgent || '';
        if (/Edg\//.test(ua)) return 'Edge';
        if (/OPR\//.test(ua)) return 'Opera';
        if (/Firefox\//.test(ua)) return 'Firefox';
        if (/Chrome\//.test(ua) && !/Chromium\//.test(ua)) return 'Chrome';
        if (/Safari\//.test(ua) && !/Chrome\//.test(ua)) return 'Safari';
        return 'Unknown';
    }

    function connectionInfo() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!connection) return {};
        return {
            effectiveType: connection.effectiveType || null,
            downlink: connection.downlink || null,
            rtt: connection.rtt || null,
            saveData: Boolean(connection.saveData),
        };
    }

    function reportPlayerError(title, message, context = {}) {
        const stage = context.stage || 'unknown';
        const key = `${stage}:${title || ''}:${message || ''}:${AUDIO}`;
        if (reportedPlayerErrors.has(key)) return;
        reportedPlayerErrors.add(key);

        const payload = {
            severity: context.severity || 'error',
            event_type: context.event_type || 'player_error',
            stage,
            content_id: TMDB_ID,
            content_title: CONTENT_TITLE,
            content_type: CONTENT_TYPE,
            season: CURRENT_S,
            episode: CURRENT_E,
            audio: AUDIO,
            error_title: title || '',
            error_message: message || '',
            technical_message: context.technical_message || message || title || '',
            player_url: ORIGINAL_PLAYER_URL,
            api_url: API_URL,
            media_type: context.media_type || currentMediaType || '',
            media_url: context.media_url || currentMediaUrl || '',
            is_embedded_browser: IS_EMBEDDED_BROWSER,
            is_vpn_suspected: false,
            browser_name: browserName(),
            network: connectionInfo(),
            diagnostics: {
                readyState: video?.readyState ?? null,
                networkState: video?.networkState ?? null,
                videoErrorCode: video?.error?.code ?? null,
                hlsDetails: context.hls_details || null,
                maskedUrl: window.location.href,
            },
        };

        const body = JSON.stringify(payload);
        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon('/api/player/log', blob)) return;
            }
        } catch (_) {}

        fetch('/api/player/log', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
            keepalive: true,
        }).catch(() => {});
    }

    // ─── Elementos ───────────────────────────────────────────────────────
    const video          = document.getElementById('pip-video');
    const splitAudio     = document.getElementById('pip-audio');
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
    const audioSwitcher  = document.getElementById('audio-switcher');
    const openExternalBrowser = document.getElementById('open-external-browser');
    const copyPlayerLink = document.getElementById('copy-player-link');
    const displayMenu    = document.getElementById('display-menu');
    const displayMenuBtn = document.getElementById('display-menu-btn');
    const settingsMenu   = document.getElementById('player-settings-menu');
    const settingsBtn    = document.getElementById('player-settings-btn');
    const castMenu       = document.getElementById('cast-menu');
    const castMenuBtn    = document.getElementById('cast-menu-btn');
    const castStatus     = document.getElementById('cast-status');
    const castLauncherShell = document.getElementById('cast-launcher-shell');
    const nativeCastLauncher = document.getElementById('native-cast-launcher');

    let hls           = null;
    let nextEpData    = null;
    let controlsTimer = null;
    let isDragging    = false;
    let toastTimer    = null;
    let isSeeking     = false;
    let activeAudioMode = 'standard';
    let activeDataMode = 'standard';
    let activeCastMode = 'tv_standard';
    let audioEngine = null;
    let currentMediaUrl = '';
    let currentMediaType = '';
    let originalMediaUrl = '';
    let originalMediaType = '';
    let cdnVideoUrl = '';
    let cdnAudioUrls = {};
    let playbackUsesCdn = false;
    let playbackUsesSplitAudio = false;
    let googleCastSdkPromise = null;
    let googleCastInitialized = false;
    let googleCastWarmupStarted = false;
    let castPermissionPromptOpen = false;
    let googleCastMediaLoadPromise = null;

    function currentAbsoluteUrl() {
        return IS_EMBEDDED_BROWSER ? ORIGINAL_PLAYER_URL : window.location.href;
    }

    function setupEmbeddedBrowserBlock() {
        if (!IS_EMBEDDED_BROWSER) return false;

        const href = currentAbsoluteUrl();
        reportPlayerError('Acesso via navegador interno', 'O player foi aberto dentro de um navegador interno de aplicativo.', {
            stage: 'embedded_browser',
            severity: 'warning',
            event_type: 'player_blocked_environment',
            technical_message: 'Embedded browser detected by server-side user-agent pattern.',
        });
        const android = /Android/i.test(navigator.userAgent);
        const ios = /iPhone|iPad|iPod/i.test(navigator.userAgent);

        openExternalBrowser?.addEventListener('click', () => {
            if (android) {
                const withoutProtocol = href.replace(/^https?:\/\//i, '');
                window.location.href = `intent://${withoutProtocol}#Intent;scheme=${window.location.protocol.replace(':', '')};package=com.android.chrome;end`;
                return;
            }

            if (ios) {
                window.location.href = href.replace(/^https?:\/\//i, 'googlechrome://');
                setTimeout(() => showToast('Se não abrir, copie o link e cole no Safari ou Chrome.'), 700);
                return;
            }

            window.open(href, '_blank', 'noopener');
        });

        copyPlayerLink?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(href);
                showToast('Link copiado.');
            } catch (_) {
                showToast('Copie o link pela barra de endereços.');
            }
        });

        return true;
    }

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

    function isMobileDevice() {
        return /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) || window.innerWidth < 768;
    }

    function isFullscreen() {
        return !!document.fullscreenElement || !!document.webkitFullscreenElement;
    }

    async function requestPlayerFullscreen() {
        const request = playerWrap.requestFullscreen || playerWrap.webkitRequestFullscreen;
        if (!request) {
            if (video.webkitEnterFullscreen && isMobileDevice()) video.webkitEnterFullscreen();
            return;
        }

        try {
            await request.call(playerWrap, { navigationUI: 'hide' });
        } catch (_) {
            await request.call(playerWrap);
        }
    }

    async function lockLandscape() {
        if (!isMobileDevice() || !screen.orientation?.lock) return;
        try {
            await screen.orientation.lock('landscape');
        } catch (_) {}
    }

    async function enterLandscapeFullscreen(silent = false) {
        try {
            if (!isFullscreen()) await requestPlayerFullscreen();
            await lockLandscape();
            showControls();
        } catch (_) {
            if (!silent) showToast('Toque em tela cheia para virar o player.');
        }
    }

    let mobileFullscreenPrimed = false;
    function primeMobileLandscapeFullscreen() {
        if (!isMobileDevice() || mobileFullscreenPrimed) return;
        mobileFullscreenPrimed = true;

        enterLandscapeFullscreen(true);

        const retry = () => enterLandscapeFullscreen(true);
        ['pointerup', 'touchend', 'click'].forEach(eventName => {
            playerWrap.addEventListener(eventName, retry, { once: true, passive: true });
        });
    }

    function applyFitMode(mode, persist = true) {
        const normalized = mode === 'cover' ? 'cover' : 'contain';
        if (normalized === 'cover' && !CAN_PREMIUM_FILL) {
            showToast('Recurso disponivel no plano pago ou cortesia.');
            return;
        }

        playerWrap.classList.toggle('fit-cover', normalized === 'cover');
        document.querySelectorAll('[data-fit-mode]').forEach(option => {
            const active = option.dataset.fitMode === normalized;
            option.classList.toggle('active', active);
            option.setAttribute('aria-checked', String(active));
        });

        if (persist && CAN_PREMIUM_FILL) {
            try { localStorage.setItem('pipocine-player-fit-mode', normalized); } catch (_) {}
        }
        if (persist) showToast(normalized === 'cover' ? 'Preenchendo tela sem distorcer.' : 'Formato original.');
    }

    function setupDisplayMenu() {
        if (!displayMenu || !displayMenuBtn) return;

        displayMenuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = displayMenu.classList.toggle('open');
            settingsMenu?.classList.remove('open');
            settingsBtn?.setAttribute('aria-expanded', 'false');
            castMenu?.classList.remove('open');
            castMenuBtn?.setAttribute('aria-expanded', 'false');
            displayMenuBtn.setAttribute('aria-expanded', String(open));
            showControls();
        });

        displayMenu.addEventListener('click', (event) => event.stopPropagation());

        document.addEventListener('click', () => {
            displayMenu.classList.remove('open');
            displayMenuBtn.setAttribute('aria-expanded', 'false');
            settingsMenu?.classList.remove('open');
            settingsBtn?.setAttribute('aria-expanded', 'false');
            castMenu?.classList.remove('open');
            castMenuBtn?.setAttribute('aria-expanded', 'false');
        });

        document.querySelectorAll('[data-fit-mode]').forEach(option => {
            option.addEventListener('click', () => {
                if (option.classList.contains('locked')) {
                    showToast('Assine ou use uma cortesia ativa para liberar.');
                    return;
                }
                applyFitMode(option.dataset.fitMode || 'contain');
                displayMenu.classList.remove('open');
                displayMenuBtn.setAttribute('aria-expanded', 'false');
            });
        });

        if (CAN_PREMIUM_FILL) {
            try {
                applyFitMode(localStorage.getItem('pipocine-player-fit-mode') || 'contain', false);
            } catch (_) {
                applyFitMode('contain', false);
            }
        }
    }

    function getFeature(group, id) {
        return (PLAYER_FEATURES[group] || []).find(feature => feature.id === id) || null;
    }

    function firstEnabledFeature(group) {
        return (PLAYER_FEATURES[group] || []).find(feature => feature.enabled) || null;
    }

    function getStoredFeature(group, fallback) {
        try {
            const stored = localStorage.getItem(`pipocine-player-${group}-mode`);
            const feature = stored ? getFeature(group, stored) : null;
            return feature && feature.enabled ? stored : fallback;
        } catch (_) {
            return fallback;
        }
    }

    function persistFeature(group, id) {
        try { localStorage.setItem(`pipocine-player-${group}-mode`, id); } catch (_) {}
    }

    function configurePlaybackSource(data) {
        originalMediaUrl = data?.url || '';
        originalMediaType = data?.media_type || '';
        const internalCdn = data?.cdn_internal?.enabled ? data.cdn_internal : null;
        cdnVideoUrl = internalCdn?.video_url || '';
        cdnAudioUrls = internalCdn?.audio_urls || {};
        playbackUsesCdn = Boolean(cdnVideoUrl);
        playbackUsesSplitAudio = Boolean(internalCdn && internalCdn.mode === 'internal_realtime_split_mp4');
        configureSplitAudio(cdnAudioUrls[activeAudioMode] || cdnAudioUrls.standard || '');

        return {
            url: playbackUsesCdn ? cdnVideoUrlForProfile(activeAudioMode || 'standard') : originalMediaUrl,
            mediaType: playbackUsesCdn ? (internalCdn?.media_type || data?.media_type || 'auto') : (data?.media_type || ''),
            fallbackUrl: originalMediaUrl,
            fallbackType: data?.media_type || '',
        };
    }

    function cdnVideoUrlForProfile(profile) {
        if (!cdnVideoUrl) return '';
        return cdnVideoUrl;
    }

    function configureSplitAudio(audioUrl) {
        if (!splitAudio) return;
        if (!playbackUsesSplitAudio || !audioUrl) {
            splitAudio.pause();
            splitAudio.removeAttribute('src');
            splitAudio.load();
            video.muted = false;
            return;
        }

        video.muted = true;
        if (splitAudio.getAttribute('src') !== audioUrl) {
            splitAudio.src = audioUrl;
            splitAudio.load();
        }
        splitAudio.volume = parseFloat(volumeSlider?.value || '1');
        splitAudio.muted = video.muted && Number(splitAudio.volume || 0) === 0;
    }

    function activeVolumeElement() {
        return playbackUsesSplitAudio && splitAudio ? splitAudio : video;
    }

    function switchCdnAudioProfile(feature, persist = true) {
        if (!feature || !cdnVideoUrl) return false;

        activeAudioMode = feature.id;
        updateFeatureMenuState('audio', activeAudioMode);
        if (persist) persistFeature('audio', activeAudioMode);

        disconnectAudioEngine();
        const resumeAt = Math.max(0, video.currentTime || 0);
        const nextAudioUrl = cdnAudioUrls[activeAudioMode] || cdnAudioUrls.standard || '';
        if (playbackUsesSplitAudio && nextAudioUrl) {
            const shouldResume = !video.paused;
            configureSplitAudio(nextAudioUrl);
            splitAudio.currentTime = resumeAt;
            if (shouldResume) splitAudio.play().catch(() => {});
            if (persist) showToast(feature.label);
            return true;
        }

        if (persist) showToast(feature.label);
        startPlayerAt(cdnVideoUrlForProfile(activeAudioMode), 'mp4', resumeAt, originalMediaUrl, originalMediaType || 'auto');
        return true;
    }

    function mediaCanUseWebAudio() {
        const source = video.currentSrc || video.src || currentMediaUrl || '';
        if (!source) return false;

        try {
            const url = new URL(source, window.location.href);
            if (url.protocol === 'blob:' || url.protocol === 'data:') return true;
            return url.origin === window.location.origin;
        } catch (_) {
            return false;
        }
    }

    function fallbackToStandardAudio(message, persist = true) {
        activeAudioMode = 'standard';
        updateFeatureMenuState('audio', activeAudioMode);
        if (persist) persistFeature('audio', activeAudioMode);
        if (message) showToast(message);
    }

    function updateFeatureMenuState(group, id) {
        document.querySelectorAll(`[data-feature-group="${group}"]`).forEach(option => {
            const active = option.dataset.featureId === id;
            option.classList.toggle('active', active);
            option.setAttribute('aria-checked', String(active));
        });
    }

    function setupSettingsMenu() {
        if (!settingsMenu || !settingsBtn) return;

        settingsBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = settingsMenu.classList.toggle('open');
            displayMenu?.classList.remove('open');
            displayMenuBtn?.setAttribute('aria-expanded', 'false');
            castMenu?.classList.remove('open');
            castMenuBtn?.setAttribute('aria-expanded', 'false');
            settingsBtn.setAttribute('aria-expanded', String(open));
            showControls();
        });

        settingsMenu.addEventListener('click', (event) => event.stopPropagation());

        document.querySelectorAll('[data-feature-group][data-feature-id]').forEach(option => {
            option.addEventListener('click', () => {
                const group = option.dataset.featureGroup;
                const id = option.dataset.featureId;
                const feature = getFeature(group, id);

                if (!feature || option.classList.contains('locked') || !feature.enabled) {
                    showToast('Disponivel no plano pago ou cortesia.');
                    return;
                }

                if (group === 'audio') {
                    applyAudioMode(id, true);
                } else if (group === 'data') {
                    applyDataMode(id, true);
                } else if (group === 'cast') {
                    applyCastMode(id, true);
                    connectToTv();
                }
            });
        });

        activeAudioMode = getStoredFeature('audio', firstEnabledFeature('audio')?.id || 'standard');
        activeDataMode = getStoredFeature('data', firstEnabledFeature('data')?.id || 'standard');
        activeCastMode = getStoredFeature('cast', firstEnabledFeature('cast')?.id || 'tv_standard');
        updateFeatureMenuState('audio', activeAudioMode);
        updateFeatureMenuState('data', activeDataMode);
        updateFeatureMenuState('cast', activeCastMode);

        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection?.addEventListener) {
            connection.addEventListener('change', () => {
                if (activeDataMode === 'standard') applyHlsDataMode();
            });
        }

        ['pointerup', 'touchend', 'click', 'play'].forEach(eventName => {
            video.addEventListener(eventName, () => {
                if (audioEngine?.context?.state === 'suspended') {
                    audioEngine.context.resume().catch(() => {});
                }
            }, { passive: true });
        });

        if (activeAudioMode !== 'standard') {
            video.addEventListener('play', () => applyAudioMode(activeAudioMode, false), { once: true });
        }
    }

    function disconnectAudioEngine() {
        if (!audioEngine) return;
        audioEngine.nodes.forEach(node => {
            try { node.disconnect(); } catch (_) {}
        });
        try { audioEngine.source.disconnect(); } catch (_) {}
        audioEngine.nodes = [];
    }

    function initAudioEngine() {
        if (audioEngine) return audioEngine;

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            showToast('Audio avancado indisponivel neste navegador.');
            return null;
        }

        try {
            const context = new AudioContextClass();
            audioEngine = {
                context,
                source: context.createMediaElementSource(video),
                nodes: [],
            };
            return audioEngine;
        } catch (_) {
            showToast('Este navegador bloqueou o processamento de audio.');
            return null;
        }
    }

    function setAudioParam(param, value) {
        if (!param || value === undefined || value === null) return;
        try { param.setValueAtTime(value, audioEngine.context.currentTime); } catch (_) { param.value = value; }
    }

    function createFilters(filters) {
        return (filters || []).map(config => {
            const node = audioEngine.context.createBiquadFilter();
            node.type = config.type || 'peaking';
            setAudioParam(node.frequency, Number(config.frequency || 1000));
            setAudioParam(node.Q, Number(config.q || 0.7));
            setAudioParam(node.gain, Number(config.gain || 0));
            return node;
        });
    }

    function createCompressor(config) {
        if (!config) return null;
        const node = audioEngine.context.createDynamicsCompressor();
        setAudioParam(node.threshold, Number(config.threshold ?? -18));
        setAudioParam(node.knee, Number(config.knee ?? 20));
        setAudioParam(node.ratio, Number(config.ratio ?? 4));
        setAudioParam(node.attack, Number(config.attack ?? 0.006));
        setAudioParam(node.release, Number(config.release ?? 0.24));
        return node;
    }

    function connectLinearAudioGraph(params) {
        const nodes = [
            ...createFilters(params.filters),
            createCompressor(params.compressor),
            audioEngine.context.createGain(),
        ].filter(Boolean);

        const master = nodes[nodes.length - 1];
        setAudioParam(master.gain, Number(params.gain || 1));

        let previous = audioEngine.source;
        nodes.forEach(node => {
            previous.connect(node);
            previous = node;
        });
        previous.connect(audioEngine.context.destination);
        audioEngine.nodes = nodes;
    }

    function connectSurroundGraph(params) {
        const context = audioEngine.context;
        const splitter = context.createChannelSplitter(2);
        const merger = context.createChannelMerger(2);
        const leftDelay = context.createDelay(0.05);
        const rightDelay = context.createDelay(0.05);
        const leftCross = context.createGain();
        const rightCross = context.createGain();
        const filters = createFilters(params.filters);
        const compressor = createCompressor(params.compressor);
        const master = context.createGain();
        const delay = Number(params.delay || 0.016);
        const crossGain = Number(params.crossGain || 0.18);

        setAudioParam(leftDelay.delayTime, delay);
        setAudioParam(rightDelay.delayTime, delay);
        setAudioParam(leftCross.gain, crossGain);
        setAudioParam(rightCross.gain, crossGain);
        setAudioParam(master.gain, Number(params.gain || 1));

        audioEngine.source.connect(splitter);
        splitter.connect(merger, 0, 0);
        splitter.connect(merger, 1, 1);
        splitter.connect(leftDelay, 0);
        leftDelay.connect(leftCross);
        leftCross.connect(merger, 0, 1);
        splitter.connect(rightDelay, 1);
        rightDelay.connect(rightCross);
        rightCross.connect(merger, 0, 0);

        let previous = merger;
        [...filters, compressor, master].filter(Boolean).forEach(node => {
            previous.connect(node);
            previous = node;
        });
        previous.connect(context.destination);

        audioEngine.nodes = [splitter, merger, leftDelay, rightDelay, leftCross, rightCross, ...filters, compressor, master].filter(Boolean);
    }

    function applyAudioMode(id, persist = true) {
        const feature = getFeature('audio', id) || getFeature('audio', 'standard');
        if (!feature || !feature.enabled) {
            showToast('Disponivel no plano pago ou cortesia.');
            return;
        }

        if (playbackUsesSplitAudio && cdnVideoUrl && switchCdnAudioProfile(feature, persist)) {
            return;
        }

        if (feature.id !== 'standard' && !mediaCanUseWebAudio()) {
            fallbackToStandardAudio('Audio avancado indisponivel nesta fonte. Volume normal mantido.');
            return;
        }

        activeAudioMode = feature.id;
        updateFeatureMenuState('audio', activeAudioMode);
        if (persist) persistFeature('audio', activeAudioMode);

        if (feature.id === 'standard' && !audioEngine) {
            if (persist) showToast('Audio padrao.');
            return;
        }

        if (feature.id === 'standard' && !mediaCanUseWebAudio()) {
            if (persist) showToast('Audio padrao.');
            return;
        }

        const engine = initAudioEngine();
        if (!engine) {
            fallbackToStandardAudio('', persist);
            return;
        }

        disconnectAudioEngine();
        const params = feature.params || {};
        if (params.graph === 'surround') {
            connectSurroundGraph(params);
        } else {
            connectLinearAudioGraph(params);
        }

        if (engine.context.state === 'suspended') {
            engine.context.resume().catch(() => {});
        }

        if (persist) showToast(feature.label);
    }

    function applyDataMode(id, persist = true) {
        const feature = getFeature('data', id) || getFeature('data', 'standard');
        if (!feature || !feature.enabled) {
            showToast('Disponivel no plano pago ou cortesia.');
            return;
        }

        activeDataMode = feature.id;
        updateFeatureMenuState('data', activeDataMode);
        if (persist) persistFeature('data', activeDataMode);
        applyHlsDataMode();
        if (persist) showToast(feature.label);
    }

    function setupCastMenu() {
        if (!castMenu || !castMenuBtn) return;

        castMenuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = castMenu.classList.toggle('open');
            displayMenu?.classList.remove('open');
            settingsMenu?.classList.remove('open');
            displayMenuBtn?.setAttribute('aria-expanded', 'false');
            settingsBtn?.setAttribute('aria-expanded', 'false');
            castMenuBtn.setAttribute('aria-expanded', String(open));
            updateCastStatus();
            if (open) warmupCastPermissionPrompt('menu');
            showControls();
        });

        ['pointerenter', 'focus', 'touchstart'].forEach(eventName => {
            castMenuBtn.addEventListener(eventName, () => warmupCastPermissionPrompt('intent'), { passive: true });
        });

        nativeCastLauncher?.addEventListener('click', () => {
            updateCastStatus('Abrindo seletor de transmissao do navegador...');
        }, { passive: true });

        castMenu.addEventListener('click', (event) => event.stopPropagation());
        setupCastAvailability();
        updateCastStatus();
    }

    function isSlowConnection() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return Boolean(connection?.saveData || ['slow-2g', '2g', '3g'].includes(connection?.effectiveType));
    }

    function applyCastMode(id, persist = true) {
        const feature = getFeature('cast', id) || getFeature('cast', 'tv_standard');
        if (!feature || !feature.enabled) {
            showToast('Disponivel no plano pago ou cortesia.');
            return;
        }

        activeCastMode = feature.id;
        updateFeatureMenuState('cast', activeCastMode);
        if (persist) persistFeature('cast', activeCastMode);

        const params = feature.params || {};
        const dataMode = params.slowNetworkDataMode && isSlowConnection()
            ? params.slowNetworkDataMode
            : params.preferredDataMode;

        if (dataMode && getFeature('data', dataMode)?.enabled) {
            applyDataMode(dataMode, false);
        }

        if (params.preferredAudioMode && getFeature('audio', params.preferredAudioMode)?.enabled) {
            applyAudioMode(params.preferredAudioMode, false);
        }

        updateCastStatus(`${feature.label} selecionada. Procurando recurso nativo do dispositivo.`);
        if (persist) showToast(feature.label);
    }

    function updateCastStatus(message = '') {
        if (!castStatus) return;
        const feature = getFeature('cast', activeCastMode) || getFeature('cast', 'tv_standard');
        const suffix = feature ? `${feature.label}.` : 'Transmissao Padrao.';
        castStatus.textContent = message || `${suffix} Usa AirPlay, Chromecast ou Remote Playback quando o dispositivo permitir.`;
    }

    function mediaUrlForCast() {
        const source = currentMediaUrl || video.currentSrc || video.src || '';
        if (!source) return '';
        try {
            return new URL(source, window.location.href).href;
        } catch (_) {
            return source;
        }
    }

    function castContentType(url, mediaType) {
        const lower = String(url || '').split('?')[0].toLowerCase();
        const type = String(mediaType || '').toLowerCase();
        if (type === 'm3u8' || lower.endsWith('.m3u8')) return 'application/x-mpegURL';
        if (type === 'webm' || lower.endsWith('.webm')) return 'video/webm';
        if (type === 'mkv' || lower.endsWith('.mkv')) return 'video/x-matroska';
        return 'video/mp4';
    }

    function isSecureCastContext() {
        return window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);
    }

    function hasTransientUserActivation() {
        return navigator.userActivation?.isActive !== false;
    }

    function googleCastReady() {
        return Boolean(window.cast?.framework && window.chrome?.cast && googleCastInitialized);
    }

    function warmupCastPermissionPrompt(reason = '') {
        if (googleCastReady() || googleCastWarmupStarted || !isSecureCastContext()) return;

        googleCastWarmupStarted = true;
        if (reason === 'menu') {
            updateCastStatus('Preparando permissao de transmissao do navegador...');
        }

        initGoogleCast()
            .then((ready) => {
                if (!ready) {
                    googleCastWarmupStarted = false;
                    if (reason === 'menu') {
                        updateCastStatus('Chromecast nao foi liberado por este navegador. Tentaremos as APIs nativas.');
                    }
                    return;
                }

                if (reason === 'menu') {
                    updateCastStatus('Permissao pronta. Toque em Transmissao Padrao para escolher a TV.');
                }
            })
            .catch(() => {
                googleCastWarmupStarted = false;
                if (reason === 'menu') {
                    updateCastStatus('Chromecast nao carregou agora. Ainda tentaremos AirPlay ou Remote Playback.');
                }
            });
    }

    function scheduleCastPermissionWarmup() {
        const warmCast = () => warmupCastPermissionPrompt('ready');
        if ('requestIdleCallback' in window) {
            requestIdleCallback(warmCast, { timeout: 2500 });
        } else {
            setTimeout(warmCast, 900);
        }
    }

    function setupCastAvailability() {
        video.disableRemotePlayback = false;

        if (video.remote?.addEventListener) {
            video.remote.addEventListener('connect', () => updateCastStatus('Transmitindo pela TV.'));
            video.remote.addEventListener('connecting', () => updateCastStatus('Conectando com a TV...'));
            video.remote.addEventListener('disconnect', () => {
                updateCastStatus('Transmissao encerrada.');
            });
        }

        if (video.remote?.watchAvailability) {
            video.remote.watchAvailability((available) => {
                if (available) updateCastStatus('TV detectada. Toque em Transmissao Padrao.');
            }).catch(() => {});
        }

        video.addEventListener('webkitplaybacktargetavailabilitychanged', (event) => {
            if (event.availability === 'available') updateCastStatus('AirPlay disponivel. Toque em Transmissao Padrao.');
        });

        video.addEventListener('webkitcurrentplaybacktargetiswirelesschanged', () => {
            updateCastStatus(video.webkitCurrentPlaybackTargetIsWireless ? 'Transmitindo por AirPlay.' : 'AirPlay encerrado.');
        });
    }

    async function connectToTv() {
        const feature = getFeature('cast', activeCastMode) || getFeature('cast', 'tv_standard');
        if (!feature?.enabled) {
            showToast('Disponivel no plano pago ou cortesia.');
            return;
        }

        if (castPermissionPromptOpen) return;
        castPermissionPromptOpen = true;

        applyCastMode(feature.id, false);

        if (!mediaUrlForCast()) {
            updateCastStatus('Aguarde o video carregar antes de transmitir.');
            showToast('Aguarde o video carregar.');
            castPermissionPromptOpen = false;
            return;
        }

        if (!isSecureCastContext()) {
            updateCastStatus('A transmissao exige HTTPS ou localhost para o navegador liberar permissao.');
            showToast('Abra em HTTPS para transmitir.');
            castPermissionPromptOpen = false;
            return;
        }

        if (!hasTransientUserActivation()) {
            updateCastStatus('Toque novamente em Transmissao Padrao para o navegador abrir a permissao.');
            showToast('Toque novamente para permitir.');
            castPermissionPromptOpen = false;
            return;
        }

        updateCastStatus('Abrindo permissao de transmissao do navegador...');

        try {
            if (googleCastReady() && await startGoogleCast(true)) return;

            if (typeof video.webkitShowPlaybackTargetPicker === 'function') {
                try {
                    video.webkitShowPlaybackTargetPicker();
                    updateCastStatus('Selecione a TV pelo AirPlay.');
                    return;
                } catch (_) {}
            }

            if (await startRemotePlayback()) return;

            if (!googleCastReady()) {
                updateCastStatus('Preparando Chromecast. Toque novamente em Transmissao Padrao.');
                warmupCastPermissionPrompt('menu');
                showToast('Toque novamente para escolher a TV.');
                return;
            }

            if (await startGoogleCast(true)) return;

            updateCastStatus('Nenhuma permissao de transmissao foi liberada por este navegador.');
            showToast('Transmissao indisponivel neste navegador.');
        } finally {
            castPermissionPromptOpen = false;
        }
    }

    async function startRemotePlayback() {
        if (!video.remote?.prompt) return false;
        try {
            await video.remote.prompt();
            updateCastStatus('Selecione a TV detectada pelo navegador.');
            return true;
        } catch (_) {
            return false;
        }
    }

    function loadGoogleCastSdk() {
        if (window.cast?.framework && window.chrome?.cast) return Promise.resolve(true);
        if (googleCastSdkPromise) return googleCastSdkPromise;

        googleCastSdkPromise = new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Tempo esgotado ao carregar Google Cast')), 8000);
            const previous = window.__onGCastApiAvailable;
            window.__onGCastApiAvailable = (available) => {
                if (typeof previous === 'function') previous(available);
                clearTimeout(timeout);
                if (available) resolve(true);
                else reject(new Error('Google Cast indisponivel'));
            };

            const existing = document.querySelector('script[data-pipocine-cast-sdk]');
            if (existing) {
                existing.addEventListener('load', () => resolve(Boolean(window.cast?.framework)), { once: true });
                existing.addEventListener('error', () => reject(new Error('Falha ao carregar Google Cast')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1';
            script.async = true;
            script.dataset.pipocineCastSdk = 'true';
            script.onerror = () => {
                clearTimeout(timeout);
                reject(new Error('Falha ao carregar Google Cast'));
            };
            document.head.appendChild(script);
        });

        googleCastSdkPromise = googleCastSdkPromise.catch((error) => {
            googleCastSdkPromise = null;
            throw error;
        });

        return googleCastSdkPromise;
    }

    async function initGoogleCast() {
        await loadGoogleCastSdk();
        if (googleCastInitialized) return true;
        if (!window.cast?.framework || !window.chrome?.cast) return false;

        const castContext = cast.framework.CastContext.getInstance();
        castContext.setOptions({
            receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
            autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED,
        });

        castContext.addEventListener(cast.framework.CastContextEventType.SESSION_STATE_CHANGED, (event) => {
            const state = event.sessionState;
            if (state === cast.framework.SessionState.SESSION_STARTED || state === cast.framework.SessionState.SESSION_RESUMED) {
                updateCastStatus('TV conectada. Enviando video...');
                loadGoogleCastMedia(event.session).catch(() => {
                    updateCastStatus('TV conectada, mas nao foi possivel enviar o video.');
                });
            }

            if (state === cast.framework.SessionState.SESSION_ENDED) {
                handleCastEnded();
            }
        });

        googleCastInitialized = true;
        castLauncherShell?.classList.add('cast-launcher-ready');
        nativeCastLauncher?.setAttribute('title', 'Abrir seletor de transmissao');
        return true;
    }

    async function startGoogleCast(requireGesture = false) {
        try {
            if (requireGesture && !hasTransientUserActivation()) return false;
            const ready = await initGoogleCast();
            if (!ready) return false;
            if (requireGesture && !hasTransientUserActivation()) return false;
            const castContext = cast.framework.CastContext.getInstance();
            const session = castContext.getCurrentSession() || await castContext.requestSession();
            if (!session) return false;
            await loadGoogleCastMedia(session);
            return true;
        } catch (_) {
            return false;
        }
    }

    async function loadGoogleCastMedia(existingSession = null) {
        if (googleCastMediaLoadPromise) return googleCastMediaLoadPromise;
        googleCastMediaLoadPromise = loadGoogleCastMediaNow(existingSession).finally(() => {
            googleCastMediaLoadPromise = null;
        });
        return googleCastMediaLoadPromise;
    }

    async function loadGoogleCastMediaNow(existingSession = null) {
        if (!window.chrome?.cast?.media || !window.cast?.framework) return false;

        const session = existingSession || cast.framework.CastContext.getInstance().getCurrentSession();
        if (!session) return false;

        const url = mediaUrlForCast();
        if (!url) return false;

        const mediaInfo = new chrome.cast.media.MediaInfo(url, castContentType(url, currentMediaType));
        mediaInfo.streamType = chrome.cast.media.StreamType.BUFFERED;
        mediaInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
        mediaInfo.metadata.title = CONTENT_TITLE;
        mediaInfo.metadata.subtitle = IS_SERIE ? `T${CURRENT_S} Ep. ${CURRENT_E}` : 'Filme';
        if (CONTENT_POSTER) mediaInfo.metadata.images = [{ url: CONTENT_POSTER }];
        mediaInfo.customData = {
            app: 'Pipocine',
            profile: 'tv_standard',
            targetLatency: 'normal',
        };

        const request = new chrome.cast.media.LoadRequest(mediaInfo);
        request.autoplay = !video.paused;
        request.currentTime = Math.max(0, Math.floor(video.currentTime || 0));
        await session.loadMedia(request);

        if (!video.paused) video.pause();
        updateCastStatus('Transmitindo: Transmissao Padrao.');
        showToast('Transmitindo para TV');
        return true;
    }

    function handleCastEnded() {
        updateCastStatus('Transmissao encerrada.');
    }

    function effectiveDataFeature() {
        const selected = getFeature('data', activeDataMode) || getFeature('data', 'standard');
        if (selected?.id !== 'standard') return selected;

        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType)) {
            return getFeature('data', 'low') || selected;
        }

        return selected;
    }

    function createHlsInstance() {
        const feature = effectiveDataFeature();
        const params = feature?.params || {};
        return new Hls({
            enableWorker: playbackUsesCdn,
            lowLatencyMode: playbackUsesCdn,
            maxBufferLength: Number(params.maxBufferLength || 30),
            maxMaxBufferLength: playbackUsesCdn ? 60 : Number(params.maxBufferLength || 30),
            backBufferLength: Number(params.backBufferLength || 30),
            manifestLoadingTimeOut: 10000,
            manifestLoadingMaxRetry: 3,
            levelLoadingTimeOut: 10000,
            levelLoadingMaxRetry: 3,
            fragLoadingTimeOut: 20000,
            fragLoadingMaxRetry: 5,
        });
    }

    function applyHlsDataMode() {
        if (!hls || !Array.isArray(hls.levels) || !hls.levels.length) return;

        const feature = effectiveDataFeature();
        const strategy = feature?.params?.strategy || 'auto';
        const levelCount = hls.levels.length;
        let cap = -1;

        if (strategy === 'low') {
            cap = 0;
            hls.currentLevel = -1;
            hls.nextLevel = 0;
        } else if (strategy === 'medium') {
            cap = Math.max(0, Math.min(levelCount - 1, Math.floor((levelCount - 1) * Number(feature.params.capRatio || 0.66))));
            hls.currentLevel = -1;
            hls.nextLevel = cap;
        } else if (strategy === 'highest') {
            cap = levelCount - 1;
            hls.currentLevel = cap;
            hls.nextLevel = cap;
        } else {
            hls.currentLevel = -1;
            hls.nextLevel = -1;
        }

        hls.autoLevelCapping = cap;
        if (feature?.params?.maxBufferLength) {
            hls.config.maxBufferLength = Number(feature.params.maxBufferLength);
        }
    }

    // ─── Carregar vídeo via API ───────────────────────────────────────────
    function loadVideo() {
        if (IS_EMBEDDED_BROWSER) return;

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
                    showError(data.error || 'Vídeo indisponível', data.message || '', {
                        stage: 'api_response',
                        technical_message: JSON.stringify(data),
                    });
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
                const playback = configurePlaybackSource(data);
                startPlayer(playback.url, playback.mediaType, playback.fallbackUrl, playback.fallbackType);
            })
            .catch(error => showError('Erro de conexão', 'Não foi possível conectar ao servidor de vídeo.', {
                stage: 'api_fetch',
                technical_message: error?.message || 'Fetch failed',
            }));
    }

    window.loadVideo = loadVideo;

    function showError(title, message, context = {}) {
        loaderOverlay.classList.add('hidden');
        errTitle.textContent = FRIENDLY_PLAYER_ERROR_TITLE;
        errMessage.textContent = FRIENDLY_PLAYER_ERROR_MESSAGE;
        reportPlayerError(title, message, context);
        errorOverlay.classList.add('visible');
    }

    window.addEventListener('error', event => {
        showError('Erro interno do player', event.message || 'Erro de runtime no player.', {
            stage: 'runtime_error',
            severity: 'fatal',
            technical_message: `${event.message || 'Runtime error'} @ ${event.filename || 'inline'}:${event.lineno || 0}:${event.colno || 0}`,
        });
    });

    window.addEventListener('unhandledrejection', event => {
        const reason = event.reason;
        showError('Erro interno do player', reason?.message || String(reason || 'Promise rejeitada no player.'), {
            stage: 'unhandled_rejection',
            severity: 'fatal',
            technical_message: reason?.stack || reason?.message || String(reason || 'Unhandled rejection'),
        });
    });

    function startPlayer(url, mediaType, fallbackUrl = '', fallbackType = '') {
        if (!url) { showError('URL inválida', 'O link de vídeo retornado é inválido.', { stage: 'invalid_media_url', media_type: mediaType || '' }); return; }

        currentMediaUrl = url;
        currentMediaType = mediaType || '';
        const isHLS = mediaType === 'm3u8' || url.includes('.m3u8');

        if (isHLS) {
            if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                hls = createHlsInstance();
                hls.loadSource(url);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    applyHlsDataMode();
                    loaderOverlay.classList.add('hidden');
                    showControls();
                    if (RESUME_TIME > 5 && video.duration && RESUME_TIME < video.duration - 10) {
                        video.currentTime = RESUME_TIME;
                        RESUME_TIME = 0;
                    }
                    primeMobileLandscapeFullscreen();
                    scheduleCastPermissionWarmup();
                    video.play().catch(() => {});
                });
                hls.on(Hls.Events.ERROR, (_e, d) => {
                    if (!d.fatal) return;
                    if (playbackUsesCdn && fallbackUrl && fallbackUrl !== url) {
                        playbackUsesCdn = false;
                        cdnVideoUrl = '';
                        cdnAudioUrls = {};
                        showToast('CDN indisponivel. Usando fonte original.');
                        startPlayer(fallbackUrl, fallbackType || 'auto');
                        return;
                    }
                    showError('Erro ao carregar stream', 'O video HLS encontrou um erro fatal.', {
                        stage: 'hls_error',
                        media_type: mediaType || 'm3u8',
                        media_url: url,
                        hls_details: d,
                        technical_message: d?.details || d?.type || 'Fatal HLS error',
                    });
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari nativo
                video.src = url;
                video.load();
                video.addEventListener('canplay', onReady, { once: true });
                video.addEventListener('error', onVideoError, { once: true });
            } else {
                showError('Formato não suportado', 'Seu navegador não suporta streams HLS.', {
                    stage: 'unsupported_hls',
                    media_type: mediaType || 'm3u8',
                    media_url: url,
                });
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
                if (playbackUsesCdn && fallbackUrl && fallbackUrl !== url) {
                    playbackUsesCdn = false;
                    playbackUsesSplitAudio = false;
                    cdnVideoUrl = '';
                    cdnAudioUrls = {};
                    configureSplitAudio('');
                    showToast('CDN interna indisponivel. Usando fonte original.');
                    startPlayer(fallbackUrl, fallbackType || 'auto');
                    return;
                }
                onVideoError();
            }, { once: true });
        }
    }

    function onReady() {
        loaderOverlay.classList.add('hidden');
        showControls();
        // Restaura ponto exato onde o usuário parou
        if (RESUME_TIME > 5 && video.duration && RESUME_TIME < video.duration - 10) {
            video.currentTime = RESUME_TIME;
            RESUME_TIME = 0; // aplica apenas uma vez
        }
        primeMobileLandscapeFullscreen();
        scheduleCastPermissionWarmup();
        video.play().catch(() => {});
    }

    function onVideoError() {
        showError('Erro ao carregar vídeo', 'Não foi possível reproduzir este arquivo de vídeo.', {
            stage: 'video_element_error',
            media_type: currentMediaType || '',
            media_url: currentMediaUrl || '',
            technical_message: video?.error ? `HTMLMediaElement error code ${video.error.code}` : 'Unknown video element error',
        });
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
        const volumeTarget = activeVolumeElement();
        volumeTarget.muted = !volumeTarget.muted;
        iconVol.style.display  = volumeTarget.muted ? 'none'  : '';
        iconMute.style.display = volumeTarget.muted ? ''      : 'none';
        volumeSlider.value = volumeTarget.muted ? 0 : volumeTarget.volume;
    };

    window.toggleFullscreen = async function () {
        const isFs = isFullscreen();

        if (!isFs) {
            await enterLandscapeFullscreen(false);
            return;
        } else {
            const exit = document.exitFullscreen || document.webkitExitFullscreen;
            if (exit) await exit.call(document);
            if (isMobileDevice() && screen.orientation?.unlock) {
                try { screen.orientation.unlock(); } catch (_) {}
            }
        }
    };

    // Sincroniza quando o usuário sai do fullscreen via Esc ou gesto do sistema
    document.addEventListener('fullscreenchange',       syncFsIcon);
    document.addEventListener('webkitfullscreenchange', syncFsIcon);
    function syncFsIcon() {
        const isFs = isFullscreen();
        document.getElementById('icon-fs').style.display      = isFs ? 'none' : '';
        document.getElementById('icon-fs-exit').style.display = isFs ? '' : 'none';
        if (isFs) {
            lockLandscape();
            showControls();
            return;
        }
        if (isMobileDevice() && screen.orientation?.unlock) {
            try { screen.orientation.unlock(); } catch (_) {}
        }
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

    function syncSplitAudioTime(force = false) {
        if (!playbackUsesSplitAudio || !splitAudio) return;
        if (force || Math.abs((splitAudio.currentTime || 0) - (video.currentTime || 0)) > 0.25) {
            try { splitAudio.currentTime = video.currentTime || 0; } catch (_) {}
        }
    }

    video.addEventListener('play', () => {
        if (!playbackUsesSplitAudio || !splitAudio?.src) return;
        syncSplitAudioTime(true);
        splitAudio.playbackRate = video.playbackRate || 1;
        splitAudio.play().catch(() => {});
    });

    video.addEventListener('pause', () => {
        if (playbackUsesSplitAudio && splitAudio) splitAudio.pause();
    });

    video.addEventListener('seeking', () => {
        if (playbackUsesSplitAudio && splitAudio) splitAudio.pause();
    });

    video.addEventListener('seeked', () => {
        if (!playbackUsesSplitAudio || !splitAudio) return;
        syncSplitAudioTime(true);
        if (!video.paused) splitAudio.play().catch(() => {});
    });

    video.addEventListener('ratechange', () => {
        if (playbackUsesSplitAudio && splitAudio) splitAudio.playbackRate = video.playbackRate || 1;
    });

    video.addEventListener('timeupdate', () => syncSplitAudioTime(false));

    // Volume
    volumeSlider.addEventListener('input', () => {
        const volumeTarget = activeVolumeElement();
        volumeTarget.volume = parseFloat(volumeSlider.value);
        volumeTarget.muted = volumeTarget.volume === 0;
        iconVol.style.display  = volumeTarget.muted ? 'none' : '';
        iconMute.style.display = volumeTarget.muted ? ''     : 'none';
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
        if (!shouldKeepControlsVisible()) {
            controlsTimer = setTimeout(hideControls, 3000);
        }
    }

    function shouldKeepControlsVisible() {
        return video.paused ||
            isDragging ||
            displayMenu?.classList.contains('open') ||
            settingsMenu?.classList.contains('open') ||
            castMenu?.classList.contains('open');
    }

    function hideControls() {
        if (shouldKeepControlsVisible()) return;
        controls.classList.add('hidden');
        nav.classList.add('hidden');
        playerWrap.classList.remove('cursor-visible');
    }

    function showControlsFromPointer(event) {
        if (event.pointerType === 'touch') return;
        showControls();
    }

    playerWrap.addEventListener('pointermove', showControlsFromPointer);
    window.addEventListener('orientationchange', () => setTimeout(showControls, 250));
    window.addEventListener('resize', () => {
        if (isMobileDevice() || isFullscreen()) setTimeout(showControls, 120);
    });
    video.addEventListener('pause', showControls);
    video.addEventListener('playing', showControls);

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
        if (isMobileDevice()) return;
        if (e.target === video || e.target === playerWrap) togglePlay();
    });

    // ─── Teclas de atalho ─────────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
        switch (e.code) {
            case 'Space': case 'KeyK': e.preventDefault(); togglePlay(); break;
            case 'ArrowRight': e.preventDefault(); skip(10);  break;
            case 'ArrowLeft':  e.preventDefault(); skip(-10); break;
            case 'ArrowUp': {
                e.preventDefault();
                const volumeTarget = activeVolumeElement();
                volumeTarget.volume = Math.min(1, volumeTarget.volume + .1);
                volumeTarget.muted = false;
                volumeSlider.value = volumeTarget.volume;
                break;
            }
            case 'ArrowDown': {
                e.preventDefault();
                const volumeTarget = activeVolumeElement();
                volumeTarget.volume = Math.max(0, volumeTarget.volume - .1);
                volumeTarget.muted = volumeTarget.volume === 0;
                volumeSlider.value = volumeTarget.volume;
                break;
            }
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

    // ─── Audio toggle nos controles ───────────────────────────────────────
    function buildAudioTabs(hasDub, hasLeg) {
        if (!audioSwitcher) return;
        audioSwitcher.innerHTML = '';
        if (hasDub) {
            const btn = document.createElement('button');
            btn.className = 'audio-tab' + (AUDIO === 'dub' ? ' active' : '');
            btn.dataset.audio = 'dub';
            btn.type = 'button';
            btn.textContent = 'DUB';
            btn.onclick = () => switchAudio('dub');
            audioSwitcher.appendChild(btn);
        }
        if (hasLeg) {
            const btn = document.createElement('button');
            btn.className = 'audio-tab' + (AUDIO === 'leg' ? ' active' : '');
            btn.dataset.audio = 'leg';
            btn.type = 'button';
            btn.textContent = 'LEG';
            btn.onclick = () => switchAudio('leg');
            audioSwitcher.appendChild(btn);
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
        maskPlayerUrl();
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
                if (!data.success) {
                    showError(data.error, data.message, {
                        stage: 'api_response_audio_switch',
                        technical_message: JSON.stringify(data),
                    });
                    return;
                }
                if (data.audio) { AUDIO = data.audio; audioBadge.textContent = data.audio === 'dub' ? 'DUB' : 'LEG'; }
                if (IS_SERIE && data.next_episode) { nextEpData = data.next_episode; buildNextEpLink(nextEpData); }
                const playback = configurePlaybackSource(data);
                startPlayerAt(playback.url, playback.mediaType, resumeAt, playback.fallbackUrl, playback.fallbackType);
            })
            .catch(error => showError('Erro de conexão', '', {
                stage: 'api_fetch_audio_switch',
                technical_message: error?.message || 'Fetch failed',
            }));
    }

    function startPlayerAt(url, mediaType, resumeAt, fallbackUrl = '', fallbackType = '') {
        if (!url) {
            showError('URL inválida', 'O link de vídeo retornado é inválido.', { stage: 'invalid_media_url_audio_switch', media_type: mediaType || '' });
            return;
        }
        currentMediaUrl = url || '';
        currentMediaType = mediaType || '';
        const isHLS = mediaType === 'm3u8' || url.includes('.m3u8');
        if (isHLS && typeof Hls !== 'undefined' && Hls.isSupported()) {
            hls = createHlsInstance();
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                if (resumeAt) video.currentTime = resumeAt;
                applyHlsDataMode();
                loaderOverlay.classList.add('hidden');
                showControls();
                primeMobileLandscapeFullscreen();
                scheduleCastPermissionWarmup();
                video.play().catch(() => {});
            });
            hls.on(Hls.Events.ERROR, (_e, d) => {
                if (!d.fatal) return;
                if (playbackUsesCdn && fallbackUrl && fallbackUrl !== url) {
                    playbackUsesCdn = false;
                    cdnVideoUrl = '';
                    cdnAudioUrls = {};
                    showToast('CDN indisponivel. Usando fonte original.');
                    startPlayerAt(fallbackUrl, fallbackType || 'auto', resumeAt);
                    return;
                }
                showError('Erro ao carregar stream', 'O video HLS encontrou um erro fatal.', {
                    stage: 'hls_error_audio_switch',
                    media_type: mediaType || 'm3u8',
                    media_url: url,
                    hls_details: d,
                    technical_message: d?.details || d?.type || 'Fatal HLS error',
                });
            });
        } else {
            video.src = url;
            video.load();
            const onReadyAt = () => {
                if (resumeAt) video.currentTime = resumeAt;
                loaderOverlay.classList.add('hidden');
                showControls();
                primeMobileLandscapeFullscreen();
                scheduleCastPermissionWarmup();
                video.play().catch(() => {});
            };
            const fallbackAt = setTimeout(() => {
                video.removeEventListener('canplay', onReadyAt);
                if (video.readyState >= 1) { onReadyAt(); }
                else { video.addEventListener('loadedmetadata', onReadyAt, { once: true }); }
            }, 8000);
            video.addEventListener('canplay', () => { clearTimeout(fallbackAt); onReadyAt(); }, { once: true });
            video.addEventListener('error', () => {
                clearTimeout(fallbackAt);
                if (playbackUsesCdn && fallbackUrl && fallbackUrl !== url) {
                    playbackUsesCdn = false;
                    playbackUsesSplitAudio = false;
                    cdnVideoUrl = '';
                    cdnAudioUrls = {};
                    configureSplitAudio('');
                    showToast('CDN interna indisponivel. Usando fonte original.');
                    startPlayerAt(fallbackUrl, fallbackType || 'auto', resumeAt);
                    return;
                }
                onVideoError();
            }, { once: true });
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
    setupDisplayMenu();
    setupSettingsMenu();
    setupCastMenu();
    if (!setupEmbeddedBrowserBlock()) {
        WatchProgress.init().then(() => loadVideo());
    }

})();
</script>
</body>
</html>
