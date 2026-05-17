<?php

declare(strict_types=1);

namespace Controllers\Admin;

use PDO;
use Throwable;
use Services\Admin\AdminAuthService;

/**
 * SecurityAdminController — API REST para gerenciamento da camada de segurança.
 *
 * Rotas disponíveis via /api/admin/security/:
 *
 *  GET  security/dashboard         → Métricas gerais em tempo real
 *  GET  security/threats           → Últimos eventos de ameaça (paginado)
 *  GET  security/incidents         → Log de incidentes (paginado)
 *  GET  security/bans              → Banimentos ativos
 *  POST security/bans/create       → Bane um IP manualmente
 *  POST security/bans/{id}/lift    → Remove um banimento
 *  GET  security/quarantine        → IPs em quarentena
 *  GET  security/whitelist         → Entradas da whitelist
 *  POST security/whitelist/add     → Adiciona entrada à whitelist
 *  POST security/whitelist/{id}/remove → Remove entrada da whitelist
 *  GET  security/reputation/{ip}   → Reputação de um IP específico
 *  GET  security/patterns          → Padrões distribuídos ativos
 *  GET  security/route-profiles    → Perfis de risco por rota
 *  POST security/route-profiles/update → Atualiza threshold de uma rota
 */
final class SecurityAdminController
{
    public function __construct(
        private readonly AdminAuthService $auth,
        private readonly PDO              $pdo
    ) {}

    public function handle(string $action, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->auth->requireAdmin();
        } catch (Throwable) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Acesso nao autorizado.']);
            return;
        }

        try {
            $parts  = explode('/', ltrim(str_replace('security/', '', $action), '/'));
            $seg    = $parts[0] ?? '';
            $sub    = $parts[1] ?? '';

            match ($seg) {
                'dashboard'      => $this->dashboard(),
                'threats'        => $this->threats(),
                'incidents'      => $this->incidents(),
                'bans'           => $this->dispatchBans($sub, $parts, $method),
                'quarantine'     => $this->quarantine(),
                'whitelist'      => $this->dispatchWhitelist($sub, $parts, $method),
                'reputation'     => $this->reputation($sub),
                'patterns'       => $this->patterns(),
                'route-profiles' => $this->dispatchRouteProfiles($sub, $method),
                default          => $this->notFound(),
            };
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    private function dashboard(): void
    {
        $metrics = [
            'threat_events_24h'    => $this->scalar("SELECT COUNT(*) FROM sec_threat_events WHERE created_at >= NOW() - INTERVAL 24 HOUR"),
            'threat_events_1h'     => $this->scalar("SELECT COUNT(*) FROM sec_threat_events WHERE created_at >= NOW() - INTERVAL 1 HOUR"),
            'active_bans'          => $this->scalar("SELECT COUNT(*) FROM sec_ip_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())"),
            'active_quarantine'    => $this->scalar("SELECT COUNT(*) FROM sec_quarantine WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())"),
            'critical_events_24h'  => $this->scalar("SELECT COUNT(*) FROM sec_threat_events WHERE severity = 'critical' AND created_at >= NOW() - INTERVAL 24 HOUR"),
            'high_risk_ips'        => $this->scalar("SELECT COUNT(*) FROM sec_ip_reputation WHERE threat_score >= 500"),
            'bots_detected'        => $this->scalar("SELECT COUNT(*) FROM sec_ip_reputation WHERE is_bot_detected = 1"),
            'active_patterns'      => $this->scalar("SELECT COUNT(*) FROM sec_distributed_pattern WHERE status = 'active'"),
            'rate_limits_1h'       => $this->scalar("SELECT COUNT(*) FROM sec_threat_events WHERE event_type = 'rate_limit_exceeded' AND created_at >= NOW() - INTERVAL 1 HOUR"),
        ];

        // Top 5 IPs mais ameaçadores
        $stmt = $this->pdo->query(
            "SELECT ip_address, threat_score, mitigation_level, is_bot_detected, last_request_at
             FROM sec_ip_reputation
             ORDER BY threat_score DESC
             LIMIT 5"
        );
        $topThreats = $stmt ? $stmt->fetchAll() : [];

        // Distribuição de eventos por tipo (últimas 24h)
        $stmt = $this->pdo->query(
            "SELECT event_type, COUNT(*) AS total
             FROM sec_threat_events
             WHERE created_at >= NOW() - INTERVAL 24 HOUR
             GROUP BY event_type
             ORDER BY total DESC
             LIMIT 10"
        );
        $eventBreakdown = $stmt ? $stmt->fetchAll() : [];

        echo json_encode([
            'success'        => true,
            'metrics'        => $metrics,
            'top_threats'    => $topThreats,
            'event_breakdown'=> $eventBreakdown,
            'generated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // THREAT EVENTS
    // =========================================================================

    private function threats(): void
    {
        $page     = max(0, (int) ($_GET['page'] ?? 0));
        $per      = min(100, max(10, (int) ($_GET['per'] ?? 50)));
        $offset   = $page * $per;
        $severity = $_GET['severity'] ?? null;
        $type     = $_GET['type'] ?? null;

        $where  = ['1=1'];
        $params = [];

        if ($severity && in_array($severity, ['low','medium','high','critical'], true)) {
            $where[]  = 'severity = ?';
            $params[] = $severity;
        }
        if ($type) {
            $where[]  = 'event_type = ?';
            $params[] = $type;
        }

        $sql = 'SELECT * FROM sec_threat_events WHERE ' . implode(' AND ', $where)
             . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $per;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $countParams = array_slice($params, 0, -2);
        $count       = (int) $this->scalar('SELECT COUNT(*) FROM sec_threat_events WHERE ' . implode(' AND ', $where), $countParams);

        echo json_encode([
            'success' => true,
            'events'  => $rows,
            'total'   => $count,
            'page'    => $page,
            'pages'   => (int) ceil($count / $per),
        ]);
    }

    // =========================================================================
    // INCIDENTS
    // =========================================================================

    private function incidents(): void
    {
        $page   = max(0, (int) ($_GET['page'] ?? 0));
        $per    = min(50, max(10, (int) ($_GET['per'] ?? 25)));
        $offset = $page * $per;

        $stmt = $this->pdo->prepare(
            'SELECT id, incident_id, incident_type, severity, ip_address, action_taken,
                    mitigation_level, threat_score, created_at
             FROM sec_incident_log
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$per, $offset]);
        $rows  = $stmt->fetchAll();
        $count = (int) $this->scalar('SELECT COUNT(*) FROM sec_incident_log');

        echo json_encode([
            'success'   => true,
            'incidents' => $rows,
            'total'     => $count,
            'page'      => $page,
            'pages'     => (int) ceil($count / $per),
        ]);
    }

    // =========================================================================
    // BANS
    // =========================================================================

    private function dispatchBans(string $sub, array $parts, string $method): void
    {
        if ($sub === 'create' && $method === 'POST') {
            $this->createBan();
            return;
        }

        // /bans/{id}/lift
        if ($sub !== '' && isset($parts[2]) && $parts[2] === 'lift' && $method === 'POST') {
            $this->liftBan((int) $sub);
            return;
        }

        // /bans — lista
        $this->listBans();
    }

    private function listBans(): void
    {
        $page   = max(0, (int) ($_GET['page'] ?? 0));
        $per    = min(100, (int) ($_GET['per'] ?? 50));
        $offset = $page * $per;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM sec_ip_bans
             WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY banned_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$per, $offset]);
        $rows  = $stmt->fetchAll();
        $count = (int) $this->scalar("SELECT COUNT(*) FROM sec_ip_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");

        echo json_encode(['success' => true, 'bans' => $rows, 'total' => $count]);
    }

    private function createBan(): void
    {
        $body     = $this->body();
        $ip       = trim($body['ip_address'] ?? '');
        $type     = in_array($body['ban_type'] ?? '', ['soft','hard','shadow'], true) ? $body['ban_type'] : 'soft';
        $reason   = trim($body['reason'] ?? 'Ban manual via painel admin');
        $duration = max(0, (int) ($body['duration_seconds'] ?? 900));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'IP invalido.']);
            return;
        }

        $expires = $duration > 0 ? date('Y-m-d H:i:s', time() + $duration) : null;

        $this->pdo->prepare(
            'INSERT INTO sec_ip_bans
             (ip_address, ban_type, reason, event_type, threat_score_at_ban, is_active, expires_at)
             VALUES (?, ?, ?, "manual_admin_ban", 0, 1, ?)'
        )->execute([$ip, $type, $reason, $expires]);

        echo json_encode(['success' => true, 'message' => "IP {$ip} banido com sucesso ({$type})."]);
    }

    private function liftBan(int $banId): void
    {
        $body    = $this->body();
        $liftedBy = trim($body['lifted_by'] ?? 'admin');

        $this->pdo->prepare(
            'UPDATE sec_ip_bans
             SET is_active = 0, lifted_at = NOW(), lifted_by = ?
             WHERE id = ?'
        )->execute([$liftedBy, $banId]);

        echo json_encode(['success' => true, 'message' => 'Ban removido com sucesso.']);
    }

    // =========================================================================
    // QUARANTINE
    // =========================================================================

    private function quarantine(): void
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM sec_quarantine
             WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY entered_at DESC
             LIMIT 100'
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo json_encode(['success' => true, 'quarantine' => $rows, 'total' => count($rows)]);
    }

    // =========================================================================
    // WHITELIST
    // =========================================================================

    private function dispatchWhitelist(string $sub, array $parts, string $method): void
    {
        if ($sub === 'add' && $method === 'POST') {
            $this->addWhitelist();
            return;
        }
        if ($sub !== '' && isset($parts[2]) && $parts[2] === 'remove' && $method === 'POST') {
            $this->removeWhitelist((int) $sub);
            return;
        }
        $this->listWhitelist();
    }

    private function listWhitelist(): void
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM sec_whitelist WHERE is_active = 1 ORDER BY created_at DESC'
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo json_encode(['success' => true, 'whitelist' => $rows]);
    }

    private function addWhitelist(): void
    {
        $body    = $this->body();
        $type    = trim($body['entry_type'] ?? '');
        $value   = trim($body['entry_value'] ?? '');
        $desc    = trim($body['description'] ?? '');
        $addedBy = trim($body['added_by'] ?? 'admin');

        $allowed = ['ip','ip_network','user_agent_prefix','asn','api_token','cdn_range'];
        if (!in_array($type, $allowed, true) || $value === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Dados invalidos.']);
            return;
        }

        $this->pdo->prepare(
            'INSERT IGNORE INTO sec_whitelist (entry_type, entry_value, description, added_by)
             VALUES (?, ?, ?, ?)'
        )->execute([$type, $value, $desc, $addedBy]);

        // Invalida cache APCu da whitelist
        if (function_exists('apcu_delete')) {
            apcu_delete('sec_whitelist_v1');
        }

        echo json_encode(['success' => true, 'message' => 'Entrada adicionada a whitelist.']);
    }

    private function removeWhitelist(int $entryId): void
    {
        $this->pdo->prepare(
            'UPDATE sec_whitelist SET is_active = 0 WHERE id = ?'
        )->execute([$entryId]);

        if (function_exists('apcu_delete')) {
            apcu_delete('sec_whitelist_v1');
        }

        echo json_encode(['success' => true, 'message' => 'Entrada removida da whitelist.']);
    }

    // =========================================================================
    // IP REPUTATION
    // =========================================================================

    private function reputation(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'IP invalido.']);
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT * FROM sec_ip_reputation WHERE ip_address = ? LIMIT 1'
        );
        $stmt->execute([$ip]);
        $rep = $stmt->fetch();

        // Últimos 10 eventos de ameaça
        $stmt = $this->pdo->prepare(
            'SELECT event_type, severity, action_taken, threat_score_at_event, created_at
             FROM sec_threat_events
             WHERE ip_address = ?
             ORDER BY created_at DESC
             LIMIT 10'
        );
        $stmt->execute([$ip]);
        $events = $stmt->fetchAll();

        // Ban ativo?
        $stmt = $this->pdo->prepare(
            'SELECT * FROM sec_ip_bans WHERE ip_address = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute([$ip]);
        $ban = $stmt->fetch();

        echo json_encode([
            'success'    => true,
            'ip'         => $ip,
            'reputation' => $rep ?: null,
            'events'     => $events,
            'active_ban' => $ban ?: null,
        ]);
    }

    // =========================================================================
    // DISTRIBUTED PATTERNS
    // =========================================================================

    private function patterns(): void
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM sec_distributed_pattern
             WHERE status = 'active'
             ORDER BY first_detected_at DESC
             LIMIT 50"
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo json_encode(['success' => true, 'patterns' => $rows]);
    }

    // =========================================================================
    // ROUTE PROFILES
    // =========================================================================

    private function dispatchRouteProfiles(string $sub, string $method): void
    {
        if ($sub === 'update' && $method === 'POST') {
            $this->updateRouteProfile();
            return;
        }
        $this->listRouteProfiles();
    }

    private function listRouteProfiles(): void
    {
        $stmt = $this->pdo->query('SELECT * FROM sec_route_risk_profile ORDER BY is_critical DESC, route_group ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo json_encode(['success' => true, 'profiles' => $rows]);
    }

    private function updateRouteProfile(): void
    {
        $body  = $this->body();
        $group = trim($body['route_group'] ?? '');

        $allowed = ['rate_limit_clean','rate_limit_suspicious','rate_limit_hostile',
                    'score_delta_per_hit','burst_threshold_rps','delay_on_suspicious',
                    'requires_challenge','is_active'];

        $sets   = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $body)) {
                $sets[]   = "{$col} = ?";
                $params[] = $body[$col];
            }
        }

        if (empty($sets) || $group === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Dados invalidos.']);
            return;
        }

        $params[] = $group;
        $this->pdo->prepare(
            'UPDATE sec_route_risk_profile SET ' . implode(', ', $sets) . ' WHERE route_group = ?'
        )->execute($params);

        // Invalida cache APCu do perfil
        if (function_exists('apcu_delete')) {
            apcu_delete('sec_rp_' . $group);
        }

        echo json_encode(['success' => true, 'message' => "Perfil '{$group}' atualizado."]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function scalar(string $sql, array $params = []): mixed
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function body(): array
    {
        static $data = null;
        if ($data === null) {
            $raw  = file_get_contents('php://input');
            $data = $raw ? (json_decode($raw, true) ?? $_POST) : $_POST;
        }
        return $data ?: [];
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint nao encontrado.']);
    }
}
