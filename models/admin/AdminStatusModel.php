<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminStatusModel
{
    public function __construct(private PDO $db)
    {
    }

    public function currentTimestamp(): string
    {
        try {
            return (string) $this->db->query("SELECT NOW()")->fetchColumn();
        } catch (\Throwable) {
            return date('Y-m-d H:i:s');
        }
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_status_components (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parent_id BIGINT UNSIGNED NULL,
                component_key VARCHAR(90) NOT NULL,
                name VARCHAR(140) NOT NULL,
                description VARCHAR(255) NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                is_critical TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_psc_key (component_key),
                KEY idx_psc_parent (parent_id),
                KEY idx_psc_public_order (is_public, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_status_incidents (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                slug VARCHAR(220) NOT NULL,
                incident_type VARCHAR(40) NOT NULL DEFAULT 'incident',
                category VARCHAR(80) NOT NULL DEFAULT 'Degraded Performance',
                impact VARCHAR(40) NOT NULL DEFAULT 'degraded_performance',
                status VARCHAR(40) NOT NULL DEFAULT 'investigating',
                visibility VARCHAR(20) NOT NULL DEFAULT 'public',
                public_description TEXT NULL,
                internal_description TEXT NULL,
                systems_affected TEXT NULL,
                started_at DATETIME NOT NULL,
                resolved_at DATETIME NULL,
                scheduled_start_at DATETIME NULL,
                scheduled_end_at DATETIME NULL,
                owner_admin_id BIGINT UNSIGNED NULL,
                created_by_admin_id BIGINT UNSIGNED NULL,
                updated_by_admin_id BIGINT UNSIGNED NULL,
                archived_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_psi_slug (slug),
                KEY idx_psi_status_impact (status, impact),
                KEY idx_psi_visibility_started (visibility, started_at),
                KEY idx_psi_type (incident_type),
                KEY idx_psi_owner (owner_admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_status_incident_components (
                incident_id BIGINT UNSIGNED NOT NULL,
                component_id BIGINT UNSIGNED NOT NULL,
                impact VARCHAR(40) NOT NULL DEFAULT 'degraded_performance',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (incident_id, component_id),
                KEY idx_psic_component (component_id),
                KEY idx_psic_impact (impact)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_status_updates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                incident_id BIGINT UNSIGNED NOT NULL,
                update_type VARCHAR(60) NOT NULL DEFAULT 'Update',
                status VARCHAR(40) NOT NULL,
                impact VARCHAR(40) NOT NULL,
                public_message TEXT NULL,
                internal_note TEXT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                created_by_admin_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_psu_incident_created (incident_id, created_at),
                KEY idx_psu_public_created (is_public, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_status_internal_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                incident_id BIGINT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL,
                message VARCHAR(500) NULL,
                payload_json JSON NULL,
                admin_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_psil_incident_created (incident_id, created_at),
                KEY idx_psil_action_created (action, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->seedComponents();
    }

    public function listComponents(bool $publicOnly = false): array
    {
        $sql = "
            SELECT c.*, p.name AS parent_name
            FROM pipocine_status_components c
            LEFT JOIN pipocine_status_components p ON p.id = c.parent_id
        ";
        if ($publicOnly) {
            $sql .= " WHERE c.is_public = 1";
        }
        $sql .= " ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.name";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveComponent(array $component): array
    {
        $id = (int) ($component['id'] ?? 0);
        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE pipocine_status_components
                SET parent_id = ?, component_key = ?, name = ?, description = ?, is_public = ?,
                    is_critical = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $component['parent_id'],
                $component['component_key'],
                $component['name'],
                $component['description'],
                $component['is_public'] ? 1 : 0,
                $component['is_critical'] ? 1 : 0,
                $component['sort_order'],
                $id,
            ]);
            return $this->componentById($id) ?: [];
        }

        $stmt = $this->db->prepare("
            INSERT INTO pipocine_status_components
                (parent_id, component_key, name, description, is_public, is_critical, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                parent_id = VALUES(parent_id),
                name = VALUES(name),
                description = VALUES(description),
                is_public = VALUES(is_public),
                is_critical = VALUES(is_critical),
                sort_order = VALUES(sort_order),
                updated_at = NOW()
        ");
        $stmt->execute([
            $component['parent_id'],
            $component['component_key'],
            $component['name'],
            $component['description'],
            $component['is_public'] ? 1 : 0,
            $component['is_critical'] ? 1 : 0,
            $component['sort_order'],
        ]);

        $found = $this->componentByKey($component['component_key']);
        return $found ?: [];
    }

    public function deleteComponent(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM pipocine_status_components WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function componentById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pipocine_status_components WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function componentByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pipocine_status_components WHERE component_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createIncident(array $incident, array $componentIds, int $adminId): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_status_incidents
                (title, slug, incident_type, category, impact, status, visibility,
                 public_description, internal_description, systems_affected, started_at,
                 resolved_at, scheduled_start_at, scheduled_end_at, owner_admin_id,
                 created_by_admin_id, updated_by_admin_id, archived_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $incident['title'],
            $incident['slug'],
            $incident['incident_type'],
            $incident['category'],
            $incident['impact'],
            $incident['status'],
            $incident['visibility'],
            $incident['public_description'],
            $incident['internal_description'],
            $incident['systems_affected'],
            $incident['started_at'],
            $incident['resolved_at'],
            $incident['scheduled_start_at'],
            $incident['scheduled_end_at'],
            $incident['owner_admin_id'],
            $adminId,
            $adminId,
            $incident['archived_at'],
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->syncIncidentComponents($id, $componentIds, $incident['impact']);
        $initialMessage = trim((string) ($incident['initial_public_message'] ?? ''));
        if ($initialMessage === '') {
            $initialMessage = 'Estamos investigando este incidente e publicaremos novas informacoes assim que houver atualizacoes.';
        }
        $this->addUpdate([
            'incident_id' => $id,
            'update_type' => $this->defaultUpdateType($incident['status']),
            'status' => $incident['status'],
            'impact' => $incident['impact'],
            'public_message' => $initialMessage,
            'internal_note' => 'Incident created.',
            'is_public' => $incident['visibility'] === 'public',
            'created_by_admin_id' => $adminId,
        ]);
        $this->log($id, 'incident_created', 'Incident created.', $incident, $adminId);

        return $this->incidentById($id, false) ?: [];
    }

    public function updateIncident(int $id, array $incident, array $componentIds, int $adminId): array
    {
        $before = $this->incidentById($id, false);
        $stmt = $this->db->prepare("
            UPDATE pipocine_status_incidents
            SET title = ?, incident_type = ?, category = ?, impact = ?, status = ?, visibility = ?,
                public_description = ?, internal_description = ?, systems_affected = ?, started_at = ?,
                resolved_at = ?, scheduled_start_at = ?, scheduled_end_at = ?, owner_admin_id = ?,
                updated_by_admin_id = ?, archived_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $incident['title'],
            $incident['incident_type'],
            $incident['category'],
            $incident['impact'],
            $incident['status'],
            $incident['visibility'],
            $incident['public_description'],
            $incident['internal_description'],
            $incident['systems_affected'],
            $incident['started_at'],
            $incident['resolved_at'],
            $incident['scheduled_start_at'],
            $incident['scheduled_end_at'],
            $incident['owner_admin_id'],
            $adminId,
            $incident['archived_at'],
            $id,
        ]);

        $this->syncIncidentComponents($id, $componentIds, $incident['impact']);
        if ($before) {
            if ((string) $before['impact'] !== (string) $incident['impact']) {
                $this->addUpdate([
                    'incident_id' => $id,
                    'update_type' => 'Status Changed',
                    'status' => $incident['status'],
                    'impact' => $incident['impact'],
                    'public_message' => 'O impacto do incidente foi atualizado para ' . $incident['category'] . '.',
                    'internal_note' => 'Impact changed from ' . $before['impact'] . ' to ' . $incident['impact'],
                    'is_public' => $incident['visibility'] === 'public',
                    'created_by_admin_id' => $adminId,
                ]);
            } elseif ((string) $before['status'] !== (string) $incident['status']) {
                $this->addUpdate([
                    'incident_id' => $id,
                    'update_type' => $this->defaultUpdateType($incident['status']),
                    'status' => $incident['status'],
                    'impact' => $incident['impact'],
                    'public_message' => 'O status do incidente foi atualizado para ' . $incident['status'] . '.',
                    'internal_note' => 'Status changed from ' . $before['status'] . ' to ' . $incident['status'],
                    'is_public' => $incident['visibility'] === 'public',
                    'created_by_admin_id' => $adminId,
                ]);
            }
        }
        $this->log($id, 'incident_updated', 'Incident updated.', $incident, $adminId);

        return $this->incidentById($id, false) ?: [];
    }

    public function updateIncidentStatus(int $id, string $status, string $impact, string $category, int $adminId): array
    {
        $resolvedAt = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare("
            UPDATE pipocine_status_incidents
            SET status = ?, impact = ?, category = ?, resolved_at = COALESCE(?, resolved_at),
                updated_by_admin_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $impact, $category, $resolvedAt, $adminId, $id]);

        $this->addUpdate([
            'incident_id' => $id,
            'update_type' => $this->defaultUpdateType($status),
            'status' => $status,
            'impact' => $impact,
            'public_message' => $status === 'resolved'
                ? 'O incidente foi resolvido.'
                : 'A categoria do incidente foi alterada para ' . $category . '.',
            'internal_note' => 'Quick status update.',
            'is_public' => 1,
            'created_by_admin_id' => $adminId,
        ]);
        $this->log($id, 'incident_status_updated', 'Incident status updated.', compact('status', 'impact', 'category'), $adminId);

        return $this->incidentById($id, false) ?: [];
    }

    public function addUpdate(array $update): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_status_updates
                (incident_id, update_type, status, impact, public_message, internal_note, is_public, created_by_admin_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $update['incident_id'],
            $update['update_type'],
            $update['status'],
            $update['impact'],
            $update['public_message'],
            $update['internal_note'],
            !empty($update['is_public']) ? 1 : 0,
            $update['created_by_admin_id'] ?? null,
            $update['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare("SELECT * FROM pipocine_status_updates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function listIncidents(array $filters = [], bool $publicOnly = false, int $limit = 80): array
    {
        [$where, $params] = $this->incidentFilter($filters, $publicOnly);
        $limit = max(1, min(250, $limit));
        $ownerSelect = $publicOnly ? "NULL AS owner_name" : "a.display_name AS owner_name";
        $ownerJoin = $publicOnly ? "" : "LEFT JOIN admin_users a ON a.id = i.owner_admin_id";
        $stmt = $this->db->prepare("
            SELECT i.*, {$ownerSelect}
            FROM pipocine_status_incidents i
            {$ownerJoin}
            {$where}
            ORDER BY
                CASE WHEN i.status IN ('investigating','identified','monitoring','maintenance') THEN 0 ELSE 1 END,
                i.started_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item['components'] = $this->incidentComponents((int) $item['id'], $publicOnly);
            $item['updates'] = $this->incidentUpdates((int) $item['id'], $publicOnly);
        }
        unset($item);
        return $items;
    }

    public function incidentById(int $id, bool $publicOnly = false): ?array
    {
        $ownerSelect = $publicOnly ? "NULL AS owner_name" : "a.display_name AS owner_name";
        $ownerJoin = $publicOnly ? "" : "LEFT JOIN admin_users a ON a.id = i.owner_admin_id";
        $sql = "
            SELECT i.*, {$ownerSelect}
            FROM pipocine_status_incidents i
            {$ownerJoin}
            WHERE i.id = ?
        ";
        if ($publicOnly) {
            $sql .= " AND i.visibility = 'public' AND i.archived_at IS NULL";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$incident) {
            return null;
        }

        $incident['components'] = $this->incidentComponents($id, $publicOnly);
        $incident['updates'] = $this->incidentUpdates($id, $publicOnly);
        if (!$publicOnly) {
            $incident['logs'] = $this->incidentLogs($id);
        }
        return $incident;
    }

    public function deleteIncident(int $id, int $adminId): void
    {
        $this->log($id, 'incident_deleted', 'Incident deleted.', [], $adminId);
        $this->db->prepare("DELETE FROM pipocine_status_internal_logs WHERE incident_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM pipocine_status_updates WHERE incident_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM pipocine_status_incident_components WHERE incident_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM pipocine_status_incidents WHERE id = ?")->execute([$id]);
    }

    public function activePublicIncidents(): array
    {
        return $this->listIncidents([
            'active' => true,
            'visibility' => 'public',
        ], true, 20);
    }

    public function incidentRangeForBars(string $from, string $to): array
    {
        $stmt = $this->db->prepare("
            SELECT i.id, i.title, i.impact, i.category, i.status, i.started_at, i.resolved_at,
                   i.scheduled_start_at, i.scheduled_end_at, ic.component_id, ic.impact AS component_impact
            FROM pipocine_status_incidents i
            JOIN pipocine_status_incident_components ic ON ic.incident_id = i.id
            WHERE i.visibility = 'public'
              AND i.archived_at IS NULL
              AND i.started_at <= ?
              AND COALESCE(i.resolved_at, i.scheduled_end_at, NOW()) >= ?
        ");
        $stmt->execute([$to, $from]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function observabilityDaily(string $from): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    DATE(created_at) AS day,
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END) AS api_requests,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 400 THEN 1 ELSE 0 END) AS success_requests,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_requests,
                    SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS client_errors,
                    SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS server_errors,
                    SUM(CASE WHEN status_code = 401 THEN 1 ELSE 0 END) AS unauthorized,
                    SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) AS rate_limited,
                    AVG(duration_ms) AS avg_latency_ms,
                    AVG(CASE WHEN is_api = 1 THEN duration_ms END) AS avg_api_latency_ms,
                    MAX(duration_ms) AS max_latency_ms,
                    SUM(request_bytes + response_bytes) AS total_bytes,
                    SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                    COUNT(DISTINCT ip_address) AS unique_ips,
                    COUNT(DISTINCT path) AS unique_endpoints
                FROM pipocine_request_metrics
                WHERE created_at >= ?
                GROUP BY day
                ORDER BY day ASC
            ");
            $stmt->execute([$from]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    public function observabilityRealtime(int $minutes = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN is_api = 1 THEN 1 ELSE 0 END) AS api_requests,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 400 THEN 1 ELSE 0 END) AS success_requests,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_requests,
                    SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS client_errors,
                    SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS server_errors,
                    SUM(CASE WHEN status_code = 401 THEN 1 ELSE 0 END) AS unauthorized,
                    SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) AS rate_limited,
                    AVG(duration_ms) AS avg_latency_ms,
                    MAX(duration_ms) AS max_latency_ms,
                    SUM(request_bytes + response_bytes) AS total_bytes,
                    SUM(CASE WHEN is_api = 1 THEN request_bytes + response_bytes ELSE 0 END) AS api_bytes,
                    COUNT(DISTINCT ip_address) AS unique_ips,
                    COUNT(DISTINCT path) AS unique_endpoints
                FROM pipocine_request_metrics
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([max(1, $minutes)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function observabilityApiRealtimeSeries(int $minutes = 60): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') AS bucket,
                    COUNT(*) AS api_requests,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_requests,
                    AVG(duration_ms) AS avg_latency_ms,
                    MAX(duration_ms) AS max_latency_ms,
                    SUM(request_bytes + response_bytes) AS api_bytes
                FROM pipocine_request_metrics
                WHERE is_api = 1
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                GROUP BY bucket
                ORDER BY bucket ASC
            ");
            $stmt->execute([max(5, min(180, $minutes))]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    public function log(?int $incidentId, string $action, string $message, array $payload, ?int $adminId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_status_internal_logs
                (incident_id, action, message, payload_json, admin_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $incidentId,
            $action,
            $message,
            $payload ? json_encode($payload, JSON_UNESCAPED_SLASHES) : null,
            $adminId,
        ]);
    }

    private function syncIncidentComponents(int $incidentId, array $componentIds, string $impact): void
    {
        $this->db->prepare("DELETE FROM pipocine_status_incident_components WHERE incident_id = ?")->execute([$incidentId]);
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO pipocine_status_incident_components (incident_id, component_id, impact)
            VALUES (?, ?, ?)
        ");
        foreach (array_unique(array_map('intval', $componentIds)) as $componentId) {
            if ($componentId > 0) {
                $stmt->execute([$incidentId, $componentId, $impact]);
            }
        }
    }

    private function incidentComponents(int $incidentId, bool $publicOnly): array
    {
        $sql = "
            SELECT c.*, ic.impact AS incident_impact
            FROM pipocine_status_incident_components ic
            JOIN pipocine_status_components c ON c.id = ic.component_id
            WHERE ic.incident_id = ?
        ";
        if ($publicOnly) {
            $sql .= " AND c.is_public = 1";
        }
        $sql .= " ORDER BY c.sort_order, c.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function incidentUpdates(int $incidentId, bool $publicOnly): array
    {
        $sql = "SELECT * FROM pipocine_status_updates WHERE incident_id = ?";
        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }
        $sql .= " ORDER BY created_at ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function incidentLogs(int $incidentId): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, a.display_name AS admin_name
            FROM pipocine_status_internal_logs l
            LEFT JOIN admin_users a ON a.id = l.admin_id
            WHERE l.incident_id = ?
            ORDER BY l.created_at DESC
            LIMIT 120
        ");
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function incidentFilter(array $filters, bool $publicOnly): array
    {
        $where = [];
        $params = [];
        if ($publicOnly || ($filters['visibility'] ?? '') === 'public') {
            $where[] = "i.visibility = 'public'";
        }
        if ($publicOnly) {
            $where[] = "i.archived_at IS NULL";
        }
        if (!empty($filters['active'])) {
            $where[] = "i.status IN ('investigating','identified','monitoring','maintenance')";
        }
        foreach (['status', 'impact', 'incident_type', 'category'] as $key) {
            if (!empty($filters[$key])) {
                $where[] = "i.{$key} = ?";
                $params[] = $filters[$key];
            }
        }
        if (!empty($filters['owner_admin_id'])) {
            $where[] = "i.owner_admin_id = ?";
            $params[] = (int) $filters['owner_admin_id'];
        }
        if (!empty($filters['from'])) {
            $where[] = "i.started_at >= ?";
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = "i.started_at <= ?";
            $params[] = $filters['to'];
        }
        if (!empty($filters['component_id'])) {
            $where[] = "EXISTS (
                SELECT 1 FROM pipocine_status_incident_components fic
                WHERE fic.incident_id = i.id AND fic.component_id = ?
            )";
            $params[] = (int) $filters['component_id'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function defaultUpdateType(string $status): string
    {
        return match ($status) {
            'investigating' => 'Investigating',
            'identified' => 'Identified',
            'monitoring' => 'Monitoring',
            'resolved' => 'Resolved',
            'maintenance' => 'Maintenance Started',
            default => 'Update',
        };
    }

    private function seedComponents(): void
    {
        $components = [
            ['platform', null, 'PipoCine Platform', 'Core public experience.', 1, 1, 10],
            ['web-app', 'platform', 'Web App', 'Frontend pages and user sessions.', 1, 1, 20],
            ['api', 'platform', 'API', 'Public and authenticated API surface.', 1, 1, 30],
            ['auth', 'platform', 'Auth Service', 'Login, sessions, profiles and device checks.', 1, 1, 40],
            ['player', 'platform', 'Player', 'Playback, streams and watch experience.', 1, 1, 50],
            ['database', 'platform', 'Database', 'Primary data layer.', 1, 1, 60],
            ['cdn', 'platform', 'CDN', 'Static and media delivery.', 1, 0, 70],
            ['ads', 'platform', 'Ads', 'Advertiser dashboard and campaigns.', 1, 0, 80],
            ['support', 'platform', 'Support', 'Support chat and ticket flow.', 1, 0, 90],
        ];

        $ids = [];
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_status_components
                (parent_id, component_key, name, description, is_public, is_critical, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                is_public = VALUES(is_public),
                is_critical = VALUES(is_critical),
                sort_order = VALUES(sort_order)
        ");

        foreach ($components as $row) {
            [$key, $parentKey, $name, $description, $isPublic, $isCritical, $sort] = $row;
            $parentId = $parentKey ? ($ids[$parentKey] ?? null) : null;
            $stmt->execute([$parentId, $key, $name, $description, $isPublic, $isCritical, $sort]);
            $component = $this->componentByKey($key);
            if ($component) {
                $ids[$key] = (int) $component['id'];
            }
        }
    }
}
