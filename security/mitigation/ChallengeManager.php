<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;
use Throwable;

final class ChallengeManager
{
    private const BYPASS_COOKIE = '_sec_chpass';
    private const BYPASS_TTL = 3600;

    public function __construct(
        private readonly DbSecurityStore $store,
        private readonly \PDO $pdo
    ) {}

    public function requiresChallenge(string $ip, array $routeProfile): bool
    {
        if (!(bool) ($routeProfile['requires_challenge'] ?? false)) {
            return false;
        }

        return !$this->hasBypassCookie($ip);
    }

    public function issueChallenge(string $ip, string $routeGroup): void
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + SecurityConfig::CHALLENGE_TTL_SECONDS);

            $this->pdo->prepare(
                'INSERT INTO sec_challenge_sessions
                 (challenge_token, ip_address, challenge_type, status, original_path,
                  threat_score_at_issue, attempts, max_attempts, expires_at)
                 VALUES (?, ?, "captcha", "pending", ?, 0, 0, ?, ?)'
            )->execute([
                $token,
                $ip,
                $path,
                SecurityConfig::CHALLENGE_MAX_ATTEMPTS,
                $expires,
            ]);
        } catch (Throwable) {}

        SecurityBlockResponder::block($ip, $path, 429, 'Verificacao necessaria.', 5);
    }

    public function validateChallenge(string $token, string $ip): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_challenge_sessions
                 WHERE challenge_token = ?
                   AND ip_address = ?
                   AND status = "pending"
                   AND expires_at > NOW()
                 LIMIT 1'
            );
            $stmt->execute([$token, $ip]);
            $row = $stmt->fetch();

            if (!$row) {
                return false;
            }

            $this->pdo->prepare(
                'UPDATE sec_challenge_sessions
                 SET status = "passed", passed_at = NOW()
                 WHERE challenge_token = ?'
            )->execute([$token]);

            $this->issueBypassCookie($ip);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasBypassCookie(string $ip): bool
    {
        $cookie = $_COOKIE[self::BYPASS_COOKIE] ?? '';
        if (strlen($cookie) < 16) {
            return false;
        }

        $expected = hash_hmac('sha256', $ip, SecurityConfig::secret());
        return hash_equals(substr($expected, 0, 32), substr($cookie, 0, 32));
    }

    private function issueBypassCookie(string $ip): void
    {
        if (headers_sent()) {
            return;
        }

        $value = substr(hash_hmac('sha256', $ip, SecurityConfig::secret()), 0, 32);
        setcookie(self::BYPASS_COOKIE, $value, [
            'expires' => time() + self::BYPASS_TTL,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }
}
