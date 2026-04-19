<?php
use App\Core\Router;
use App\Controllers\Auth\AdminAuthController;
use App\Middlewares\AdminAuthMiddleware;

$router = new Router();

$router->add('POST', '/api/admin/login', [AdminAuthController::class, 'login']);

$router->add('GET', '/api/admin/validate', [AdminAuthController::class, 'validateSession'], [AdminAuthMiddleware::class]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);