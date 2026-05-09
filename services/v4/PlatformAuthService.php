<?php

declare(strict_types=1);

namespace Services\V4;

use Models\V4\PlatformUserModel;

class PlatformAuthService
{
    private PlatformUserModel $users;
    private TwoFactorService $twoFactor;

    public function __construct(PlatformUserModel $users, TwoFactorService $twoFactor)
    {
        $this->users = $users;
        $this->twoFactor = $twoFactor;
    }

    public function checkEmail(string $identifier): array
    {
        $parsed = $this->parseIdentifier($identifier);

        if (!$parsed['valid']) {
            return [
                'success' => false,
                'exists' => false,
                'message' => 'Informe um email ou numero de celular valido.'
            ];
        }

        return [
            'success' => true,
            'type' => $parsed['type'],
            'exists' => (bool) $this->users->findByIdentifier($parsed['email'], $parsed['phone'])
        ];
    }

    public function register(string $identifier, string $password, string $passwordConfirmation, string $fullName): array
    {
        $parsed = $this->parseIdentifier($identifier);
        $fullName = trim($fullName);

        if (!$parsed['valid']) {
            return ['success' => false, 'message' => 'Informe um email ou numero de celular valido.'];
        }

        if ($this->users->findByIdentifier($parsed['email'], $parsed['phone'])) {
            return ['success' => false, 'message' => $parsed['type'] === 'email' ? 'Este email ja esta cadastrado.' : 'Este celular ja esta cadastrado.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'A senha precisa ter pelo menos 8 caracteres.'];
        }

        if ($password !== $passwordConfirmation) {
            return ['success' => false, 'message' => 'As senhas nao conferem.'];
        }

        if (strlen($fullName) < 3) {
            return ['success' => false, 'message' => 'Informe seu nome completo.'];
        }

        $userId = $this->users->create($parsed['email'], $parsed['phone'], password_hash($password, PASSWORD_DEFAULT), $fullName);
        $user = $this->users->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Nao foi possivel criar sua conta.'];
        }

        return $this->createAuthenticatedSession($user, true);
    }

    public function login(string $identifier, string $password, ?string $deviceToken = null): array
    {
        $parsed = $this->parseIdentifier($identifier);
        $user = $parsed['valid'] ? $this->users->findByIdentifier($parsed['email'], $parsed['phone']) : null;

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return ['success' => false, 'message' => 'Email, celular ou senha incorretos.'];
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return ['success' => false, 'message' => 'Sua conta esta indisponivel no momento.'];
        }

        $userId = (int) $user['id'];
        $status = $this->twoFactor->getStatus($userId);

        if (!empty($status['enabled'])) {
            if ($deviceToken && $this->twoFactor->isTrustedDevice($userId, $deviceToken)) {
                return $this->createAuthenticatedSession($user);
            }

            return $this->createTwoFactorChallenge($userId);
        }

        return $this->createAuthenticatedSession($user);
    }

    public function verifyTwoFactorLogin(string $verifyToken, string $code, bool $rememberDevice, ?string $deviceToken): array
    {
        $verifyToken = trim($verifyToken);
        $code = preg_replace('/\D+/', '', trim($code));

        if ($verifyToken === '' || $code === '') {
            return ['success' => false, 'message' => 'Token e codigo sao obrigatorios.'];
        }

        $tokenHash = hash('sha256', $verifyToken);
        $challenge = $this->users->getTwoFactorChallenge($tokenHash);

        if (!$challenge) {
            $this->clearTwoFactorChallengeCookie();
            return ['success' => false, 'message' => 'Verificacao expirada. Entre novamente.'];
        }

        $result = $this->twoFactor->verifyLogin(
            (int) $challenge['user_id'],
            $code,
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $rememberDevice ? $deviceToken : null
        );

        if (empty($result['success'])) {
            return [
                'success' => false,
                'message' => $result['error'] ?? 'Codigo invalido.',
                'blocked' => $result['blocked'] ?? false
            ];
        }

        $user = $this->users->findById((int) $challenge['user_id']);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario nao encontrado.'];
        }

        $session = $this->createAuthenticatedSession($user);
        if (empty($session['success'])) {
            return $session;
        }

        $this->users->consumeTwoFactorChallenge($tokenHash);
        $this->clearTwoFactorChallengeCookie();

        return ['success' => true];
    }

    private function createAuthenticatedSession(array $user, bool $created = false): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);

        if (!$this->users->createSession((int) $user['id'], hash('sha256', $rawToken), $expiresAt)) {
            return ['success' => false, 'message' => 'Erro ao criar sessao.'];
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['email'] ?: $user['phone'];
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_phone'] = $user['phone'] ?? null;
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['profile_pic_url'] = $user['avatar_url'] ?? null;
        $_SESSION['session_id'] = session_id();
        $_SESSION['auth_provider'] = 'pipocine';

        setcookie('pipocine_token', $rawToken, time() + 2592000, '/', '', false, true);

        return [
            'success' => true,
            'created' => $created,
            'redirect' => '/select-profile'
        ];
    }

    private function createTwoFactorChallenge(int $userId): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        if (!$this->users->createTwoFactorChallenge($userId, hash('sha256', $rawToken), $expiresAt)) {
            return ['success' => false, 'message' => 'Erro ao iniciar verificacao em duas etapas.'];
        }

        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['profile_pic_url'], $_SESSION['profile_id']);
        setcookie('pipocine_token', '', time() - 3600, '/', '', false, true);
        setcookie('pipocine_2fa_challenge', $rawToken, time() + 600, '/', '', false, true);

        return [
            'success' => true,
            'requires_2fa' => true,
            'verify_token' => $rawToken,
            'redirect' => '/verify=' . rawurlencode($rawToken)
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function isValidEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    private function parseIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);

        if ($this->isValidEmail($identifier)) {
            return [
                'valid' => true,
                'type' => 'email',
                'email' => $this->normalizeEmail($identifier),
                'phone' => null
            ];
        }

        $phone = $this->normalizePhone($identifier);
        if (strlen($phone) >= 12 && strlen($phone) <= 15) {
            return [
                'valid' => true,
                'type' => 'phone',
                'email' => null,
                'phone' => $phone
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'email' => null,
            'phone' => null
        ];
    }

    private function clearTwoFactorChallengeCookie(): void
    {
        setcookie('pipocine_2fa_challenge', '', time() - 3600, '/', '', false, true);
    }

    private function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return $ip;
    }
}
