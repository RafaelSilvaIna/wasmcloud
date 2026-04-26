<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../hooks/ProfileHook.php';
ProfileHook::enforceProfile();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (strpos($requestUri, '/api/') === 0) {

    if (strpos($requestUri, '/api/auth/') === 0) {
        require_once __DIR__ . '/../models/AuthModel.php';
        require_once __DIR__ . '/../services/AuthService.php';
        require_once __DIR__ . '/../controllers/AuthController.php';

        $authModel = new AuthModel($pdoCineveo);
        $authService = new AuthService($authModel);
        $authController = new AuthController($authService);

        if ($requestUri === '/api/auth/status' && $requestMethod === 'GET') {
            $authController->getStatus(); exit;
        }
        if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
            $authController->login(); exit;
        }
    }

    if (strpos($requestUri, '/api/profiles/') === 0) {
        require_once __DIR__ . '/../helpers/AvatarHelper.php';
        require_once __DIR__ . '/../models/AuthModel.php';
        require_once __DIR__ . '/../models/ProfileModel.php';
        require_once __DIR__ . '/../services/ProfileService.php';
        require_once __DIR__ . '/../controllers/ProfileController.php';

        $authModel = new AuthModel($pdoCineveo);
        $profileModel = new ProfileModel($pdo);
        $profileService = new ProfileService($profileModel, $authModel);
        $profileController = new ProfileController($profileService);

        if ($requestUri === '/api/profiles/list' && $requestMethod === 'GET') {
            $profileController->list(); exit;
        }
        if ($requestUri === '/api/profiles/check-username' && $requestMethod === 'GET') {
            $profileController->checkUsername(); exit;
        }
        if ($requestUri === '/api/profiles/avatars' && $requestMethod === 'GET') {
            $profileController->getAvatars(); exit;
        }
        if ($requestUri === '/api/profiles/create' && $requestMethod === 'POST') {
            $profileController->create(); exit;
        }
        if ($requestUri === '/api/profiles/select' && $requestMethod === 'POST') {
            $profileController->select(); exit;
        }
        if ($requestUri === '/api/profiles/current' && $requestMethod === 'GET') {
            $profileController->current(); exit;
        }
        if ($requestUri === '/api/profiles/start-session' && $requestMethod === 'POST') {
            $profileController->startSession(); exit;
        }
        if ($requestUri === '/api/profiles/heartbeat' && $requestMethod === 'POST') {
            $profileController->heartbeat(); exit;
        }
        if ($requestUri === '/api/profiles/stop-session' && $requestMethod === 'POST') {
            $profileController->stopSession(); exit;
        }
        // NOVA ROTA API: Atualizar o Perfil
        if ($requestUri === '/api/profiles/update' && $requestMethod === 'POST') {
            $profileController->update(); exit;
        }
    }

    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint não encontrado']);
    exit;
}

// NOVA ROTA FRONT-END: Página de Gerenciar Perfis (Carrega o HTML)
if ($requestUri === '/manage-profiles') {
    require_once __DIR__ . '/../pages/manage-profiles.php';
    exit;
}