<?php
declare(strict_types=1);

namespace Hooks;

use Models\Admin\AdminRouteLockModel;
use PDO;

final class RouteMaintenanceHook
{
    private static bool $registered = false;
    private static float $startedAt = 0.0;
    private static ?array $activeLock = null;
    private static ?string $matchedRoute = null;
    private static ?string $pageFile = null;

    public static function boot(?PDO $pdo): void
    {
        if (!$pdo || !self::isFrontendRequest()) {
            return;
        }

        self::registerLogger($pdo);
        self::enforce($pdo);
    }

    private static function registerLogger(PDO $pdo): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::$startedAt = defined('PIPOCINE_REQUEST_STARTED_AT') ? (float) PIPOCINE_REQUEST_STARTED_AT : microtime(true);

        register_shutdown_function(static function () use ($pdo): void {
            self::recordLog($pdo);
        });
    }

    private static function enforce(PDO $pdo): void
    {
        $path = self::path();

        try {
            $model = new AdminRouteLockModel($pdo);
            foreach ($model->activeLocks() as $lock) {
                if (!self::matches($path, (string) $lock['route_path'], (string) $lock['match_type'])) {
                    continue;
                }

                self::$activeLock = $lock;
                self::$matchedRoute = (string) $lock['route_path'];
                self::$pageFile = $lock['page_file'] ?? null;

                require_once __DIR__ . '/../components/MaintenanceRouteModal.php';
                \MaintenanceRouteModal::render($lock, $path);
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[RouteMaintenanceHook] ' . $e->getMessage());
        }
    }

    private static function recordLog(PDO $pdo): void
    {
        if (!self::isFrontendRequest()) {
            return;
        }

        try {
            $model = new AdminRouteLockModel($pdo);
            $model->recordFrontendLog([
                'request_id' => bin2hex(random_bytes(16)),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'path' => substr(self::path(), 0, 255),
                'matched_route' => self::$matchedRoute,
                'page_file' => self::$pageFile,
                'status_code' => http_response_code() ?: 200,
                'was_locked' => self::$activeLock !== null,
                'lock_id' => self::$activeLock['id'] ?? null,
                'duration_ms' => (int) round((microtime(true) - self::$startedAt) * 1000),
                'ip_address' => self::clientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            ]);
        } catch (\Throwable $e) {
            error_log('[RouteMaintenanceHook:log] ' . $e->getMessage());
        }
    }

    private static function matches(string $path, string $route, string $type): bool
    {
        if ($type === 'exact') {
            return $path === self::normalizePath($route);
        }

        if ($type === 'prefix') {
            $route = self::normalizePath($route);
            return $path === $route || str_starts_with($path, rtrim($route, '/') . '/');
        }

        return @preg_match($route, $path) === 1;
    }

    private static function isFrontendRequest(): bool
    {
        $path = self::path();
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'OPTIONS') {
            return false;
        }

        foreach ([
            '/api/',
            '/assets/',
            '/cdn/',
            '/webhooks/',
            '/security/',
            '/routes/',
            '/components/',
            '/controllers/',
            '/database/',
            '/helpers/',
            '/hooks/',
            '/models/',
            '/services/',
            '/utils/',
            '/middleware/',
            '/d2xs8d3sdfsegequ6249f',
        ] as $prefix) {
            if (str_starts_with($path, rtrim($prefix, '/'))) {
                return false;
            }
        }

        return !str_starts_with($path, '/favicon');
    }

    private static function path(): string
    {
        return self::normalizePath(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        return rtrim($path, '/') ?: '/';
    }

    private static function clientIp(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '';

        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return substr($ip, 0, 45);
    }
}
