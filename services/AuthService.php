<?php
class AuthService {
    private $authModel;
    private $twoFactorService;

    public function __construct(AuthModel $authModel, ?\Services\V4\TwoFactorService $twoFactorService = null) {
        $this->authModel = $authModel;
        $this->twoFactorService = $twoFactorService;
    }

    public function authenticate(string $email, string $password, ?string $deviceToken = null): array {
        $user = $this->authModel->getUserByEmail($email);
        $hash = !empty($user['password']) ? $user['password'] : ($user['password_hash'] ?? '');
        if (!$user || !$hash || !password_verify($password, $hash)) {
            return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
        }

        if ($this->twoFactorService) {
            $userId = (int)$user['id'];
            $twoFactorStatus = $this->twoFactorService->getStatus($userId);

            if (!empty($twoFactorStatus['enabled'])) {
                if ($deviceToken && $this->twoFactorService->isTrustedDevice($userId, $deviceToken)) {
                    return $this->createAuthenticatedSession($user);
                }

                return $this->createTwoFactorChallenge($userId);
            }
        }

        return $this->createAuthenticatedSession($user);
    }

    private function createAuthenticatedSession(array $user): array {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);
        
        if ($this->authModel->createSession((int)$user['id'], $tokenHash, $expiresAt)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'] ?: $user['name'];
            $_SESSION['profile_pic_url'] = $user['profile_pic_url'];
            $_SESSION['session_id'] = $sessionId;
            setcookie('cineveo_token', $rawToken, time() + 2592000, '/', '', false, true);
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Erro ao criar sessão.'];
    }

    public function verifyTwoFactorLogin(string $verifyToken, string $code, bool $rememberDevice = false, ?string $deviceToken = null): array {
        if (!$this->twoFactorService) {
            return ['success' => false, 'message' => 'Verificacao em duas etapas indisponivel.'];
        }

        $verifyToken = trim($verifyToken);
        $code = preg_replace('/\D+/', '', trim($code));

        if ($verifyToken === '' || $code === '') {
            return ['success' => false, 'message' => 'Token e codigo sao obrigatorios.'];
        }

        $tokenHash = hash('sha256', $verifyToken);
        $challenge = $this->authModel->getTwoFactorChallenge($tokenHash);

        if (!$challenge) {
            $this->clearTwoFactorChallengeCookie();
            return ['success' => false, 'message' => 'Verificacao expirada. Entre novamente.'];
        }

        $result = $this->twoFactorService->verifyLogin(
            (int)$challenge['user_id'],
            $code,
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $rememberDevice ? $deviceToken : null
        );

        if (empty($result['success'])) {
            return [
                'success' => false,
                'message' => $result['error'] ?? 'Codigo invalido.',
                'blocked' => $result['blocked'] ?? false,
                'remaining_attempts' => $result['remaining_attempts'] ?? null
            ];
        }

        $user = $this->authModel->getUserData((int)$challenge['user_id']);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario nao encontrado.'];
        }

        $sessionResult = $this->createAuthenticatedSession($user);
        if (empty($sessionResult['success'])) {
            return $sessionResult;
        }

        $this->authModel->consumeTwoFactorChallenge($tokenHash);
        $this->authModel->deleteTwoFactorChallenge($tokenHash);
        $this->clearTwoFactorChallengeCookie();

        return [
            'success' => true,
            'used_backup' => $result['used_backup'] ?? false
        ];
    }

    private function createTwoFactorChallenge(int $userId): array {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        if (!$this->authModel->createTwoFactorChallenge($userId, $tokenHash, $expiresAt)) {
            return ['success' => false, 'message' => 'Erro ao iniciar verificacao em duas etapas.'];
        }

        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['profile_pic_url'], $_SESSION['profile_id']);
        setcookie('cineveo_token', '', time() - 3600, '/', '', false, true);
        setcookie('cineveo_2fa_challenge', $rawToken, time() + 600, '/', '', false, true);

        return [
            'success' => true,
            'requires_2fa' => true,
            'verify_token' => $rawToken,
            'redirect' => '/verify=' . rawurlencode($rawToken)
        ];
    }

    private function clearTwoFactorChallengeCookie(): void {
        setcookie('cineveo_2fa_challenge', '', time() - 3600, '/', '', false, true);
    }

    private function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $parts = explode(',', $ip);
                    $ip = trim($parts[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Autentica um perfil específico (usado na seleção de perfil)
     */
    public function authenticateProfile(int $profileId, int $userId): array {
        // Verifica se já existe sessão ativa para este perfil
        if ($this->authModel->hasActiveSession($profileId)) {
            return ['success' => false, 'message' => 'Perfil já está sendo utilizado em outro dispositivo.', 'code' => 'SESSION_ACTIVE'];
        }
        
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);
        
        // Cria sessão ativa para o perfil
        if ($this->authModel->createActiveSession($profileId, $userId, $sessionId, $expiresAt)) {
            $_SESSION['profile_id'] = $profileId;
            $_SESSION['session_id'] = $sessionId;
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Erro ao ativar perfil.'];
    }

    public function checkRealTimeAuth(): array {
        if (!isset($_SESSION['user_id'])) {
            return ['isAuthenticated' => false, 'user' => null];
        }
        
        $userId = (int)$_SESSION['user_id'];
        $profileId = isset($_SESSION['profile_id']) ? (int)$_SESSION['profile_id'] : null;
        $sessionId = $_SESSION['session_id'] ?? session_id();
        
        // Se há perfil selecionado, verifica se a sessão do perfil está ativa
        if ($profileId) {
            if (!$this->authModel->hasActiveSession($profileId)) {
                $_SESSION = [];
                session_destroy();
                return ['isAuthenticated' => false, 'user' => null, 'sessionExpired' => true];
            }
            // Atualiza última atividade do perfil
            $this->authModel->updateSessionActivity($sessionId);
        }
        
        $user = $this->authModel->getUserData($userId);
        if (!$user) {
            $_SESSION = [];
            session_destroy();
            return ['isAuthenticated' => false, 'user' => null];
        }
        
        return [
            'isAuthenticated' => true,
            'user' => [
                'id' => $user['id'],
                'fullName' => $user['full_name'] ?: $user['name'],
                'avatar' => $user['profile_pic_url'],
                'plan' => $user['plan_type']
            ]
        ];
    }

    /**
     * Verifica se o perfil tem sessão ativa em outro dispositivo
     */
    public function hasActiveSessionElsewhere(int $profileId): bool {
        $currentSessionId = session_id();

        $db = $this->authModel->getDbPipocine();
        if (!$db) {
            return false;
        }
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM profile_active_sessions 
            WHERE profile_id = ? AND is_active = 1 AND expires_at > NOW() 
            AND session_id != ?
        ");
        $stmt->execute([$profileId, $currentSessionId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Obtém informações do dispositivo ativo do perfil
     */
    public function getActiveDeviceInfo(int $profileId): array {
        $currentSessionId = session_id();

        $db = $this->authModel->getDbPipocine();
        if (!$db) {
            return ['device' => 'Dispositivo desconhecido'];
        }
        
        $stmt = $db->prepare("
            SELECT user_agent, ip_address, last_activity, created_at
            FROM profile_active_sessions 
            WHERE profile_id = ? AND is_active = 1 AND expires_at > NOW() 
            AND session_id != ?
            ORDER BY last_activity DESC
            LIMIT 1
        ");
        $stmt->execute([$profileId, $currentSessionId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['device' => 'Dispositivo desconhecido'];
        }
        
        $device = $this->parseUserAgent($result['user_agent']);
        $location = $this->getLocationFromIP($result['ip_address']);
        $time = $this->formatLastActivity($result['last_activity']);
        
        return [
            'device' => $device,
            'location' => $location,
            'time' => $time
        ];
    }

    /**
     * Parse do user agent para identificar dispositivo
     */
    private function parseUserAgent(string $userAgent): string {
        $userAgent = strtolower($userAgent);
        
        // Detecta navegador
        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'edg') !== false) {
            $browser = 'Edge';
        } else {
            $browser = 'Navegador';
        }
        
        // Detecta sistema operacional
        if (strpos($userAgent, 'windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($userAgent, 'mac') !== false) {
            $os = 'macOS';
        } elseif (strpos($userAgent, 'android') !== false) {
            $os = 'Android';
        } elseif (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            $os = 'iOS';
        } elseif (strpos($userAgent, 'linux') !== false) {
            $os = 'Linux';
        } else {
            $os = 'Sistema';
        }
        
        // Detecta mobile
        $isMobile = strpos($userAgent, 'mobile') !== false || 
                   strpos($userAgent, 'android') !== false || 
                   strpos($userAgent, 'iphone') !== false;
        
        return $isMobile ? "$browser no $os (Mobile)" : "$browser no $os";
    }

    /**
     * Obtém localização aproximada do IP
     */
    private function getLocationFromIP(string $ip): string {
        // Para implementação futura com GeoIP
        // Por enquanto, retorna uma mensagem genérica
        return 'Localização não disponível';
    }

    /**
     * Formata a última atividade
     */
    private function formatLastActivity(string $lastActivity): string {
        $timestamp = strtotime($lastActivity);
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return 'Agora há pouco';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "Há $minutes minuto" . ($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Há $hours hora" . ($hours > 1 ? 's' : '');
        } else {
            $days = floor($diff / 86400);
            return "Há $days dia" . ($days > 1 ? 's' : '');
        }
    }
}
