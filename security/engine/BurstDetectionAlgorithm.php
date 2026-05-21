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
        $now    = microtime(true);
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $key    = 'sec_burst_' . hash('sha256', $ip . ':' . $path);
        $window = $this->loadWindow($key);
        $window[] = $now;

        $window = array_filter($window, fn($ts) => ($now - $ts) <= self::WINDOW_SECS);
        $window = array_values($window);
        $this->saveWindow($key, $window);

        if (count($window) < 2) {
            return 0.0;
        }

        $elapsed = $now - $window[0];
        if ($elapsed <= 0.0) {
            return 0.0;
        }

        return count($window) / $elapsed;
    }

    private function loadWindow(string $key): array
    {
        if (function_exists('apcu_fetch')) {
            $data = apcu_fetch($key, $success);
            return ($success && is_array($data)) ? $data : [];
        }

        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pipocine_burst_' . hash('sha256', $key) . '.json';
        $raw = is_file($file) ? @file_get_contents($file) : '';
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        return is_array($data) ? $data : [];
    }

    private function saveWindow(string $key, array $window): void
    {
        if (function_exists('apcu_store')) {
            apcu_store($key, $window, self::WINDOW_SECS + 2);
            return;
        }

        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pipocine_burst_' . hash('sha256', $key) . '.json';
        @file_put_contents($file, json_encode($window), LOCK_EX);
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
