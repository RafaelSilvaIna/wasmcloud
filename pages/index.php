<?php
require_once __DIR__ . '/../routes/index.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestUri === '/' || $requestUri === '/home') {
    require_once __DIR__ . '/home.php';
    exit;
}

if ($requestUri === '/login') {
    require_once __DIR__ . '/login.php';
    exit;
}

http_response_code(404);
echo "<h1>404 - Página não encontrada</h1>";
exit;