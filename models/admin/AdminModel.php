<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminModel
{
    private const DEFAULT_EMAIL = 'mrphantommt@gmail.com';
    private const LEGACY_EMAILS = ['mrphantm@gmail.com'];
    private const DEFAULT_PASSWORD = 'MR12MT34MTM"';
    private const DEFAULT_IP = '128.201.122.3';

    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(120) NOT NULL DEFAULT 'Administrador',
                status ENUM('active','disabled') NOT NULL DEFAULT 'active',
                last_login_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_allowed_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                label VARCHAR(120) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                jti_hash VARCHAR(64) NOT NULL UNIQUE,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_id (admin_id),
                KEY idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NULL,
                event_type VARCHAR(80) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) NOT NULL,
                payload JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_event (event_type),
                KEY idx_admin_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->seedDefaults();
        $this->cleanupExpiredSessions();
    }

    public function isIpAllowed(string $ip): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM admin_allowed_ips WHERE ip_address = ? AND active = 1 LIMIT 1");
        $stmt->execute([$ip]);
        return (bool) $stmt->fetchColumn();
    }

    public function adminByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }

        $allowedEmails = array_merge([self::DEFAULT_EMAIL], self::LEGACY_EMAILS);
        if (!in_array($email, $allowedEmails, true)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($allowedEmails), '?'));
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email IN ({$placeholders}) AND status = 'active' ORDER BY id ASC LIMIT 1");
        $stmt->execute($allowedEmails);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: null;
    }

    public function bootstrapAdminFromCredential(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        if (!in_array($email, $this->defaultEmails(), true) || !hash_equals(self::DEFAULT_PASSWORD, $password)) {
            return null;
        }

        $hash = password_hash(self::DEFAULT_PASSWORD, PASSWORD_ARGON2ID);
        $this->db->prepare("
            INSERT INTO admin_users (email, password_hash, display_name, status)
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                display_name = VALUES(display_name),
                status = 'active'
        ")->execute([$email, $hash, 'Pipocine Admin']);

        return $this->adminByEmail($email);
    }

    public function adminById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, email, display_name, status, last_login_at FROM admin_users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: null;
    }

    public function createSession(int $adminId, string $jtiHash, string $ip, string $userAgent, string $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_sessions (admin_id, jti_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $jtiHash, $ip, substr($userAgent, 0, 255), $expiresAt]);

        $this->db->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?")->execute([$adminId]);
    }

    public function validSession(int $adminId, string $jtiHash, string $ip): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM admin_sessions
            WHERE admin_id = ? AND jti_hash = ? AND ip_address = ? AND expires_at > NOW() AND revoked_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$adminId, $jtiHash, $ip]);
        return (bool) $stmt->fetchColumn();
    }

    public function revokeSession(string $jtiHash): void
    {
        $stmt = $this->db->prepare("UPDATE admin_sessions SET revoked_at = NOW() WHERE jti_hash = ?");
        $stmt->execute([$jtiHash]);
    }

    public function dashboardStats(): array
    {
        return [
            'users' => $this->countTable('platform_users'),
            'profiles' => $this->countTable('profiles'),
            'payments' => $this->countTable('subscription_payments'),
            'active_admin_sessions' => $this->countWhere('admin_sessions', 'expires_at > NOW() AND revoked_at IS NULL'),
            'suspended_users' => $this->countWhere('platform_users', "moderation_status = 'suspended' AND (moderation_until IS NULL OR moderation_until > NOW())"),
            'banned_users' => $this->countWhere('platform_users', "moderation_status = 'banned'"),
        ];
    }

    public function audit(?int $adminId, string $eventType, string $ip, string $userAgent, array $payload = []): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_audit_logs (admin_id, event_type, ip_address, user_agent, payload)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $eventType, $ip, substr($userAgent, 0, 255), json_encode($payload)]);
    }

    public function failedLoginCount(string $ip, int $minutes = 15): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM admin_audit_logs
            WHERE ip_address = ?
              AND event_type IN ('admin_login_failed', 'admin_login_blocked_ip')
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$ip, max(1, $minutes)]);
        return (int) $stmt->fetchColumn();
    }

    private function seedDefaults(): void
    {
        $stmt = $this->db->prepare("SELECT id FROM admin_allowed_ips WHERE ip_address = ? LIMIT 1");
        $stmt->execute([self::DEFAULT_IP]);
        if (!$stmt->fetchColumn()) {
            $this->db->prepare("INSERT INTO admin_allowed_ips (ip_address, label) VALUES (?, ?)")
                ->execute([self::DEFAULT_IP, 'IP principal do administrador']);
        }

        foreach ($this->defaultEmails() as $email) {
            $stmt = $this->db->prepare("SELECT id, password_hash FROM admin_users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $this->db->prepare("
                    INSERT INTO admin_users (email, password_hash, display_name)
                    VALUES (?, ?, ?)
                ")->execute([
                    $email,
                    password_hash(self::DEFAULT_PASSWORD, PASSWORD_ARGON2ID),
                    'Pipocine Admin',
                ]);
                continue;
            }

            if (!password_verify(self::DEFAULT_PASSWORD, (string) $admin['password_hash'])) {
                $this->db->prepare("UPDATE admin_users SET password_hash = ?, status = 'active' WHERE id = ?")
                    ->execute([
                        password_hash(self::DEFAULT_PASSWORD, PASSWORD_ARGON2ID),
                        (int) $admin['id'],
                    ]);
            }
        }
    }

    private function defaultEmails(): array
    {
        return array_values(array_unique(array_merge([self::DEFAULT_EMAIL], self::LEGACY_EMAILS)));
    }

    private function cleanupExpiredSessions(): void
    {
        $this->db->exec("DELETE FROM admin_sessions WHERE expires_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    private function countTable(string $table): int
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return 0;
        }

        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countWhere(string $table, string $where): int
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return 0;
        }

        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
