<?php

declare(strict_types=1);

namespace Security\Storage;

use PDO;
use Throwable;

/**
 * DbSecurityStore — Camada de acesso ao banco para todas as tabelas sec_*.
 *
 * Encapsula todas as queries de leitura/escrita da camada de segurança.
 * Todas as operações são tolerantes a falha (try/catch silencioso) para
 * garantir fail-open: se o banco falhar, a requisição não é interrompida.
 */
final class DbSecurityStore
{
    public function __construct(private readonly PDO $pdo) {}

    // =========================================================================
    // IP REPUTATION
    // =========================================================================

    /**
     * Carrega ou cria o registro de reputação de um IP.
     * Retorna array com os campos da sec_ip_reputation ou null em falha.
     */
    public function getOrCreateIpReputation(string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_ip_reputation WHERE ip_address = ? LIMIT 1'
            );
            $stmt->execute([$ip]);
            $row = $stmt->fetch();

            if ($row) {
                return $row;
            }

            // Cria registro inicial
            $this->pdo->prepare(
                'INSERT IGNORE INTO sec_ip_reputation
                 (ip_address, ip_network, threat_score, mitigation_level)
                 VALUES (?, ?, 0, 1)'
            )->execute([$ip, $this->deriveNetwork($ip)]);

            $stmt->execute([$ip]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Incrementa o threat_score do IP e atualiza contadores.
     */
    public function incrementIpScore(
        string $ip,
        int    $delta,
        string $flag = '',
        array  $counters = []
    ): void {
        try {
            $sets   = ['threat_score = LEAST(threat_score + ?, 1000)', 'last_request_at = NOW()'];
            $params = [$delta];

            if ($flag !== '') {
                $sets[]   = 'behavior_flags = TRIM(BOTH "," FROM CONCAT(behavior_flags, IF(behavior_flags = "", "", ","), ?))';
                $params[] = $flag;
            }

            foreach ($counters as $col => $inc) {
                $allowed = [
                    'req_count_1min', 'req_count_1hour', 'req_count_24hour',
                    'error_count_1hour', 'sensitive_route_hits', 'unique_routes_1hour',
                    'concurrent_connections',
                ];
                if (in_array($col, $allowed, true)) {
                    $sets[]   = "{$col} = {$col} + ?";
                    $params[] = (int) $inc;
                }
            }

            // Atualiza mitigation_level automaticamente baseado no score
            $sets[] = 'mitigation_level = CASE
                WHEN LEAST(threat_score + ?, 1000) >= 750 THEN 5
                WHEN LEAST(threat_score + ?, 1000) >= 500 THEN 4
                WHEN LEAST(threat_score + ?, 1000) >= 250 THEN 3
                WHEN LEAST(threat_score + ?, 1000) >= 100 THEN 2
                ELSE 1 END';
            $params[] = $delta;
            $params[] = $delta;
            $params[] = $delta;
            $params[] = $delta;

            $params[] = $ip;

            $sql = 'UPDATE sec_ip_reputation SET ' . implode(', ', $sets)
                 . ' WHERE ip_address = ?';
            $this->pdo->prepare($sql)->execute($params);
        } catch (Throwable) {}
    }

    /**
     * Atualiza flags de bot/scraper/scanner.
     */
    public function flagIpAsBot(string $ip, string $type = 'bot'): void
    {
        try {
            $col = match ($type) {
                'scraper' => 'is_scraper_detected',
                'scanner' => 'is_scanner_detected',
                default   => 'is_bot_detected',
            };
            $this->pdo->prepare(
                "UPDATE sec_ip_reputation SET {$col} = 1 WHERE ip_address = ?"
            )->execute([$ip]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // FINGERPRINT REPUTATION
    // =========================================================================

    public function getOrCreateFingerprintReputation(string $hash, string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_fingerprint_reputation WHERE fingerprint_hash = ? LIMIT 1'
            );
            $stmt->execute([$hash]);
            $row = $stmt->fetch();

            if ($row) {
                // Atualiza IP mais recente
                $this->pdo->prepare(
                    'UPDATE sec_fingerprint_reputation
                     SET ip_address = ?, req_count_total = req_count_total + 1, req_count_1hour = req_count_1hour + 1
                     WHERE fingerprint_hash = ?'
                )->execute([$ip, $hash]);
                return $row;
            }

            $this->pdo->prepare(
                'INSERT IGNORE INTO sec_fingerprint_reputation
                 (fingerprint_hash, fingerprint_raw, ip_address, threat_score)
                 VALUES (?, ?, ?, 0)'
            )->execute([$hash, '', $ip]);

            $stmt->execute([$hash]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    // =========================================================================
    // BANS
    // =========================================================================

    /**
     * Verifica se um IP está banido (consulta ativa).
     * Retorna o registro do ban ou null se livre.
     */
    public function getActiveBan(string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_ip_bans
                 WHERE ip_address = ?
                   AND is_active = 1
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $stmt->execute([$ip]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Insere um novo banimento.
     */
    public function createBan(
        string  $ip,
        string  $type,
        string  $reason,
        int     $durationSeconds,
        int     $threatScore = 0,
        string  $eventType = ''
    ): void {
        try {
            $expires = $durationSeconds > 0
                ? date('Y-m-d H:i:s', time() + $durationSeconds)
                : null;

            $this->pdo->prepare(
                'INSERT INTO sec_ip_bans
                 (ip_address, ip_network, ban_type, reason, event_type, threat_score_at_ban, is_active, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
            )->execute([
                $ip,
                $this->deriveNetwork($ip),
                $type,
                $reason,
                $eventType,
                $threatScore,
                $expires,
            ]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // QUARANTINE
    // =========================================================================

    public function getActiveQuarantine(string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_quarantine
                 WHERE ip_address = ?
                   AND is_active = 1
                   AND (expires_at IS NULL OR expires_at > NOW())
                 LIMIT 1'
            );
            $stmt->execute([$ip]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public function createQuarantine(
        string $ip,
        string $reason,
        int    $durationSeconds,
        int    $threatScore = 0,
        int    $responseDelayMs = 2000,
        int    $maxReqPerMin = 5
    ): void {
        try {
            $expires = date('Y-m-d H:i:s', time() + $durationSeconds);
            $this->pdo->prepare(
                'INSERT INTO sec_quarantine
                 (ip_address, is_active, threat_score, response_delay_ms, max_req_per_minute, expires_at, reason)
                 VALUES (?, 1, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   is_active = 1,
                   threat_score = VALUES(threat_score),
                   response_delay_ms = VALUES(response_delay_ms),
                   max_req_per_minute = VALUES(max_req_per_minute),
                   expires_at = VALUES(expires_at),
                   reason = VALUES(reason)'
            )->execute([$ip, $threatScore, $responseDelayMs, $maxReqPerMin, $expires, $reason]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // WHITELIST
    // =========================================================================

    /**
     * Carrega todas as entradas ativas da whitelist.
     */
    public function getAllWhitelistEntries(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT entry_type, entry_value FROM sec_whitelist
                 WHERE is_active = 1
                   AND (expires_at IS NULL OR expires_at > NOW())'
            );
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    // =========================================================================
    // RATE LIMIT WINDOWS (in-DB fallback)
    // =========================================================================

    /**
     * Sliding Window: incrementa e retorna o count atual na janela.
     * Retorna [count, isExceeded, limit]
     */
    public function slidingWindowIncrement(
        string $key,
        string $keyType,
        string $routeGroup,
        string $ip,
        int    $windowSeconds,
        int    $limitThreshold
    ): array {
        try {
            $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);

            // Tenta UPDATE na janela existente
            $stmt = $this->pdo->prepare(
                'SELECT id, request_count, window_start FROM sec_rate_limit_windows
                 WHERE limit_key = ? AND window_start > ?
                 ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([$key, $windowStart]);
            $row = $stmt->fetch();

            if ($row) {
                $newCount  = (int) $row['request_count'] + 1;
                $exceeded  = $newCount > $limitThreshold;
                $this->pdo->prepare(
                    'UPDATE sec_rate_limit_windows
                     SET request_count = ?, is_exceeded = ?, exceeded_at = IF(? AND exceeded_at IS NULL, NOW(), exceeded_at)
                     WHERE id = ?'
                )->execute([$newCount, $exceeded ? 1 : 0, $exceeded ? 1 : 0, $row['id']]);
                return [$newCount, $exceeded, $limitThreshold];
            }

            // Cria nova janela
            $this->pdo->prepare(
                'INSERT INTO sec_rate_limit_windows
                 (limit_key, key_type, route_group, ip_address, window_start, window_seconds, request_count, limit_threshold)
                 VALUES (?, ?, ?, ?, NOW(), ?, 1, ?)'
            )->execute([$key, $keyType, $routeGroup, $ip, $windowSeconds, $limitThreshold]);

            return [1, false, $limitThreshold];
        } catch (Throwable) {
            return [0, false, $limitThreshold];
        }
    }

    // =========================================================================
    // BURST LOG
    // =========================================================================

    public function logBurst(
        string $ip,
        string $burstType,
        float  $observedRate,
        float  $thresholdRate,
        string $routeGroup,
        string $path,
        int    $scoreDelta
    ): void {
        try {
            $this->pdo->prepare(
                'INSERT INTO sec_burst_log
                 (ip_address, burst_type, observed_rate, threshold_rate, route_group, request_path, score_delta)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$ip, $burstType, $observedRate, $thresholdRate, $routeGroup, $path, $scoreDelta]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // THREAT EVENTS
    // =========================================================================

    public function logThreatEvent(
        string  $ip,
        string  $eventType,
        string  $severity,
        string  $actionTaken,
        int     $threatScore,
        int     $scoreDelta,
        array   $context = []
    ): void {
        try {
            $this->pdo->prepare(
                'INSERT INTO sec_threat_events
                 (ip_address, fingerprint_hash, user_id, event_type, severity,
                  http_method, request_path, user_agent,
                  threat_score_at_event, score_delta, action_taken, event_details)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $ip,
                $context['fingerprint'] ?? null,
                $context['user_id']     ?? null,
                $eventType,
                $severity,
                $context['method']      ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                $context['path']        ?? ($_SERVER['REQUEST_URI']    ?? '/'),
                $context['user_agent']  ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $threatScore,
                $scoreDelta,
                $actionTaken,
                !empty($context['details']) ? json_encode($context['details']) : null,
            ]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // INCIDENT LOG
    // =========================================================================

    public function logIncident(
        string $incidentType,
        string $severity,
        string $ip,
        string $actionTaken,
        int    $mitigation,
        int    $threatScore,
        array  $securityContext = []
    ): void {
        try {
            $incidentId = str_replace('-', '', substr(bin2hex(random_bytes(16)), 0, 32));
            $this->pdo->prepare(
                'INSERT INTO sec_incident_log
                 (incident_id, incident_type, severity, ip_address, fingerprint_hash,
                  user_id, http_method, request_path, user_agent,
                  action_taken, mitigation_level, threat_score, security_context)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $incidentId,
                $incidentType,
                $severity,
                $ip,
                $securityContext['fingerprint'] ?? null,
                $securityContext['user_id']     ?? null,
                $_SERVER['REQUEST_METHOD']      ?? 'GET',
                $_SERVER['REQUEST_URI']         ?? '/',
                $_SERVER['HTTP_USER_AGENT']     ?? '',
                $actionTaken,
                $mitigation,
                $threatScore,
                !empty($securityContext) ? json_encode($securityContext) : null,
            ]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // ADAPTIVE PENALTIES
    // =========================================================================

    public function recordPenalty(
        string $ip,
        string $penaltyType,
        string $severity,
        string $triggerEvent,
        int    $threatScore,
        int    $durationSeconds,
        int    $delayMs = 0
    ): void {
        try {
            $expires = $durationSeconds > 0
                ? date('Y-m-d H:i:s', time() + $durationSeconds)
                : null;

            $this->pdo->prepare(
                'INSERT INTO sec_adaptive_penalties
                 (ip_address, penalty_type, severity, trigger_event, threat_score_at,
                  penalty_duration_seconds, delay_applied_ms, is_active, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)'
            )->execute([
                $ip, $penaltyType, $severity, $triggerEvent,
                $threatScore, $durationSeconds, $delayMs, $expires,
            ]);
        } catch (Throwable) {}
    }

    // =========================================================================
    // ROUTE RISK PROFILES
    // =========================================================================

    public function getRouteProfile(string $routeGroup): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM sec_route_risk_profile
                 WHERE route_group = ? AND is_active = 1 LIMIT 1'
            );
            $stmt->execute([$routeGroup]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function deriveNetwork(string $ip): string
    {
        if (str_contains($ip, ':')) {
            // IPv6: /48
            $groups = explode(':', $ip);
            return implode(':', array_slice($groups, 0, 3)) . '::/48';
        }
        // IPv4: /24
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3)) . '.0/24';
    }
}
