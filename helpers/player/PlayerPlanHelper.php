<?php

declare(strict_types=1);

namespace Helpers\Player;

use PDO;
use Throwable;

final class PlayerPlanHelper
{
    public static function hasProAccess(?PDO $pdo, int $userId): bool
    {
        if (!$pdo || $userId <= 0) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 1
                FROM user_subscriptions
                WHERE user_id = ?
                  AND status = 'active'
                  AND expires_at > NOW()
                  AND (
                      source = 'admin_courtesy'
                      OR (COALESCE(source, 'paid') = 'paid' AND plan_code <> 'casual')
                  )
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return self::hasLegacyPaidAccess($pdo, $userId);
        }
    }

    private static function hasLegacyPaidAccess(PDO $pdo, int $userId): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT 1
                FROM user_subscriptions
                WHERE user_id = ?
                  AND status = 'active'
                  AND expires_at > NOW()
                  AND plan_code <> 'casual'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
