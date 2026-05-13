<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminApiMetricsModel
{
    public function __construct(private PDO $db)
    {
    }

    // -------------------------------------------------------------------------
    // Insercao de registro (reutiliza a mesma tabela do UsageMetricsModel)
    // -------------------------------------------------------------------------

    public function record(array $metric): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_request_metrics
                (request_id, method, path, route_group, is_api, status_code,
                 request_bytes, response_bytes, duration_ms, ip_address, user_agent, referer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            substr((string) ($metric['user_agent'] ?? ''), 0, 255),
            substr((string) ($metric['referer']    ?? ''), 0, 255),
        ]);
    }

    // -------------------------------------------------------------------------
    // Resumo geral (15+ metricas)
    // -------------------------------------------------------------------------

    public function summary(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                                                          AS total_calls,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END)                      AS api_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END)              AS error_calls,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END)              AS server_error_calls,
                SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS client_error_calls,
                SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END)               AS success_calls,
                SUM(CASE WHEN status_code = 401 THEN 1 ELSE 0 END)               AS unauthorized_calls,
                SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END)               AS rate_limited_calls,
                ROUND(
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) * 100.0
                    / NULLIF(COUNT(*), 0), 2
                )                                                                 AS error_rate_pct,
                ROUND(AVG(duration_ms), 2)                                        AS avg_latency_ms,
                ROUND(
                    AVG(CASE WHEN is_api = 1 THEN duration_ms END), 2
                )                                                                 AS avg_api_latency_ms,
                MAX(duration_ms)                                                  AS max_latency_ms,
                MIN(duration_ms)                                                  AS min_latency_ms,
                ROUND(
                    SUM(CASE WHEN duration_ms > 1000 THEN 1 ELSE 0 END) * 100.0
                    / NULLIF(COUNT(*), 0), 2
                )                                                                 AS slow_requests_pct,
                SUM(request_bytes + response_bytes)                               AS total_bytes,
                SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                ROUND(AVG(request_bytes + response_bytes), 0)                    AS avg_payload_bytes,
                COUNT(DISTINCT ip_address)                                        AS unique_ips,
                COUNT(DISTINCT path)                                              AS unique_endpoints,
                SUM(CASE WHEN method = 'GET'    THEN 1 ELSE 0 END)               AS get_calls,
                SUM(CASE WHEN method = 'POST'   THEN 1 ELSE 0 END)               AS post_calls,
                SUM(CASE WHEN method = 'PUT' OR method = 'PATCH' THEN 1 ELSE 0 END) AS put_calls,
                SUM(CASE WHEN method = 'DELETE' THEN 1 ELSE 0 END)               AS delete_calls
            FROM pipocine_request_metrics
            WHERE created_at >= ?
        ");
        $stmt->execute([$since]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // -------------------------------------------------------------------------
    // Serie temporal (para graficos)
    // -------------------------------------------------------------------------

    public function timeSeries(string $since, string $bucket): array
    {
        $format = $bucket === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(created_at, '{$format}')                                  AS bucket_label,
                COUNT(*)                                                              AS total_calls,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END)                          AS api_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END)                  AS error_calls,
                ROUND(AVG(duration_ms), 1)                                            AS avg_latency_ms,
                SUM(request_bytes + response_bytes)                                   AS total_bytes,
                COUNT(DISTINCT ip_address)                                            AS unique_ips
            FROM pipocine_request_metrics
            WHERE created_at >= ?
            GROUP BY bucket_label
            ORDER BY bucket_label ASC
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Endpoints mais acessados
    // -------------------------------------------------------------------------

    public function topEndpoints(string $since, int $limit = 15): array
    {
        $stmt = $this->db->prepare("
            SELECT
                path,
                route_group,
                method,
                COUNT(*)                                      AS total_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_calls,
                ROUND(AVG(duration_ms), 1)                    AS avg_latency_ms,
                MAX(duration_ms)                              AS max_latency_ms,
                SUM(request_bytes + response_bytes)           AS total_bytes
            FROM pipocine_request_metrics
            WHERE created_at >= ? AND is_api = 1
            GROUP BY path, route_group, method
            ORDER BY total_calls DESC
            LIMIT ?
        ");
        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Distribuicao de status codes
    // -------------------------------------------------------------------------

    public function statusDistribution(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT
                status_code,
                COUNT(*) AS total
            FROM pipocine_request_metrics
            WHERE created_at >= ? AND is_api = 1
            GROUP BY status_code
            ORDER BY total DESC
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Distribuicao por grupo de rota
    // -------------------------------------------------------------------------

    public function groupDistribution(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT
                route_group,
                COUNT(*)                                AS total_calls,
                ROUND(AVG(duration_ms), 1)              AS avg_latency_ms,
                SUM(request_bytes + response_bytes)     AS total_bytes
            FROM pipocine_request_metrics
            WHERE created_at >= ?
            GROUP BY route_group
            ORDER BY total_calls DESC
        ");
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // IPs mais ativos
    // -------------------------------------------------------------------------

    public function topIps(string $since, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ip_address,
                COUNT(*)                                        AS total_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_calls,
                ROUND(AVG(duration_ms), 1)                      AS avg_latency_ms
            FROM pipocine_request_metrics
            WHERE created_at >= ?
            GROUP BY ip_address
            ORDER BY total_calls DESC
            LIMIT ?
        ");
        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // P95 / P99 de latencia (percentil aproximado via subconsulta)
    // -------------------------------------------------------------------------

    public function latencyPercentiles(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT
                MAX(CASE WHEN pct <= 50 THEN duration_ms END) AS p50,
                MAX(CASE WHEN pct <= 90 THEN duration_ms END) AS p90,
                MAX(CASE WHEN pct <= 95 THEN duration_ms END) AS p95,
                MAX(CASE WHEN pct <= 99 THEN duration_ms END) AS p99
            FROM (
                SELECT
                    duration_ms,
                    ROW_NUMBER() OVER (ORDER BY duration_ms) AS rn,
                    COUNT(*) OVER ()                          AS total,
                    ROUND(ROW_NUMBER() OVER (ORDER BY duration_ms) * 100.0 / COUNT(*) OVER (), 2) AS pct
                FROM pipocine_request_metrics
                WHERE created_at >= ? AND is_api = 1
            ) ranked
        ");
        $stmt->execute([$since]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // -------------------------------------------------------------------------
    // Erros recentes detalhados
    // -------------------------------------------------------------------------

    public function recentErrors(string $since, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT
                path,
                method,
                status_code,
                duration_ms,
                ip_address,
                created_at
            FROM pipocine_request_metrics
            WHERE created_at >= ? AND status_code >= 400 AND is_api = 1
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Realtime (ultimos N minutos)
    // -------------------------------------------------------------------------

    public function realtime(int $minutes = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                                                          AS total_calls,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END)                      AS api_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END)              AS error_calls,
                ROUND(AVG(duration_ms), 1)                                        AS avg_latency_ms,
                SUM(request_bytes + response_bytes)                               AS bytes_total,
                SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes
            FROM pipocine_request_metrics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([max(1, $minutes)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // -------------------------------------------------------------------------
    // Throughput por minuto (ultimos 60 minutos em buckets de 1 min)
    // -------------------------------------------------------------------------

    public function throughputMinutes(int $minutes = 60): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') AS bucket_label,
                COUNT(*)                                       AS total_calls,
                SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END)   AS api_calls,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_calls
            FROM pipocine_request_metrics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE) AND is_api = 1
            GROUP BY bucket_label
            ORDER BY bucket_label ASC
        ");
        $stmt->execute([$minutes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
