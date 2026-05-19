<?php

declare(strict_types=1);

/**
 * ROUTER — API v4 Pipocine
 *
 * Sistema de PIN de Segurança
 * - Criação de PIN de 4 dígitos
 * - Validação de PIN com rate limiting (3 tentativas / 30 min)
 * - Verificação de existência de PIN
 * - Alteração e remoção de PIN
 *
 * Sistema de 2FA (Verificação em Duas Etapas)
 * - Setup com Google Authenticator
 * - Validação de códigos TOTP
 * - Dispositivos confiáveis
 * - Códigos de backup
 *
 * Acesso: /api/v4/*
 * Incluído por: routes/index.php
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ── Dependências base ────────────────────────────────────────
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../utils/v2/ResponseUtil.php';

// ── Helper ───────────────────────────────────────────────────
require_once __DIR__ . '/../../helpers/GoogleAuthenticatorHelper.php';

// ── Middleware ───────────────────────────────────────────────
require_once __DIR__ . '/../../middleware/PinRateLimitMiddleware.php';

// ── Classes do sistema de PIN ──────────────────────────────
require_once __DIR__ . '/../../models/v4/PinModel.php';
require_once __DIR__ . '/../../services/v4/PinService.php';
require_once __DIR__ . '/../../controllers/v4/PinController.php';

// ── Classes do sistema de 2FA ────────────────────────────────
require_once __DIR__ . '/../../models/v4/TwoFactorModel.php';
require_once __DIR__ . '/../../services/v4/TwoFactorService.php';
require_once __DIR__ . '/../../controllers/v4/TwoFactorController.php';
require_once __DIR__ . '/../../models/v4/PlatformUserModel.php';
require_once __DIR__ . '/../../services/v4/PlatformAuthService.php';
require_once __DIR__ . '/../../controllers/v4/PlatformAuthController.php';
require_once __DIR__ . '/../../models/v4/QrLoginModel.php';
require_once __DIR__ . '/../../services/v4/QrLoginService.php';
require_once __DIR__ . '/../../controllers/v4/QrLoginController.php';
require_once __DIR__ . '/../../helpers/v4/EvoPayClient.php';
require_once __DIR__ . '/../../models/v4/SubscriptionModel.php';
require_once __DIR__ . '/../../services/v4/SubscriptionService.php';
require_once __DIR__ . '/../../controllers/v4/SubscriptionController.php';
require_once __DIR__ . '/../../models/v4/AccountStatusModel.php';
require_once __DIR__ . '/../../services/v4/AccountStatusService.php';
require_once __DIR__ . '/../../controllers/v4/AccountStatusController.php';
require_once __DIR__ . '/../../models/v4/FamilyBoxModel.php';
require_once __DIR__ . '/../../services/v4/FamilyBoxService.php';
require_once __DIR__ . '/../../controllers/v4/FamilyBoxController.php';

use Middleware\PinRateLimitMiddleware;
use Models\V4\PinModel;
use Services\V4\PinService;
use Controllers\V4\PinController;
use Models\V4\TwoFactorModel;
use Services\V4\TwoFactorService;
use Controllers\V4\TwoFactorController;
use Models\V4\PlatformUserModel;
use Services\V4\PlatformAuthService;
use Controllers\V4\PlatformAuthController;
use Models\V4\QrLoginModel;
use Services\V4\QrLoginService;
use Controllers\V4\QrLoginController;
use Helpers\V4\EvoPayClient;
use Models\V4\SubscriptionModel;
use Services\V4\SubscriptionService;
use Controllers\V4\SubscriptionController;
use Models\V4\AccountStatusModel;
use Services\V4\AccountStatusService;
use Controllers\V4\AccountStatusController;
use Models\V4\FamilyBoxModel;
use Services\V4\FamilyBoxService;
use Controllers\V4\FamilyBoxController;

// ── Inicia sessão ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cabeçalhos CORS / JSON ───────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Extrai a sub-rota após /api/v4/ ──────────────────────────
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action     = ltrim(str_replace('/api/v4/', '', $requestUri), '/');
$method     = $_SERVER['REQUEST_METHOD'];

if (str_starts_with($action, 'auth/')) {
    $userModel = new PlatformUserModel($pdo);
    $twoFactorModel = new TwoFactorModel($pdo);
    $twoFactorService = new TwoFactorService($twoFactorModel);
    $service = new PlatformAuthService($userModel, $twoFactorService);
    $controller = new PlatformAuthController($service);

    $controller->handle($action, $method);
    exit;
}

if (str_starts_with($action, 'qr-login/')) {
    $qrModel = new QrLoginModel($pdo);
    $userModel = new PlatformUserModel($pdo);
    $service = new QrLoginService($qrModel, $userModel);
    $controller = new QrLoginController($service);

    $controller->handle($action, $method);
    exit;
}

// ── Verifica autenticação ────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    ResponseUtil::json([
        'success' => false,
        'error' => 'Usuário não autenticado'
    ], 401);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ── Despacha ─────────────────────────────────────────────────
try {

    // ── Rotas de PIN ─────────────────────────────────────────
    if (str_starts_with($action, 'pin')) {
        $model         = new PinModel($pdo);
        $middleware    = new PinRateLimitMiddleware($model);
        $service       = new PinService($model);
        $controller    = new PinController($service);
        
        // Verifica rate limit antes de permitir validações
        if ($action === 'pin/validate' || $action === 'pin/create' || $action === 'pin/change' || $action === 'pin/remove') {
            $rateLimit = $middleware->check($userId);
            
            if ($rateLimit['blocked']) {
                ResponseUtil::json([
                    'success' => false,
                    'blocked' => true,
                    'error' => $rateLimit['message'],
                    'remaining_seconds' => $rateLimit['remaining_seconds'],
                    'remaining_minutes' => $rateLimit['remaining_minutes']
                ], 429);
                exit;
            }
        }
        
        $controller->handle($action, $method);
        exit;
    }

    // ── Rotas de 2FA ─────────────────────────────────────────
    if (str_starts_with($action, '2fa')) {
        $model      = new TwoFactorModel($pdo);
        $service    = new TwoFactorService($model);
        $controller = new TwoFactorController($service);
        
        $controller->handle($action, $method);
        exit;
    }

    if (str_starts_with($action, 'subscription/')) {
        $model = new SubscriptionModel($pdo);
        $userModel = new PlatformUserModel($pdo);
        $evopay = new EvoPayClient(require __DIR__ . '/../../config/evopay.php');
        $service = new SubscriptionService($model, $userModel, $evopay);
        $controller = new SubscriptionController($service);

        $controller->handle($action, $method, $userId);
        exit;
    }

    if (str_starts_with($action, 'account/')) {
        $model = new AccountStatusModel($pdo);
        $service = new AccountStatusService($model);
        $controller = new AccountStatusController($service);

        $controller->handle($action, $method, $userId);
        exit;
    }

    if (str_starts_with($action, 'box/') || str_starts_with($action, 'family/')) {
        $model = new FamilyBoxModel($pdo);
        $service = new FamilyBoxService($model);
        $controller = new FamilyBoxController($service);

        $controller->handle($action, $method, $userId);
        exit;
    }

    // ── Limpeza de tentativas antigas (manutenção) ───────────
    if ($action === 'cleanup/pin-attempts' && $method === 'POST') {
        // Verifica se é um administrador (pode adicionar verificação específica)
        $model = new PinModel($pdo);
        $middleware = new PinRateLimitMiddleware($model);
        
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $hours = isset($data['hours']) ? (int) $data['hours'] : 24;
        
        $result = $middleware->cleanup(max(1, $hours));
        
        ResponseUtil::json([
            'success' => $result,
            'message' => $result ? 'Tentativas antigas removidas' : 'Erro ao limpar tentativas'
        ]);
        exit;
    }

    // ── Rota não encontrada ──────────────────────────────────
    ResponseUtil::json([
        'success' => false,
        'error' => 'Rota v4 não encontrada'
    ], 404);

} catch (Throwable $e) {
    error_log("[API v4] Erro: " . $e->getMessage());
    
    ResponseUtil::json([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'detalhe' => $e->getMessage(),
        'arquivo' => basename($e->getFile()),
        'linha' => $e->getLine(),
    ], 500);
}
