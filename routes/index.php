<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (strpos($requestUri, '/api/') === 0) {
    
    require_once __DIR__ . '/../database/db.php';
    require_once __DIR__ . '/../models/AuthModel.php';
    require_once __DIR__ . '/../services/AuthService.php';
    require_once __DIR__ . '/../controllers/AuthController.php';

    $authModel = new AuthModel($pdoCineveo);
    $authService = new AuthService($authModel);
    $authController = new AuthController($authService);

    if ($requestUri === '/api/auth/status' && $requestMethod === 'GET') {
        $authController->getStatus();
    }

    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint Not Found']);
    exit;
}