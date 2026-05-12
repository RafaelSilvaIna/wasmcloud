<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminUsageMetricsModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_request_metrics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id CHAR(32) NOT NULL,
                method VARCHAR(12) NOT NULL,
                path VARCHAR(255) NOT NULL,
                route_group VARCHAR(80) NOT NULL DEFAULT 'frontend',
                is_api TINYINT(1) NOT NULL DEFAULT 0,
                status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
                request_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
                response_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
                duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                referer VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_prm_created (created_at),
                KEY idx_prm_path_created (path, created_at),
                KEY idx_prm_group_created (route_group, created_at),
                KEY idx_prm_api_created (is_api, created_at),
                KEY idx_prm_status_created (status_code, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function record(array $metric): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_request_metrics
                (request_id, method, path, route_group, is_api, status_code, request_bytes, response_bytes, duration_ms, ip_address, user_agent, referer)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $metric['request_id'],
            $metric['method'],
            $metric['path'],
            $metric['route_group'],
            $metric['is_api'] ? 1 : 0,
            $metric['status_code'],
            $metric['request_bytes'],
            $metric['response_bytes'],
            $metric['duration_ms'],
            $metric['ip_address'],
            substr((string) $metric['user_agent'], 0, 255),
            substr((string) $metric['referer'], 0, 255),
        ]);
    }

    public function summary(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_requests,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END) AS api_requests,
                SUM(request_bytes + response_bytes) AS total_bytes,
                SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                AVG(duration_ms) AS avg_duration_ms,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_requests,
                COUNT(DISTINCT ip_address) AS unique_ips
            FROM pipocine_request_metrics
            WHERE created_at >= ?
        ");
        $stmt->execute([$since]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function timeSeries(string $since, string $bucket): array
    {
        $format = $bucket === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(created_at, '{$format}') AS bucket_label,
                COUNT(*) AS total_requests,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END) AS api_requests,
                SUM(request_bytes + response_bytes) AS total_bytes,
                SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                AVG(duration_ms) AS avg_duration_ms
            FROM pipocine_request_metrics
            WHERE created_at >= ?
            GROUP BY bucket_label
            ORDER BY bucket_label ASC
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function topRoutes(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT path, route_group, COUNT(*) AS total_requests, SUM(request_bytes + response_bytes) AS total_bytes, AVG(duration_ms) AS avg_duration_ms
            FROM pipocine_request_metrics
            WHERE created_at >= ?
            GROUP BY path, route_group
            ORDER BY total_requests DESC
            LIMIT 12
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function realtime(int $minutes = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS requests,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END) AS api_requests,
                SUM(request_bytes + response_bytes) AS bytes_total,
                SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                AVG(duration_ms) AS avg_duration_ms
            FROM pipocine_request_metrics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([max(1, $minutes)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
