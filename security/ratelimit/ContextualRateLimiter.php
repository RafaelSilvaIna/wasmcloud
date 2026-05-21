<?php

declare(strict_types=1);

namespace Security\RateLimit;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;

/**
 * ContextualRateLimiter — Rate limiting contextual por rota e nível de ameaça.
 *
 * Determina o limite adequado para o IP com base em:
 *   - Grupo de rota (auth, stream, search, admin, etc.)
 *   - Score de ameaça atual (clean / suspicious / hostile)
 *
 * Usa sliding window in-DB como fallback (sec_rate_limit_windows).
 * Com APCu disponível, mantém contadores em memória para máxima performance.
 */
final class ContextualRateLimiter
{
    /** Janela padrão: 60 segundos */
    private const WINDOW_SECONDS = 60;

    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Verifica se o IP excedeu o limite para a rota/grupo atual.
     *
     * @param string $ip          IP de origem
     * @param int    $threatScore Score atual de ameaça
     * @param string $routeGroup  Grupo de rota identificado
     * @param array  $routeProfile Perfil da rota (de sec_route_risk_profile)
     * @return array [exceeded, count, limit]
     */
    public function check(
        string $ip,
        int    $threatScore,
        string $routeGroup,
        array  $routeProfile
    ): array {
        $limit = $this->resolveLimit($threatScore, $routeGroup, $routeProfile);
        $key   = hash('sha256', $ip . ':' . $routeGroup . ':' . floor(time() / self::WINDOW_SECONDS));

        // Tenta APCu primeiro (in-memory, sub-ms)
        if (function_exists('apcu_fetch')) {
            return $this->checkApcu($key, $limit);
        }

        // Fallback: sliding window no banco
        [$count, $exceeded, $limit] = $this->store->slidingWindowIncrement(
            $key,
            'ip',
            $routeGroup,
            $ip,
            self::WINDOW_SECONDS,
            $limit
        );

        return [$exceeded, $count, $limit];
    }

    // -------------------------------------------------------------------------

    /**
     * Resolve o limite de requisições com base no score de ameaça e no perfil da rota.
     */
    private function resolveLimit(int $score, string $routeGroup, array $routeProfile): int
    {
        [$clean, $suspicious, $hostile] = $this->fallbackLimits($routeGroup);

        if ($score >= SecurityConfig::SCORE_BLOCK) {
            return max($hostile, (int) ($routeProfile['rate_limit_hostile'] ?? $hostile));
        }
        if ($score >= SecurityConfig::SCORE_RATE_LIMIT) {
            return max($suspicious, (int) ($routeProfile['rate_limit_suspicious'] ?? $suspicious));
        }
        return max($clean, (int) ($routeProfile['rate_limit_clean'] ?? $clean));
    }

    private function fallbackLimits(string $routeGroup): array
    {
        return match ($routeGroup) {
            'catalog' => [900, 240, 80],
            'api_v2'  => [420, 140, 45],
            'api_v3'  => [300, 100, 35],
            'api_v4'  => [240, 80, 25],
            default   => [
                SecurityConfig::GLOBAL_RATE_CLEAN,
                SecurityConfig::GLOBAL_RATE_SUSPICIOUS,
                SecurityConfig::GLOBAL_RATE_HOSTILE,
            ],
        };
    }

    private function checkApcu(string $key, int $limit): array
    {
        $cacheKey = 'sec_rl_' . $key;
        apcu_add($cacheKey, 0, self::WINDOW_SECONDS);
        $newCount = apcu_inc($cacheKey, 1, $success, self::WINDOW_SECONDS);
        if (!$success) {
            apcu_store($cacheKey, 1, self::WINDOW_SECONDS);
            $newCount = 1;
        }

        return [$newCount > $limit, $newCount, $limit];
    }
}
