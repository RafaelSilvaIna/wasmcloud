<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../helpers/admin/AdminJwt.php';
require_once __DIR__ . '/../../models/admin/AdminModel.php';
require_once __DIR__ . '/../../services/admin/AdminAuthService.php';
require_once __DIR__ . '/../../controllers/admin/AdminController.php';

use Controllers\Admin\AdminController;
use Models\Admin\AdminModel;
use Services\Admin\AdminAuthService;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = ltrim(str_replace('/api/admin/', '', $requestUri), '/');
$method = $_SERVER['REQUEST_METHOD'];

try {
    if (!$pdo) {
        throw new RuntimeException('Banco Pipocine indisponivel.');
    }

    $model = new AdminModel($pdo);
    $auth = new AdminAuthService($model);
    $controller = new AdminController($auth, $model);
    $controller->handle($action, $method);
} catch (Throwable $e) {
    error_log('[API Admin] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
    ]);
}
