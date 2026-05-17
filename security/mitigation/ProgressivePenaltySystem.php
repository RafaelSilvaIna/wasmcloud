<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;
use Security\Logger\SecurityLogger;

/**
 * ProgressivePenaltySystem — Escala penalidades com base no histórico de violações.
 *
 * Cada violação acumula histórico. Reincidência resulta em penalidade mais severa.
 *
 * Níveis:
 *   1 (0–99)   : Monitoramento silencioso
 *   2 (100–249) : Rate limiting adaptativo
 *   3 (250–499) : Delay artificial + rate limit
 *   4 (500–749) : Soft ban temporário
 *   5 (750+)   : Hard ban ou quarentena
 */
final class ProgressivePenaltySystem
{
    public function __construct(
        private readonly DbSecurityStore  $store,
        private readonly BanManager       $banManager,
        private readonly QuarantineManager$quarantineManager,
        private readonly AdaptiveSlowdown $slowdown,
        private readonly SecurityLogger   $logger
    ) {}

    /**
     * Avalia o score atual e aplica a penalidade adequada.
     *
     * @return string  Ação aplicada: 'monitor', 'rate_limit', 'delay', 'ban', 'quarantine'
     */
    public function evaluate(
        string $ip,
        int    $threatScore,
        string $triggerEvent,
        array  $routeProfile = []
    ): string {
        if ($threatScore >= SecurityConfig::SCORE_QUARANTINE) {
            // Nível 5: verifica se já está em quarentena ou precisa ser quarentenado
            $existing = $this->quarantineManager->getActiveQuarantine($ip);
            if (!$existing) {
                $this->quarantineManager->quarantine($ip, $threatScore, $triggerEvent);
                $this->store->recordPenalty(
                    $ip, 'quarantine', 'critical', $triggerEvent,
                    $threatScore, SecurityConfig::QUARANTINE_SECONDS
                );
                $this->logger->incident($ip, 'quarantine_entered', 'quarantined', 5, $threatScore);
            }
            return 'quarantine';
        }

        if ($threatScore >= SecurityConfig::SCORE_BLOCK) {
            // Nível 4: soft ban
            $existingBan = $this->banManager->getActiveBan($ip);
            if (!$existingBan) {
                $this->banManager->applyAutomaticBan($ip, $threatScore, $triggerEvent);
                $this->store->recordPenalty(
                    $ip, 'soft_ban', 'high', $triggerEvent,
                    $threatScore, SecurityConfig::BAN_SOFT_SECONDS
                );
            }
            return 'ban';
        }

        if ($threatScore >= SecurityConfig::SCORE_DELAY) {
            // Nível 3: delay artificial
            $delayMs = $this->slowdown->apply($threatScore, $routeProfile);
            if ($delayMs > 0) {
                $this->store->recordPenalty(
                    $ip, 'delay_adaptive', 'medium', $triggerEvent,
                    $threatScore, 0, $delayMs
                );
            }
            return 'delay';
        }

        if ($threatScore >= SecurityConfig::SCORE_RATE_LIMIT) {
            // Nível 2: registra rate limiting (o rate limiter já bloqueou se necessário)
            $this->store->recordPenalty(
                $ip, 'rate_limit', 'low', $triggerEvent,
                $threatScore, 60
            );
            return 'rate_limit';
        }

        // Nível 1: monitoramento silencioso
        return 'monitor';
    }
}
