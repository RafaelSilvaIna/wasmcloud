<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminStatusModel;

final class AdminStatusService
{
    private const PUBLIC_HEALTH_FLOOR = 93.0;
    private ?string $currentTimestamp = null;

    private const IMPACT_LABELS = [
        'operational' => 'Operational',
        'degraded_performance' => 'Degraded Performance',
        'partial_outage' => 'Partial Outage',
        'major_outage' => 'Major Outage',
        'maintenance' => 'Under Maintenance',
        'security_incident' => 'Security Incident',
        'network_incident' => 'Network Incident',
        'api_degradation' => 'API Degradation',
        'database_incident' => 'Database Incident',
        'third_party_provider_issue' => 'Third-Party Provider Issue',
        'resolved' => 'Resolved',
    ];

    private const IMPACT_RANK = [
        'operational' => 0,
        'degraded_performance' => 1,
        'api_degradation' => 1,
        'network_incident' => 2,
        'third_party_provider_issue' => 2,
        'database_incident' => 3,
        'security_incident' => 3,
        'partial_outage' => 3,
        'major_outage' => 4,
        'maintenance' => 5,
        'resolved' => 0,
    ];

    public function __construct(private AdminStatusModel $model)
    {
        $this->model->ensureSchema();
    }

    public function components(): array
    {
        return [
            'success' => true,
            'components' => $this->model->listComponents(false),
        ];
    }

    public function saveComponent(array $payload): array
    {
        $component = $this->model->saveComponent($this->sanitizeComponent($payload));
        return [
            'success' => true,
            'component' => $component,
        ];
    }

    public function deleteComponent(int $id): array
    {
        $this->model->deleteComponent($id);
        return ['success' => true];
    }

    public function incidents(array $filters): array
    {
        $items = $this->decorateIncidents($this->model->listIncidents($this->sanitizeFilters($filters), false, 160));
        return [
            'success' => true,
            'incidents' => $items,
            'filters' => $filters,
        ];
    }

    public function incident(int $id): array
    {
        $incident = $this->model->incidentById($id, false);
        if (!$incident) {
            throw new \InvalidArgumentException('Incidente nao encontrado.');
        }

        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function createIncident(array $payload, int $adminId): array
    {
        $incident = $this->model->createIncident(
            $this->sanitizeIncident($payload),
            $this->componentIds($payload),
            $adminId
        );

        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function updateIncident(int $id, array $payload, int $adminId): array
    {
        $incident = $this->model->updateIncident(
            $id,
            $this->sanitizeIncident($payload, $id),
            $this->componentIds($payload),
            $adminId
        );

        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function publishUpdate(int $id, array $payload, int $adminId): array
    {
        $incident = $this->model->incidentById($id, false);
        if (!$incident) {
            throw new \InvalidArgumentException('Incidente nao encontrado.');
        }

        $update = $this->model->addUpdate([
            'incident_id' => $id,
            'update_type' => $this->cleanEnumText((string) ($payload['update_type'] ?? 'Update'), 'Update'),
            'status' => $this->cleanStatus((string) ($payload['status'] ?? $incident['status'])),
            'impact' => $this->cleanImpact((string) ($payload['impact'] ?? $incident['impact'])),
            'public_message' => trim((string) ($payload['public_message'] ?? '')),
            'internal_note' => trim((string) ($payload['internal_note'] ?? '')),
            'is_public' => !empty($payload['is_public']),
            'created_by_admin_id' => $adminId,
        ]);

        $this->model->log($id, 'timeline_update_published', 'Timeline update published.', $update, $adminId);

        return [
            'success' => true,
            'update' => $update,
            'incident' => $this->decorateIncident($this->model->incidentById($id, false) ?: []),
        ];
    }

    public function quickStatus(int $id, array $payload, int $adminId): array
    {
        $impact = $this->cleanImpact((string) ($payload['impact'] ?? 'degraded_performance'));
        $status = $this->cleanStatus((string) ($payload['status'] ?? 'monitoring'));
        $category = trim((string) ($payload['category'] ?? self::IMPACT_LABELS[$impact] ?? 'Degraded Performance'));
        $incident = $this->model->updateIncidentStatus($id, $status, $impact, $category, $adminId);

        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function resolveIncident(int $id, int $adminId): array
    {
        $incident = $this->model->updateIncidentStatus($id, 'resolved', 'resolved', 'Resolved', $adminId);
        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function archiveIncident(int $id, int $adminId): array
    {
        $incident = $this->model->incidentById($id, false);
        if (!$incident) {
            throw new \InvalidArgumentException('Incidente nao encontrado.');
        }
        $incident['status'] = 'archived';
        $incident['archived_at'] = date('Y-m-d H:i:s');
        $incident = $this->model->updateIncident($id, $incident, array_column($incident['components'] ?? [], 'id'), $adminId);
        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function convertToMaintenance(int $id, int $adminId): array
    {
        $incident = $this->model->updateIncidentStatus($id, 'maintenance', 'maintenance', 'Under Maintenance', $adminId);
        $this->model->addUpdate([
            'incident_id' => $id,
            'update_type' => 'Maintenance Started',
            'status' => 'maintenance',
            'impact' => 'maintenance',
            'public_message' => 'Este incidente foi convertido em manutencao programada.',
            'internal_note' => 'Converted to scheduled maintenance.',
            'is_public' => 1,
            'created_by_admin_id' => $adminId,
        ]);

        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident),
        ];
    }

    public function deleteIncident(int $id, int $adminId): array
    {
        $this->model->deleteIncident($id, $adminId);
        return ['success' => true];
    }

    public function publicOverview(int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $now = $this->now();
        $nowTs = strtotime($now) ?: time();
        $components = $this->model->listComponents(true);
        $active = $this->decorateIncidents($this->model->activePublicIncidents());
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days', $nowTs));
        $to = date('Y-m-d 23:59:59', $nowTs);
        $bars = $this->componentBars($components, $this->model->incidentRangeForBars($from, $to), $days, $now);
        $health = $this->healthSeries($days, $active);
        $history = $this->history(90);

        return [
            'success' => true,
            'generated_at' => $this->isoTimestamp($now),
            'overall' => $this->overallStatus($active),
            'active_incidents' => $active,
            'components' => $this->componentTree($components, $bars, $active),
            'health' => $health,
            'api_response' => $this->apiResponseSeries(60),
            'realtime' => $this->model->observabilityRealtime(5),
            'history' => $history,
        ];
    }

    public function publicIncident(int $id): array
    {
        $incident = $this->model->incidentById($id, true);
        if (!$incident) {
            throw new \InvalidArgumentException('Incidente nao encontrado.');
        }
        return [
            'success' => true,
            'incident' => $this->decorateIncident($incident, true),
        ];
    }

    private function history(int $days): array
    {
        $from = date('Y-m-d 00:00:00', strtotime('-' . max(1, $days) . ' days'));
        $items = $this->decorateIncidents($this->model->listIncidents([
            'visibility' => 'public',
            'from' => $from,
        ], true, 120));
        $items = array_values(array_filter($items, static function (array $item): bool {
            return in_array((string) ($item['status'] ?? ''), ['resolved', 'archived'], true)
                || !empty($item['resolved_at']);
        }));

        $grouped = [];
        foreach ($items as $item) {
            $day = substr((string) $item['started_at'], 0, 10);
            $grouped[$day][] = $item;
        }
        krsort($grouped);

        return $grouped;
    }

    private function componentBars(array $components, array $incidents, int $days, string $now): array
    {
        $nowTs = strtotime($now) ?: time();
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-{$i} days", $nowTs));
        }

        $children = [];
        foreach ($components as $component) {
            $parentId = (int) ($component['parent_id'] ?? 0);
            if ($parentId > 0) {
                $children[$parentId][] = (int) $component['id'];
            }
        }

        $bars = [];
        foreach ($components as $component) {
            $cid = (int) $component['id'];
            foreach ($dates as $date) {
                $bars[$cid][$date] = [
                    'date' => $date,
                    'status' => 'operational',
                    'label' => 'Operational',
                    'rank' => 0,
                    'uptime_pct' => 100,
                    'duration_minutes' => 0,
                    'incidents' => [],
                ];
            }
        }

        foreach ($incidents as $incident) {
            $cid = (int) $incident['component_id'];
            $startDay = substr((string) $incident['started_at'], 0, 10);
            $endDay = substr((string) ($incident['resolved_at'] ?: $incident['scheduled_end_at'] ?: $now), 0, 10);
            $impact = (string) ($incident['component_impact'] ?: $incident['impact']);
            $rank = self::IMPACT_RANK[$impact] ?? 1;
            foreach ($dates as $date) {
                if ($date < $startDay || $date > $endDay || !isset($bars[$cid][$date])) {
                    continue;
                }
                if ($rank >= $bars[$cid][$date]['rank']) {
                    $bars[$cid][$date]['status'] = $impact;
                    $bars[$cid][$date]['label'] = self::IMPACT_LABELS[$impact] ?? $incident['category'];
                    $bars[$cid][$date]['rank'] = $rank;
                }
                $bars[$cid][$date]['duration_minutes'] += $this->overlapMinutesForDay($incident, $date, $now);
                $bars[$cid][$date]['incidents'][] = [
                    'id' => (int) $incident['id'],
                    'title' => $incident['title'],
                    'category' => $incident['category'],
                ];
            }
        }

        foreach ($components as $component) {
            $cid = (int) $component['id'];
            if (empty($children[$cid])) {
                continue;
            }
            foreach ($dates as $date) {
                foreach ($children[$cid] as $childId) {
                    if (!isset($bars[$childId][$date])) {
                        continue;
                    }
                    $child = $bars[$childId][$date];
                    if ($child['rank'] >= $bars[$cid][$date]['rank']) {
                        $bars[$cid][$date]['status'] = $child['status'];
                        $bars[$cid][$date]['label'] = $child['label'];
                        $bars[$cid][$date]['rank'] = $child['rank'];
                    }
                    $bars[$cid][$date]['duration_minutes'] += $child['duration_minutes'];
                    $bars[$cid][$date]['incidents'] = array_merge($bars[$cid][$date]['incidents'], $child['incidents']);
                }
            }
        }

        foreach ($bars as &$componentBars) {
            foreach ($componentBars as &$bar) {
                $impactMinutes = min(1440, (int) $bar['duration_minutes']);
                $bar['uptime_pct'] = round(max(0, 100 - ($impactMinutes / 1440 * 100)), 2);
                unset($bar['rank']);
            }
            $componentBars = array_values($componentBars);
        }
        unset($componentBars, $bar);

        return $bars;
    }

    private function componentTree(array $components, array $bars, array $active): array
    {
        $activeStatuses = $this->activeComponentStatuses($active);
        $byId = [];
        foreach ($components as $component) {
            $id = (int) $component['id'];
            $component['children'] = [];
            $component['bars'] = $bars[$id] ?? [];
            $component['current_status'] = $activeStatuses[$id] ?? 'operational';
            $byId[$id] = $component;
        }

        $tree = [];
        foreach ($byId as $id => &$component) {
            $parentId = (int) ($component['parent_id'] ?? 0);
            if ($parentId > 0 && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$component;
            } else {
                $tree[] = &$component;
            }
        }
        unset($component);

        foreach ($tree as &$component) {
            $this->applyChildCurrentStatus($component);
        }
        unset($component);

        return $tree;
    }

    private function activeComponentStatuses(array $active): array
    {
        $statuses = [];
        foreach ($active as $incident) {
            foreach (($incident['components'] ?? []) as $component) {
                $id = (int) ($component['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $impact = (string) ($component['incident_impact'] ?? $incident['impact'] ?? 'degraded_performance');
                $currentRank = self::IMPACT_RANK[$statuses[$id] ?? 'operational'] ?? 0;
                $rank = self::IMPACT_RANK[$impact] ?? 1;
                if ($rank >= $currentRank) {
                    $statuses[$id] = $impact;
                }
            }
        }
        return $statuses;
    }

    private function applyChildCurrentStatus(array &$component): string
    {
        $status = (string) ($component['current_status'] ?? 'operational');
        $rank = self::IMPACT_RANK[$status] ?? 0;
        foreach ($component['children'] as &$child) {
            $childStatus = $this->applyChildCurrentStatus($child);
            $childRank = self::IMPACT_RANK[$childStatus] ?? 0;
            if ($childRank >= $rank) {
                $status = $childStatus;
                $rank = $childRank;
            }
        }
        unset($child);
        $component['current_status'] = $status;
        return $status;
    }

    private function healthSeries(int $days, array $active): array
    {
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $rows = [];
        foreach ($this->model->observabilityDaily($from) as $row) {
            $rows[(string) $row['day']] = $row;
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $row = $rows[$day] ?? [];
            $score = $this->scoreDay($row);
            $series[] = [
                'date' => $day,
                'score' => $score,
                'uptime_pct' => $score,
                'avg_latency_ms' => round((float) ($row['avg_latency_ms'] ?? 0), 1),
                'avg_api_latency_ms' => round((float) ($row['avg_api_latency_ms'] ?? 0), 1),
                'max_latency_ms' => (int) ($row['max_latency_ms'] ?? 0),
                'error_rate_pct' => $this->percent((float) ($row['error_requests'] ?? 0), (float) ($row['total_requests'] ?? 0)),
                'success_rate_pct' => $this->percent((float) ($row['success_requests'] ?? 0), (float) ($row['total_requests'] ?? 0)),
                'client_error_rate_pct' => $this->percent((float) ($row['client_errors'] ?? 0), (float) ($row['total_requests'] ?? 0)),
                'server_error_rate_pct' => $this->percent((float) ($row['server_errors'] ?? 0), (float) ($row['total_requests'] ?? 0)),
                'total_requests' => (int) ($row['total_requests'] ?? 0),
                'api_requests' => (int) ($row['api_requests'] ?? 0),
                'total_bytes' => (int) ($row['total_bytes'] ?? 0),
                'unique_ips' => (int) ($row['unique_ips'] ?? 0),
                'unique_endpoints' => (int) ($row['unique_endpoints'] ?? 0),
            ];
        }

        $recentWithTraffic = array_values(array_filter($series, static fn (array $row): bool => (int) ($row['total_requests'] ?? 0) > 0));
        $currentScore = $recentWithTraffic
            ? (float) end($recentWithTraffic)['score']
            : 100.0;
        foreach ($active as $incident) {
            $rank = self::IMPACT_RANK[$incident['impact']] ?? 1;
            $currentScore = min($currentScore, match (true) {
                $rank >= 5 => 82,
                $rank >= 4 => 35,
                $rank >= 3 => 62,
                $rank >= 2 => 78,
                default => 90,
            });
        }
        $recentApiTraffic = array_values(array_filter($series, static fn (array $row): bool => (int) ($row['api_requests'] ?? 0) > 0));
        $currentApiResponse = $recentApiTraffic
            ? (float) end($recentApiTraffic)['avg_api_latency_ms']
            : 0.0;

        return [
            'current_score' => round($currentScore, 2),
            'current_api_response_ms' => round($currentApiResponse, 1),
            'series' => $series,
        ];
    }

    private function apiResponseSeries(int $minutes): array
    {
        $rows = $this->model->observabilityApiRealtimeSeries($minutes);
        $series = array_map(static function (array $row): array {
            $requests = (int) ($row['api_requests'] ?? 0);
            $errors = (int) ($row['error_requests'] ?? 0);
            return [
                'time' => (string) ($row['bucket'] ?? ''),
                'avg_latency_ms' => round((float) ($row['avg_latency_ms'] ?? 0), 1),
                'max_latency_ms' => (int) ($row['max_latency_ms'] ?? 0),
                'api_requests' => $requests,
                'error_rate_pct' => $requests > 0 ? round(($errors / $requests) * 100, 2) : 0.0,
                'api_bytes' => (int) ($row['api_bytes'] ?? 0),
            ];
        }, $rows);

        $recent = array_values(array_filter($series, static fn (array $row): bool => (int) $row['api_requests'] > 0));
        $current = $recent ? (float) end($recent)['avg_latency_ms'] : 0.0;

        return [
            'window_minutes' => max(5, min(180, $minutes)),
            'current_ms' => round($current, 1),
            'series' => $series,
        ];
    }

    private function scoreDay(array $row): float
    {
        $total = max(0.0, (float) ($row['total_requests'] ?? 0));
        if ($total <= 0) {
            return 100.0;
        }

        if ($total < 20) {
            return 100.0;
        }

        $errorRate = $this->percent((float) ($row['error_requests'] ?? 0), $total);
        $serverRate = $this->percent((float) ($row['server_errors'] ?? 0), $total);
        $rateLimited = $this->percent((float) ($row['rate_limited'] ?? 0), $total);
        $latency = (float) ($row['avg_latency_ms'] ?? 0);
        $maxLatency = (float) ($row['max_latency_ms'] ?? 0);

        $latencyPenalty = max(0, ($latency - 1200) / 180) + max(0, ($maxLatency - 5000) / 1500);
        $score = 100 - ($serverRate * 1.8) - ($errorRate * .45) - ($rateLimited * .25) - $latencyPenalty;
        return round(max(self::PUBLIC_HEALTH_FLOOR, min(100, $score)), 2);
    }

    private function overallStatus(array $active): array
    {
        if (!$active) {
            return [
                'key' => 'operational',
                'label' => 'All Systems Operational',
                'tone' => 'ok',
                'rank' => 0,
            ];
        }

        $rank = 0;
        $maintenance = false;
        foreach ($active as $incident) {
            $impact = (string) ($incident['impact'] ?? 'degraded_performance');
            $rank = max($rank, self::IMPACT_RANK[$impact] ?? 1);
            $maintenance = $maintenance || $impact === 'maintenance' || $incident['status'] === 'maintenance';
        }

        if ($maintenance) {
            return ['key' => 'maintenance', 'label' => 'Under Maintenance', 'tone' => 'maintenance', 'rank' => 5];
        }
        if ($rank >= 4) {
            return ['key' => 'major_outage', 'label' => 'Major Outage', 'tone' => 'danger', 'rank' => $rank];
        }
        if ($rank >= 3) {
            return ['key' => 'partial_outage', 'label' => 'Partial Outage', 'tone' => 'warn', 'rank' => $rank];
        }
        if ($rank >= 1) {
            return ['key' => 'degraded', 'label' => 'Partial System Degradation', 'tone' => 'degraded', 'rank' => $rank];
        }

        return ['key' => 'incident', 'label' => 'Incident Ongoing', 'tone' => 'warn', 'rank' => $rank];
    }

    private function decorateIncidents(array $items, bool $public = false): array
    {
        return array_map(fn (array $item): array => $this->decorateIncident($item, $public), $items);
    }

    private function decorateIncident(array $item, bool $public = false): array
    {
        if (!$item) {
            return [];
        }
        $resolved = !empty($item['resolved_at']);
        $item['impact_label'] = self::IMPACT_LABELS[$item['impact']] ?? $item['category'];
        $item['duration_seconds'] = $this->durationSeconds($item['started_at'], $item['resolved_at'] ?? null);
        $item['duration_label'] = $resolved
            ? 'Resolvido apos ' . $this->durationLabel($item['duration_seconds'])
            : $this->durationLabel($item['duration_seconds']) . ' em andamento';
        $item['component_names'] = array_map(static fn ($c) => $c['name'], $item['components'] ?? []);
        if ($public) {
            unset($item['internal_description'], $item['logs'], $item['owner_admin_id'], $item['created_by_admin_id'], $item['updated_by_admin_id']);
            foreach ($item['updates'] as &$update) {
                unset($update['internal_note'], $update['created_by_admin_id']);
            }
            unset($update);
        }
        return $item;
    }

    private function sanitizeIncident(array $payload, ?int $id = null): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Titulo do incidente e obrigatorio.');
        }

        $impact = $this->cleanImpact((string) ($payload['impact'] ?? 'degraded_performance'));
        $status = $this->cleanStatus((string) ($payload['status'] ?? 'investigating'));
        $startedAt = $this->dateOrNow($payload['started_at'] ?? null);

        return [
            'title' => substr($title, 0, 180),
            'slug' => $id ? ($payload['slug'] ?? $this->slug($title)) : $this->slug($title . '-' . bin2hex(random_bytes(3))),
            'incident_type' => $this->cleanType((string) ($payload['incident_type'] ?? 'incident')),
            'category' => substr(trim((string) ($payload['category'] ?? (self::IMPACT_LABELS[$impact] ?? 'Degraded Performance'))), 0, 80),
            'impact' => $impact,
            'status' => $status,
            'visibility' => ((string) ($payload['visibility'] ?? 'public')) === 'private' ? 'private' : 'public',
            'public_description' => trim((string) ($payload['public_description'] ?? '')),
            'initial_public_message' => trim((string) ($payload['initial_public_message'] ?? '')),
            'internal_description' => trim((string) ($payload['internal_description'] ?? '')),
            'systems_affected' => trim((string) ($payload['systems_affected'] ?? '')),
            'started_at' => $startedAt,
            'resolved_at' => $this->dateOrNull($payload['resolved_at'] ?? null),
            'scheduled_start_at' => $this->dateOrNull($payload['scheduled_start_at'] ?? null),
            'scheduled_end_at' => $this->dateOrNull($payload['scheduled_end_at'] ?? null),
            'owner_admin_id' => !empty($payload['owner_admin_id']) ? (int) $payload['owner_admin_id'] : null,
            'archived_at' => $this->dateOrNull($payload['archived_at'] ?? null),
        ];
    }

    private function sanitizeComponent(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome do componente e obrigatorio.');
        }

        $key = trim((string) ($payload['component_key'] ?? ''));
        if ($key === '') {
            $key = $this->slug($name);
        }

        $id = (int) ($payload['id'] ?? 0);
        $parentId = !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null;
        if ($id > 0 && $parentId === $id) {
            throw new \InvalidArgumentException('Um sistema nao pode ser pai dele mesmo.');
        }

        return [
            'id' => $id,
            'parent_id' => $parentId,
            'component_key' => substr(preg_replace('/[^a-z0-9_-]+/', '-', strtolower($key)) ?: $this->slug($name), 0, 90),
            'name' => substr($name, 0, 140),
            'description' => substr(trim((string) ($payload['description'] ?? '')), 0, 255),
            'is_public' => !empty($payload['is_public']),
            'is_critical' => !empty($payload['is_critical']),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ];
    }

    private function sanitizeFilters(array $filters): array
    {
        $out = [];
        foreach (['status', 'impact', 'incident_type', 'category', 'visibility'] as $key) {
            if (!empty($filters[$key]) && $filters[$key] !== 'all') {
                $out[$key] = trim((string) $filters[$key]);
            }
        }
        foreach (['component_id', 'owner_admin_id'] as $key) {
            if (!empty($filters[$key])) {
                $out[$key] = (int) $filters[$key];
            }
        }
        foreach (['from', 'to'] as $key) {
            if (!empty($filters[$key])) {
                $out[$key] = $this->dateOrNull($filters[$key]);
            }
        }
        return $out;
    }

    private function componentIds(array $payload): array
    {
        $ids = $payload['component_ids'] ?? [];
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }
        return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
    }

    private function cleanImpact(string $impact): string
    {
        $impact = strtolower(trim($impact));
        return array_key_exists($impact, self::IMPACT_LABELS) ? $impact : 'degraded_performance';
    }

    private function cleanStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['investigating', 'identified', 'monitoring', 'resolved', 'scheduled', 'archived', 'maintenance'];
        return in_array($status, $allowed, true) ? $status : 'investigating';
    }

    private function cleanType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['incident', 'maintenance', 'security', 'network', 'api', 'database', 'third_party'];
        return in_array($type, $allowed, true) ? $type : 'incident';
    }

    private function cleanEnumText(string $value, string $fallback): string
    {
        $value = trim($value);
        return $value === '' ? $fallback : substr($value, 0, 60);
    }

    private function durationSeconds(string $start, ?string $end): int
    {
        $nowTs = strtotime($this->now()) ?: time();
        $startTs = strtotime($start) ?: $nowTs;
        $endTs = $end ? (strtotime($end) ?: $nowTs) : $nowTs;
        return max(0, $endTs - $startTs);
    }

    private function durationLabel(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        if ($hours <= 0) {
            return $mins . 'min';
        }
        return $hours . 'h ' . $mins . 'min';
    }

    private function overlapMinutesForDay(array $incident, string $day, string $now): int
    {
        $start = max(strtotime((string) $incident['started_at']) ?: 0, strtotime($day . ' 00:00:00') ?: 0);
        $nowTs = strtotime($now) ?: time();
        $endSource = $incident['resolved_at'] ?: $incident['scheduled_end_at'] ?: $now;
        $end = min(strtotime((string) $endSource) ?: $nowTs, strtotime($day . ' 23:59:59') ?: $nowTs);
        return max(0, (int) floor(($end - $start) / 60));
    }

    private function percent(float $part, float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return round($part * 100 / $total, 2);
    }

    private function dateOrNow(mixed $value): string
    {
        $date = $this->dateOrNull($value);
        return $date ?: $this->now();
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function now(): string
    {
        if ($this->currentTimestamp === null) {
            $this->currentTimestamp = $this->model->currentTimestamp();
        }
        return $this->currentTimestamp;
    }

    private function isoTimestamp(string $value): string
    {
        $ts = strtotime($value);
        return $ts ? date('c', $ts) : date('c');
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: 'incident';
        return trim($slug, '-') ?: 'incident';
    }
}
