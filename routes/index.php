<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../hooks/SecurityChallengeHook.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '/security/continue' && $requestMethod === 'POST') {
    require_once __DIR__ . '/../security/config/SecurityConfig.php';
    require_once __DIR__ . '/../security/ratelimit/ClientRequestGuard.php';
    require_once __DIR__ . '/../security/storage/DbSecurityStore.php';
    require_once __DIR__ . '/../security/logger/SecurityLogger.php';
    require_once __DIR__ . '/../security/mitigation/BanManager.php';
    require_once __DIR__ . '/../security/mitigation/QuarantineManager.php';
    require_once __DIR__ . '/../security/mitigation/SecurityBlockResponder.php';

    $token = (string) ($_POST['token'] ?? '');
    $sessionToken = (string) ($_SESSION['_sec_resume_token'] ?? '');
    $sessionIp = (string) ($_SESSION['_sec_resume_ip'] ?? '');
    $resolvedIp = \Security\RateLimit\ClientRequestGuard::resolveClientIp();
    $signedResume = \Security\Mitigation\SecurityBlockResponder::validateResumeToken($token, $resolvedIp);
    $legacySessionResume = $token !== ''
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token)
        && ($sessionIp === '' || $sessionIp === $resolvedIp);

    $targetPath = $signedResume['target'] ?? (string) ($_POST['target'] ?? '/home');
    $targetPath = parse_url($targetPath, PHP_URL_PATH) ?: '/home';
    if (!str_starts_with($targetPath, '/') || str_starts_with($targetPath, '/security/')) {
        $targetPath = '/home';
    }

    if (($signedResume !== null || $legacySessionResume) && $pdo) {
        $store = new \Security\Storage\DbSecurityStore($pdo);
        foreach (array_unique(array_filter([$resolvedIp, $sessionIp])) as $ipToRelease) {
            $store->deactivateActiveBans($ipToRelease);
            $store->deactivateActiveQuarantine($ipToRelease);
            $store->clearRateLimitWindows($ipToRelease);
            $store->resetIpReputation($ipToRelease);
        }

        $logger = new \Security\Logger\SecurityLogger($store);
        $banManager = new \Security\Mitigation\BanManager($store, $logger);
        $quarantineManager = new \Security\Mitigation\QuarantineManager($store, $logger);
        foreach (array_unique(array_filter([$resolvedIp, $sessionIp])) as $ipToRelease) {
            $banManager->invalidateCache($ipToRelease);
            $quarantineManager->invalidateCache($ipToRelease);
        }

        if (function_exists('apcu_delete')) {
            foreach (array_unique(array_filter([$resolvedIp, $sessionIp])) as $ipToRelease) {
                apcu_delete('sec_rep_' . md5($ipToRelease));
            }
        }

        \Security\RateLimit\ClientRequestGuard::issueTemporaryBypass($resolvedIp, 180);

        $_SESSION['req_cnt'] = 0;
        $_SESSION['req_time'] = time();
        unset($_SESSION['_sec_resume_token'], $_SESSION['_sec_resume_ip'], $_SESSION['_sec_resume_target']);
        header('Location: ' . $targetPath);
        exit;
    }

    if ($pdo) {
        $store = new \Security\Storage\DbSecurityStore($pdo);
        $store->deactivateActiveBans($resolvedIp);
        $store->deactivateActiveQuarantine($resolvedIp);
        $store->clearRateLimitWindows($resolvedIp);
        $store->resetIpReputation($resolvedIp);

        $logger = new \Security\Logger\SecurityLogger($store);
        (new \Security\Mitigation\BanManager($store, $logger))->invalidateCache($resolvedIp);
        (new \Security\Mitigation\QuarantineManager($store, $logger))->invalidateCache($resolvedIp);
        if (function_exists('apcu_delete')) {
            apcu_delete('sec_rep_' . md5($resolvedIp));
        }
    }

    \Security\RateLimit\ClientRequestGuard::issueTemporaryBypass($resolvedIp, 180);
    unset($_SESSION['_sec_resume_token'], $_SESSION['_sec_resume_ip'], $_SESSION['_sec_resume_target']);
    header('Location: /home');
    exit;

    exit('Confirmação inválida.');
}

if ($requestUri === '/security/challenge' && $requestMethod === 'GET') {
    require_once __DIR__ . '/../security/config/SecurityConfig.php';
    require_once __DIR__ . '/../security/ratelimit/ClientRequestGuard.php';
    require_once __DIR__ . '/../security/mitigation/SecurityBlockResponder.php';
    require_once __DIR__ . '/../components/SuspiciousActivityModal.php';
    $target = (string) ($_SESSION['_sec_resume_target'] ?? '/home');
    $ip = \Security\RateLimit\ClientRequestGuard::resolveClientIp();
    $token = \Security\Mitigation\SecurityBlockResponder::createResumeToken($ip, $target);
    $_SESSION['_sec_resume_token'] = $token;
    $_SESSION['_sec_resume_ip'] = $ip;

    SuspiciousActivityModal::render($token, $target);
    exit;
}

// =========================================================
// GLOBAL SECURITY LAYER — Anti-DDoS / Anti-Bot
// Executado antes de QUALQUER rota — cobre todos os sub-
// roteadores presentes e futuros carregados abaixo.
// =========================================================
require_once __DIR__ . '/../middleware/GlobalSecurityMiddleware.php';
\Middleware\GlobalSecurityMiddleware::handle($pdo ?? null);
\SecurityChallengeHook::injectClientBridge();

require_once __DIR__ . '/../hooks/ProfileHook.php';
require_once __DIR__ . '/../hooks/v4/AccountStatusHook.php';
require_once __DIR__ . '/../hooks/v4/SubscriptionHook.php';
require_once __DIR__ . '/../hooks/device/DeviceHook.php';
ProfileHook::redirectTvToQrLogin();
\Hooks\V4\AccountStatusHook::enforce($pdo);
\Hooks\V4\SubscriptionHook::enforcePlanAccess($pdo);
ProfileHook::enforceProfile($pdo);
\Hooks\Device\DeviceHook::enforce($pdo);

if (strpos($requestUri, '/cdn/') === 0) {
    require_once __DIR__ . '/cdn/index.php';
    exit;
}

if (strpos($requestUri, '/api/') === 0) {

    if (strpos($requestUri, '/api/admin/') === 0) {
        require_once __DIR__ . '/admin/index.php';
        exit;
    }

    if (strpos($requestUri, '/api/status/') === 0) {
        require_once __DIR__ . '/status/index.php';
        exit;
    }

    if (strpos($requestUri, '/api/v4/') === 0) {
        require_once __DIR__ . '/v4/index.php';
        exit;
    }

    if (strpos($requestUri, '/api/devices/') === 0) {
        require_once __DIR__ . '/devices/index.php';
        exit;
    }

    // =========================================================
    // ROTAS DE AUTENTICAÇÃOa
    // =========================================================
    if (strpos($requestUri, '/api/auth/') === 0) {
        require_once __DIR__ . '/../helpers/GoogleAuthenticatorHelper.php';
        require_once __DIR__ . '/../models/AuthModel.php';
        require_once __DIR__ . '/../models/v4/TwoFactorModel.php';
        require_once __DIR__ . '/../services/v4/TwoFactorService.php';
        require_once __DIR__ . '/../services/AuthService.php';
        require_once __DIR__ . '/../controllers/AuthController.php';

        $authModel = new AuthModel($pdoCineveo, $pdo);
        $twoFactorModel = new \Models\V4\TwoFactorModel($pdo);
        $twoFactorService = new \Services\V4\TwoFactorService($twoFactorModel);
        $authService = new AuthService($authModel, $twoFactorService);
        $authController = new AuthController($authService);

        if ($requestUri === '/api/auth/status' && $requestMethod === 'GET') {
            $authController->getStatus();
            exit;
        }
        if ($requestUri === '/api/auth/logout' && $requestMethod === 'POST') {
            if (!empty($_SESSION['user_id'])) {
                require_once __DIR__ . '/../models/device/DeviceModel.php';
                require_once __DIR__ . '/../helpers/device/DeviceFingerprint.php';
                require_once __DIR__ . '/../services/device/DeviceService.php';

                $deviceService = new \Services\Device\DeviceService(new \Models\Device\DeviceModel($pdo));
                $deviceService->release((int) $_SESSION['user_id']);
            }

            if (!empty($_SESSION['profile_id'])) {
                try {
                    $pdo->prepare("
                        UPDATE profile_active_sessions
                        SET is_active = 0
                        WHERE profile_id = ? AND session_id = ?
                    ")->execute([(int) $_SESSION['profile_id'], session_id()]);
                } catch (Throwable $e) {}
            }

            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            setcookie('cineveo_token', '', time() - 3600, '/', '', false, true);
            setcookie('pipocine_token', '', time() - 3600, '/', '', false, true);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
            exit;
        }
        if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
            $authController->login();
            exit;
        }
        if ($requestUri === '/api/auth/verify-2fa' && $requestMethod === 'POST') {
            $authController->verifyTwoFactorLogin();
            exit;
        }
        if ($requestUri === '/api/auth/activate-profile' && $requestMethod === 'POST') {
            $authController->activateProfile();
            exit;
        }
    }

    // =========================================================
    // ROTAS DE PERFIS
    // =========================================================
    if (strpos($requestUri, '/api/profiles/') === 0) {
        require_once __DIR__ . '/../helpers/AvatarHelper.php';
        require_once __DIR__ . '/../models/AuthModel.php';    // ADICIONADO: Necessário para checar o plano Premium
        require_once __DIR__ . '/../models/ProfileModel.php';
        require_once __DIR__ . '/../services/ProfileService.php';
        require_once __DIR__ . '/../controllers/ProfileController.php';

        // CORREÇÃO DO ERRO 500: Instanciamos o AuthModel e passamos para o ProfileService
        $authModel = new AuthModel($pdoCineveo, $pdo);
        $profileModel = new ProfileModel($pdo);
        $profileService = new ProfileService($profileModel, $authModel);
        $profileController = new ProfileController($profileService);

        if ($requestUri === '/api/profiles/list' && $requestMethod === 'GET') {
            $profileController->list();
            exit;
        }
        if ($requestUri === '/api/profiles/check-username' && $requestMethod === 'GET') {
            $profileController->checkUsername();
            exit;
        }
        if ($requestUri === '/api/profiles/avatars' && $requestMethod === 'GET') {
            $profileController->getAvatars();
            exit;
        }
        if ($requestUri === '/api/profiles/create' && $requestMethod === 'POST') {
            $profileController->create();
            exit;
        }
        if ($requestUri === '/api/profiles/select' && $requestMethod === 'POST') {
            $profileController->select();
            exit;
        }
        if ($requestUri === '/api/profiles/current' && $requestMethod === 'GET') {
            $profileController->current();
            exit;
        }
        if ($requestUri === '/api/profiles/start-session' && $requestMethod === 'POST') {
            $profileController->startSession();
            exit;
        }
        if ($requestUri === '/api/profiles/heartbeat' && $requestMethod === 'POST') {
            $profileController->heartbeat();
            exit;
        }
        if ($requestUri === '/api/profiles/stop-session' && $requestMethod === 'POST') {
            $profileController->stopSession();
            exit;
        }
        if ($requestUri === '/api/profiles/update' && $requestMethod === 'POST') {
            $profileController->update();
            exit;
        }
        if ($requestUri === '/api/profiles/delete' && $requestMethod === 'POST') {
            $profileController->delete();
            exit;
        }
        if ($requestUri === '/api/profiles/issue-creation-token' && $requestMethod === 'POST') {
            $profileController->issueCreationToken();
            exit;
        }
        if ($requestUri === '/api/profiles/issue-edit-token' && $requestMethod === 'GET') {
            $profileController->issueEditToken();
            exit;
        }
        // Rota de criação com token (substitui /api/profiles/create para o novo fluxo)
        if ($requestUri === '/api/profiles/create-with-token' && $requestMethod === 'POST') {
            $profileController->createWithToken();
            exit;
        }
    }

    // Se a rota da API não for encontrada
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint não encontrado']);
    exit;
}

// =========================================================
// ROTAS DE FRONT-END (PÁGINAS)
// =========================================================
if ($requestUri === '/manage-profiles') {
    require_once __DIR__ . '/../pages/manage-profiles.php';
    exit;
}

if ($requestUri === '/box' || $requestUri === '/box/') {
    require_once __DIR__ . '/../pages/box.php';
    exit;
}

if ($requestUri === '/settings') {
    require_once __DIR__ . '/../pages/settings.php';
    exit;
}

if (preg_match('/^\/d2xs8d3sdfsegequ6249f=([A-Za-z0-9_-]+)$/', $requestUri, $m)) {
    $_GET['route'] = $m[1];
    require_once __DIR__ . '/../pages/d2xs8d3sdfsegequ6249f.php';
    exit;
}

if ($requestUri === '/d2xs8d3sdfsegequ6249f' || $requestUri === '/d2xs8d3sdfsegequ6249f/') {
    require_once __DIR__ . '/../pages/d2xs8d3sdfsegequ6249f.php';
    exit;
}

if ($requestUri === '/error' || str_starts_with($requestUri, '/error/')) {
    require_once __DIR__ . '/../pages/error/index.php';
    exit;
}

if ($requestUri === '/plan' || $requestUri === '/plan/') {
    require_once __DIR__ . '/../pages/plan/main.php';
    exit;
}

if ($requestUri === '/plan/checkout') {
    require_once __DIR__ . '/../pages/plan/checkout.php';
    exit;
}

if ($requestUri === '/plan/pix') {
    require_once __DIR__ . '/../pages/plan/pix.php';
    exit;
}

if ($requestUri === '/plan/payment' || str_starts_with($requestUri, '/plan/payment/active=')) {
    require_once __DIR__ . '/../pages/plan/payment.php';
    exit;
}

if ($requestUri === '/plan/me') {
    require_once __DIR__ . '/../pages/plan/me.php';
    exit;
}
