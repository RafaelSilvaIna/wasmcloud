<?php

declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../middleware/GlobalSecurityMiddleware.php';
require_once __DIR__ . '/../../controllers/cdn/CdnController.php';

\Middleware\GlobalSecurityMiddleware::handle($pdo ?? null);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
$controller = new \Controllers\Cdn\CdnController(
    new \Middleware\Cdn\CdnSecurityMiddleware(),
    new \Services\Cdn\CdnSourceResolver($pdoCineveo ?? null),
    new \Services\Cdn\CdnStreamService()
);

if (preg_match('#^/cdn/video/([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\.mp4$#', $path, $m)) {
    $controller->video($m[1]);
    exit;
}

if (preg_match('#^/cdn/audio/(standard|smart_eq|virtual_surround|safe_boost)/([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\.m4a$#', $path, $m)) {
    $controller->audio($m[1], $m[2]);
    exit;
}

\Helpers\Cdn\CdnHeaders::noStore();
http_response_code(404);
echo 'CDN endpoint nao encontrado.';
