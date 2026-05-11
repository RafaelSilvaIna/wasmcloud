<?php

declare(strict_types=1);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestUri === '/pages/plan' || $requestUri === '/pages/plan/') {
    header('Location: /plan', true, 302);
    exit;
}

if ($requestUri === '/plan' || $requestUri === '/plan/') {
    require_once __DIR__ . '/main.php';
    exit;
}

if ($requestUri === '/plan/checkout') {
    require_once __DIR__ . '/checkout.php';
    exit;
}

if ($requestUri === '/plan/pix') {
    require_once __DIR__ . '/pix.php';
    exit;
}

if ($requestUri === '/plan/payment' || str_starts_with($requestUri, '/plan/payment/active=')) {
    require_once __DIR__ . '/payment.php';
    exit;
}

if ($requestUri === '/plan/me') {
    require_once __DIR__ . '/me.php';
    exit;
}

http_response_code(404);
echo '<!doctype html><meta charset="utf-8"><title>404</title><body style="background:#050505;color:#fff;font-family:sans-serif;display:grid;place-items:center;min-height:100vh;margin:0"><main style="text-align:center"><h1>404</h1><p>Rota de plano nao encontrada.</p><a style="color:#f5c451" href="/plan">Voltar aos planos</a></main></body>';
