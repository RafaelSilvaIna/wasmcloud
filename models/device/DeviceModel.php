<?php

declare(strict_types=1);

namespace Models\Device;

use PDO;
use Throwable;

final class DeviceModel
{
    private PDO $db;

    public const HEARTBEAT_TTL = 90;
    public const CLEANUP_AFTER_SECONDS = 600;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS account_devices (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                device_id VARCHAR(128) NOT NULL,
                session_id VARCHAR(128) NOT NULL,
                ip_partial VARCHAR(20) NOT NULL DEFAULT '',
                user_agent_hash VARCHAR(64) NOT NULL DEFAULT '',
                device_label VARCHAR(80) NOT NULL DEFAULT '',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_heartbeat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_device_session (device_id, session_id),
                KEY idx_user_active (user_id, is_active),
                KEY idx_heartbeat (last_heartbeat),
                KEY idx_user_device (user_id, device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensureUniqueDeviceKey();
    }

    public function upsertDevice(
        int $userId,
        string $deviceId,
        string $sessionId,
        string $ipPartial,
        string $userAgentHash,
        string $deviceLabel
    ): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO account_devices
                    (user_id, device_id, session_id, ip_partial, user_agent_hash, device_label, is_active, last_heartbeat)
                VALUES
                    (?, ?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    is_active = 1,
                    last_heartbeat = NOW(),
                    session_id = VALUES(session_id),
                    ip_partial = VALUES(ip_partial),
                    user_agent_hash = VALUES(user_agent_hash),
                    device_label = VALUES(device_label)
            ");

            return $stmt->execute([
                $userId,
                $deviceId,
                $sessionId,
                $ipPartial,
                $userAgentHash,
                $deviceLabel,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    public function deactivateDevice(int $userId, string $deviceId, string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account_devices
                SET is_active = 0
                WHERE user_id = ? AND device_id = ?
            ");
            return $stmt->execute([$userId, $deviceId]);
        } catch (Throwable) {
            return false;
        }
    }

    public function deactivateProfileSession(int $userId, int $profileId, string $sessionId): bool
    {
        if ($profileId <= 0 || $sessionId === '') {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE profile_active_sessions
                SET is_active = 0
                WHERE user_id = ?
                  AND profile_id = ?
                  AND session_id = ?
            ");
            $stmt->execute([$userId, $profileId, $sessionId]);

            $stmt = $this->db->prepare("
                UPDATE profiles
                SET is_watching = 0,
                    current_session_id = NULL,
                    last_active_at = NOW()
                WHERE user_id = ?
                  AND id = ?
                  AND current_session_id = ?
            ");
            $stmt->execute([$userId, $profileId, $sessionId]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function deactivateAllDevices(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account_devices
                SET is_active = 0
                WHERE user_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (Throwable) {
            return false;
        }
    }

    public function isSessionActiveForUser(int $userId, string $sessionId): bool
    {
        if ($sessionId === '') {
            return false;
        }

        try {
            $ttl = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM account_devices
                WHERE user_id = ?
                  AND session_id = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");
            $stmt->execute([$userId, $sessionId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function deactivateOtherDevicesForSession(int $userId, string $sessionId, string $deviceId): void
    {
        if ($sessionId === '') {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE account_devices
                SET is_active = 0
                WHERE user_id = ?
                  AND session_id = ?
                  AND device_id <> ?
            ");
            $stmt->execute([$userId, $sessionId, $deviceId]);
        } catch (Throwable) {
        }
    }

    public function touchProfileSession(int $userId, int $profileId, string $sessionId): void
    {
        if ($profileId <= 0 || $sessionId === '') {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE profile_active_sessions
                SET last_activity = NOW()
                WHERE user_id = ?
                  AND profile_id = ?
                  AND session_id = ?
                  AND is_active = 1
            ");
            $stmt->execute([$userId, $profileId, $sessionId]);
        } catch (Throwable) {
        }
    }

    public function countActiveDevices(int $userId): int
    {
        try {
            $ttl = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT device_id) AS cnt
                FROM account_devices
                WHERE user_id = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");
            $stmt->execute([$userId]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function listActiveDevices(int $userId): array
    {
        try {
            $ttl = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT id, device_id, session_id, ip_partial, device_label, last_heartbeat
                FROM account_devices
                WHERE user_id = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                ORDER BY last_heartbeat DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    public function isDeviceActive(int $userId, string $deviceId): bool
    {
        try {
            $ttl = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM account_devices
                WHERE user_id = ?
                  AND device_id = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");
            $stmt->execute([$userId, $deviceId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function cleanup(): void
    {
        try {
            $ttl = self::HEARTBEAT_TTL;
            $after = self::CLEANUP_AFTER_SECONDS;

            $this->db->exec("
                UPDATE account_devices
                SET is_active = 0
                WHERE is_active = 1
                  AND last_heartbeat < DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");

            $this->db->exec("
                DELETE FROM account_devices
                WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL {$after} SECOND)
            ");

            $this->cleanupProfileLeases();
        } catch (Throwable) {
        }
    }

    private function cleanupProfileLeases(): void
    {
        try {
            $ttl = self::HEARTBEAT_TTL;
            $after = self::CLEANUP_AFTER_SECONDS;

            $this->db->exec("
                UPDATE profile_active_sessions pas
                LEFT JOIN account_devices ad
                  ON ad.user_id = pas.user_id
                 AND ad.session_id = pas.session_id
                 AND ad.is_active = 1
                 AND ad.last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                SET pas.is_active = 0
                WHERE pas.is_active = 1
                  AND (
                      pas.expires_at <= NOW()
                      OR pas.last_activity < DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                      OR ad.id IS NULL
                  )
            ");

            $this->db->exec("
                UPDATE profiles p
                LEFT JOIN profile_active_sessions pas
                  ON pas.profile_id = p.id
                 AND pas.session_id = p.current_session_id
                 AND pas.is_active = 1
                 AND pas.expires_at > NOW()
                SET p.is_watching = 0,
                    p.current_session_id = NULL,
                    p.last_active_at = NOW()
                WHERE p.is_watching = 1
                  AND p.current_session_id IS NOT NULL
                  AND pas.id IS NULL
            ");

            $this->db->exec("
                DELETE FROM profile_active_sessions
                WHERE is_active = 0
                  AND last_activity < DATE_SUB(NOW(), INTERVAL {$after} SECOND)
            ");
        } catch (Throwable) {
        }
    }

    private function ensureUniqueDeviceKey(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS cnt
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'account_devices'
                  AND index_name = 'uk_user_device'
            ");
            $stmt->execute();
            if ((int) $stmt->fetchColumn() > 0) {
                return;
            }

            $this->db->exec("
                DELETE ad1
                FROM account_devices ad1
                JOIN account_devices ad2
                  ON ad1.user_id = ad2.user_id
                 AND ad1.device_id = ad2.device_id
                 AND ad1.id < ad2.id
            ");

            $this->db->exec("
                ALTER TABLE account_devices
                ADD UNIQUE KEY uk_user_device (user_id, device_id)
            ");
        } catch (Throwable) {
        }
    }
}
