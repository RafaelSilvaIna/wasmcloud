<?php

declare(strict_types=1);

final class PerformanceResilienceGuard
{
    private const LOG_DIR = __DIR__ . '/../data/logs';
    private const LOG_FILE = self::LOG_DIR . '/resilience.log';
    private const FRONTEND_SCRIPT = '/assets/js/pipocine-resilience.js?v=20260523.2';

    private static bool $booted = false;
    private static bool $frontendBridgeBooted = false;
    private static string $requestId = '';

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        self::$requestId = self::createRequestId();

        ini_set('default_socket_timeout', '5');
        ini_set('mysqlnd.net_read_timeout', '5');

        if (!headers_sent()) {
            header('X-Request-ID: ' . self::$requestId, false);
            header('X-Pipocine-Resilience: active', false);
        }

        set_exception_handler(static function (Throwable $throwable): void {
            self::logThrowable('uncaught_exception', $throwable);
            self::renderFailure(500, 'Erro interno controlado.');
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            self::log('php_error', [
                'severity' => $severity,
                'message' => $message,
                'file' => basename($file),
                'line' => $line,
            ]);

            return false;
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if (!is_array($error) || !self::isFatal((int) ($error['type'] ?? 0))) {
                return;
            }

            self::log('fatal_error', [
                'severity' => (int) ($error['type'] ?? 0),
                'message' => (string) ($error['message'] ?? 'fatal error'),
                'file' => basename((string) ($error['file'] ?? '')),
                'line' => (int) ($error['line'] ?? 0),
            ]);

            if (!headers_sent()) {
                self::renderFailure(500, 'Falha fatal controlada.');
            }
        });
    }

    public static function bootFrontendBridge(): void
    {
        if (self::$frontendBridgeBooted || self::isApiRequest() || self::isAssetRequest()) {
            return;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return;
        }

        self::$frontendBridgeBooted = true;

        ob_start(static function (string $html): string {
            return self::injectFrontendBridge($html);
        });
    }

    public static function requestId(): string
    {
        if (self::$requestId === '') {
            self::$requestId = self::createRequestId();
        }

        return self::$requestId;
    }

    public static function serviceUnavailable(string $component): never
    {
        self::log('service_unavailable', [
            'component' => $component,
        ]);

        self::renderFailure(503, 'Servico temporariamente indisponivel.');
        exit;
    }

    private static function injectFrontendBridge(string $html): string
    {
        if ($html === ''
            || stripos($html, '<html') === false
            || stripos($html, self::FRONTEND_SCRIPT) !== false
            || stripos($html, '</body>') === false
        ) {
            return $html;
        }

        $headTags = implode("\n", [
            '<meta name="pipocine-request-id" content="' . htmlspecialchars(self::requestId(), ENT_QUOTES, 'UTF-8') . '">',
            '<link rel="manifest" href="/manifest.webmanifest">',
            '<link rel="preload" href="' . self::FRONTEND_SCRIPT . '" as="script">',
        ]);

        if (stripos($html, '<link rel="manifest"') === false && stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $headTags . "\n</head>", $html, 1) ?? $html;
        } elseif (stripos($html, '</head>') !== false) {
            $html = preg_replace(
                '/<\/head>/i',
                '<meta name="pipocine-request-id" content="' . htmlspecialchars(self::requestId(), ENT_QUOTES, 'UTF-8') . '">' . "\n" .
                '<link rel="preload" href="' . self::FRONTEND_SCRIPT . '" as="script">' . "\n</head>",
                $html,
                1
            ) ?? $html;
        }

        $script = '<script src="' . self::FRONTEND_SCRIPT . '" defer></script>';
        return preg_replace('/<\/body>/i', $script . "\n</body>", $html, 1) ?? $html;
    }

    private static function renderFailure(int $status, string $message): void
    {
        http_response_code($status);
        $requestId = self::requestId();

        if (self::isApiRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8', true);
                header('Cache-Control: no-store, no-cache, must-revalidate', true);
                header('Retry-After: 5', false);
            }

            echo json_encode([
                'success' => false,
                'sucesso' => false,
                'erro' => $message,
                'request_id' => $requestId,
                'retry_after' => 5,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8', true);
            header('Cache-Control: no-store, no-cache, must-revalidate', true);
            header('Retry-After: 5', false);
        }

        echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">' .
            '<meta name="viewport" content="width=device-width,initial-scale=1">' .
            '<meta name="pipocine-request-id" content="' . htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') . '">' .
            '<title>PipoCine</title><style>' .
            'body{margin:0;background:#08090d;color:#f8fafc;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;display:grid;min-height:100vh;place-items:center}' .
            'main{width:min(520px,calc(100% - 32px));text-align:center}' .
            'img{width:72px;height:72px;object-fit:contain;margin-bottom:24px}' .
            'h1{font-size:24px;margin:0 0 10px}p{color:#aab2c0;line-height:1.6;margin:0 0 24px}' .
            'a{color:#fff;background:#e50914;text-decoration:none;border-radius:8px;padding:12px 18px;font-weight:700;display:inline-block}' .
            '</style></head><body><main><img src="/assets/img/logo-pipocine.png" alt="PipoCine">' .
            '<h1>Estamos reconectando.</h1><p>O PipoCine encontrou uma instabilidade, mas a camada de resiliencia ja registrou o erro. Tente novamente em alguns segundos.</p>' .
            '<a href="/home">Voltar</a></main>' .
            '<script src="' . self::FRONTEND_SCRIPT . '" defer></script>' .
            '</body></html>';
    }

    private static function logThrowable(string $event, Throwable $throwable): void
    {
        self::log($event, [
            'type' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'file' => basename($throwable->getFile()),
            'line' => $throwable->getLine(),
        ]);
    }

    private static function log(string $event, array $context = []): void
    {
        if (!is_dir(self::LOG_DIR)) {
            @mkdir(self::LOG_DIR, 0775, true);
        }

        $entry = [
            'at' => gmdate('c'),
            'event' => $event,
            'request_id' => self::requestId(),
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            'path' => parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
            'ip_hash' => hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '')),
            'context' => self::sanitize($context),
        ];

        @file_put_contents(
            self::LOG_FILE,
            json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private static function sanitize(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            $key = (string) $key;
            if (preg_match('/pass|token|secret|cookie|auth/i', $key)) {
                $safe[$key] = '[redacted]';
                continue;
            }

            $safe[$key] = is_scalar($value) || $value === null ? $value : '[complex]';
        }

        return $safe;
    }

    private static function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    }

    private static function isApiRequest(): bool
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        return str_starts_with($path, '/api/') || str_starts_with($path, '/cdn/');
    }

    private static function isAssetRequest(): bool
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        return str_starts_with($path, '/assets/')
            || str_starts_with($path, '/data/')
            || $path === '/sw.js'
            || $path === '/manifest.webmanifest'
            || preg_match('/\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|map)$/i', $path) === 1;
    }

    private static function createRequestId(): string
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (Throwable) {
            return str_replace('.', '', uniqid('req', true));
        }
    }
}
