<?php

declare(strict_types=1);

namespace Security\Engine;

use Security\Config\SecurityConfig;
use Security\Logger\SecurityLogger;
use Security\Reputation\IpReputationCache;
use Security\Storage\DbSecurityStore;

/**
 * RiskScoreEngine — Acumula e aplica pontuações de ameaça por IP.
 *
 * Cada evento de ameaça incrementa o score em sec_ip_reputation.
 * O engine também invalida o cache APCu após cada atualização.
 */
final class RiskScoreEngine
{
    public function __construct(
        private readonly DbSecurityStore   $store,
        private readonly IpReputationCache $cache,
        private readonly SecurityLogger    $logger
    ) {}

    /**
     * Registra um evento de ameaça e incrementa o score do IP.
     *
     * @param string $ip        IP de origem
     * @param string $eventType Um dos tipos definidos em sec_threat_events.event_type
     * @param array  $context   Dados adicionais do evento
     * @return int              Novo score estimado (após incremento)
     */
    public function record(string $ip, string $eventType, array $context = []): int
    {
        $delta      = SecurityConfig::SCORE_DELTA[$eventType] ?? 10;
        $currentRep = $this->cache->get($ip);
        $currentScore = (int) ($currentRep['threat_score'] ?? 0);
        $newScore   = min($currentScore + $delta, SecurityConfig::SCORE_MAX);

        // Atualiza no banco com flag comportamental se disponível
        $flag     = $context['behavior_flag'] ?? '';
        $counters = $context['counters']       ?? [];

        $this->store->incrementIpScore($ip, $delta, $flag, $counters);
        $this->cache->invalidate($ip);

        // Determina action_taken com base no novo score
        $action = $this->resolveAction($newScore, $eventType);

        // Loga o evento
        $this->logger->event($ip, $eventType, $action, $newScore, $delta, $context);

        return $newScore;
    }

    /**
     * Retorna o score atual de um IP sem modificá-lo.
     */
    public function currentScore(string $ip): int
    {
        return $this->cache->getScore($ip);
    }

    /**
     * Retorna o nível de mitigação atual (1–5).
     */
    public function mitigationLevel(string $ip): int
    {
        return $this->cache->getMitigationLevel($ip);
    }

    // -------------------------------------------------------------------------

    private function resolveAction(int $score, string $eventType): string
    {
        if ($score >= SecurityConfig::SCORE_QUARANTINE) {
            return 'quarantined';
        }
        if ($score >= SecurityConfig::SCORE_BLOCK) {
            return 'hard_banned';
        }
        if ($score >= SecurityConfig::SCORE_DELAY) {
            return 'delayed';
        }
        if ($score >= SecurityConfig::SCORE_RATE_LIMIT) {
            return 'rate_limited';
        }
        return 'log_only';
    }
}
