<?php
declare(strict_types=1);

namespace Services\Admin;

use Helpers\Admin\AdminJwt;
use Models\Admin\AdminModel;

final class AdminAuthService
{
    private const COOKIE = 'pipocine_admin_jwt';
    private const TTL = 3600;

    public function __construct(private AdminModel $admins)
    {
        $this->admins->ensureSchema();
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $password = trim($password);
        $ip = $this->clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        if (!$this->admins->isIpAllowed($ip)) {
            $this->admins->audit(null, 'admin_login_blocked_ip', $ip, $userAgent, ['email' => $email]);
            return ['success' => false, 'message' => 'Acesso administrativo nao autorizado para este IP.'];
        }

        $admin = $this->admins->adminByEmail($email);
        $passwordHash = $admin ? (string) $admin['password_hash'] : '';
        $validPassword = $admin && (
            password_verify($password, $passwordHash)
            || hash_equals($passwordHash, md5($password))
        );

        if (!$validPassword) {
            $admin = $this->admins->bootstrapAdminFromCredential($email, $password);
            $passwordHash = $admin ? (string) $admin['password_hash'] : '';
            $validPassword = $admin && password_verify($password, $passwordHash);
        }

        if (!$validPassword) {
            $this->admins->audit($admin ? (int) $admin['id'] : null, 'admin_login_failed', $ip, $userAgent, ['email' => $email]);
            return [
                'success' => false,
                'message' => $admin ? 'Senha administrativa invalida.' : 'Admin nao encontrado para este e-mail.',
            ];
        }

        $jti = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL);
        $this->admins->createSession((int) $admin['id'], hash('sha256', $jti), $ip, $userAgent, $expiresAt);

        $jwt = AdminJwt::issue([
            'sub' => (string) $admin['id'],
            'jti' => $jti,
            'scope' => 'admin',
            'ip_hash' => hash('sha256', $ip),
        ], $this->secret(), self::TTL);

        $this->setCookie($jwt, time() + self::TTL);
        $this->admins->audit((int) $admin['id'], 'admin_login_success', $ip, $userAgent);

        return [
            'success' => true,
            'admin' => $this->publicAdmin($admin),
            'expires_in' => self::TTL,
        ];
    }

    public function currentAdmin(): ?array
    {
        $payload = $this->verifiedPayload();
        if (!$payload) {
            return null;
        }

        return $this->admins->adminById((int) $payload['sub']);
    }

    public function requireAdmin(): array
    {
        $admin = $this->currentAdmin();
        if (!$admin) {
            throw new \RuntimeException('Admin nao autenticado.');
        }

        return $admin;
    }

    public function status(): array
    {
        $admin = $this->currentAdmin();
        return [
            'success' => true,
            'authenticated' => (bool) $admin,
            'admin' => $admin ? $this->publicAdmin($admin) : null,
            'ip_allowed' => $this->admins->isIpAllowed($this->clientIp()),
        ];
    }

    public function logout(): array
    {
        $payload = $this->verifiedPayload(false);
        if ($payload && !empty($payload['jti'])) {
            $this->admins->revokeSession(hash('sha256', (string) $payload['jti']));
            $this->admins->audit((int) ($payload['sub'] ?? 0), 'admin_logout', $this->clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        }

        $this->setCookie('', time() - 3600);
        return ['success' => true];
    }

    public function isRequestAllowed(): bool
    {
        return $this->admins->isIpAllowed($this->clientIp());
    }

    public function requestIp(): string
    {
        return $this->clientIp();
    }

    private function verifiedPayload(bool $requireIp = true): ?array
    {
        $jwt = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($jwt === '') {
            return null;
        }

        $payload = AdminJwt::verify($jwt, $this->secret());
        if (!$payload || ($payload['scope'] ?? '') !== 'admin') {
            return null;
        }

        $ip = $this->clientIp();
        if ($requireIp && ($payload['ip_hash'] ?? '') !== hash('sha256', $ip)) {
            return null;
        }

        $adminId = (int) ($payload['sub'] ?? 0);
        $jti = (string) ($payload['jti'] ?? '');
        if ($adminId <= 0 || $jti === '') {
            return null;
        }

        if (!$this->admins->validSession($adminId, hash('sha256', $jti), $ip)) {
            return null;
        }

        return $payload;
    }

    private function publicAdmin(array $admin): array
    {
        return [
            'id' => (int) $admin['id'],
            'email' => $admin['email'],
            'display_name' => $admin['display_name'] ?? 'Administrador',
            'last_login_at' => $admin['last_login_at'] ?? null,
        ];
    }

    private function clientIp(): string
    {
        $candidates = [];

        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'] as $header) {
            if (!empty($_SERVER[$header])) {
                $candidates[] = trim((string) $_SERVER[$header]);
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $candidates[] = trim($ip);
            }
        }

        $candidates[] = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    private function secret(): string
    {
        $configured = getenv('PIPOCINE_ADMIN_JWT_SECRET');
        if (is_string($configured) && strlen($configured) >= 32) {
            return $configured;
        }

        $dbConfig = \constant('DB_PIPO');
        return hash('sha256', $dbConfig['name'] . $dbConfig['pass'] . __DIR__);
    }

    private function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
}
