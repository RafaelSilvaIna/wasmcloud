<?php

declare(strict_types=1);

namespace Security\Reputation;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;

/**
 * IpReputationCache — Cache de reputação de IPs em memória (APCu/in-request).
 *
 * Carrega o registro da sec_ip_reputation uma vez por request e o mantém
 * em memória para evitar consultas repetidas ao banco.
 */
final class IpReputationCache
{
    /** Cache in-process (sobrevive apenas ao request atual) */
    private static array $cache = [];

    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Carrega (ou cria) o registro de reputação de um IP.
     */
    public function get(string $ip): ?array
    {
        if (isset(self::$cache[$ip])) {
            return self::$cache[$ip];
        }

        // APCu: cache entre requests dentro do mesmo processo PHP
        $key = 'sec_rep_' . md5($ip);
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($key, $success);
            if ($success && is_array($cached)) {
                self::$cache[$ip] = $cached;
                return $cached;
            }
        }

        $row = $this->store->getOrCreateIpReputation($ip);
        if ($row) {
            self::$cache[$ip] = $row;
            if (function_exists('apcu_store')) {
                apcu_store($key, $row, SecurityConfig::CACHE_REPUTATION_TTL);
            }
        }

        return $row;
    }

    /**
     * Invalida o cache para um IP específico (chamado após mudança de score).
     */
    public function invalidate(string $ip): void
    {
        unset(self::$cache[$ip]);
        if (function_exists('apcu_delete')) {
            apcu_delete('sec_rep_' . md5($ip));
        }
    }

    /**
     * Retorna o threat_score atual de um IP (0 se desconhecido).
     */
    public function getScore(string $ip): int
    {
        $rep = $this->get($ip);
        return (int) ($rep['threat_score'] ?? 0);
    }

    /**
     * Retorna o nível de mitigação atual (1–5).
     */
    public function getMitigationLevel(string $ip): int
    {
        $rep = $this->get($ip);
        return (int) ($rep['mitigation_level'] ?? 1);
    }
}
