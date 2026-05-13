<?php

declare(strict_types=1);

namespace Helpers\Suporte;

/**
 * In-session rate limiter for support messages.
 * Allows a maximum of 10 messages per minute per session token.
 * Falls back to IP-based limiting for anonymous requests without a token.
 */
final class SupportRateLimit
{
    private const MAX_PER_MINUTE = 10;
    private const WINDOW_SECONDS = 60;

    /**
     * Check and consume one "slot" for the given key (session_token or IP).
     * Returns true if allowed, false if rate limited.
     */
    public static function check(string $key): bool
    {
        $slotKey  = 'support_rl_' . hash('sha256', $key);
        $countKey = $slotKey . '_cnt';
        $timeKey  = $slotKey . '_ts';

        $now   = time();
        $ts    = (int) ($_SESSION[$timeKey] ?? 0);
        $count = (int) ($_SESSION[$countKey] ?? 0);

        if ($now - $ts >= self::WINDOW_SECONDS) {
            $_SESSION[$timeKey]  = $now;
            $_SESSION[$countKey] = 1;
            return true;
        }

        if ($count >= self::MAX_PER_MINUTE) {
            return false;
        }

        $_SESSION[$countKey]++;
        return true;
    }
}
