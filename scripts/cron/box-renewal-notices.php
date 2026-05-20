<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);

define('PIPOCINE_DB_CONFIG_ONLY', true);
require_once $root . '/database/db.php';
require_once $root . '/models/v4/FamilyBoxModel.php';
require_once $root . '/services/v4/FamilyBoxService.php';

use Models\V4\FamilyBoxModel;
use Services\V4\FamilyBoxService;

$lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pipocine-box-renewal-notices.lock';
$lock = fopen($lockPath, 'c');

if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "[pipocine-box] outra execucao ainda esta rodando\n");
    exit(0);
}

try {
    $pdo = createPDO(DB_PIPO['name'], DB_PIPO['user_primary'], DB_PIPO['pass'])
        ?? createPDO(DB_PIPO['name'], DB_PIPO['user_fallback'], DB_PIPO['pass']);

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Conexao Pipocine indisponivel.');
    }

    $limit = 1000;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--limit=')) {
            $limit = (int) substr($arg, 8);
        }
    }

    $service = new FamilyBoxService(new FamilyBoxModel($pdo));
    $result = $service->processRenewalNotices($limit);

    $line = sprintf(
        "[pipocine-box] checked=%d created=%d skipped=%d errors=%d\n",
        (int) $result['checked'],
        (int) $result['created'],
        (int) $result['skipped'],
        (int) $result['errors']
    );

    fwrite(STDOUT, $line);
    exit($result['success'] ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, '[pipocine-box] erro: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    if (is_resource($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
