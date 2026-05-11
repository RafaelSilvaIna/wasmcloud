<?php
declare(strict_types=1);

namespace Helpers\Admin;

final class AdminJwt
{
    private const ALG = 'HS256';
    private const TYP = 'JWT';
    private const ISS = 'pipocine-admin';
    private const AUD = 'pipocine-admin-panel';

    public static function issue(array $claims, string $secret, int $ttlSeconds = 3600): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iss' => self::ISS,
            'aud' => self::AUD,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $header = ['alg' => self::ALG, 'typ' => self::TYP];
        $head = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $body = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = self::sign($head . '.' . $body, $secret);

        return $head . '.' . $body . '.' . $signature;
    }

    public static function verify(string $jwt, string $secret): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$head, $body, $signature] = $parts;
        $expected = self::sign($head . '.' . $body, $secret);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $header = json_decode(self::base64UrlDecode($head), true);
        $payload = json_decode(self::base64UrlDecode($body), true);
        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== self::ALG) {
            return null;
        }

        $now = time();
        if (($payload['iss'] ?? '') !== self::ISS || ($payload['aud'] ?? '') !== self::AUD) {
            return null;
        }
        if ((int) ($payload['nbf'] ?? 0) > $now || (int) ($payload['exp'] ?? 0) <= $now) {
            return null;
        }

        return $payload;
    }

    private static function sign(string $data, string $secret): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
