<?php

declare(strict_types=1);

namespace Security\RateLimit;

final class ClientRequestGuard
{
    private const STORE_DIR = 'pipocine_request_guard';
    private const CONCURRENCY_TTL = 30;

    private static array $enteredConcurrency = [];

    public static function handle(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (self::isBypassedPath($path)) {
            return;
        }

        $ip = self::resolveClientIp();
        if (self::hasTemporaryBypass($ip)) {
            return;
        }

        $clientKey = self::clientKey($ip);
        $group = self::routeGroup($path);
        $limits = self::limitsFor($group, $_SERVER['REQUEST_METHOD'] ?? 'GET');

        self::checkConcurrency($clientKey, $limits['concurrency'], $limits['retry']);
        self::checkWindow('burst', $clientKey . ':' . $group, $limits['window'], $limits['burst'], $limits['retry']);

        $requestKey = $clientKey . ':' . ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ':' . $path;
        self::checkWindow('duplicate', $requestKey, 5, $limits['duplicate'], $limits['retry']);
    }

    public static function resolveClientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';

        $cfIp = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cfIp !== '' && self::isTrustedProxy($remote) && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== '' && self::isTrustedProxy($remote) && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        $xff = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($xff !== '' && self::isTrustedProxy($remote)) {
            $candidate = trim(explode(',', $xff)[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '127.0.0.1';
    }

    public static function issueTemporaryBypass(string $ip, int $ttl = 90): void
    {
        if (headers_sent()) {
            return;
        }

        $expires = time() + $ttl;
        $value = $expires . ':' . hash_hmac('sha256', $ip . '|' . $expires, self::secret());
        setcookie('_sec_continue_ok', $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }

    public static function hasTemporaryBypass(string $ip): bool
    {
        $cookie = (string) ($_COOKIE['_sec_continue_ok'] ?? '');
        if ($cookie === '' || !str_contains($cookie, ':')) {
            return false;
        }

        [$expires, $mac] = explode(':', $cookie, 2);
        $expiresInt = (int) $expires;
        if ($expiresInt < time() || $mac === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $ip . '|' . $expiresInt, self::secret());
        return hash_equals($expected, $mac);
    }

    private static function checkConcurrency(string $clientKey, int $limit, int $retryAfter): void
    {
        $key = 'sec_conc_' . $clientKey;
        $count = self::incrementCounter($key, self::CONCURRENCY_TTL);
        self::$enteredConcurrency[$key] = true;

        register_shutdown_function(static function () use ($key): void {
            if (!isset(self::$enteredConcurrency[$key])) {
                return;
            }
            unset(self::$enteredConcurrency[$key]);
            self::decrementCounter($key);
        });

        if ($count > $limit) {
            self::decrementCounter($key);
            unset(self::$enteredConcurrency[$key]);
            self::reject(429, 'Muitas requisicoes simultaneas. Aguarde um instante.', $retryAfter);
        }
    }

    private static function checkWindow(
        string $prefix,
        string $keyMaterial,
        int $windowSeconds,
        int $limit,
        int $retryAfter
    ): void {
        $key = 'sec_' . $prefix . '_' . hash('sha256', $keyMaterial . ':' . floor(time() / $windowSeconds));
        $count = self::incrementCounter($key, $windowSeconds + 2);

        if ($count > $limit) {
            self::reject(429, 'Requisicoes em excesso detectadas. Tente novamente em alguns segundos.', $retryAfter);
        }
    }

    private static function incrementCounter(string $key, int $ttl): int
    {
        if (function_exists('apcu_add') && function_exists('apcu_inc')) {
            apcu_add($key, 0, $ttl);
            $value = apcu_inc($key, 1, $success, $ttl);
            return $success ? (int) $value : 1;
        }

        return self::withFileCounter($key, $ttl, 1);
    }

    private static function decrementCounter(string $key): void
    {
        if (function_exists('apcu_fetch') && function_exists('apcu_dec') && function_exists('apcu_delete')) {
            $current = apcu_fetch($key, $success);
            if (!$success) {
                return;
            }
            if ((int) $current <= 1) {
                apcu_delete($key);
                return;
            }
            apcu_dec($key);
            return;
        }

        self::withFileCounter($key, self::CONCURRENCY_TTL, -1);
    }

    private static function withFileCounter(string $key, int $ttl, int $delta): int
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::STORE_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
        $fh = @fopen($file, 'c+');
        if (!$fh) {
            return 1;
        }

        try {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            $data = $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($data) || (int) ($data['expires'] ?? 0) < time()) {
                $data = ['count' => 0, 'expires' => time() + $ttl];
            }

            $data['count'] = max(0, (int) $data['count'] + $delta);
            if ($delta > 0) {
                $data['expires'] = time() + $ttl;
            }

            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($data));
            fflush($fh);

            return (int) $data['count'];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private static function limitsFor(string $group, string $method): array
    {
        $mutating = !in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);

        return match ($group) {
            'auth' => ['window' => 10, 'burst' => 12, 'duplicate' => $mutating ? 4 : 8, 'concurrency' => 3, 'retry' => 5],
            'admin' => ['window' => 10, 'burst' => 18, 'duplicate' => $mutating ? 5 : 10, 'concurrency' => 4, 'retry' => 5],
            'stream', 'cdn' => ['window' => 10, 'burst' => 45, 'duplicate' => 18, 'concurrency' => 8, 'retry' => 3],
            'search' => ['window' => 10, 'burst' => 25, 'duplicate' => 10, 'concurrency' => 5, 'retry' => 3],
            'api' => ['window' => 10, 'burst' => 40, 'duplicate' => $mutating ? 8 : 16, 'concurrency' => 6, 'retry' => 3],
            default => ['window' => 10, 'burst' => 50, 'duplicate' => $mutating ? 10 : 20, 'concurrency' => 8, 'retry' => 2],
        };
    }

    private static function routeGroup(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/api/auth/'),
            str_starts_with($path, '/api/v4/auth/'),
            str_starts_with($path, '/api/v4/qr-login/'),
            str_starts_with($path, '/login') => 'auth',
            str_starts_with($path, '/api/admin/'),
            str_starts_with($path, '/admin') => 'admin',
            str_starts_with($path, '/cdn/') => 'cdn',
            str_starts_with($path, '/player'),
            str_starts_with($path, '/api/v2/exhibition'),
            str_starts_with($path, '/api/v2/episode-url') => 'stream',
            str_starts_with($path, '/busca'),
            str_starts_with($path, '/api/v2/busca') => 'search',
            str_starts_with($path, '/api/') => 'api',
            default => 'global',
        };
    }

    private static function clientKey(string $ip): string
    {
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160);
        $sessionName = defined('SESSION_NAME') ? (string) constant('SESSION_NAME') : 'CINEVEO_SECURE_V2';
        $sessionCookie = (string) ($_COOKIE[$sessionName] ?? '');
        return hash('sha256', $ip . '|' . $sessionCookie . '|' . $ua);
    }

    private static function reject(int $code, string $message, int $retryAfter): never
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        require_once dirname(__DIR__) . '/mitigation/SecurityBlockResponder.php';
        \Security\Mitigation\SecurityBlockResponder::block(
            self::resolveClientIp(),
            $path,
            $code,
            $message,
            $retryAfter
        );
    }

    private static function isBypassedPath(string $path): bool
    {
        return in_array($path, ['/security/continue', '/security/challenge'], true)
            || str_starts_with($path, '/webhooks/');
    }

    private static function isTrustedProxy(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        $configured = defined('PIPOCINE_TRUSTED_PROXIES') ? (string) PIPOCINE_TRUSTED_PROXIES : '';
        foreach (array_filter(array_map('trim', explode(',', $configured))) as $trusted) {
            if ($trusted === $ip) {
                return true;
            }
        }

        return false;
    }

    private static function secret(): string
    {
        if (class_exists('\\Security\\Config\\SecurityConfig')
            && method_exists('\\Security\\Config\\SecurityConfig', 'secret')
        ) {
            return \Security\Config\SecurityConfig::secret();
        }

        $env = getenv('PIPOCINE_SECURITY_SECRET');
        if (is_string($env) && strlen($env) >= 32) {
            return $env;
        }

        return hash('sha256', __DIR__ . '|pipocine-request-guard');
    }
}
