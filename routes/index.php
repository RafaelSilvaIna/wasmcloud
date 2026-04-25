<?php
require_once __DIR__ . '/../hooks/ProfileHook.php';
ProfileHook::enforceProfile();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (strpos($requestUri, '/api/') === 0) {
    
    require_once __DIR__ . '/../database/db.php';

    if (strpos($requestUri, '/api/auth/') === 0) {
        require_once __DIR__ . '/../models/AuthModel.php';
        require_once __DIR__ . '/../services/AuthService.php';
        require_once __DIR__ . '/../controllers/AuthController.php';

        $authModel = new AuthModel($pdoCineveo);
        $authService = new AuthService($authModel);
        $authController = new AuthController($authService);

        if ($requestUri === '/api/auth/status' && $requestMethod === 'GET') {
            $authController->getStatus();
        }
        if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
            $authController->login();
        }
    }

    if (strpos($requestUri, '/api/profiles/') === 0) {
        require_once __DIR__ . '/../helpers/AvatarHelper.php';
        require_once __DIR__ . '/../models/ProfileModel.php';
        require_once __DIR__ . '/../services/ProfileService.php';
        require_once __DIR__ . '/../controllers/ProfileController.php';

        $profileModel = new ProfileModel($pdo);
        $profileService = new ProfileService($profileModel);
        $profileController = new ProfileController($profileService);

        if ($requestUri === '/api/profiles/list' && $requestMethod === 'GET') {
            $profileController->list();
        }
        if ($requestUri === '/api/profiles/check-username' && $requestMethod === 'GET') {
            $profileController->checkUsername();
        }
        if ($requestUri === '/api/profiles/avatars' && $requestMethod === 'GET') {
            $profileController->getAvatars();
        }
        if ($requestUri === '/api/profiles/create' && $requestMethod === 'POST') {
            $profileController->create();
        }
        if ($requestUri === '/api/profiles/select' && $requestMethod === 'POST') {
            $profileController->select();
        }
    }

    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint não encontrado']);
    exit;
}