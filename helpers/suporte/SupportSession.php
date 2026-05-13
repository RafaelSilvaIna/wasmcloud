<?php

declare(strict_types=1);

namespace Helpers\Suporte;

/** Generates and validates the anonymous support session token (64 hex chars). */
final class SupportSession
{
    /** Generate a cryptographically secure 64-char hex token. */
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Validate token format: exactly 64 lowercase hex chars. */
    public static function isValid(string $token): bool
    {
        return preg_match('/^[0-9a-f]{64}$/', $token) === 1;
    }

    /** Retrieve token from X-Support-Token header or POST body. */
    public static function fromRequest(): ?string
    {
        $token = $_SERVER['HTTP_X_SUPPORT_TOKEN']
            ?? $_POST['session_token']
            ?? (self::parseJsonBody()['session_token'] ?? null);

        if (!$token || !self::isValid((string) $token)) {
            return null;
        }

        return (string) $token;
    }

    /** Return the current authenticated user ID from session, or null. */
    public static function authenticatedUserId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        return $id !== null ? (int) $id : null;
    }

    /** Return the authenticated user's display name, or 'Visitante'. */
    public static function displayName(): string
    {
        return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Visitante';
    }

    /** Best-effort client IP. */
    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return explode(',', $_SERVER[$k])[0];
            }
        }
        return '0.0.0.0';
    }

    private static function parseJsonBody(): array
    {
        static $parsed = null;
        if ($parsed === null) {
            $raw = file_get_contents('php://input');
            $parsed = $raw ? (json_decode($raw, true) ?? []) : [];
        }
        return $parsed;
    }
}
