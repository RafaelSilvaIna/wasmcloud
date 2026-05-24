<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminBoxModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
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

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_box_campaigns (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                audience ENUM('all','user') NOT NULL,
                target_email VARCHAR(190) NULL,
                target_user_id INT NULL,
                box_type VARCHAR(60) NOT NULL,
                title VARCHAR(160) NOT NULL,
                body VARCHAR(600) NOT NULL,
                action_url VARCHAR(255) NULL,
                action_label VARCHAR(80) NULL,
                tone VARCHAR(32) NOT NULL DEFAULT 'info',
                recipients_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_box_created (created_at),
                KEY idx_admin_box_audience (audience),
                KEY idx_admin_box_target (target_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function summary(): array
    {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM pipocine_box_items WHERE type LIKE 'admin_%') AS admin_items,
                (SELECT COUNT(*) FROM pipocine_box_items WHERE type LIKE 'admin_%' AND status = 'unread') AS unread_admin_items,
                (SELECT COUNT(*) FROM admin_box_campaigns) AS campaigns,
                (SELECT COALESCE(SUM(recipients_count), 0) FROM admin_box_campaigns) AS total_recipients,
                (SELECT COUNT(*) FROM platform_users WHERE status = 'active') AS active_users
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function campaigns(): array
    {
        $stmt = $this->db->query("
            SELECT c.*, a.display_name AS admin_name
            FROM admin_box_campaigns c
            LEFT JOIN admin_users a ON a.id = c.admin_id
            ORDER BY c.created_at DESC
            LIMIT 80
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUsers(string $query): array
    {
        $like = '%' . trim($query) . '%';
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name, status
            FROM platform_users
            WHERE email LIKE ? OR phone LIKE ? OR full_name LIKE ?
            ORDER BY full_name ASC
            LIMIT 20
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function userByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name, status
            FROM platform_users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function createForUser(
        int $adminId,
        int $targetUserId,
        string $type,
        string $title,
        string $body,
        array $payload
    ): int {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->db->beginTransaction();
        try {
            $item = $this->insertItem($targetUserId, $type, $title, $body, $jsonPayload);
            $this->insertCampaign($adminId, 'user', $targetUserId, $payload['target_email'] ?? null, $type, $title, $body, $payload, 1);
            $this->db->commit();
            return $item;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createForAll(
        int $adminId,
        string $type,
        string $title,
        string $body,
        array $payload
    ): int {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO pipocine_box_items
                    (target_user_id, actor_user_id, type, title, body, action_status, payload)
                SELECT id, NULL, ?, ?, ?, 'none', ?
                FROM platform_users
                WHERE status = 'active'
            ");
            $stmt->execute([$type, $title, $body, $jsonPayload]);
            $count = $stmt->rowCount();

            $this->insertCampaign($adminId, 'all', null, null, $type, $title, $body, $payload, $count);
            $this->db->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function insertItem(
        int $targetUserId,
        string $type,
        string $title,
        string $body,
        ?string $payload
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_box_items
                (target_user_id, actor_user_id, type, title, body, action_status, payload)
            VALUES (?, NULL, ?, ?, ?, 'none', ?)
        ");
        $stmt->execute([$targetUserId, $type, $title, $body, $payload]);
        return (int) $this->db->lastInsertId();
    }

    private function insertCampaign(
        int $adminId,
        string $audience,
        ?int $targetUserId,
        ?string $targetEmail,
        string $type,
        string $title,
        string $body,
        array $payload,
        int $recipients
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO admin_box_campaigns
                (admin_id, audience, target_email, target_user_id, box_type, title, body, action_url, action_label, tone, recipients_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminId,
            $audience,
            $targetEmail,
            $targetUserId,
            $type,
            $title,
            $body,
            $payload['action_url'] ?? null,
            $payload['action_label'] ?? null,
            $payload['tone'] ?? 'info',
            max(0, $recipients),
        ]);
    }
}
