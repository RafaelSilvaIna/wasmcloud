<?php

declare(strict_types=1);

namespace Hooks\Cdn;

use Models\Cdn\CdnTokenModel;

final class CdnTokenCleanupHook
{
    private const PROBABILITY_PERCENT = 2;

    public static function maybeRun(CdnTokenModel $tokens): void
    {
        try {
            if (random_int(1, 100) <= self::PROBABILITY_PERCENT) {
                $tokens->cleanupExpired(200);
            }
        } catch (\Throwable $ex) {
            error_log('[CDN token cleanup] ' . $ex->getMessage());
        }
    }
}
