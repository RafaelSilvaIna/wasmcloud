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

if ($requestUri === '/select-profile') {
    require_once __DIR__ . '/select-profile.php';
    exit;
}

// NOVA ROTA: Direciona para a página de gerenciamento de perfis
if ($requestUri === '/manage-profiles') {
    require_once __DIR__ . '/manage-profiles.php';
    exit;
}

http_response_code(404);
echo "<h1>404 - Página não encontrada</h1>";
exit;