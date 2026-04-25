<?php
class AuthService {
    private $authModel;

    public function __construct(AuthModel $authModel) {
        $this->authModel = $authModel;
    }

    public function authenticate(string $email, string $password): array {
        $user = $this->authModel->getUserByEmail($email);
        $hash = !empty($user['password']) ? $user['password'] : ($user['password_hash'] ?? '');
        if (!$user || !$hash || !password_verify($password, $hash)) {
            return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
        }
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);
        if ($this->authModel->createSession((int)$user['id'], $tokenHash, $expiresAt)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'] ?: $user['name'];
            $_SESSION['profile_pic_url'] = $user['profile_pic_url'];
            setcookie('cineveo_token', $rawToken, time() + 2592000, '/', '', false, true);
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Erro ao criar sessão.'];
    }

    public function checkRealTimeAuth(): array {
        if (!isset($_SESSION['user_id'])) {
            return ['isAuthenticated' => false, 'user' => null];
        }
        $user = $this->authModel->getUserData((int)$_SESSION['user_id']);
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
}