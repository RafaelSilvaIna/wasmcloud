<?php
declare(strict_types=1);

namespace Hooks\Ads;

final class AdsAuthHook
{
    public static function requireCommercialLogin(): void
    {
        if (empty($_SESSION['ads_account_id'])) {
            header('Location: /ads/login');
            exit;
        }
    }
}
