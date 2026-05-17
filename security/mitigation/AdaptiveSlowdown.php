<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;

/**
 * AdaptiveSlowdown — Aplica delay artificial progressivo baseado no score de ameaça.
 *
 * Nível 3 (250–499): 500ms
 * Nível 4 (500–749): 1.5s
 * Quarentena (750+): 3s (gerenciado pelo QuarantineManager)
 *
 * O delay é aplicado via usleep() antes que a resposta seja enviada.
 * Isso aumenta o custo de ataques sem impactar usuários legítimos.
 */
final class AdaptiveSlowdown
{
    /**
     * Aplica o delay adequado para o score informado.
     * Retorna o delay aplicado em ms (0 se nenhum).
     */
    public function apply(int $threatScore, array $routeProfile = []): int
    {
        $delayUs = $this->resolveDelay($threatScore, $routeProfile);

        if ($delayUs > 0) {
            usleep($delayUs);
            return intdiv($delayUs, 1000);
        }

        return 0;
    }

    /**
     * Retorna o delay em microssegundos para o score dado.
     */
    public function resolveDelay(int $threatScore, array $routeProfile = []): int
    {
        // Delay específico da rota para IPs suspeitos
        $routeDelayMs = (int) ($routeProfile['delay_on_suspicious'] ?? 0);

        if ($threatScore >= SecurityConfig::SCORE_BLOCK) {
            return max(SecurityConfig::DELAY_LEVEL_4_US, $routeDelayMs * 1000);
        }

        if ($threatScore >= SecurityConfig::SCORE_DELAY) {
            return max(SecurityConfig::DELAY_LEVEL_3_US, $routeDelayMs * 1000);
        }

        if ($routeDelayMs > 0
            && $threatScore >= SecurityConfig::SCORE_RATE_LIMIT
        ) {
            return $routeDelayMs * 1000;
        }

        return 0;
    }
}
