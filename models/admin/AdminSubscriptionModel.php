<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminSubscriptionModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
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

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS subscription_events (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                payment_id BIGINT NULL,
                subscription_id BIGINT NULL,
                event_type VARCHAR(80) NOT NULL,
                payload JSON NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_event (user_id, event_type),
                INDEX idx_payment_event (payment_id, event_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        foreach ([
            "ALTER TABLE user_subscriptions ADD COLUMN source ENUM('paid','admin_courtesy') NOT NULL DEFAULT 'paid' AFTER status",
            "ALTER TABLE user_subscriptions ADD COLUMN granted_by_admin_id INT NULL AFTER payment_id",
            "ALTER TABLE user_subscriptions ADD COLUMN grant_reason VARCHAR(255) NULL AFTER granted_by_admin_id",
            "CREATE INDEX idx_user_subscriptions_source ON user_subscriptions (source, status, expires_at)",
        ] as $sql) {
            try {
                $this->db->exec($sql);
            } catch (\Throwable) {
            }
        }
    }

    public function summary(): array
    {
        $stmt = $this->db->query("
            SELECT
                (SELECT COALESCE(SUM(amount), 0)
                 FROM subscription_payments
                 WHERE status = 'paid' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS month_revenue,
                (SELECT COUNT(DISTINCT user_id)
                 FROM user_subscriptions
                 WHERE status = 'active' AND expires_at > NOW() AND COALESCE(source, 'paid') = 'paid') AS paid_subscribers,
                (SELECT COUNT(DISTINCT user_id)
                 FROM user_subscriptions
                 WHERE status = 'active' AND expires_at > NOW() AND source = 'admin_courtesy') AS courtesy_subscribers,
                (SELECT COUNT(DISTINCT user_id)
                 FROM user_subscriptions
                 WHERE status = 'active' AND expires_at > NOW()) AS active_total,
                (SELECT COUNT(DISTINCT user_id)
                 FROM subscription_payments
                 WHERE status = 'paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS monthly_payers
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function revenueSeries(): array
    {
        $stmt = $this->db->query("
            SELECT DATE(paid_at) AS day, SUM(amount) AS revenue, COUNT(*) AS payments
            FROM subscription_payments
            WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
            GROUP BY DATE(paid_at)
            ORDER BY day ASC
        ");
        return $this->normalizeRevenue($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listSubscriptions(): array
    {
        $stmt = $this->db->query("
            SELECT s.*, u.email, u.phone, u.full_name, a.display_name AS admin_name
            FROM user_subscriptions s
            JOIN platform_users u ON u.id = s.user_id
            LEFT JOIN admin_users a ON a.id = s.granted_by_admin_id
            ORDER BY s.created_at DESC
            LIMIT 250
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUsers(string $query): array
    {
        $like = '%' . trim($query) . '%';
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name
            FROM platform_users
            WHERE email LIKE ? OR phone LIKE ? OR full_name LIKE ?
            ORDER BY full_name ASC
            LIMIT 20
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function userById(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, email, phone, full_name FROM platform_users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function grantCourtesy(int $userId, int $adminId, int $durationDays, string $reason): int
    {
        $startedAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'));

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE user_subscriptions
                SET status = 'expired', updated_at = NOW()
                WHERE user_id = ? AND status = 'active'
            ")->execute([$userId]);

            $stmt = $this->db->prepare("
                INSERT INTO user_subscriptions
                    (user_id, plan_code, status, source, started_at, expires_at, amount_paid, payment_id, granted_by_admin_id, grant_reason)
                VALUES
                    (?, 'gold', 'active', 'admin_courtesy', ?, ?, 0.00, NULL, ?, ?)
            ");
            $stmt->execute([$userId, $startedAt, $expiresAt, $adminId, $reason]);
            $id = (int) $this->db->lastInsertId();

            $this->db->prepare("
                INSERT INTO subscription_events (user_id, subscription_id, event_type, payload, ip_address, user_agent)
                VALUES (?, ?, 'admin_courtesy_granted', ?, ?, ?)
            ")->execute([
                $userId,
                $id,
                json_encode(['duration_days' => $durationDays, 'reason' => $reason, 'admin_id' => $adminId]),
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function normalizeRevenue(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['day']] = $row;
        }

        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'day' => $day,
                'revenue' => (float) ($indexed[$day]['revenue'] ?? 0),
                'payments' => (int) ($indexed[$day]['payments'] ?? 0),
            ];
        }
        return $series;
    }
}
