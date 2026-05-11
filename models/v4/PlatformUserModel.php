<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;

class PlatformUserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureModerationColumns();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, password_hash, full_name, avatar_url, status,
                   COALESCE(moderation_status, 'active') AS moderation_status,
                   moderation_reason, moderation_until
            FROM platform_users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, password_hash, full_name, avatar_url, status,
                   COALESCE(moderation_status, 'active') AS moderation_status,
                   moderation_reason, moderation_until
            FROM platform_users
            WHERE phone = ?
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByIdentifier(?string $email, ?string $phone): ?array
    {
        if ($email) {
            return $this->findByEmail($email);
        }

        if ($phone) {
            return $this->findByPhone($phone);
        }

        return null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name, avatar_url, status,
                   COALESCE(moderation_status, 'active') AS moderation_status,
                   moderation_reason, moderation_until
            FROM platform_users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function create(?string $email, ?string $phone, string $passwordHash, string $fullName): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_users (email, phone, password_hash, full_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$email, $phone, $passwordHash, $fullName]);

        return (int) $this->db->lastInsertId();
    }

    public function createSession(int $userId, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_user_sessions
                (user_id, token_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function logActivity(int $userId, string $eventType, array $details = []): void
    {
        $this->ensureActivityTable();

        $stmt = $this->db->prepare("
            INSERT INTO platform_user_activity_logs
                (user_id, event_type, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $eventType,
            $this->clientIp(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            json_encode($details),
        ]);
    }

    private function ensureActivityTable(): void
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
    }

    private function ensureModerationColumns(): void
    {
        foreach ([
            "ALTER TABLE platform_users ADD COLUMN moderation_status ENUM('active','suspended','banned') NOT NULL DEFAULT 'active' AFTER status",
            "ALTER TABLE platform_users ADD COLUMN moderation_reason VARCHAR(500) NULL AFTER moderation_status",
            "ALTER TABLE platform_users ADD COLUMN moderation_until DATETIME NULL AFTER moderation_reason",
            "ALTER TABLE platform_users ADD COLUMN moderated_by INT NULL AFTER moderation_until",
            "ALTER TABLE platform_users ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by",
        ] as $sql) {
            try {
                $this->db->exec($sql);
            } catch (\Throwable) {
            }
        }
    }

    private function clientIp(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '';

        return str_contains($ip, ',') ? trim(explode(',', $ip)[0]) : $ip;
    }

    public function createTwoFactorChallenge(int $userId, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO two_factor_login_challenges
                (user_id, token_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function getTwoFactorChallenge(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, token_hash, expires_at
            FROM two_factor_login_challenges
            WHERE token_hash = ? AND expires_at > NOW() AND consumed_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        return $challenge ?: null;
    }

    public function consumeTwoFactorChallenge(string $tokenHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE two_factor_login_challenges
            SET consumed_at = NOW()
            WHERE token_hash = ? AND consumed_at IS NULL
        ");

        return $stmt->execute([$tokenHash]);
    }
}
