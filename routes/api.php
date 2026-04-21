<?php
use App\Core\Router;
use App\Controllers\Auth\AppAuthController;
use App\Controllers\Auth\AdminAuthController;
use App\Controllers\Admin\AccountManagerController;
use App\Controllers\Admin\SystemConfigController;
use App\Controllers\Admin\LogViewerController;
use App\Controllers\Admin\BrandingController;
use App\Controllers\Admin\ThemeController;
use App\Controllers\Public\ImageDeliveryController;
use App\Controllers\Public\ThemeDeliveryController;
use App\Middlewares\AdminAuthMiddleware;
use App\Middlewares\MasterShieldMiddleware;

$router = new Router();

$router->add('POST', '/api/auth/login', [AppAuthController::class, 'login']);
$router->add('GET', '/api/admin/validate', [AdminAuthController::class, 'validateSession'], [AdminAuthMiddleware::class]);

$router->add('GET', '/api/branding/info', [ImageDeliveryController::class, 'getBrandingInfo']);
$router->add('GET', '/api/img/logo', [ImageDeliveryController::class, 'serveLogo']);
$router->add('GET', '/api/img/favicon', [ImageDeliveryController::class, 'serveFavicon']);
$router->add('GET', '/api/img/icon', [ImageDeliveryController::class, 'serveIcon']);
$router->add('GET', '/api/public/theme', [ThemeDeliveryController::class, 'getThemeColors']);

$router->add('POST', '/api/master/accounts/create', [AccountManagerController::class, 'createAccount'], [MasterShieldMiddleware::class]);

$router->add('GET', '/api/master/config/get', [SystemConfigController::class, 'getConfigs'], [MasterShieldMiddleware::class]);
$router->add('PUT', '/api/master/config/update', [SystemConfigController::class, 'updateConfigs'], [MasterShieldMiddleware::class]);

$router->add('GET', '/api/master/logs/viewer', [LogViewerController::class, 'getExtendedLogs'], [MasterShieldMiddleware::class]);

$router->add('POST', '/api/master/branding/update', [BrandingController::class, 'updateBranding'], [MasterShieldMiddleware::class]);

$router->add('PUT', '/api/master/theme/update', [ThemeController::class, 'update'], [MasterShieldMiddleware::class]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);