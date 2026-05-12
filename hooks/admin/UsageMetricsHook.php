<?php
declare(strict_types=1);

namespace Hooks\Admin;

use Models\Admin\AdminUsageMetricsModel;
use PDO;

final class UsageMetricsHook
{
    private static float $startedAt = 0.0;
    private static bool $registered = false;

    public static function register(?PDO $pdo): void
    {
        if (!$pdo || self::$registered) {
            return;
        }

        self::$registered = true;
        self::$startedAt = defined('PIPOCINE_REQUEST_STARTED_AT') ? (float) PIPOCINE_REQUEST_STARTED_AT : microtime(true);

        register_shutdown_function(static function () use ($pdo): void {
            self::record($pdo);
        });
    }

    private static function record(PDO $pdo): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if (str_starts_with($path, '/assets/') || str_starts_with($path, '/favicon')) {
            return;
        }

        try {
            $responseBytes = 0;
            if (ob_get_level() > 0) {
                $length = ob_get_length();
                $responseBytes = is_int($length) ? max(0, $length) : 0;
            }

            $requestBytes = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $requestBytes += strlen((string) ($_SERVER['QUERY_STRING'] ?? ''));
            $requestBytes += strlen((string) ($_SERVER['HTTP_COOKIE'] ?? ''));
            $requestBytes += strlen((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

            $model = new AdminUsageMetricsModel($pdo);
            $model->record([
                'request_id' => bin2hex(random_bytes(16)),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'path' => substr($path, 0, 255),
                'route_group' => self::routeGroup($path),
                'is_api' => str_starts_with($path, '/api/'),
                'status_code' => http_response_code() ?: 200,
                'request_bytes' => max(0, $requestBytes),
                'response_bytes' => $responseBytes,
                'duration_ms' => (int) round((microtime(true) - self::$startedAt) * 1000),
                'ip_address' => self::clientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            ]);
        } catch (\Throwable $e) {
            error_log('[UsageMetricsHook] ' . $e->getMessage());
        }
    }

    private static function routeGroup(string $path): string
    {
        if (str_starts_with($path, '/api/admin')) return 'api_admin';
        if (str_starts_with($path, '/api/v4')) return 'api_v4';
        if (str_starts_with($path, '/api/v3')) return 'api_v3';
        if (str_starts_with($path, '/api/v2')) return 'api_v2';
        if (str_starts_with($path, '/api/')) return 'api_legacy';
        if (str_starts_with($path, '/d2xs8d3sdfsegequ6249f')) return 'admin_page';
        if (str_starts_with($path, '/error')) return 'error_page';
        return 'frontend';
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
