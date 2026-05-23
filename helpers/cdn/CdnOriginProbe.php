<?php

declare(strict_types=1);

namespace Helpers\Cdn;

require_once __DIR__ . '/CdnHttpClient.php';
require_once __DIR__ . '/CdnUrlGuard.php';

final class CdnOriginProbe
{
    private const OK_TTL = 120;
    private const FAIL_TTL = 30;
    private const MAX_BYTES = 65536;

    public static function check(string $url, string $mediaType = 'auto'): array
    {
        $url = trim($url);
        if ($url === '') {
            return self::result(false, 'source_url_empty');
        }

        try {
            CdnUrlGuard::assertAllowedExternalUrl($url);
        } catch (\Throwable $ex) {
            return self::result(false, 'source_url_blocked');
        }

        $cached = self::readCache($url);
        if ($cached !== null) {
            return $cached + ['cached' => true];
        }

        if (!function_exists('curl_init')) {
            return self::writeCache($url, self::result(false, 'curl_unavailable'));
        }

        $mediaType = strtolower($mediaType);
        $isHls = $mediaType === 'm3u8' || str_contains(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.m3u8');
        $body = '';
        $status = 0;
        $contentType = '';

        $headers = CdnHttpClient::requestHeaders(
            $isHls ? null : 'bytes=0-1',
            $isHls
                ? 'application/vnd.apple.mpegurl,application/x-mpegURL,text/plain,*/*;q=0.8'
                : 'video/*,audio/*,application/octet-stream,*/*;q=0.8'
        );

        $ch = curl_init();
        curl_setopt_array($ch, CdnHttpClient::curlBaseOptions($url) + [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$status, &$contentType): int {
                $trimmed = trim($line);
                if (preg_match('/^HTTP\/\S+\s+(\d+)/i', $trimmed, $m)) {
                    $status = (int) $m[1];
                    $contentType = '';
                    return strlen($line);
                }

                if (stripos($line, 'Content-Type:') === 0) {
                    $contentType = trim(substr($line, strlen('Content-Type:')));
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$body): int {
                $body .= $chunk;
                if (strlen($body) >= self::MAX_BYTES) {
                    return 0;
                }

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlMessage = curl_error($ch);
        curl_close($ch);

        $writeStopped = defined('CURLE_WRITE_ERROR') && $curlError === CURLE_WRITE_ERROR;
        if ($curlError && !$writeStopped) {
            return self::writeCache($url, self::result(false, 'origin_curl_' . $curlError, [
                'detail' => $curlMessage,
            ]));
        }

        if (!in_array($status, [200, 206], true)) {
            return self::writeCache($url, self::result(false, 'origin_http_' . ($status ?: 0), [
                'http_status' => $status,
            ]));
        }

        if ($isHls && !str_contains(ltrim($body), '#EXTM3U')) {
            return self::writeCache($url, self::result(false, 'origin_not_hls_playlist', [
                'http_status' => $status,
                'content_type' => $contentType,
            ]));
        }

        if (!$isHls && self::looksLikeHtmlOrJson($contentType, $body)) {
            return self::writeCache($url, self::result(false, 'origin_returned_non_media', [
                'http_status' => $status,
                'content_type' => $contentType,
            ]));
        }

        return self::writeCache($url, self::result(true, 'ok', [
            'http_status' => $status,
            'content_type' => $contentType,
        ]));
    }

    private static function looksLikeHtmlOrJson(string $contentType, string $body): bool
    {
        $contentType = strtolower($contentType);
        if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/json')) {
            return true;
        }

        $prefix = strtolower(ltrim(substr($body, 0, 128)));
        return str_starts_with($prefix, '<!doctype html')
            || str_starts_with($prefix, '<html')
            || str_starts_with($prefix, '{')
            || str_starts_with($prefix, '[');
    }

    private static function result(bool $ok, string $reason, array $extra = []): array
    {
        return [
            'ok' => $ok,
            'reason' => $reason,
            'checked_at' => time(),
        ] + $extra;
    }

    private static function readCache(string $url): ?array
    {
        $path = self::cachePath($url);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        $cached = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($cached) || (int) ($cached['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        unset($cached['expires_at']);
        return $cached;
    }

    private static function writeCache(string $url, array $result): array
    {
        $dir = self::cacheDir();
        if ($dir !== null) {
            $ttl = !empty($result['ok']) ? self::OK_TTL : self::FAIL_TTL;
            $payload = $result + ['expires_at' => time() + $ttl];
            @file_put_contents(self::cachePath($url), json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        return $result;
    }

    private static function cachePath(string $url): string
    {
        return (self::cacheDir() ?? sys_get_temp_dir()) . DIRECTORY_SEPARATOR . hash('sha256', $url) . '.json';
    }

    private static function cacheDir(): ?string
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cdn-probe-cache';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        return $dir;
    }
}
