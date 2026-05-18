<?php
declare(strict_types=1);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestUri === '/pages/docs' || $requestUri === '/pages/docs/') {
    header('Location: /docs', true, 302);
    exit;
}

if ($requestUri === '/docs' || $requestUri === '/docs/') {
    header('Location: /docs/beneficios-familiar', true, 302);
    exit;
}

if ($requestUri === '/docs/beneficios-familiar') {
    require_once __DIR__ . '/beneficios-familiar.php';
    exit;
}

http_response_code(404);
echo '<!doctype html><meta charset="utf-8"><title>404</title><body style="background:#050505;color:#fff;font-family:sans-serif;display:grid;place-items:center;min-height:100vh;margin:0"><main style="text-align:center"><h1>404</h1><p>Documento nao encontrado.</p><a style="color:#e50914" href="/plan">Voltar aos planos</a></main></body>';
