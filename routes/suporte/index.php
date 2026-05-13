<?php

declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';

// Helpers
require_once __DIR__ . '/../../helpers/suporte/SupportCipher.php';
require_once __DIR__ . '/../../helpers/suporte/SupportSession.php';
require_once __DIR__ . '/../../helpers/suporte/SupportRateLimit.php';

// Models
require_once __DIR__ . '/../../models/suporte/SupportChatModel.php';
require_once __DIR__ . '/../../models/suporte/SupportMessageModel.php';
require_once __DIR__ . '/../../models/suporte/SupportImageModel.php';

// Services
require_once __DIR__ . '/../../services/suporte/SupportChatService.php';
require_once __DIR__ . '/../../services/suporte/SupportMessageService.php';
require_once __DIR__ . '/../../services/suporte/SupportImageService.php';

// Controllers
require_once __DIR__ . '/../../controllers/suporte/SupportChatController.php';
require_once __DIR__ . '/../../controllers/suporte/SupportMessageController.php';
require_once __DIR__ . '/../../controllers/suporte/SupportImageController.php';
require_once __DIR__ . '/../../controllers/suporte/SupportAdminController.php';

// Cleanup hook
require_once __DIR__ . '/../../hooks/suporte/SupportStorageCleanupHook.php';

// Admin dependencies (loaded on demand inside the if-block below, but aliases declared here)
require_once __DIR__ . '/../../helpers/admin/AdminJwt.php';
require_once __DIR__ . '/../../models/admin/AdminModel.php';
require_once __DIR__ . '/../../services/admin/AdminAuthService.php';

use Controllers\Suporte\SupportChatController;
use Controllers\Suporte\SupportMessageController;
use Controllers\Suporte\SupportImageController;
use Controllers\Suporte\SupportAdminController;
use Models\Suporte\SupportChatModel;
use Models\Suporte\SupportMessageModel;
use Models\Suporte\SupportImageModel;
use Services\Suporte\SupportChatService;
use Services\Suporte\SupportMessageService;
use Services\Suporte\SupportImageService;
use Hooks\Suporte\SupportStorageCleanupHook;
use Models\Admin\AdminModel;
use Services\Admin\AdminAuthService;

// Database required
if (!$pdo) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Banco de dados indisponivel.']);
    exit;
}

// Register cleanup hook
SupportStorageCleanupHook::register($pdo);

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse action from URI:  /api/suporte/{action...}
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method     = $_SERVER['REQUEST_METHOD'];

// Strip prefix and query string
$action = preg_replace('#^/api/suporte/?#', '', $requestUri);
$action = trim($action, '/');

// -----------------------------------------------------------------------
// ADMIN ROUTES  — require admin JWT
// -----------------------------------------------------------------------
if (str_starts_with($action, 'admin/')) {
    $adminModel = new AdminModel($pdo);
    $adminAuth  = new AdminAuthService($adminModel);

    if (!$adminAuth->isRequestAllowed()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
        exit;
    }

    $admin = $adminAuth->currentAdmin();
    if (!$admin) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Autenticacao admin necessaria.']);
        exit;
    }

    $chatModel    = new SupportChatModel($pdo);
    $msgModel     = new SupportMessageModel($pdo);
    $imgModel     = new SupportImageModel($pdo);
    $chatService  = new SupportChatService($chatModel);
    $msgService   = new SupportMessageService($msgModel, $chatModel);
    $imgService   = new SupportImageService($imgModel);

    $adminController = new SupportAdminController(
        $chatService,
        $msgService,
        $imgService,
        $admin['display_name'] ?? $admin['email'] ?? 'Admin'
    );
    $adminController->handle($action, $method);
    exit;
}

// -----------------------------------------------------------------------
// IMAGE SERVE  — public with token validation
// -----------------------------------------------------------------------
if (str_starts_with($action, 'image/')) {
    $token        = substr($action, strlen('image/'));
    $imgModel     = new SupportImageModel($pdo);
    $imgService   = new SupportImageService($imgModel);
    $imgCtrl      = new SupportImageController($imgService);
    $imgCtrl->serve($token);
    exit;
}

// -----------------------------------------------------------------------
// USER ROUTES  — session_token required
// -----------------------------------------------------------------------
$chatModel   = new SupportChatModel($pdo);
$msgModel    = new SupportMessageModel($pdo);
$imgModel    = new SupportImageModel($pdo);
$chatService = new SupportChatService($chatModel);
$msgService  = new SupportMessageService($msgModel, $chatModel);
$imgService  = new SupportImageService($imgModel);

if (str_starts_with($action, 'chat/')) {
    $chatCtrl = new SupportChatController($chatService);
    $chatCtrl->handle($action, $method);
    exit;
}

if (str_starts_with($action, 'messages/')) {
    $msgCtrl = new SupportMessageController($msgService, $chatService, $imgService);
    $msgCtrl->handle($action, $method);
    exit;
}

// Fallback 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Endpoint nao encontrado.']);
