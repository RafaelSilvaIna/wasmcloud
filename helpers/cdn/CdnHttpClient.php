<?php

declare(strict_types=1);

namespace Helpers\Cdn;

final class CdnHttpClient
{
    private const USER_AGENT = 'PipocineMediaProxy/1.0 (+https://pipocine.site)';
    private const MAX_PLAYLIST_BYTES = 5242880;

    public static function requestHeaders(?string $range = null, ?string $accept = null): array
    {
        $headers = [
            'User-Agent: ' . self::USER_AGENT,
            'Accept-Encoding: identity',
            'Connection: keep-alive',
        ];

        if ($accept !== null && $accept !== '') {
            $headers[] = 'Accept: ' . $accept;
        }

        $range = self::sanitizeRange($range);
        if ($range !== null) {
            $headers[] = 'Range: ' . $range;
        }

        return $headers;
    }

    public static function sanitizeRange(?string $range): ?string
    {
        $range = trim((string) $range);
        if ($range === '') {
            return null;
        }

        return preg_match('/^bytes=\d*-\d*(?:,\d*-\d*)?$/', $range) ? $range : null;
    }

    public static function safeResponseHeader(string $name, string $value): ?string
    {
        $name = strtolower(trim($name));
        $value = trim(str_replace(["\r", "\n"], '', $value));
        if ($value === '') {
            return null;
        }

        $allowed = [
            'accept-ranges' => 'Accept-Ranges',
            'content-length' => 'Content-Length',
            'content-range' => 'Content-Range',
            'content-type' => 'Content-Type',
            'etag' => 'ETag',
            'last-modified' => 'Last-Modified',
        ];

        return isset($allowed[$name]) ? $allowed[$name] . ': ' . $value : null;
    }

    public static function curlBaseOptions(string $url): array
    {
        return [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_LOW_SPEED_LIMIT => 256,
            CURLOPT_LOW_SPEED_TIME => 20,
            CURLOPT_BUFFERSIZE => 1024 * 256,
        ];
    }

    public static function maxPlaylistBytes(): int
    {
        return self::MAX_PLAYLIST_BYTES;
    }
}
