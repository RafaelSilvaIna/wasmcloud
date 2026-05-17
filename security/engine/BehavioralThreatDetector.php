<?php

declare(strict_types=1);

namespace Security\Engine;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;

/**
 * BehavioralThreatDetector — Análise multi-heurística de comportamento.
 *
 * Analisa a requisição atual e o histórico de reputação do IP para
 * detectar padrões de bot, scraper, scanner e abuso de rotas.
 *
 * Retorna um array de eventos detectados para serem processados pelo RiskScoreEngine.
 */
final class BehavioralThreatDetector
{
    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Analisa a requisição e retorna lista de [eventType, context] detectados.
     *
     * @param string $ip          IP de origem
     * @param array  $reputation  Registro atual de sec_ip_reputation
     * @param string $routeGroup  Grupo de rota identificado
     * @return array              [[eventType, context], ...]
     */
    public function analyze(string $ip, array $reputation, string $routeGroup): array
    {
        $detected = [];

        $ua          = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $path        = $_SERVER['REQUEST_URI']     ?? '/';

        // ----------------------------------------------------------------
        // 1. User-Agent inválido ou ausente
        // ----------------------------------------------------------------
        if ($this->isInvalidUserAgent($ua)) {
            $detected[] = [
                'event'   => 'invalid_user_agent',
                'context' => ['behavior_flag' => 'invalid_user_agent', 'details' => ['ua' => substr($ua, 0, 120)]],
            ];
        }

        // ----------------------------------------------------------------
        // 2. User-Agent de bot conhecido
        // ----------------------------------------------------------------
        if (!empty($ua) && $this->isKnownBotUA($ua)) {
            $this->store->flagIpAsBot($ip, 'bot');
            $detected[] = [
                'event'   => 'bot_pattern_detected',
                'context' => ['behavior_flag' => 'invalid_user_agent', 'details' => ['ua' => substr($ua, 0, 120)]],
            ];
        }

        // ----------------------------------------------------------------
        // 3. Auth flooding
        // ----------------------------------------------------------------
        if ($routeGroup === 'auth') {
            $authCount = (int) ($reputation['req_count_1hour'] ?? 0);
            if ($authCount > SecurityConfig::AUTH_FLOOD_THRESHOLD) {
                $detected[] = [
                    'event'   => 'auth_flooding',
                    'context' => [
                        'behavior_flag' => 'auth_flooding',
                        'counters'      => ['sensitive_route_hits' => 1],
                        'details'       => ['auth_count_1h' => $authCount],
                    ],
                ];
            }
        }

        // ----------------------------------------------------------------
        // 4. Scraper: muitas rotas distintas em uma hora
        // ----------------------------------------------------------------
        $uniqueRoutes = (int) ($reputation['unique_routes_1hour'] ?? 0);
        if ($uniqueRoutes > SecurityConfig::SCRAPER_UNIQUE_ROUTES_PER_HOUR) {
            $this->store->flagIpAsBot($ip, 'scraper');
            $detected[] = [
                'event'   => 'scraper_detected',
                'context' => [
                    'behavior_flag' => 'crawl_pattern',
                    'details'       => ['unique_routes_1h' => $uniqueRoutes],
                ],
            ];
        }

        // ----------------------------------------------------------------
        // 5. Velocidade de requisição suspeita (constante intervals)
        //    Detectado por contadores em sessão
        // ----------------------------------------------------------------
        if ($this->detectConstantIntervals()) {
            $detected[] = [
                'event'   => 'bot_pattern_detected',
                'context' => [
                    'behavior_flag' => 'constant_intervals',
                    'details'       => ['pattern' => 'constant_timing'],
                ],
            ];
        }

        // ----------------------------------------------------------------
        // 6. Route flooding em rotas críticas
        // ----------------------------------------------------------------
        $sensitiveHits = (int) ($reputation['sensitive_route_hits'] ?? 0);
        if (in_array($routeGroup, ['auth', 'admin', 'recovery', 'api_v4'], true)
            && $sensitiveHits > 50
        ) {
            $detected[] = [
                'event'   => 'route_flooding',
                'context' => [
                    'behavior_flag' => 'route_flooding',
                    'counters'      => ['sensitive_route_hits' => 1],
                    'details'       => ['sensitive_hits' => $sensitiveHits, 'route' => $routeGroup],
                ],
            ];
        }

        // ----------------------------------------------------------------
        // 7. Abuso de streaming
        // ----------------------------------------------------------------
        if ($routeGroup === 'stream' && (int) ($reputation['req_count_1hour'] ?? 0) > 200) {
            $detected[] = [
                'event'   => 'stream_abuse',
                'context' => [
                    'behavior_flag' => 'stream_abuse',
                    'details'       => ['req_1h' => $reputation['req_count_1hour']],
                ],
            ];
        }

        // ----------------------------------------------------------------
        // 8. Abuso de busca
        // ----------------------------------------------------------------
        if ($routeGroup === 'search' && (int) ($reputation['req_count_1hour'] ?? 0) > 120) {
            $detected[] = [
                'event'   => 'search_abuse',
                'context' => [
                    'behavior_flag' => 'search_abuse',
                    'details'       => ['req_1h' => $reputation['req_count_1hour']],
                ],
            ];
        }

        return $detected;
    }

    // -------------------------------------------------------------------------

    private function isInvalidUserAgent(string $ua): bool
    {
        return trim($ua) === '' || strlen($ua) < 5;
    }

    private function isKnownBotUA(string $ua): bool
    {
        $lower = strtolower($ua);
        foreach (SecurityConfig::BOT_UA_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta intervalos constantes entre requisições via sessão PHP.
     * Bots tendem a fazer requisições em intervalos muito regulares.
     */
    private function detectConstantIntervals(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $now      = microtime(true);
        $history  = $_SESSION['_sec_ts_history'] ?? [];
        $history[]= $now;

        // Mantém apenas os últimos 10 timestamps
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        $_SESSION['_sec_ts_history'] = $history;

        if (count($history) < 6) {
            return false;
        }

        // Calcula desvio padrão dos intervalos
        $intervals = [];
        for ($i = 1; $i < count($history); $i++) {
            $intervals[] = $history[$i] - $history[$i - 1];
        }

        $mean = array_sum($intervals) / count($intervals);
        if ($mean < 0.05) {
            // Menos de 50ms entre requisições = definitivamente bot
            return true;
        }

        $variance = 0.0;
        foreach ($intervals as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $stddev = sqrt($variance / count($intervals));

        // Coeficiente de variação muito baixo = intervalos constantes
        $cv = $mean > 0 ? $stddev / $mean : 1.0;
        return $cv < 0.05 && $mean < 1.0;
    }
}
