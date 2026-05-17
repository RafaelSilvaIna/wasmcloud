<?php

declare(strict_types=1);

namespace Security\Engine;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;

/**
 * BurstDetectionAlgorithm — Detecta picos abruptos incompatíveis com tráfego humano.
 *
 * Usa janela deslizante em sessão PHP para calcular a taxa de requisições por segundo.
 * Registra bursts detectados em sec_burst_log.
 */
final class BurstDetectionAlgorithm
{
    private const SESSION_KEY = '_sec_burst_window';

    /** Janela de observação em segundos */
    private const WINDOW_SECS = 5;

    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Verifica se o IP está em burst.
     * Retorna [isBurst, observedRps, threshold].
     */
    public function check(string $ip, string $routeGroup, array $routeProfile): array
    {
        $threshold = (float) ($routeProfile['burst_threshold_rps'] ?? SecurityConfig::BURST_RPS_GLOBAL);
        $path      = $_SERVER['REQUEST_URI'] ?? '/';

        $rps = $this->computeRps($ip);

        if ($rps > $threshold) {
            $this->store->logBurst(
                $ip,
                $this->classifyBurst($routeGroup),
                $rps,
                $threshold,
                $routeGroup,
                $path,
                SecurityConfig::SCORE_DELTA['burst_detected'] ?? 40
            );
            return [true, $rps, $threshold];
        }

        return [false, $rps, $threshold];
    }

    // -------------------------------------------------------------------------

    /**
     * Calcula a taxa de requisições por segundo usando uma janela em sessão.
     */
    private function computeRps(string $ip): float
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return 0.0;
        }

        $now    = microtime(true);
        $window = $_SESSION[self::SESSION_KEY] ?? [];

        // Adiciona timestamp atual
        $window[] = $now;

        // Remove timestamps fora da janela
        $window = array_filter($window, fn($ts) => ($now - $ts) <= self::WINDOW_SECS);
        $window = array_values($window);

        $_SESSION[self::SESSION_KEY] = $window;

        if (count($window) < 2) {
            return 0.0;
        }

        $elapsed = $now - $window[0];
        if ($elapsed <= 0.0) {
            return 0.0;
        }

        return count($window) / $elapsed;
    }

    private function classifyBurst(string $routeGroup): string
    {
        return match ($routeGroup) {
            'auth'    => 'parallel_auth',
            'stream'  => 'stream_hammering',
            'search'  => 'search_hammering',
            default   => 'req_per_second',
        };
    }
}
