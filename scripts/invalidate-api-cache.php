<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/ApiPerformanceMiddleware.php';

$tags = array_slice($argv ?? [], 1);
if (!$tags) {
    $tags = ['api', 'catalog', 'search'];
}

\Middleware\ApiPerformanceMiddleware::invalidateTags($tags);

echo 'Invalidated API cache tags: ' . implode(', ', $tags) . PHP_EOL;
