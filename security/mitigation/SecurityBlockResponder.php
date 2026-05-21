<?php

declare(strict_types=1);

namespace Security\Mitigation;

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
            echo json_encode([
                'success' => false,
                'error' => $message,
                'code' => $code,
                'retry_after' => $retryAfter,
                'security_challenge' => true,
                'challenge_url' => '/security/challenge',
            ]);
            exit;
        }

        self::renderModal($code);
    }

    public static function isApiLikePath(string $path): bool
    {
        return str_starts_with($path, '/api/') || str_starts_with($path, '/cdn/');
    }

    private static function resumeTarget(string $path): string
    {
        if (self::isApiLikePath($path)) {
            $refererPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
            return is_string($refererPath) && str_starts_with($refererPath, '/')
                ? $refererPath
                : '/home';
        }

        $target = parse_url($path, PHP_URL_PATH) ?: '/home';
        return str_starts_with($target, '/') ? $target : '/home';
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
            $_SESSION['_sec_resume_token'] = bin2hex(random_bytes(24));
            $_SESSION['_sec_resume_ip'] = $ip;
        }

        $_SESSION['_sec_resume_target'] = $target;
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

        if ($token === '') {
            $token = bin2hex(random_bytes(24));
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_sec_resume_token'] = $token;
                $_SESSION['_sec_resume_target'] = $target;
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
}
