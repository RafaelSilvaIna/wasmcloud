<?php

declare(strict_types=1);

namespace Hooks\Suporte;

use Models\Suporte\SupportImageModel;
use Services\Suporte\SupportImageService;

/**
 * Probabilistic cleanup hook (1 in 50 requests) for expired support images.
 * Registered as a shutdown function to not block the response.
 */
final class SupportStorageCleanupHook
{
    private static bool $registered = false;

    public static function register(\PDO $pdo): void
    {
        if (self::$registered) return;
        self::$registered = true;

        // Only run cleanup ~1 in 50 requests to avoid overhead
        if (random_int(1, 50) !== 1) return;

        register_shutdown_function(static function () use ($pdo): void {
            try {
                $model   = new SupportImageModel($pdo);
                $service = new SupportImageService($model);
                $service->cleanup();
            } catch (\Throwable $e) {
                // Silent — cleanup is best-effort
            }
        });
    }
}
