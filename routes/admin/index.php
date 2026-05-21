<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$rootDir = dirname(__DIR__, 2);

require_once $rootDir . '/database/db.php';
require_once $rootDir . '/helpers/admin/AdminJwt.php';
require_once $rootDir . '/models/admin/AdminModel.php';
require_once $rootDir . '/models/admin/AdminUserModerationModel.php';
require_once $rootDir . '/models/admin/AdminUsageMetricsModel.php';
require_once $rootDir . '/models/admin/AdminApiMetricsModel.php';
require_once $rootDir . '/models/admin/AdminSubscriptionModel.php';
require_once $rootDir . '/models/admin/AdminAdsReviewModel.php';
require_once $rootDir . '/models/admin/AdminRouteLockModel.php';
require_once $rootDir . '/models/admin/AdminStatusModel.php';
require_once $rootDir . '/models/ads/AdsCampaignModel.php';
require_once $rootDir . '/services/admin/AdminAuthService.php';
require_once $rootDir . '/services/admin/AdminUserModerationService.php';
require_once $rootDir . '/services/admin/AdminUsageMetricsService.php';
require_once $rootDir . '/services/admin/AdminApiMetricsService.php';
require_once $rootDir . '/services/admin/AdminSubscriptionService.php';
require_once $rootDir . '/services/admin/AdminAdsReviewService.php';
require_once $rootDir . '/services/admin/AdminRouteLockService.php';
require_once $rootDir . '/services/admin/AdminStatusService.php';
require_once $rootDir . '/controllers/admin/AdminController.php';
require_once $rootDir . '/controllers/admin/AdminUsageMetricsController.php';
require_once $rootDir . '/controllers/admin/AdminApiMetricsController.php';
require_once $rootDir . '/controllers/admin/AdminSubscriptionController.php';
require_once $rootDir . '/controllers/admin/SecurityAdminController.php';
require_once $rootDir . '/controllers/admin/AdminAdsReviewController.php';
require_once $rootDir . '/controllers/admin/AdminRouteLockController.php';
require_once $rootDir . '/controllers/admin/AdminStatusController.php';

use Controllers\Admin\AdminController;
use Controllers\Admin\AdminSubscriptionController;
use Controllers\Admin\AdminUsageMetricsController;
use Controllers\Admin\AdminApiMetricsController;
use Controllers\Admin\AdminAdsReviewController;
use Controllers\Admin\AdminRouteLockController;
use Controllers\Admin\AdminStatusController;
use Models\Admin\AdminModel;
use Models\Admin\AdminSubscriptionModel;
use Models\Admin\AdminUserModerationModel;
use Models\Admin\AdminUsageMetricsModel;
use Models\Admin\AdminApiMetricsModel;
use Models\Admin\AdminAdsReviewModel;
use Models\Admin\AdminRouteLockModel;
use Models\Admin\AdminStatusModel;
use Models\Ads\AdsCampaignModel;
use Services\Admin\AdminAuthService;
use Services\Admin\AdminSubscriptionService;
use Services\Admin\AdminUserModerationService;
use Services\Admin\AdminUsageMetricsService;
use Services\Admin\AdminApiMetricsService;
use Services\Admin\AdminAdsReviewService;
use Services\Admin\AdminRouteLockService;
use Services\Admin\AdminStatusService;

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
    if (str_starts_with($action, 'security/') || $action === 'security/dashboard') {
        $secController = new \Controllers\Admin\SecurityAdminController(
            new AdminAuthService($model),
            $pdo
        );
        $secController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'route-locks/')) {
        $routeLockController = new AdminRouteLockController(
            $auth,
            new AdminRouteLockService(new AdminRouteLockModel($pdo), $rootDir)
        );
        $routeLockController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'status/')) {
        $statusController = new AdminStatusController(
            $auth,
            new AdminStatusService(new AdminStatusModel($pdo))
        );
        $statusController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'metrics/')) {
        $metrics = new AdminUsageMetricsService(new AdminUsageMetricsModel($pdo));
        $metricsController = new AdminUsageMetricsController($auth, $metrics);
        $metricsController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'api-metrics/')) {
        $apiMetrics = new AdminApiMetricsService(new AdminApiMetricsModel($pdo));
        $apiMetricsController = new AdminApiMetricsController($auth, $apiMetrics);
        $apiMetricsController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'subscriptions')) {
        $subscriptions = new AdminSubscriptionService(new AdminSubscriptionModel($pdo));
        $subscriptionController = new AdminSubscriptionController($auth, $subscriptions);
        $subscriptionController->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'ads-reviews')) {
        $adsReviewController = new AdminAdsReviewController(
            $auth,
            new AdminAdsReviewService(
                $pdo,
                new AdminAdsReviewModel($pdo),
                new AdsCampaignModel($pdo)
            )
        );
        $adsReviewController->handle($action, $method);
        exit;
    }

    $moderation = new AdminUserModerationService(new AdminUserModerationModel($pdo));
    $controller = new AdminController($auth, $model, $moderation);
    $controller->handle($action, $method);
} catch (Throwable $e) {
    error_log('[API Admin] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
    ]);
}
