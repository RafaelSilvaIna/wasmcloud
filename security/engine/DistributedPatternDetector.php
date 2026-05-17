<?php

declare(strict_types=1);

namespace Security\Engine;

use Security\Storage\DbSecurityStore;
use Throwable;

/**
 * DistributedPatternDetector — Detecta ataques DDoS coordenados entre múltiplos IPs.
 *
 * Agrupa IPs por rede /24 ou /48 e verifica se há concentração anormal de
 * tráfego de uma mesma rede. Correlações são salvas em sec_distributed_pattern.
 */
final class DistributedPatternDetector
{
    /** Mínimo de IPs distintos da mesma rede /24 para considerar ataque distribuído */
    private const MIN_IPS_PER_NETWORK  = 5;

    /** Máximo de RPM combinado antes de classificar como flood distribuído */
    private const MAX_RPM_DISTRIBUTED  = 300;

    /** Cache APCu: contadores por rede */
    private const CACHE_PREFIX = 'sec_dist_net_';

    /** TTL da janela de detecção (segundos) */
    private const WINDOW_TTL = 60;

    public function __construct(private readonly \PDO $pdo) {}

    /**
     * Registra o IP atual na janela de detecção e verifica padrão distribuído.
     * Retorna true se um ataque distribuído foi detectado.
     */
    public function observe(string $ip, string $routeGroup): bool
    {
        if (!function_exists('apcu_fetch')) {
            return false;
        }

        $network  = $this->deriveNetwork($ip);
        $cacheKey = self::CACHE_PREFIX . md5($network . ':' . $routeGroup);

        $data = apcu_fetch($cacheKey, $success);
        if (!$success || !is_array($data)) {
            $data = ['ips' => [], 'count' => 0, 'started' => time()];
        }

        // Janela expirada — reset
        if ((time() - $data['started']) > self::WINDOW_TTL) {
            $data = ['ips' => [], 'count' => 0, 'started' => time()];
        }

        $data['ips'][$ip] = true;
        $data['count']++;

        apcu_store($cacheKey, $data, self::WINDOW_TTL);

        $distinctIps = count($data['ips']);
        $rpm         = $data['count'];

        if ($distinctIps >= self::MIN_IPS_PER_NETWORK
            && $rpm >= self::MAX_RPM_DISTRIBUTED
        ) {
            $this->recordPattern($network, $routeGroup, $distinctIps, $rpm, array_keys($data['ips']));
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------

    private function recordPattern(
        string $network,
        string $routeGroup,
        int    $ipsInvolved,
        int    $rpm,
        array  $sampleIps
    ): void {
        try {
            $patternId = str_replace('-', '', substr(bin2hex(random_bytes(16)), 0, 32));
            $sample    = json_encode(array_slice($sampleIps, 0, 20));
            $path      = $_SERVER['REQUEST_URI'] ?? '/';

            $this->pdo->prepare(
                'INSERT INTO sec_distributed_pattern
                 (pattern_id, pattern_type, ips_involved, requests_per_minute,
                  target_route, sample_ips, ip_network, status, threat_level, group_action)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "active", "high", "throttle")
                 ON DUPLICATE KEY UPDATE
                   ips_involved = VALUES(ips_involved),
                   requests_per_minute = VALUES(requests_per_minute),
                   last_updated_at = NOW()'
            )->execute([
                $patternId, 'same_network_flood',
                $ipsInvolved, $rpm, $path, $sample, $network,
            ]);
        } catch (Throwable) {}
    }

    private function deriveNetwork(string $ip): string
    {
        if (str_contains($ip, ':')) {
            $groups = explode(':', $ip);
            return implode(':', array_slice($groups, 0, 3)) . '::/48';
        }
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3)) . '.0/24';
    }
}
