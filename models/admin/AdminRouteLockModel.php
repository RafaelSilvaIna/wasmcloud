<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminRouteLockModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_route_locks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                route_path VARCHAR(255) NOT NULL,
                match_type ENUM('exact','prefix','regex') NOT NULL DEFAULT 'exact',
                page_file VARCHAR(255) NULL,
                route_label VARCHAR(160) NULL,
                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                maintenance_title VARCHAR(120) NOT NULL DEFAULT 'Pagina em manutencao',
                maintenance_message VARCHAR(500) NULL,
                locked_by_admin_id BIGINT UNSIGNED NULL,
                locked_at DATETIME NULL,
                unlocked_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_prl_route_type (route_path, match_type),
                KEY idx_prl_locked (is_locked, match_type),
                KEY idx_prl_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_frontend_route_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id CHAR(32) NOT NULL,
                method VARCHAR(12) NOT NULL,
                path VARCHAR(255) NOT NULL,
                matched_route VARCHAR(255) NULL,
                page_file VARCHAR(255) NULL,
                status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
                was_locked TINYINT(1) NOT NULL DEFAULT 0,
                lock_id BIGINT UNSIGNED NULL,
                duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                referer VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_pfrl_created (created_at),
                KEY idx_pfrl_path_created (path, created_at),
                KEY idx_pfrl_locked_created (was_locked, created_at),
                KEY idx_pfrl_duration (duration_ms)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function allLocks(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM pipocine_route_locks
            ORDER BY is_locked DESC, route_path ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeLocks(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM pipocine_route_locks
            WHERE is_locked = 1
            ORDER BY
                CASE match_type WHEN 'exact' THEN 1 WHEN 'prefix' THEN 2 ELSE 3 END,
                LENGTH(route_path) DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setLock(array $route, bool $locked, ?int $adminId): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_route_locks
                (route_path, match_type, page_file, route_label, is_locked, maintenance_title, maintenance_message,
                 locked_by_admin_id, locked_at, unlocked_at)
            VALUES
                (:route_path, :match_type, :page_file, :route_label, :is_locked, :maintenance_title, :maintenance_message,
                 :admin_id, :locked_at, :unlocked_at)
            ON DUPLICATE KEY UPDATE
                page_file = VALUES(page_file),
                route_label = VALUES(route_label),
                is_locked = VALUES(is_locked),
                maintenance_title = VALUES(maintenance_title),
                maintenance_message = VALUES(maintenance_message),
                locked_by_admin_id = VALUES(locked_by_admin_id),
                locked_at = IF(VALUES(is_locked) = 1, NOW(), locked_at),
                unlocked_at = IF(VALUES(is_locked) = 0, NOW(), NULL),
                updated_at = NOW()
        ");

        $stmt->execute([
            ':route_path' => $route['route_path'],
            ':match_type' => $route['match_type'],
            ':page_file' => $route['page_file'] ?? null,
            ':route_label' => $route['route_label'] ?? null,
            ':is_locked' => $locked ? 1 : 0,
            ':maintenance_title' => $route['maintenance_title'] ?? 'Pagina em manutencao',
            ':maintenance_message' => $route['maintenance_message'] ?? null,
            ':admin_id' => $adminId,
            ':locked_at' => $locked ? date('Y-m-d H:i:s') : null,
            ':unlocked_at' => $locked ? null : date('Y-m-d H:i:s'),
        ]);

        return $this->findByIdentity($route['route_path'], $route['match_type']) ?? [];
    }

    public function deleteLock(string $routePath, string $matchType): void
    {
        $stmt = $this->db->prepare("DELETE FROM pipocine_route_locks WHERE route_path = ? AND match_type = ?");
        $stmt->execute([$routePath, $matchType]);
    }

    public function findByIdentity(string $routePath, string $matchType): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM pipocine_route_locks
            WHERE route_path = ? AND match_type = ?
            LIMIT 1
        ");
        $stmt->execute([$routePath, $matchType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function recordFrontendLog(array $log): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_frontend_route_logs
                (request_id, method, path, matched_route, page_file, status_code, was_locked,
                 lock_id, duration_ms, ip_address, user_agent, referer)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $log['request_id'],
            $log['method'],
            $log['path'],
            $log['matched_route'] ?? null,
            $log['page_file'] ?? null,
            (int) $log['status_code'],
            !empty($log['was_locked']) ? 1 : 0,
            $log['lock_id'] ?? null,
            (int) $log['duration_ms'],
            $log['ip_address'] ?? null,
            substr((string) ($log['user_agent'] ?? ''), 0, 255),
            substr((string) ($log['referer'] ?? ''), 0, 255),
        ]);
    }

    public function recentLogs(int $limit = 80): array
    {
        $limit = max(1, min(250, $limit));
        $stmt = $this->db->query("
            SELECT *
            FROM pipocine_frontend_route_logs
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function routeStats(string $since, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare("
            SELECT
                path,
                COUNT(*) AS total_hits,
                SUM(CASE WHEN was_locked = 1 THEN 1 ELSE 0 END) AS locked_hits,
                AVG(duration_ms) AS avg_duration_ms,
                MAX(created_at) AS last_hit_at
            FROM pipocine_frontend_route_logs
            WHERE created_at >= ?
            GROUP BY path
            ORDER BY total_hits DESC, last_hit_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
