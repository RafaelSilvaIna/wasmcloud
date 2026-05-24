<?php

declare(strict_types=1);

namespace Services\Cdn;

use Helpers\Cdn\CdnHeaders;
use Helpers\Cdn\CdnHttpClient;
use Helpers\Cdn\CdnUrlGuard;

require_once __DIR__ . '/../../helpers/cdn/CdnHeaders.php';
require_once __DIR__ . '/../../helpers/cdn/CdnHttpClient.php';
require_once __DIR__ . '/../../helpers/cdn/CdnUrlGuard.php';
require_once __DIR__ . '/CdnTokenService.php';

final class CdnProxyStreamService
{
    public function __construct(private ?CdnTokenService $tokens = null)
    {
        $this->tokens ??= new CdnTokenService();
    }

    public function stream(array $source): void
    {
        $url = (string) ($source['url'] ?? '');
        $origin = (string) ($source['origin'] ?? '');
        $mediaType = strtolower((string) ($source['media_type'] ?? $this->detectMediaType($url)));

        CdnUrlGuard::assertAllowedExternalUrl($url);

        if ($mediaType === 'm3u8') {
            $this->streamPlaylist($url, $origin);
            return;
        }

        $this->streamBinary($url);
    }

    private function streamBinary(string $url): void
    {
        if (!function_exists('curl_init')) {
            CdnHeaders::noStore();
            http_response_code(503);
            echo 'cURL indisponivel no servidor.';
            return;
        }

        @set_time_limit(0);
        ignore_user_abort(false);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $range = CdnHttpClient::sanitizeRange($_SERVER['HTTP_RANGE'] ?? null);
        $headers = [];
        $status = 0;
        $headersSent = false;
        $bodyBytes = 0;

        $sendHeaders = function () use (&$headersSent, &$headers, &$status, $url, $method): void {
            if ($headersSent) {
                return;
            }

            $streamable = in_array($status, [200, 206], true);
            if (!$streamable && $status !== 416) {
                CdnHeaders::noStore();
                http_response_code(424);
                header('Content-Type: text/plain; charset=utf-8');
                header('X-Pipocine-CDN: internal-origin-proxy');
                header('X-Pipocine-CDN-Error: upstream_unavailable');
                $headersSent = true;
                if ($method !== 'HEAD') {
                    echo 'Fonte de video indisponivel no proxy CDN.';
                }
                return;
            }

            http_response_code($status);
            CdnHeaders::proxy($this->detectMediaType($url), 60);

            $hasContentType = false;
            foreach ($headers as $name => $value) {
                $safe = CdnHttpClient::safeResponseHeader($name, $value);
                if ($safe === null) {
                    continue;
                }
                if (strtolower($name) === 'content-type') {
                    $hasContentType = true;
                }
                header($safe);
            }

            if (!$hasContentType) {
                header('Content-Type: ' . $this->contentTypeForUrl($url));
            }

            $headersSent = true;
            flush();
        };

        $ch = curl_init();
        curl_setopt_array($ch, CdnHttpClient::curlBaseOptions($url) + [
            CURLOPT_HTTPHEADER => CdnHttpClient::requestHeaders($range, 'video/*,audio/*,application/octet-stream,*/*;q=0.8', $url),
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$headers, &$status): int {
                $trimmed = trim($line);
                if (preg_match('/^HTTP\/\S+\s+(\d+)/i', $trimmed, $m)) {
                    $status = (int) $m[1];
                    $headers = [];
                    return strlen($line);
                }

                $pos = strpos($line, ':');
                if ($pos !== false) {
                    $name = strtolower(trim(substr($line, 0, $pos)));
                    $value = trim(substr($line, $pos + 1));
                    $headers[$name] = $value;
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$sendHeaders, &$status, &$bodyBytes): int {
                $sendHeaders();
                if (!in_array($status, [200, 206], true)) {
                    return 0;
                }

                echo $chunk;
                $bodyBytes += strlen($chunk);
                flush();
                return strlen($chunk);
            },
        ]);

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlMessage = curl_error($ch);
        curl_close($ch);

        if (!$headersSent) {
            $sendHeaders();
        }

        if ($curlError && $bodyBytes === 0 && !connection_aborted()) {
            error_log('[CDN proxy] curl_error=' . $curlError . ' message=' . $curlMessage . ' host=' . (parse_url($url, PHP_URL_HOST) ?: ''));
        }
    }

    private function streamPlaylist(string $url, string $origin): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $playlist = $this->fetchPlaylist($url);
        $baseUrl = $playlist['effective_url'] ?: $url;
        $rewritten = $this->rewritePlaylist((string) $playlist['body'], $baseUrl, $origin);

        while (ob_get_level()) {
            ob_end_clean();
        }

        CdnHeaders::playlist();
        header('Content-Length: ' . strlen($rewritten));

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'HEAD') {
            echo $rewritten;
        }
    }

    private function fetchPlaylist(string $url): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL indisponivel no servidor.');
        }

        $body = '';
        $status = 0;

        $ch = curl_init();
        curl_setopt_array($ch, CdnHttpClient::curlBaseOptions($url) + [
            CURLOPT_HTTPHEADER => CdnHttpClient::requestHeaders(null, 'application/vnd.apple.mpegurl,application/x-mpegURL,text/plain,*/*;q=0.8', $url),
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$status): int {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/i', trim($line), $m)) {
                    $status = (int) $m[1];
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$body): int {
                $body .= $chunk;
                return strlen($body) > CdnHttpClient::maxPlaylistBytes() ? 0 : strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $curlError = curl_errno($ch);
        $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlMessage = curl_error($ch);
        curl_close($ch);

        if ($curlError || !in_array($status, [200, 206], true) || trim($body) === '') {
            throw new \RuntimeException('Nao foi possivel carregar playlist HLS. status=' . $status . ' curl=' . $curlError . ' ' . $curlMessage);
        }

        return [
            'body' => $body,
            'effective_url' => $effectiveUrl,
        ];
    }

    private function rewritePlaylist(string $playlist, string $baseUrl, string $origin): string
    {
        $lines = preg_split('/\r\n|\n|\r/', $playlist);
        if (!is_array($lines)) {
            return $playlist;
        }

        $out = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $out[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                if (str_contains($trimmed, 'URI="')) {
                    $out[] = preg_replace_callback('/URI="([^"]+)"/', function (array $m) use ($baseUrl, $origin): string {
                        $absolute = $this->absoluteUrl($m[1], $baseUrl);
                        if ($absolute === null) {
                            return $m[0];
                        }
                        return 'URI="' . $this->tokenizedUrl($absolute, $origin) . '"';
                    }, $line) ?? $line;
                    continue;
                }

                $out[] = $line;
                continue;
            }

            $absolute = $this->absoluteUrl($trimmed, $baseUrl);
            $out[] = $absolute === null ? $line : $this->tokenizedUrl($absolute, $origin);
        }

        return implode("\n", $out);
    }

    private function tokenizedUrl(string $url, string $origin): string
    {
        CdnUrlGuard::assertAllowedExternalUrl($url);
        $token = $this->tokens->issue([
            'url' => $url,
            'origin' => $origin !== '' ? $origin : $this->originFromUrl($url),
            'media_type' => $this->detectMediaType($url),
        ], 'video');

        return '/video/cdn?token=' . rawurlencode($token);
    }

    private function absoluteUrl(string $uri, string $baseUrl): ?string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $uri)) {
            $scheme = strtolower((string) parse_url($uri, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $uri : null;
        }

        $base = parse_url($baseUrl);
        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) ($base['host'] ?? '');
        if ($host === '') {
            return null;
        }

        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (str_starts_with($uri, '//')) {
            return $scheme . ':' . $uri;
        }

        if (str_starts_with($uri, '/')) {
            return $scheme . '://' . $host . $port . $this->normalizePath($uri);
        }

        $basePath = (string) ($base['path'] ?? '/');
        $dir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
        return $scheme . '://' . $host . $port . $this->normalizePath(($dir === '' ? '' : $dir) . '/' . $uri);
    }

    private function normalizePath(string $path): string
    {
        $query = '';
        $fragment = '';

        $fragmentPos = strpos($path, '#');
        if ($fragmentPos !== false) {
            $fragment = substr($path, $fragmentPos);
            $path = substr($path, 0, $fragmentPos);
        }

        $queryPos = strpos($path, '?');
        if ($queryPos !== false) {
            $query = substr($path, $queryPos);
            $path = substr($path, 0, $queryPos);
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return '/' . implode('/', $segments) . $query . $fragment;
    }

    private function detectMediaType(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? $url);
        if (str_contains($path, '.m3u8')) return 'm3u8';
        if (str_contains($path, '.m4s')) return 'm4s';
        if (str_contains($path, '.ts')) return 'ts';
        if (str_contains($path, '.mp4')) return 'mp4';
        if (str_contains($path, '.m4a')) return 'm4a';
        if (str_contains($path, '.aac')) return 'aac';
        if (str_contains($path, '.webm')) return 'webm';
        if (str_contains($path, '.mkv')) return 'mkv';
        if (str_contains($path, '.vtt')) return 'vtt';
        return 'binary';
    }

    private function contentTypeForUrl(string $url): string
    {
        return match ($this->detectMediaType($url)) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'm4s', 'mp4' => 'video/mp4',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'ts' => 'video/mp2t',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'vtt' => 'text/vtt; charset=utf-8',
            default => 'application/octet-stream',
        };
    }

    private function originFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        return (parse_url($url, PHP_URL_SCHEME) ?: 'https') . '://' . $host;
    }
}
