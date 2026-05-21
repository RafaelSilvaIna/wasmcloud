<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);

require_once $rootDir . '/database/db.php';
require_once $rootDir . '/models/admin/AdminStatusModel.php';
require_once $rootDir . '/services/admin/AdminStatusService.php';

use Models\Admin\AdminStatusModel;
use Services\Admin\AdminStatusService;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if (!$pdo) {
        throw new RuntimeException('Banco indisponivel.');
    }

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $action = trim(str_replace('/api/status', '', $path), '/');
    $service = new AdminStatusService(new AdminStatusModel($pdo));

    if ($action === '' || $action === 'overview') {
        echo json_encode($service->publicOverview((int) ($_GET['days'] ?? 30)));
        exit;
    }

    if (preg_match('#^incident/(\d+)$#', $action, $m)) {
        echo json_encode($service->publicIncident((int) $m[1]));
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint publico de status nao encontrado.']);
} catch (InvalidArgumentException $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[PublicStatus] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno.']);
}
