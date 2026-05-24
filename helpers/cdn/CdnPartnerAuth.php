<?php

declare(strict_types=1);

namespace Helpers\Cdn;

final class CdnPartnerAuth
{
    private const ENV_CONFIG = 'PIPOCINE_CDN_PARTNER_AUTH_JSON';
    private const DENIED_HEADERS = [
        'cookie',
        'host',
        'origin',
        'referer',
        'sec-fetch-dest',
        'sec-fetch-mode',
        'sec-fetch-site',
        'sec-fetch-user',
        'user-agent',
    ];

    public static function headersFor(string $url): array
    {
        $config = self::matchConfig($url);
        if (!$config) {
            return [];
        }

        $headers = [];
        foreach (($config['headers'] ?? []) as $name => $value) {
            $name = trim((string) $name);
            $value = trim((string) $value);

            if ($name === '' || $value === '' || self::isDeniedHeader($name)) {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                continue;
            }

            $headers[] = $name . ': ' . str_replace(["\r", "\n"], '', $value);
        }

        return $headers;
    }

    public static function curlOptionsFor(string $url): array
    {
        $config = self::matchConfig($url);
        if (!$config) {
            return [];
        }

        $options = [];
        $cert = (string) ($config['mtls_cert'] ?? '');
        $key = (string) ($config['mtls_key'] ?? '');

        if ($cert !== '' && is_file($cert)) {
            $options[CURLOPT_SSLCERT] = $cert;
        }

        if ($key !== '' && is_file($key)) {
            $options[CURLOPT_SSLKEY] = $key;
        }

        $pass = (string) ($config['mtls_key_pass'] ?? '');
        if ($pass !== '') {
            $options[CURLOPT_KEYPASSWD] = $pass;
        }

        return $options;
    }

    public static function hasConfigFor(string $url): bool
    {
        return self::matchConfig($url) !== null;
    }

    private static function matchConfig(string $url): ?array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }

        foreach (self::config() as $pattern => $config) {
            if (!is_array($config) || array_key_exists('enabled', $config) && !$config['enabled']) {
                continue;
            }

            if (self::hostMatches($host, strtolower((string) $pattern))) {
                return $config;
            }
        }

        return null;
    }

    private static function hostMatches(string $host, string $pattern): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return false;
        }

        if ($host === $pattern) {
            return true;
        }

        if (str_starts_with($pattern, '*.')) {
            $suffix = substr($pattern, 1);
            return str_ends_with($host, $suffix) && $host !== ltrim($suffix, '.');
        }

        return false;
    }

    private static function isDeniedHeader(string $name): bool
    {
        return in_array(strtolower($name), self::DENIED_HEADERS, true)
            || str_starts_with(strtolower($name), 'sec-');
    }

    private static function config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $raw = trim((string) getenv(self::ENV_CONFIG));
        if ($raw === '') {
            return $config = [];
        }

        $decoded = json_decode($raw, true);
        return $config = is_array($decoded) ? $decoded : [];
    }
}
