<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('CINEVEO_SECURE_V2');
    session_start();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/error';

if ($uri === '/error/banned') {
    require __DIR__ . '/banned.php';
    exit;
}

if ($uri === '/error/suspended' || $uri === '/error') {
    require __DIR__ . '/suspended.php';
    exit;
}

http_response_code(404);
require __DIR__ . '/suspended.php';
