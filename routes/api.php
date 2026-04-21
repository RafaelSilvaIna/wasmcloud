<?php
use App\Core\Router;
use App\Controllers\Auth\AppAuthController;
use App\Controllers\Auth\AdminAuthController;
use App\Controllers\Admin\AccountManagerController;
use App\Controllers\Admin\SystemConfigController;
use App\Controllers\Admin\LogViewerController;
use App\Middlewares\AdminAuthMiddleware;
use App\Middlewares\MasterShieldMiddleware;

$router = new Router();

$router->add('POST', '/api/auth/login', [AppAuthController::class, 'login']);

$router->add('POST', '/api/master/accounts/create', [AccountManagerController::class, 'createAccount'], [MasterShieldMiddleware::class]);
$router->add('GET', '/api/admin/validate', [AdminAuthController::class, 'validateSession'], [AdminAuthMiddleware::class]);

$router->add('GET', '/api/master/config/get', [SystemConfigController::class, 'getConfigs'], [MasterShieldMiddleware::class]);
$router->add('PUT', '/api/master/config/update', [SystemConfigController::class, 'updateConfigs'], [MasterShieldMiddleware::class]);

$router->add('GET', '/api/master/logs/viewer', [LogViewerController::class, 'getExtendedLogs'], [MasterShieldMiddleware::class]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);