<?php

declare(strict_types=1);

namespace Services\Cdn;

final class CdnTokenService
{
    private const DEFAULT_TTL = 900;

    private string $secret;
    private int $ttl;

    public function __construct(?string $secret = null, ?int $ttl = null)
    {
        $fallback = defined('SESSION_NAME') && defined('DB_PIPO') && defined('DB_CINE')
            ? hash('sha256', SESSION_NAME . (DB_PIPO['pass'] ?? '') . (DB_CINE['pass'] ?? ''))
            : hash('sha256', __DIR__);

        $this->secret = (string) ($secret ?? getenv('PIPOCINE_CDN_SECRET') ?: $fallback);
        $this->ttl = max(60, min(3600, (int) ($ttl ?? getenv('PIPOCINE_CDN_TTL') ?: self::DEFAULT_TTL)));
    }

    public function issue(array $claims, string $kind, ?string $profile = null): string
    {
        $now = time();
        $payload = [
            'v' => 1,
            'kind' => $kind,
            'profile' => $profile,
            'uid' => (int) ($_SESSION['user_id'] ?? 0),
            'pid' => (int) ($_SESSION['profile_id'] ?? 0),
            'sid' => hash('sha256', session_id()),
            'uah' => hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
            'iat' => $now,
            'exp' => $now + $this->ttl,
            'nonce' => bin2hex(random_bytes(8)),
        ] + $claims;

        return $this->encode($payload);
    }

    public function validate(string $token, string $kind, ?string $profile = null): ?array
    {
        $payload = $this->decode($token);
        if (!$payload) {
            return null;
        }

        if (($payload['kind'] ?? '') !== $kind) {
            return null;
        }

        if ($profile !== null && ($payload['profile'] ?? '') !== $profile) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        if ((int) ($payload['uid'] ?? 0) !== (int) ($_SESSION['user_id'] ?? 0)) {
            return null;
        }

        if ((int) ($payload['pid'] ?? 0) !== (int) ($_SESSION['profile_id'] ?? 0)) {
            return null;
        }

        if (($payload['sid'] ?? '') !== hash('sha256', session_id())) {
            return null;
        }

        if (($payload['uah'] ?? '') !== hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
            return null;
        }

        return $payload;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_UNESCAPED_SLASHES));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));
        return "{$header}.{$body}.{$sig}";
    }

    private function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $sig] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $json = $this->base64UrlDecode($body);
        $payload = json_decode($json, true);
        return is_array($payload) ? $payload : null;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $data = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }
}
