<?php
/**
 * ARQUIVO: pages/index.php
 * DESCRIÇÃO: Roteador principal das páginas do frontend do Pipocine.
 *
 * COMO O .HTACCESS ROTEIA PARA CÁ:
 *   RewriteRule ^pages/(.*)$ pages/index.php [QSA,L]
 *
 * Qualquer URL que não seja arquivo/pasta físico e não comece com /assets/ ou /api/v2/
 * eventualmente cai aqui via pages/index.php.
 */

// routes/index.php já inclui database/db.php, hooks e trata todas as rotas /api/*
// Se a requisição for de API, ele responde com exit e nunca chega ao código abaixo.
require_once __DIR__ . '/../routes/index.php';


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ─────────────────────────────────────────────────────────────────────────────
// ROTAS DE PÁGINAS (só chega aqui se NÃO for rota de API)
// ─────────────────────────────────────────────────────────────────────────────

// ROTA: Landing Page pública (Raiz)
if ($requestUri === '/' || $requestUri === '/main') {
    require_once __DIR__ . '/main.php';
    exit;
}

// ROTA: Home (Dashboard autenticado)
if ($requestUri === '/home') {
    require_once __DIR__ . '/home.php';
    exit;
}

// ROTA: Login
if ($requestUri === '/login') {
    require_once __DIR__ . '/login.php';
    exit;
}

// ROTA: Seleção de Perfil
if ($requestUri === '/select-profile') {
    require_once __DIR__ . '/select-profile.php';
    exit;
}

// ROTA: Gerenciamento de Perfis
if ($requestUri === '/manage-profiles') {
    require_once __DIR__ . '/manage-profiles.php';
    exit;
}

// ROTA: Página de Exibição — detalhes + lista de episódios (/view?id=X&type=serie)
if ($requestUri === '/view') {
    require_once __DIR__ . '/view.php';
    exit;
}

// ROTA: Player de vídeo (/player?id=X&type=serie&s=1&e=1  OU  /player?id=X&type=filme)
if ($requestUri === '/player') {
    require_once __DIR__ . '/player.php';
    exit;
}

// ROTA: Informações completas /info=<tmdb_id>
if (preg_match('/^\/info=(\d+)$/', $requestUri, $m)) {
    $_GET['tmdb_id'] = (int) $m[1];
    require_once __DIR__ . '/info.php';
    exit;
}

// ROTA: Informações sem ID (fallback)
if ($requestUri === '/info') {
    require_once __DIR__ . '/info.php';
    exit;
}

// ROTA: Minha Lista — biblioteca do perfil (histórico, salvos e curtidos)
if ($requestUri === '/minha-lista') {
    require_once __DIR__ . '/minha-lista.php';
    exit;
}

// ROTA: Página de Plataforma (/plataforma?marca=netflix)
if ($requestUri === '/plataforma') {
    require_once __DIR__ . '/plataforma.php';
    exit;
}

// ROTA: Busca de conteudo (/busca?q=...)
if ($requestUri === '/busca') {
    require_once __DIR__ . '/busca.php';
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ROTAS DO PLAYER — formatos alternativos de URL gerados pelo cineveo
// ─────────────────────────────────────────────────────────────────────────────

// ROTA: /assistir/{slug}-t{temporada}-ep{episodio}.html
// Exemplo: /assistir/breaking-bad-t1-ep1.html
if (preg_match('/^\/assistir\/(.+)-t(\d+)-ep(\d+)\.html$/', $requestUri, $m)) {
    $serieSlug = $m[1];
    $season    = (int) $m[2];
    $episode   = (int) $m[3];

    // Resolve slug → id_tmdb no banco cineveo
    $tmdbIdResolved = null;
    if ($pdoCineveo) {
        try {
            $stmtSlug = $pdoCineveo->prepare(
                "SELECT id_tmdb FROM conteudo WHERE slug = ? AND tipo = 'serie' LIMIT 1"
            );
            $stmtSlug->execute([$serieSlug]);
            $tmdbIdResolved = $stmtSlug->fetchColumn() ?: null;
        } catch (Throwable $e) {}
    }

    if (!$tmdbIdResolved) {
        http_response_code(404);
        echo _pip_404();
        exit;
    }

    $_GET['type'] = 'serie';
    $_GET['id']   = (int) $tmdbIdResolved;
    $_GET['s']    = $season;
    $_GET['e']    = $episode;
    require_once __DIR__ . '/player.php';
    exit;
}

// ROTA: /assistir/serie/<tmdb_id>/<temporada>/<episodio>  (formato numérico — fallback)
if (preg_match('/^\/assistir\/serie\/(\d+)\/(\d+)\/(\d+)$/', $requestUri, $m)) {
    $_GET['type'] = 'serie';
    $_GET['id']   = (int) $m[1];
    $_GET['s']    = (int) $m[2];
    $_GET['e']    = (int) $m[3];
    require_once __DIR__ . '/player.php';
    exit;
}

// ROTA: /assistir/filme/<tmdb_id>  (formato numérico — fallback)
if (preg_match('/^\/assistir\/filme\/(\d+)$/', $requestUri, $m)) {
    $_GET['type'] = 'filme';
    $_GET['id']   = (int) $m[1];
    require_once __DIR__ . '/player.php';
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// FALLBACK 404
// ─────────────────────────────────────────────────────────────────────────────
http_response_code(404);
echo _pip_404();
exit;

// Helper 404 inline
function _pip_404(): string {
    return "
    <div style='background:#0a0a0a;color:#fff;height:100vh;display:flex;
                align-items:center;justify-content:center;font-family:sans-serif;'>
        <div style='text-align:center;'>
            <h1 style='font-size:4rem;margin:0;font-weight:700;'>404</h1>
            <p style='color:#888;margin:12px 0 24px;'>Oops! A página que você procura não existe.</p>
            <a href='/home' style='color:#e50914;text-decoration:none;font-weight:600;
                                   font-size:14px;border:1px solid #e50914;
                                   padding:10px 24px;border-radius:6px;
                                   transition:background .2s;'>
                Voltar para a Home
            </a>
        </div>
    </div>";
}
