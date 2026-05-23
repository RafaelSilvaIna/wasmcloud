<?php

declare(strict_types=1);

namespace Middleware;

final class ApiPerformanceMiddleware
{
    private const TARGET_MS = 300;
    private const CACHE_ROOT = __DIR__ . '/../data/cache/api-performance';
    private const STALE_SECONDS = 120;
    private const VERSION_FILE = self::CACHE_ROOT . '/versions.json';

    private static bool $booted = false;
    private static float $startedAt = 0.0;
    private static ?array $cacheContext = null;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::$startedAt = defined('PIPOCINE_REQUEST_STARTED_AT')
            ? (float) PIPOCINE_REQUEST_STARTED_AT
            : microtime(true);

        $path = self::path();
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        self::ensureCacheRoot();

        if ($method !== 'GET' && $method !== 'HEAD' && $method !== 'OPTIONS') {
            self::bumpTagsForMutation($path);
            self::purgeCurrentScope();
        }

        if ($method === 'GET') {
            self::$cacheContext = self::cacheContext($path);
            if (self::$cacheContext !== null) {
                self::serveCached(self::$cacheContext);
            }
        }

        ob_start(static function (string $body): string {
            return self::finalize($body);
        });
    }

    public static function invalidateTags(array $tags): void
    {
        $normalized = [];
        foreach ($tags as $tag) {
            $tag = preg_replace('/[^a-zA-Z0-9:_-]/', '', (string) $tag);
            if ($tag !== '') {
                $normalized[] = $tag;
            }
        }

        $normalized = array_values(array_unique($normalized));
        if (!$normalized) {
            return;
        }

        $versions = self::readVersions();
        $now = time();
        foreach ($normalized as $tag) {
            $versions[$tag] = $now;
        }

        self::writeVersions($versions);
    }

    public static function invalidateCatalog(): void
    {
        self::invalidateTags(['api', 'catalog', 'search']);
    }

    private static function finalize(string $body): string
    {
        $durationMs = (int) round((microtime(true) - self::$startedAt) * 1000);

        if (!headers_sent()) {
            header('Server-Timing: app;dur=' . $durationMs, false);
            header('X-Response-Time: ' . $durationMs . 'ms', false);
            header('X-Performance-Target: ' . self::TARGET_MS . 'ms', false);
        }

        if (self::$cacheContext !== null) {
            self::storeCache(self::$cacheContext, $body, $durationMs);
        }

        return $body;
    }

    private static function cacheContext(string $path): ?array
    {
        $ttl = self::ttlForPath($path);
        if ($ttl <= 0 || self::hasNoCacheRequest()) {
            return null;
        }

        $scope = self::scope();
        $tags = self::tagsForPath($path);
        $versions = self::versionSignature($tags);
        $query = $_GET;
        ksort($query);

        $key = hash('sha256', implode('|', [
            'api-performance-v1',
            $path,
            http_build_query($query),
            $scope,
            self::profileVariant(),
            $versions,
        ]));

        $scopeDir = self::CACHE_ROOT . '/' . hash('sha256', $scope);

        return [
            'path' => $path,
            'ttl' => $ttl,
            'scope' => $scope,
            'tags' => $tags,
            'versions' => $versions,
            'scope_dir' => $scopeDir,
            'file' => $scopeDir . '/' . $key . '.json',
        ];
    }

    private static function serveCached(array $context): void
    {
        $entry = self::readEntry((string) $context['file']);
        if (!$entry) {
            self::cacheHeader('MISS');
            return;
        }

        $now = time();
        $expiresAt = (int) ($entry['expires_at'] ?? 0);
        $staleUntil = (int) ($entry['stale_until'] ?? 0);
        if ($staleUntil < $now) {
            self::cacheHeader('EXPIRED');
            return;
        }

        $etag = (string) ($entry['etag'] ?? '');
        if ($expiresAt >= $now || self::canServeStale($entry)) {
            $status = $expiresAt >= $now ? 'HIT' : 'STALE';
            $body = (string) ($entry['body'] ?? '');
            $code = (int) ($entry['status'] ?? 200);
            $age = max(0, $now - (int) ($entry['created_at'] ?? $now));

            if (!headers_sent()) {
                if ($etag !== '') {
                    header('ETag: ' . $etag);
                }
                header('Age: ' . $age);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: private, max-age=' . (int) $context['ttl'] . ', stale-while-revalidate=' . self::STALE_SECONDS);
                header('Vary: Cookie, Authorization');
                header('Cache-Tag: ' . implode(',', (array) ($context['tags'] ?? [])));
                self::cacheHeader($status);
                header('Server-Timing: app;dur=0;desc="origin-cache"');
                header('X-Response-Time: 0ms');
                header('X-Performance-Target: ' . self::TARGET_MS . 'ms');
            }

            if ($etag !== '' && trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
                http_response_code(304);
                exit;
            }

            http_response_code($code);
            echo $body;
            exit;
        }

        self::cacheHeader('MISS-STALE');
    }

    private static function storeCache(array $context, string $body, int $durationMs): void
    {
        $status = http_response_code() ?: 200;
        if ($status !== 200 || trim($body) === '' || !self::looksJson($body)) {
            return;
        }

        $dir = (string) $context['scope_dir'];
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $now = time();
        $ttl = (int) $context['ttl'];
        $entry = [
            'status' => $status,
            'body' => $body,
            'created_at' => $now,
            'expires_at' => $now + $ttl,
            'stale_until' => $now + $ttl + self::STALE_SECONDS,
            'origin_duration_ms' => $durationMs,
            'tags' => $context['tags'] ?? [],
            'versions' => $context['versions'] ?? '',
            'etag' => '"' . sha1($body) . '"',
        ];

        $file = (string) $context['file'];
        $tmp = $file . '.' . getmypid() . '.tmp';
        @file_put_contents($tmp, json_encode($entry, JSON_UNESCAPED_SLASHES), LOCK_EX);
        @rename($tmp, $file);

        if (!headers_sent()) {
            header_remove('Pragma');
            header_remove('Expires');
            header('ETag: ' . $entry['etag']);
            header('Cache-Control: private, max-age=' . $ttl . ', stale-while-revalidate=' . self::STALE_SECONDS, true);
            header('Vary: Cookie, Authorization', false);
            header('Cache-Tag: ' . implode(',', (array) ($context['tags'] ?? [])), false);
            self::cacheHeader('MISS-STORE');
        }
    }

    private static function ttlForPath(string $path): int
    {
        $rules = [
            '/api/v2/plataforma' => 1800,
            '/api/v2/info' => 900,
            '/api/v2/conteudo' => 600,
            '/api/v2/trending' => 300,
            '/api/v2/busca' => 45,
            '/api/v3/comments/replies' => 20,
            '/api/v3/comments' => 20,
            '/api/v3/library' => 5,
            '/api/v3/watched-episodes' => 5,
            '/api/v3/watch-progress' => 3,
            '/api/profiles/current' => 3,
            '/api/profiles/list' => 3,
            '/api/admin/' => 4,
            '/api/devices/' => 5,
            '/api/v4/account/' => 5,
            '/api/v4/subscription/' => 5,
            '/api/v4/box/' => 5,
            '/api/v4/family/' => 5,
        ];

        foreach ($rules as $prefix => $ttl) {
            if (str_starts_with($path, $prefix)) {
                return self::isExcludedPath($path) ? 0 : $ttl;
            }
        }

        return 0;
    }

    private static function isExcludedPath(string $path): bool
    {
        foreach ([
            '/api/auth/',
            '/api/v4/auth/',
            '/api/v4/qr-login/',
            '/api/v4/pin',
            '/api/v4/2fa',
            '/api/v2/episode-url',
            '/api/v2/exhibition',
            '/api/suporte',
            '/api/ads',
            '/api/cdn',
        ] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function tagsForPath(string $path): array
    {
        $tags = ['api'];

        if (str_starts_with($path, '/api/v2/')) {
            $tags[] = 'catalog';
        }
        if (str_starts_with($path, '/api/v2/busca')) {
            $tags[] = 'search';
        }
        if (str_starts_with($path, '/api/v3/comments')) {
            $tags[] = 'comments';
            $contentId = (string) ($_GET['content_id'] ?? $_GET['id'] ?? '');
            if ($contentId !== '') {
                $tags[] = 'comments:' . preg_replace('/[^a-zA-Z0-9_-]/', '', $contentId);
            }
        }
        if (str_starts_with($path, '/api/v3/library')
            || str_starts_with($path, '/api/v3/watch-progress')
            || str_starts_with($path, '/api/v3/watched-episodes')
        ) {
            $tags[] = 'user';
            $tags[] = 'profile';
        }
        if (str_starts_with($path, '/api/profiles/')) {
            $tags[] = 'profiles';
            $tags[] = 'user';
        }
        if (str_starts_with($path, '/api/v4/subscription/')
            || str_starts_with($path, '/api/v4/account/')
            || str_starts_with($path, '/api/v4/box/')
            || str_starts_with($path, '/api/v4/family/')
        ) {
            $tags[] = 'user';
        }
        if (str_starts_with($path, '/api/admin/')) {
            $tags[] = 'admin';
        }
        if (str_starts_with($path, '/api/ads/')) {
            $tags[] = 'ads';
        }
        if (str_starts_with($path, '/api/devices/')) {
            $tags[] = 'devices';
            $tags[] = 'user';
        }

        return array_values(array_unique($tags));
    }

    private static function bumpTagsForMutation(string $path): void
    {
        $tags = self::mutationTags($path);
        if (!$tags) {
            return;
        }

        self::invalidateTags($tags);

        if (!headers_sent()) {
            header('X-Cache-Invalidated: ' . implode(',', $tags), false);
        }
    }

    private static function mutationTags(string $path): array
    {
        $tags = ['api'];

        if (str_starts_with($path, '/api/admin/')) {
            $tags = array_merge($tags, ['admin', 'catalog', 'search', 'user', 'profiles', 'ads', 'devices']);
        } elseif (str_starts_with($path, '/api/v2/')) {
            $tags = array_merge($tags, ['catalog', 'search']);
        } elseif (str_starts_with($path, '/api/v3/comments')) {
            $tags[] = 'comments';
        } elseif (str_starts_with($path, '/api/v3/library')
            || str_starts_with($path, '/api/v3/watch-progress')
            || str_starts_with($path, '/api/v3/watched-episodes')
        ) {
            $tags = array_merge($tags, ['user', 'profile']);
        } elseif (str_starts_with($path, '/api/profiles/')) {
            $tags = array_merge($tags, ['profiles', 'user']);
        } elseif (str_starts_with($path, '/api/v4/subscription/')
            || str_starts_with($path, '/api/v4/account/')
            || str_starts_with($path, '/api/v4/box/')
            || str_starts_with($path, '/api/v4/family/')
        ) {
            $tags[] = 'user';
        } elseif (str_starts_with($path, '/api/ads/')) {
            $tags[] = 'ads';
        } elseif (str_starts_with($path, '/api/devices/')) {
            $tags = array_merge($tags, ['devices', 'user']);
        }

        return array_values(array_unique($tags));
    }

    private static function versionSignature(array $tags): string
    {
        $versions = self::readVersions();
        $parts = [];
        foreach ($tags as $tag) {
            $parts[] = $tag . ':' . (int) ($versions[$tag] ?? 1);
        }

        return implode(',', $parts);
    }

    private static function readVersions(): array
    {
        if (!is_file(self::VERSION_FILE)) {
            return [];
        }

        $raw = @file_get_contents(self::VERSION_FILE);
        $versions = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        return is_array($versions) ? $versions : [];
    }

    private static function writeVersions(array $versions): void
    {
        self::ensureCacheRoot();
        $lock = self::VERSION_FILE . '.lock';
        $handle = @fopen($lock, 'c');
        if (!$handle) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            $current = self::readVersions();
            $merged = array_merge($current, $versions);
            $tmp = self::VERSION_FILE . '.' . getmypid() . '.tmp';
            @file_put_contents($tmp, json_encode($merged, JSON_UNESCAPED_SLASHES), LOCK_EX);
            @rename($tmp, self::VERSION_FILE);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function purgeCurrentScope(): void
    {
        $scopeDir = self::CACHE_ROOT . '/' . hash('sha256', self::scope());
        if (!is_dir($scopeDir)) {
            return;
        }

        foreach (glob($scopeDir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    private static function scope(): string
    {
        $adminJwt = (string) ($_COOKIE['pipocine_admin_jwt'] ?? '');
        if ($adminJwt !== '') {
            return 'admin:' . hash('sha256', $adminJwt);
        }

        $userId = (string) ($_SESSION['user_id'] ?? 'guest');
        $profileId = (string) ($_SESSION['profile_id'] ?? 'none');
        $sessionId = session_status() === PHP_SESSION_ACTIVE ? session_id() : 'no-session';

        return 'user:' . $userId . ':profile:' . $profileId . ':session:' . $sessionId;
    }

    private static function profileVariant(): string
    {
        return !empty($_SESSION['profile_is_kids']) ? 'kids' : 'standard';
    }

    private static function canServeStale(array $entry): bool
    {
        return (int) ($entry['stale_until'] ?? 0) >= time();
    }

    private static function readEntry(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        $entry = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        return is_array($entry) ? $entry : null;
    }

    private static function looksJson(string $body): bool
    {
        $trimmed = ltrim($body);
        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }

    private static function hasNoCacheRequest(): bool
    {
        $cacheControl = strtolower((string) ($_SERVER['HTTP_CACHE_CONTROL'] ?? ''));
        $pragma = strtolower((string) ($_SERVER['HTTP_PRAGMA'] ?? ''));

        return str_contains($cacheControl, 'no-cache')
            || str_contains($cacheControl, 'no-store')
            || str_contains($pragma, 'no-cache');
    }

    private static function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    private static function cacheHeader(string $value): void
    {
        if (!headers_sent()) {
            header('X-Origin-Cache: ' . $value, true);
        }
    }

    private static function ensureCacheRoot(): void
    {
        if (!is_dir(self::CACHE_ROOT)) {
            @mkdir(self::CACHE_ROOT, 0775, true);
        }
    }
}
