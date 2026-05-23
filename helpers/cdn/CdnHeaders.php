<?php

declare(strict_types=1);

namespace Helpers\Cdn;

final class CdnHeaders
{
    public static function noStore(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
    }

    public static function stream(string $contentType, string $mode): void
    {
        self::noStore();
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: none');
        header('X-Accel-Buffering: no');
        header('X-Pipocine-CDN: internal-realtime');
        header('X-Pipocine-CDN-Mode: ' . $mode);
    }

    public static function file(string $contentType, string $mode): void
    {
        self::noStore();
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: bytes');
        header('X-Accel-Buffering: no');
        header('X-Pipocine-CDN: internal-file-cache');
        header('X-Pipocine-CDN-Mode: ' . $mode);
    }

    public static function proxy(string $mode = 'origin-proxy', int $cacheSeconds = 30): void
    {
        header('Cache-Control: private, max-age=' . max(0, $cacheSeconds) . ', stale-while-revalidate=30');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Accept-Ranges: bytes');
        header('X-Accel-Buffering: no');
        header('X-Pipocine-CDN: internal-origin-proxy');
        header('X-Pipocine-CDN-Mode: ' . $mode);
    }

    public static function playlist(): void
    {
        header('Cache-Control: private, max-age=10, stale-while-revalidate=30');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Content-Type: application/vnd.apple.mpegurl; charset=utf-8');
        header('X-Accel-Buffering: no');
        header('X-Pipocine-CDN: internal-origin-proxy');
        header('X-Pipocine-CDN-Mode: hls-playlist');
    }

    public static function rangeNotSatisfiable(int $size): void
    {
        self::noStore();
        http_response_code(416);
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes */' . $size);
    }

    public static function json(): void
    {
        self::noStore();
        header('Content-Type: application/json; charset=utf-8');
    }
}
