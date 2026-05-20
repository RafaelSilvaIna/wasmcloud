<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;

class FamilyBoxModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS family_memberships (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_user_id INT NOT NULL,
                member_user_id INT NOT NULL,
                status ENUM('active','removed') NOT NULL DEFAULT 'active',
                accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                removed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_family_owner_status (owner_user_id, status),
                KEY idx_family_member_status (member_user_id, status),
                UNIQUE KEY uq_family_pair (owner_user_id, member_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_box_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                target_user_id INT NOT NULL,
                actor_user_id INT NULL,
                type VARCHAR(60) NOT NULL,
                title VARCHAR(160) NOT NULL,
                body VARCHAR(600) NOT NULL,
                status ENUM('unread','read') NOT NULL DEFAULT 'unread',
                action_status ENUM('none','pending','accepted','declined','canceled') NOT NULL DEFAULT 'none',
                payload JSON NULL,
                acted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_box_target_created (target_user_id, created_at),
                KEY idx_box_target_action (target_user_id, action_status),
                KEY idx_box_actor_type (actor_user_id, type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function userByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id, email, full_name, avatar_url, status FROM platform_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function userById(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, email, full_name, avatar_url, status FROM platform_users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function activeSubscription(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, p.family_member_limit
            FROM user_subscriptions s
            JOIN subscription_plans p ON p.code = s.plan_code
            WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()
            ORDER BY s.expires_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        return $subscription ?: null;
    }

    public function activeMembers(int $ownerUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT fm.*, u.full_name, u.email, u.avatar_url
            FROM family_memberships fm
            JOIN platform_users u ON u.id = fm.member_user_id
            WHERE fm.owner_user_id = ? AND fm.status = 'active'
            ORDER BY fm.accepted_at DESC
        ");
        $stmt->execute([$ownerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeMemberCount(int $ownerUserId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM family_memberships WHERE owner_user_id = ? AND status = 'active'");
        $stmt->execute([$ownerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function activeMembershipBetween(int $ownerUserId, int $memberUserId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM family_memberships
            WHERE owner_user_id = ? AND member_user_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$ownerUserId, $memberUserId]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        return $membership ?: null;
    }

    public function activeMembershipForMember(int $memberUserId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM family_memberships
            WHERE member_user_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$memberUserId]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        return $membership ?: null;
    }

    public function activeFamilyBenefitForMember(int $memberUserId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    fm.*,
                    owner.full_name AS owner_name,
                    owner.email AS owner_email,
                    s.id AS owner_subscription_id,
                    s.plan_code,
                    s.expires_at AS owner_subscription_expires_at,
                    p.name AS plan_name,
                    p.device_limit,
                    p.profile_limit,
                    p.family_member_limit,
                    p.benefits_json
                FROM family_memberships fm
                JOIN platform_users owner ON owner.id = fm.owner_user_id
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
                ORDER BY s.expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$memberUserId]);
            $benefit = $stmt->fetch(PDO::FETCH_ASSOC);
            return $benefit ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function pendingInvite(int $ownerUserId, int $targetUserId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM pipocine_box_items
            WHERE actor_user_id = ?
              AND target_user_id = ?
              AND type = 'family_invite'
              AND action_status = 'pending'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$ownerUserId, $targetUserId]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        return $invite ?: null;
    }

    public function createInvite(int $ownerUserId, int $targetUserId, string $ownerName): int
    {
        $payload = json_encode(['owner_user_id' => $ownerUserId, 'target_user_id' => $targetUserId]);
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_box_items
                (target_user_id, actor_user_id, type, title, body, action_status, payload)
            VALUES (?, ?, 'family_invite', ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $targetUserId,
            $ownerUserId,
            'Convite familiar Pipocine',
            $ownerName . ' quer adicionar voce como membro familiar do Plano Gold.',
            $payload,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function inbox(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, u.full_name AS actor_name, u.email AS actor_email, u.avatar_url AS actor_avatar
            FROM pipocine_box_items b
            LEFT JOIN platform_users u ON u.id = b.actor_user_id
            WHERE b.target_user_id = ?
            ORDER BY b.created_at DESC
            LIMIT 80
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function renewalSubscriptionsDueForNotice(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    s.id,
                    s.user_id,
                    s.plan_code,
                    COALESCE(s.source, 'paid') AS source,
                    s.expires_at,
                    CEIL(TIMESTAMPDIFF(SECOND, NOW(), s.expires_at) / 86400) AS days_left
                FROM user_subscriptions s
                WHERE s.user_id = ?
                  AND s.status = 'active'
                  AND s.plan_code = 'gold'
                  AND s.expires_at > NOW()
                  AND COALESCE(s.source, 'paid') IN ('paid', 'admin_courtesy')
                HAVING days_left IN (14, 5, 1)
                ORDER BY s.expires_at ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    public function renewalSubscriptionsDueForNotices(int $limit = 1000): array
    {
        $limit = max(1, min(5000, $limit));

        try {
            $stmt = $this->db->prepare("
                SELECT
                    s.id,
                    s.user_id,
                    s.plan_code,
                    COALESCE(s.source, 'paid') AS source,
                    s.expires_at,
                    CEIL(TIMESTAMPDIFF(SECOND, NOW(), s.expires_at) / 86400) AS days_left
                FROM user_subscriptions s
                WHERE s.status = 'active'
                  AND s.plan_code = 'gold'
                  AND s.expires_at > NOW()
                  AND COALESCE(s.source, 'paid') IN ('paid', 'admin_courtesy')
                HAVING days_left IN (14, 5, 1)
                ORDER BY s.expires_at ASC, s.id ASC
                LIMIT {$limit}
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    public function createNoticeIfMissing(
        int $targetUserId,
        ?int $actorUserId,
        string $type,
        string $dedupeKey,
        string $title,
        string $body,
        array $payload = []
    ): ?int {
        $payload['dedupe_key'] = $dedupeKey;
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $this->db->prepare("
                SELECT id
                FROM pipocine_box_items
                WHERE target_user_id = ?
                  AND type = ?
                  AND JSON_UNQUOTE(JSON_EXTRACT(payload, '$.dedupe_key')) = ?
                LIMIT 1
            ");
            $stmt->execute([$targetUserId, $type, $dedupeKey]);
            if ($stmt->fetchColumn()) {
                return null;
            }
        } catch (\Throwable) {
            $stmt = $this->db->prepare("
                SELECT id
                FROM pipocine_box_items
                WHERE target_user_id = ? AND type = ? AND body = ?
                LIMIT 1
            ");
            $stmt->execute([$targetUserId, $type, $body]);
            if ($stmt->fetchColumn()) {
                return null;
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO pipocine_box_items
                (target_user_id, actor_user_id, type, title, body, action_status, payload)
            VALUES (?, ?, ?, ?, ?, 'none', ?)
        ");
        $stmt->execute([$targetUserId, $actorUserId, $type, $title, $body, $jsonPayload]);
        return (int) $this->db->lastInsertId();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM pipocine_box_items WHERE target_user_id = ? AND status = 'unread'");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function boxItemForUser(int $itemId, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pipocine_box_items WHERE id = ? AND target_user_id = ? LIMIT 1");
        $stmt->execute([$itemId, $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    public function markRead(int $itemId, int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE pipocine_box_items SET status = 'read', updated_at = NOW() WHERE id = ? AND target_user_id = ?");
        $stmt->execute([$itemId, $userId]);
    }

    public function acceptInvite(array $item, int $limit): bool
    {
        $ownerUserId = (int) $item['actor_user_id'];
        $memberUserId = (int) $item['target_user_id'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE pipocine_box_items
                SET status = 'read', action_status = 'accepted', acted_at = NOW(), updated_at = NOW()
                WHERE id = ? AND target_user_id = ? AND action_status = 'pending'
            ");
            $stmt->execute([(int) $item['id'], $memberUserId]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT id
                FROM family_memberships
                WHERE owner_user_id = ? AND status = 'active'
                FOR UPDATE
            ");
            $stmt->execute([$ownerUserId]);
            if (count($stmt->fetchAll(PDO::FETCH_ASSOC)) >= $limit) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT owner_user_id
                FROM family_memberships
                WHERE member_user_id = ? AND status = 'active'
                FOR UPDATE
            ");
            $stmt->execute([$memberUserId]);
            $existingFamily = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existingFamily && (int) $existingFamily['owner_user_id'] !== $ownerUserId) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO family_memberships (owner_user_id, member_user_id, status, accepted_at)
                VALUES (?, ?, 'active', NOW())
                ON DUPLICATE KEY UPDATE status = 'active', accepted_at = NOW(), removed_at = NULL, updated_at = NOW()
            ");
            $stmt->execute([$ownerUserId, $memberUserId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function declineInvite(array $item): void
    {
        $stmt = $this->db->prepare("
            UPDATE pipocine_box_items
            SET status = 'read', action_status = 'declined', acted_at = NOW(), updated_at = NOW()
            WHERE id = ? AND target_user_id = ? AND action_status = 'pending'
        ");
        $stmt->execute([(int) $item['id'], (int) $item['target_user_id']]);
    }

    public function removeMember(int $ownerUserId, int $memberUserId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE family_memberships
            SET status = 'removed', removed_at = NOW(), updated_at = NOW()
            WHERE owner_user_id = ? AND member_user_id = ? AND status = 'active'
        ");
        return $stmt->execute([$ownerUserId, $memberUserId]) && $stmt->rowCount() > 0;
    }
}
