<?php

declare(strict_types=1);

namespace Helpers\Device;

final class DeviceFingerprint
{
    private const COOKIE_NAME = '_pdid';
    private const COOKIE_TTL = 31_536_000;
    private const COOKIE_DOMAIN = '';

    public static function resolve(): string
    {
        return hash('sha256', self::resolveCookieToken());
    }

    public static function partialIp(): string
    {
        $ip = self::realIp();

        if (str_starts_with($ip, '::ffff:')) {
            $ip = substr($ip, 7);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3));
        }

        $groups = explode(':', $ip);
        return implode(':', array_slice($groups, 0, 4));
    }

    public static function uaHash(): string
    {
        return hash('sha256', self::softFingerprint());
    }

    public static function softFingerprint(): string
    {
        return implode('|', [
            strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''),
            self::langPrint(),
            self::platformToken(),
        ]);
    }

    public static function deviceLabel(): string
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'safari/') && !str_contains($ua, 'chrome') => 'Safari',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'samsung') => 'Samsung Browser',
            str_contains($ua, 'webos') => 'WebOS Browser',
            str_contains($ua, 'tizen') => 'Tizen Browser',
            default => 'Navegador',
        };

        $platform = match (true) {
            str_contains($ua, 'smart-tv')
                || str_contains($ua, 'googletv')
                || str_contains($ua, 'android tv')
                || str_contains($ua, 'webos')
                || str_contains($ua, 'tizen')
                || str_contains($ua, 'hbbtv') => 'TV',
            str_contains($ua, 'ipad') => 'iPad',
            str_contains($ua, 'iphone') => 'iPhone',
            str_contains($ua, 'android') && str_contains($ua, 'mobile') => 'Android',
            str_contains($ua, 'android') => 'Tablet Android',
            str_contains($ua, 'macintosh') || str_contains($ua, 'mac os x') => 'Mac',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Dispositivo',
        };

        return "{$browser} em {$platform}";
    }

    private static function resolveCookieToken(): string
    {
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $token = (string) $_COOKIE[self::COOKIE_NAME];
            if (preg_match('/^[0-9a-f]{64}$/', $token)) {
                self::refreshCookie($token);
                return $token;
            }
        }

        $token = bin2hex(random_bytes(32));
        self::refreshCookie($token);
        $_COOKIE[self::COOKIE_NAME] = $token;

        return $token;
    }

    private static function refreshCookie(string $token): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + self::COOKIE_TTL,
            'path' => '/',
            'domain' => self::COOKIE_DOMAIN,
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function langPrint(): string
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return substr(strtolower($lang), 0, 20);
    }

    private static function platformToken(): string
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        return match (true) {
            str_contains($ua, 'windows') => 'win',
            str_contains($ua, 'macintosh') => 'mac',
            str_contains($ua, 'ipad') => 'ipad',
            str_contains($ua, 'iphone') => 'iphone',
            str_contains($ua, 'android') && str_contains($ua, 'mobile') => 'android_mobile',
            str_contains($ua, 'android') => 'android_tablet',
            str_contains($ua, 'linux') => 'linux',
            str_contains($ua, 'smart-tv')
                || str_contains($ua, 'googletv')
                || str_contains($ua, 'hbbtv')
                || str_contains($ua, 'tizen')
                || str_contains($ua, 'webos') => 'tv',
            default => 'unknown',
        };
    }

    private static function realIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            $val = $_SERVER[$key] ?? '';
            if ($val === '') {
                continue;
            }

            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
