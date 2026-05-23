<?php

declare(strict_types=1);

final class ApiAccessHook
{
    private const GLOBAL_API_KEY = 'pipo_live_global_2026_7b3d8f1f6a0c4e2f9b5a1d0e8c6f4a2b';
    private const COOKIE = '_pipo_api_access';
    private const TTL_SECONDS = 7200;

    public static function boot(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (self::isTokenIssuerPath($path, $method)) {
            self::issueBrowserToken();
            return;
        }

        if (!str_starts_with($path, '/api/')) {
            return;
        }

        if ($method === 'OPTIONS') {
            self::preflight();
        }

        if (self::isDirectBrowserNavigation()) {
            self::reject();
        }

        if (!self::hasValidGlobalKey()
            && (self::hasValidPipocineSiteToken() || self::isTrustedPipocineSiteRequest())
        ) {
            self::injectInternalApiKey();
            self::issueBrowserToken();
        }

        if (self::hasValidGlobalKey()) {
            if (!headers_sent()) {
                header('X-Pipocine-Api-Access: granted', false);
            }
            return;
        }

        self::reject();
    }

    private static function isTokenIssuerPath(string $path, string $method): bool
    {
        return in_array($method, ['GET', 'HEAD'], true)
            && !str_starts_with($path, '/api/')
            && !str_starts_with($path, '/cdn/')
            && !str_starts_with($path, '/assets/')
            && !str_starts_with($path, '/security/');
    }

    private static function hasValidGlobalKey(): bool
    {
        $provided = (string) ($_SERVER['HTTP_X_PIPOCINE_API_KEY'] ?? '');
        if ($provided === '') {
            $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
                $provided = trim($m[1]);
            }
        }

        return $provided !== '' && hash_equals(self::GLOBAL_API_KEY, $provided);
    }

    private static function injectInternalApiKey(): void
    {
        $_SERVER['HTTP_X_PIPOCINE_API_KEY'] = self::GLOBAL_API_KEY;
        $_SERVER['PIPOCINE_API_KEY_SOURCE'] = 'server-hook';
    }

    private static function hasValidPipocineSiteToken(): bool
    {
        if (self::hasCrossOriginSignal()) {
            return false;
        }

        $raw = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($raw === '' || !str_contains($raw, '.')) {
            return false;
        }

        [$payload, $signature] = explode('.', $raw, 2);
        if ($payload === '' || $signature === '') {
            return false;
        }

        $json = self::base64UrlDecode($payload);
        $data = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($data)) {
            return false;
        }

        if ((int) ($data['exp'] ?? 0) < time()) {
            return false;
        }

        $expected = self::sign($payload);
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $host = self::host();
        $uaHash = hash('sha256', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180));
        $sessionHash = hash('sha256', session_status() === PHP_SESSION_ACTIVE ? session_id() : '');

        return hash_equals((string) ($data['host'] ?? ''), $host)
            && hash_equals((string) ($data['ua'] ?? ''), $uaHash)
            && hash_equals((string) ($data['sid'] ?? ''), $sessionHash);
    }

    private static function isTrustedPipocineSiteRequest(): bool
    {
        if (self::hasCrossOriginSignal()) {
            return false;
        }

        if (self::hasSameOriginHeader()) {
            return true;
        }

        $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        return in_array($fetchSite, ['same-origin', 'same-site'], true);
    }

    private static function isDirectBrowserNavigation(): bool
    {
        $mode = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
        if ($mode === 'navigate') {
            return true;
        }

        $dest = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        if ($dest === 'document') {
            return true;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'text/html')
            && !str_contains($accept, 'application/json')
            && (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === '';
    }

    private static function hasSameOriginHeader(): bool
    {
        $host = self::host();

        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            return $originHost !== '' && hash_equals($host, $originHost);
        }

        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $refererHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
            return $refererHost !== '' && hash_equals($host, $refererHost);
        }

        return false;
    }

    private static function issueBrowserToken(): void
    {
        if (headers_sent()) {
            return;
        }

        $expires = time() + self::TTL_SECONDS;
        $payload = self::base64UrlEncode(json_encode([
            'host' => self::host(),
            'sid' => hash('sha256', session_status() === PHP_SESSION_ACTIVE ? session_id() : ''),
            'ua' => hash('sha256', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180)),
            'exp' => $expires,
            'nonce' => bin2hex(random_bytes(8)),
        ], JSON_UNESCAPED_SLASHES));

        setcookie(self::COOKIE, $payload . '.' . self::sign($payload), [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }

    private static function hasCrossOriginSignal(): bool
    {
        $host = self::host();
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            return $originHost === '' || !hash_equals($host, $originHost);
        }

        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $refererHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
            return $refererHost === '' || !hash_equals($host, $refererHost);
        }

        $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        return $fetchSite === 'cross-site';
    }

    private static function preflight(): never
    {
        if (!headers_sent()) {
            $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
            if ($origin !== '') {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Pipocine-Api-Key');
            header('Access-Control-Max-Age: 600');
        }

        http_response_code(204);
        exit;
    }

    private static function reject(): never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, private');
            header('X-Pipocine-Api-Access: denied');
        }

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'API key global obrigatoria.',
            'code' => 401,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function host(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return preg_replace('/:\d+$/', '', $host) ?: 'localhost';
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, self::GLOBAL_API_KEY);
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
