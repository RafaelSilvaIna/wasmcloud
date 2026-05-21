<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;
use Security\RateLimit\ClientRequestGuard;

final class SecurityBlockResponder
{
    public static function block(
        string $ip,
        string $path,
        int $code = 429,
        string $message = 'Atividade suspeita detectada.',
        int $retryAfter = 5
    ): never {
        $resumeTarget = self::resumeTarget($path);
        self::prepareResumeSession($ip, $resumeTarget);

        if (self::isApiLikePath($path)) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, private');
            header('Retry-After: ' . $retryAfter);
            $payload = [
                'success' => false,
                'error' => $message,
                'code' => $code,
                'retry_after' => $retryAfter,
            ];

            if (self::shouldEscalateApiChallenge($path)) {
                $payload['security_challenge'] = true;
                $payload['challenge_url'] = '/security/challenge';
            }

            echo json_encode($payload);
            exit;
        }

        self::renderModal($code);
    }

    public static function isApiLikePath(string $path): bool
    {
        return str_starts_with($path, '/api/') || str_starts_with($path, '/cdn/');
    }

    public static function createResumeToken(string $ip, string $target, int $ttl = 300): string
    {
        $payload = [
            'ip' => hash('sha256', $ip),
            'target' => self::sanitizeTarget($target),
            'exp' => time() + $ttl,
            'nonce' => bin2hex(random_bytes(12)),
        ];

        $body = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = hash_hmac('sha256', $body, SecurityConfig::secret());

        return $body . '.' . $sig;
    }

    public static function validateResumeToken(string $token, string $ip): ?array
    {
        if (!str_contains($token, '.')) {
            return null;
        }

        [$body, $sig] = explode('.', $token, 2);
        if ($body === '' || $sig === '') {
            return null;
        }

        $expected = hash_hmac('sha256', $body, SecurityConfig::secret());
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $json = self::base64UrlDecode($body);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($payload)) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        if (!hash_equals((string) ($payload['ip'] ?? ''), hash('sha256', $ip))) {
            return null;
        }

        return [
            'target' => self::sanitizeTarget((string) ($payload['target'] ?? '/home')),
            'expires_at' => (int) $payload['exp'],
        ];
    }

    private static function resumeTarget(string $path): string
    {
        if (self::isApiLikePath($path)) {
            $refererPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
            return is_string($refererPath) && str_starts_with($refererPath, '/')
                ? $refererPath
                : '/home';
        }

        return self::sanitizeTarget($path);
    }

    private static function prepareResumeSession(string $ip, string $target): void
    {
        self::ensureSession();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $hasActiveChallenge = !empty($_SESSION['_sec_resume_token'])
            && !empty($_SESSION['_sec_resume_ip'])
            && hash_equals((string) $_SESSION['_sec_resume_ip'], $ip);

        if (!$hasActiveChallenge) {
            $_SESSION['_sec_resume_ip'] = $ip;
        }

        $_SESSION['_sec_resume_target'] = $target;
        $_SESSION['_sec_resume_token'] = self::createResumeToken($ip, $target);
    }

    private static function renderModal(int $code): never
    {
        self::ensureSession();

        $token = session_status() === PHP_SESSION_ACTIVE
            ? (string) ($_SESSION['_sec_resume_token'] ?? '')
            : '';
        $target = session_status() === PHP_SESSION_ACTIVE
            ? (string) ($_SESSION['_sec_resume_target'] ?? '/home')
            : '/home';

        $ip = ClientRequestGuard::resolveClientIp();
        $target = self::sanitizeTarget($target);

        if ($token === '' || self::validateResumeToken($token, $ip) === null) {
            $token = self::createResumeToken($ip, $target);
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_sec_resume_token'] = $token;
                $_SESSION['_sec_resume_target'] = $target;
                $_SESSION['_sec_resume_ip'] = $ip;
            }
        }

        $component = dirname(__DIR__, 2) . '/components/SuspiciousActivityModal.php';
        if (is_file($component)) {
            require_once $component;
            \SuspiciousActivityModal::render($token, $target, $code);
            exit;
        }

        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Atividade suspeita detectada.');
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (function_exists('initSession')) {
            \initSession();
            return;
        }

        if (defined('SESSION_NAME')) {
            session_name((string) constant('SESSION_NAME'));
        }

        session_start();
    }

    private static function sanitizeTarget(string $target): string
    {
        $path = parse_url($target, PHP_URL_PATH) ?: '/home';
        if (!str_starts_with($path, '/') || str_starts_with($path, '/security/')) {
            return '/home';
        }

        return $path;
    }

    private static function shouldEscalateApiChallenge(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        return str_starts_with($path, '/api/auth/')
            || str_starts_with($path, '/api/v4/auth/')
            || str_starts_with($path, '/api/v4/qr-login/')
            || str_starts_with($path, '/api/admin/');
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $padded = str_pad($value, strlen($value) + (4 - strlen($value) % 4) % 4, '=');
        return base64_decode(strtr($padded, '-_', '+/'), true);
    }
}
