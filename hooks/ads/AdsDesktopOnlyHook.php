<?php
declare(strict_types=1);

namespace Hooks\Ads;

final class AdsDesktopOnlyHook
{
    public static function isMobileRequest(): bool
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return preg_match('/android|iphone|ipad|ipod|mobile|blackberry|opera mini|iemobile/', $ua) === 1;
    }
}
