<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;
use Security\Logger\SecurityLogger;
use Security\Storage\DbSecurityStore;

/**
 * QuarantineManager — Gerencia IPs em modo quarentena.
 *
 * IPs em quarentena:
 *   - Recebem delay artificial em cada resposta
 *   - Têm limite drasticamente reduzido de req/min
 *   - Continuam respondendo (não são bloqueados definitivamente)
 *   - São monitorados intensamente para coleta de padrões
 */
final class QuarantineManager
{
    private const CACHE_PREFIX = 'sec_qtn_';

    public function __construct(
        private readonly DbSecurityStore $store,
        private readonly SecurityLogger  $logger
    ) {}

    /**
     * Verifica se o IP está em quarentena.
     */
    public function getActiveQuarantine(string $ip): ?array
    {
        $cacheKey = self::CACHE_PREFIX . md5($ip);

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success) {
                return $cached === false ? null : $cached;
            }
        }

        $row = $this->store->getActiveQuarantine($ip);

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $row ?? false, 30);
        }

        return $row;
    }

    /**
     * Aplica quarentena ao IP.
     */
    public function quarantine(string $ip, int $threatScore, string $reason = ''): void
    {
        $this->store->createQuarantine(
            $ip,
            $reason ?: "Score de quarentena: {$threatScore}",
            SecurityConfig::QUARANTINE_SECONDS,
            $threatScore,
            2000,  // delay 2s
            5      // max 5 req/min
        );

        $this->invalidateCache($ip);
        $this->logger->block($ip, 'quarantine', $threatScore, $reason);

        $this->store->recordPenalty(
            $ip, 'quarantine', 'critical', 'auto_quarantine',
            $threatScore, SecurityConfig::QUARANTINE_SECONDS, 2000
        );
    }

    /**
     * Aplica o delay da quarentena (bloqueia a thread pelo tempo configurado).
     * Retorna o delay aplicado em ms.
     */
    public function applyDelay(array $quarantine): int
    {
        $delayMs = (int) ($quarantine['response_delay_ms'] ?? 2000);
        $delayUs = $delayMs * 1000;

        // Limita o delay máximo a 5 segundos
        $delayUs = min($delayUs, 5_000_000);

        if ($delayUs > 0) {
            usleep($delayUs);
        }

        return $delayMs;
    }

    public function invalidateCache(string $ip): void
    {
        if (function_exists('apcu_delete')) {
            apcu_delete(self::CACHE_PREFIX . md5($ip));
        }
    }
}
