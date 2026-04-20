<?php
use App\Core\Router;
use App\Controllers\Auth\AdminAuthController;
use App\Controllers\Auth\AppAuthController;
use App\Controllers\Admin\AccountManagerController;
use App\Middlewares\AdminAuthMiddleware;
use App\Middlewares\MasterAuthMiddleware;

$router = new Router();

// Logins
$router->add('POST', '/api/admin/login', [AdminAuthController::class, 'login']);
$router->add('POST', '/api/auth/login', [AppAuthController::class, 'login']);

// Criacao de Contas (Apenas Master)
$router->add('POST', '/api/master/accounts/create', [AccountManagerController::class, 'createAccount'], [MasterAuthMiddleware::class]);

// Validacao Admin (mantida)
$router->add('GET', '/api/admin/validate', [AdminAuthController::class, 'validateSession'], [AdminAuthMiddleware::class]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);