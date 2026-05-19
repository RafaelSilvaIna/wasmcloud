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
            if ($stmt->fetchColumn()) {
                return true;
            }

            return self::hasFamilyBenefit($pdo, $userId);
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
            if ($stmt->fetchColumn()) {
                return true;
            }

            return self::hasFamilyBenefit($pdo, $userId);
        } catch (Throwable) {
            return false;
        }
    }

    private static function hasFamilyBenefit(PDO $pdo, int $userId): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT 1
                FROM family_memberships fm
                JOIN user_subscriptions s
                  ON s.user_id = fm.owner_user_id
                 AND s.status = 'active'
                 AND s.expires_at > NOW()
                JOIN subscription_plans p
                  ON p.code = s.plan_code
                 AND p.is_active = 1
                 AND p.family_member_limit > 0
                WHERE fm.member_user_id = ?
                  AND fm.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
