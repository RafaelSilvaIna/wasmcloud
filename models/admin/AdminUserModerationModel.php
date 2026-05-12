<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminUserModerationModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS platform_user_activity_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                event_type VARCHAR(80) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                details JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_pual_user_created (user_id, created_at),
                KEY idx_pual_event (event_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS platform_user_moderation_history (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                admin_id INT NULL,
                action ENUM('suspend','ban','reactivate') NOT NULL,
                reason VARCHAR(500) NULL,
                duration_minutes INT NULL,
                expires_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_pumh_user_created (user_id, created_at),
                KEY idx_pumh_admin (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_subscriptions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                plan_code VARCHAR(40) NOT NULL,
                status ENUM('active','expired','canceled') NOT NULL DEFAULT 'active',
                started_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                canceled_at DATETIME NULL,
                amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_id BIGINT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_status_expiry (user_id, status, expires_at),
                INDEX idx_payment_id (payment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        foreach ([
            "ALTER TABLE platform_users ADD COLUMN moderation_status ENUM('active','suspended','banned') NOT NULL DEFAULT 'active' AFTER status",
            "ALTER TABLE platform_users ADD COLUMN moderation_reason VARCHAR(500) NULL AFTER moderation_status",
            "ALTER TABLE platform_users ADD COLUMN moderation_until DATETIME NULL AFTER moderation_reason",
            "ALTER TABLE platform_users ADD COLUMN moderated_by INT NULL AFTER moderation_until",
            "ALTER TABLE platform_users ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by",
            "ALTER TABLE user_subscriptions ADD COLUMN source ENUM('paid','admin_courtesy') NOT NULL DEFAULT 'paid' AFTER status",
            "ALTER TABLE user_subscriptions ADD COLUMN granted_by_admin_id INT NULL AFTER payment_id",
            "ALTER TABLE user_subscriptions ADD COLUMN grant_reason VARCHAR(255) NULL AFTER granted_by_admin_id",
        ] as $sql) {
            try {
                $this->db->exec($sql);
            } catch (\Throwable) {
            }
        }
    }

    public function listUsers(string $search = '', string $filter = 'all'): array
    {
        $params = [];
        $where = [];

        if ($search !== '') {
            $where[] = "(u.email LIKE ? OR u.phone LIKE ? OR u.full_name LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        if (in_array($filter, ['active', 'suspended', 'banned'], true)) {
            $where[] = "COALESCE(u.moderation_status, 'active') = ?";
            $params[] = $filter;
        }

        if ($filter === 'paid') {
            $where[] = "EXISTS (
                SELECT 1 FROM user_subscriptions sx
                WHERE sx.user_id = u.id AND sx.status = 'active' AND sx.expires_at > NOW() AND COALESCE(sx.source, 'paid') = 'paid'
            )";
        }

        if ($filter === 'free') {
            $where[] = "NOT EXISTS (
                SELECT 1 FROM user_subscriptions sx
                WHERE sx.user_id = u.id AND sx.status = 'active' AND sx.expires_at > NOW() AND COALESCE(sx.source, 'paid') = 'paid'
            )";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.email,
                u.phone,
                u.full_name,
                u.avatar_url,
                u.status,
                COALESCE(u.moderation_status, 'active') AS moderation_status,
                u.moderation_reason,
                u.moderation_until,
                u.created_at,
                u.updated_at,
                COUNT(DISTINCT p.id) AS profiles_count,
                COUNT(DISTINCT CASE WHEN p.is_watching = 1 THEN p.id END) AS active_profiles_count,
                CASE WHEN MAX(CASE WHEN sub.id IS NULL THEN 0 ELSE 1 END) = 1 THEN 'paid' ELSE 'free' END AS plan_type,
                MAX(s.created_at) AS last_login_at,
                MAX(l.created_at) AS last_activity_at
            FROM platform_users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_subscriptions sub ON sub.user_id = u.id AND sub.status = 'active' AND sub.expires_at > NOW() AND COALESCE(sub.source, 'paid') = 'paid'
            LEFT JOIN platform_user_sessions s ON s.user_id = u.id
            LEFT JOIN platform_user_activity_logs l ON l.user_id = u.id
            {$whereSql}
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT 250
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function userDetails(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id, email, phone, full_name, avatar_url, status,
                COALESCE(moderation_status, 'active') AS moderation_status,
                moderation_reason, moderation_until, moderated_by, moderated_at,
                created_at, updated_at,
                (
                    SELECT COUNT(*) FROM profiles p
                    WHERE p.user_id = platform_users.id AND p.is_watching = 1
                ) AS active_profiles_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM user_subscriptions s
                    WHERE s.user_id = platform_users.id AND s.status = 'active' AND s.expires_at > NOW() AND COALESCE(s.source, 'paid') = 'paid'
                ) THEN 'paid' ELSE 'free' END AS plan_type
            FROM platform_users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $profiles = $this->db->prepare("
            SELECT id, profile_name, username, profile_image, is_kids, is_watching, last_active_at
            FROM profiles
            WHERE user_id = ?
            ORDER BY id ASC
        ");
        $profiles->execute([$userId]);

        $logs = $this->db->prepare("
            SELECT id, event_type, ip_address, user_agent, details, created_at
            FROM platform_user_activity_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 80
        ");
        $logs->execute([$userId]);

        $moderation = $this->db->prepare("
            SELECT h.id, h.action, h.reason, h.duration_minutes, h.expires_at, h.created_at,
                   a.display_name AS admin_name, a.email AS admin_email
            FROM platform_user_moderation_history h
            LEFT JOIN admin_users a ON a.id = h.admin_id
            WHERE h.user_id = ?
            ORDER BY h.created_at DESC
            LIMIT 40
        ");
        $moderation->execute([$userId]);

        $chart = $this->db->prepare("
            SELECT DATE(created_at) AS activity_date, COUNT(*) AS total
            FROM platform_user_activity_logs
            WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
            GROUP BY DATE(created_at)
            ORDER BY activity_date ASC
        ");
        $chart->execute([$userId]);

        return [
            'user' => $user,
            'profiles' => $profiles->fetchAll(PDO::FETCH_ASSOC),
            'logs' => $logs->fetchAll(PDO::FETCH_ASSOC),
            'moderation_history' => $moderation->fetchAll(PDO::FETCH_ASSOC),
            'activity_chart' => $this->normalizeChart($chart->fetchAll(PDO::FETCH_ASSOC)),
        ];
    }

    public function adminAuditLogs(int $limit = 80): array
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.event_type, l.ip_address, l.user_agent, l.payload, l.created_at,
                   a.display_name AS admin_name, a.email AS admin_email
            FROM admin_audit_logs l
            LEFT JOIN admin_users a ON a.id = l.admin_id
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', max(1, min(200, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function suspendUser(int $userId, int $adminId, string $reason, int $durationMinutes): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($durationMinutes * 60));
        $stmt = $this->db->prepare("
            UPDATE platform_users
            SET moderation_status = 'suspended',
                moderation_reason = ?,
                moderation_until = ?,
                moderated_by = ?,
                moderated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reason, $expiresAt, $adminId, $userId]);

        $this->insertHistory($userId, $adminId, 'suspend', $reason, $durationMinutes, $expiresAt);
        $this->revokeUserSessions($userId);
    }

    public function banUser(int $userId, int $adminId, string $reason): void
    {
        $stmt = $this->db->prepare("
            UPDATE platform_users
            SET moderation_status = 'banned',
                moderation_reason = ?,
                moderation_until = NULL,
                moderated_by = ?,
                moderated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reason, $adminId, $userId]);

        $this->insertHistory($userId, $adminId, 'ban', $reason, null, null);
        $this->revokeUserSessions($userId);
    }

    public function reactivateUser(int $userId, int $adminId, string $reason): void
    {
        $stmt = $this->db->prepare("
            UPDATE platform_users
            SET moderation_status = 'active',
                moderation_reason = ?,
                moderation_until = NULL,
                moderated_by = ?,
                moderated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reason, $adminId, $userId]);

        $this->insertHistory($userId, $adminId, 'reactivate', $reason, null, null);
    }

    private function revokeUserSessions(int $userId): void
    {
        $this->db->prepare("DELETE FROM platform_user_sessions WHERE user_id = ?")->execute([$userId]);
        $this->db->prepare("UPDATE profile_active_sessions SET is_active = 0 WHERE user_id = ?")->execute([$userId]);
    }

    private function insertHistory(int $userId, int $adminId, string $action, string $reason, ?int $durationMinutes, ?string $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_user_moderation_history
                (user_id, admin_id, action, reason, duration_minutes, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $adminId, $action, $reason, $durationMinutes, $expiresAt]);
    }

    private function normalizeChart(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['activity_date']] = (int) $row['total'];
        }

        $points = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $points[] = [
                'date' => $date,
                'total' => $indexed[$date] ?? 0,
            ];
        }

        return $points;
    }
}
