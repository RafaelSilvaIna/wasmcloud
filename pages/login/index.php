<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/login' || $path === '/login/') {
    require __DIR__ . '/plataforma/index.php';
    exit;
}

if ($path === '/login/plataforma' || $path === '/login/plataforma/') {
    require __DIR__ . '/plataforma/index.php';
    exit;
}

if ($path === '/login/plataforma/register' || $path === '/login/plataforma/cadastro') {
    require __DIR__ . '/plataforma/register.php';
    exit;
}

if ($path === '/login/plataforma/methods' || $path === '/login/plataforma/methods/') {
    require __DIR__ . '/plataforma/methods.php';
    exit;
}

if ($path === '/login/qrcode' || $path === '/login/qrcode/') {
    require __DIR__ . '/qrcode/index.php';
    exit;
}

if ($path === '/login/qrcode/approve' || $path === '/login/qrcode/approve/') {
    require __DIR__ . '/qrcode/approve.php';
    exit;
}

if ($path === '/login/cineveo' || $path === '/login/cineveo/') {
    require __DIR__ . '/cineveo/index.php';
    exit;
}

if ($path === '/login/tv' || $path === '/login/tv/') {
    require __DIR__ . '/tv/index.php';
    exit;
}

http_response_code(404);
require __DIR__ . '/../login.php';
