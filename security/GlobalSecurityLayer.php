<?php

declare(strict_types=1);

namespace Security;

use PDO;
use Throwable;
use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;
use Security\Logger\SecurityLogger;
use Security\Whitelist\WhitelistChecker;
use Security\Reputation\IpReputationCache;
use Security\Engine\RiskScoreEngine;
use Security\Engine\BehavioralThreatDetector;
use Security\Engine\BurstDetectionAlgorithm;
use Security\Engine\DistributedPatternDetector;
use Security\RateLimit\ClientRequestGuard;
use Security\RateLimit\ContextualRateLimiter;
use Security\Mitigation\BanManager;
use Security\Mitigation\QuarantineManager;
use Security\Mitigation\AdaptiveSlowdown;
use Security\Mitigation\ChallengeManager;
use Security\Mitigation\SecurityBlockResponder;
use Security\Mitigation\ProgressivePenaltySystem;

/**
 * GlobalSecurityLayer — Orquestrador central da camada de segurança Anti-DDoS/Anti-Bot.
 *
 * Pipeline de execução por requisição (ver PLANO_SECURITY_LAYER.md):
 *
 *  [1] WhitelistChecker       → bypass se autorizado
 *  [2] Resolução de IP/FP     → identifica o cliente
 *  [3] BanManager             → bloqueia se banido
 *  [4] QuarantineManager      → aplica delay se em quarentena
 *  [5] ContextualRateLimiter  → verifica limites por rota
 *  [6] BurstDetectionAlgorithm→ detecta picos abruptos
 *  [7] BehavioralThreatDetector → análise multi-heurística
 *  [8] RiskScoreEngine        → atualiza score cumulativo
 *  [9] ProgressivePenaltySystem → aplica penalidade se necessário
 * [10] AdaptiveSlowdown       → delay adaptativo
 * [11] ChallengeManager       → challenge se rota crítica requer
 * [12] DistributedPatternDetector → detecta DDoS distribuído
 */
final class GlobalSecurityLayer
{
    private DbSecurityStore        $store;
    private SecurityLogger         $logger;
    private WhitelistChecker       $whitelist;
    private IpReputationCache      $repCache;
    private RiskScoreEngine        $scoreEngine;
    private BehavioralThreatDetector $behavioral;
    private BurstDetectionAlgorithm  $burst;
    private DistributedPatternDetector $distributed;
    private ContextualRateLimiter    $rateLimiter;
    private BanManager             $banManager;
    private QuarantineManager      $quarantine;
    private AdaptiveSlowdown       $slowdown;
    private ChallengeManager       $challenge;
    private ProgressivePenaltySystem $penalties;

    public function __construct(private readonly PDO $pdo)
    {
        $this->store       = new DbSecurityStore($pdo);
        $this->logger      = new SecurityLogger($this->store);
        $this->whitelist   = new WhitelistChecker($this->store);
        $this->repCache    = new IpReputationCache($this->store);
        $this->scoreEngine = new RiskScoreEngine($this->store, $this->repCache, $this->logger);
        $this->behavioral  = new BehavioralThreatDetector($this->store);
        $this->burst       = new BurstDetectionAlgorithm($this->store);
        $this->distributed = new DistributedPatternDetector($pdo);
        $this->rateLimiter = new ContextualRateLimiter($this->store);
        $this->banManager  = new BanManager($this->store, $this->logger);
        $this->quarantine  = new QuarantineManager($this->store, $this->logger);
        $this->slowdown    = new AdaptiveSlowdown();
        $this->challenge   = new ChallengeManager($this->store, $pdo);
        $this->penalties   = new ProgressivePenaltySystem(
            $this->store, $this->banManager, $this->quarantine,
            $this->slowdown, $this->logger
        );
    }

    /**
     * Executa o pipeline completo de segurança para a requisição atual.
     * Em caso de falha interna, respeita FAIL_OPEN.
     */
    public function handle(): void
    {
        try {
            $this->execute();
        } catch (Throwable $e) {
            if (!SecurityConfig::FAIL_OPEN) {
                http_response_code(503);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Serviço indisponível temporariamente.']);
                exit;
            }
            // FAIL_OPEN: passa silenciosamente
        }
    }

    // =========================================================================
    // PIPELINE PRINCIPAL
    // =========================================================================

    private function execute(): void
    {
        $ip         = $this->resolveIp();
        $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $asn        = $_SERVER['HTTP_X_ASN']      ?? '';
        $routeGroup = $this->resolveRouteGroup();
        $path       = $_SERVER['REQUEST_URI']      ?? '/';
        $method     = $_SERVER['REQUEST_METHOD']   ?? 'GET';

        if (ClientRequestGuard::hasTemporaryBypass($ip)) {
            return;
        }

        // ----------------------------------------------------------------
        // [1] Whitelist — bypass total se autorizado
        // ----------------------------------------------------------------
        if ($this->whitelist->isWhitelisted($ip, $ua, $asn)) {
            return;
        }

        // ----------------------------------------------------------------
        // [2] Carrega perfil de risco da rota e reputação do IP
        // ----------------------------------------------------------------
        $routeProfile = $this->loadRouteProfile($routeGroup);
        $reputation   = $this->repCache->get($ip) ?? [];
        $threatScore  = (int) ($reputation['threat_score'] ?? 0);

        // ----------------------------------------------------------------
        // [3] Ban check — encerra se banido (hard/soft) ou shadow
        // ----------------------------------------------------------------
        $activeBan = $this->banManager->getActiveBan($ip);
        if ($activeBan) {
            if (($activeBan['ban_type'] ?? '') === 'shadow') {
                $this->banManager->enforceBan($activeBan);
            } elseif ($this->shouldEnforceExistingBan($activeBan, $routeGroup, $method)) {
                $this->store->logThreatEvent(
                    $ip, 'hard_ban_applied', 'critical', 'blocked',
                    $threatScore, 0,
                    ['path' => $path, 'details' => ['ban_type' => $activeBan['ban_type']]]
                );
                $this->blockSuspiciousActivity($ip, $path, ($activeBan['ban_type'] ?? '') === 'hard' ? 403 : 429);
            }
        }

        // ----------------------------------------------------------------
        // [4] Quarentena — aplica delay se em quarentena
        // ----------------------------------------------------------------
        $activeQuarantine = $this->quarantine->getActiveQuarantine($ip);
        if ($activeQuarantine && $this->shouldApplyQuarantineDelay($routeGroup, $method)) {
            $this->quarantine->applyDelay($activeQuarantine);
        }

        // ----------------------------------------------------------------
        // [5] Rate limiting contextual
        // ----------------------------------------------------------------
        [$rateLimitExceeded, $reqCount, $limit] = $this->rateLimiter->check(
            $ip, $threatScore, $routeGroup, $routeProfile
        );

        if ($rateLimitExceeded) {
            $newScore = $this->scoreEngine->record($ip, 'rate_limit_exceeded', [
                'path'    => $path,
                'counters'=> ['req_count_1min' => 1],
                'details' => ['count' => $reqCount, 'limit' => $limit],
            ]);
            $threatScore = $newScore;

            if ($this->shouldEscalatePenalty($routeGroup, $method, $newScore, 'rate_limit_exceeded')) {
                $this->penalties->evaluate($ip, $newScore, 'rate_limit_exceeded', $routeProfile);
            }

            if ($this->shouldBlockRateLimit($reqCount, $limit, $newScore, $routeGroup, $method)) {
                $this->blockSuspiciousActivity($ip, $path);
            }
        }

        // ----------------------------------------------------------------
        // [6] Burst detection
        // ----------------------------------------------------------------
        [$isBurst, $rps, $burstThreshold] = $this->burst->check($ip, $routeGroup, $routeProfile);
        if ($isBurst) {
            $newScore = $this->scoreEngine->record($ip, 'burst_detected', [
                'path'    => $path,
                'details' => ['rps' => round($rps, 2), 'threshold' => $burstThreshold],
            ]);
            $threatScore = $newScore;

            if ($this->shouldEscalatePenalty($routeGroup, $method, $newScore, 'burst_detected')) {
                $this->penalties->evaluate($ip, $newScore, 'burst_detected', $routeProfile);
            }

            if ($this->shouldBlockBurst($rps, $burstThreshold, $newScore, $routeGroup, $method)) {
                $this->blockSuspiciousActivity($ip, $path);
            }
        }

        // ----------------------------------------------------------------
        // [7] Análise comportamental
        // ----------------------------------------------------------------
        // Incrementa contadores de rota na reputação
        if ($this->shouldPersistTrafficCounters($ip, $routeGroup, $path, $rateLimitExceeded, $isBurst, $threatScore)) {
            $this->store->incrementIpScore($ip, 0, '', [
                'req_count_1min'   => 1,
                'req_count_1hour'  => 1,
                'req_count_24hour' => 1,
                ...($this->isNewRouteInCurrentWindow($ip, $routeGroup, $path)
                    ? ['unique_routes_1hour' => 1] : []),
                ...($this->isCriticalRoute($routeGroup)
                    ? ['sensitive_route_hits' => 1] : []),
            ]);
            $this->repCache->invalidate($ip);
            $reputation  = $this->repCache->get($ip) ?? [];
            $threatScore = (int) ($reputation['threat_score'] ?? 0);
        }

        $detectedEvents = $this->shouldAnalyzeBehavior($routeGroup, $method, $threatScore, $rateLimitExceeded, $isBurst)
            ? $this->behavioral->analyze($ip, $reputation, $routeGroup)
            : [];

        // ----------------------------------------------------------------
        // [8] Score engine — registra todos os eventos detectados
        // ----------------------------------------------------------------
        foreach ($detectedEvents as ['event' => $evt, 'context' => $ctx]) {
            $ctx['path'] = $path;
            $threatScore = $this->scoreEngine->record($ip, $evt, $ctx);
        }

        // ----------------------------------------------------------------
        // [9] Progressive penalty
        // ----------------------------------------------------------------
        if ((!empty($detectedEvents) || $threatScore >= SecurityConfig::SCORE_RATE_LIMIT)
            && $this->shouldEscalatePenalty(
                $routeGroup,
                $method,
                $threatScore,
                !empty($detectedEvents) ? $detectedEvents[0]['event'] : 'anomaly_detected'
            )
        ) {
            $triggerEvent = !empty($detectedEvents) ? $detectedEvents[0]['event'] : 'anomaly_detected';
            $action = $this->penalties->evaluate($ip, $threatScore, $triggerEvent, $routeProfile);

            // Se a penalidade resultou em ban/quarentena, re-verifica e encerra
            if ($action === 'ban') {
                $freshBan = $this->banManager->getActiveBan($ip);
                if ($freshBan) {
                    if (($freshBan['ban_type'] ?? '') === 'shadow') {
                        $this->banManager->enforceBan($freshBan);
                    } elseif ($this->shouldEnforceExistingBan($freshBan, $routeGroup, $method)) {
                        $this->blockSuspiciousActivity($ip, $path, ($freshBan['ban_type'] ?? '') === 'hard' ? 403 : 429);
                    }
                }
            }

            if ($action === 'quarantine') {
                $freshQ = $this->quarantine->getActiveQuarantine($ip);
                if ($freshQ && $this->shouldApplyQuarantineDelay($routeGroup, $method)) {
                    $this->quarantine->applyDelay($freshQ);
                }
            }
        }

        // ----------------------------------------------------------------
        // [10] Adaptive slowdown (para IPs com score elevado mas não banidos)
        // ----------------------------------------------------------------
        $this->slowdown->apply($threatScore, $routeProfile);

        // ----------------------------------------------------------------
        // [11] Challenge (rotas críticas + tráfego suspeito)
        // ----------------------------------------------------------------
        if (!$this->isApiLikePath($path)
            && $this->isCriticalRoute($routeGroup)
            && $threatScore >= SecurityConfig::SCORE_RATE_LIMIT
            && $this->challenge->requiresChallenge($ip, $routeProfile)
        ) {
            $this->challenge->issueChallenge($ip, $routeGroup);
            exit;
        }

        // ----------------------------------------------------------------
        // [12] Detecção distribuída (assíncrona — não bloqueia)
        // ----------------------------------------------------------------
        $isDistributed = $this->distributed->observe($ip, $routeGroup);
        if ($isDistributed) {
            $this->scoreEngine->record($ip, 'distributed_pattern_detected', [
                'path'    => $path,
                'details' => ['route_group' => $routeGroup],
            ]);
        }

        // Pipeline completo — passa para o controller
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function resolveIp(): string
    {
        return ClientRequestGuard::resolveClientIp();
    }

    private function blockSuspiciousActivity(string $ip, string $path, int $code = 429): never
    {
        SecurityBlockResponder::block($ip, $path, $code);
    }

    private function resolveRouteGroup(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        foreach (SecurityConfig::ROUTE_GROUPS as $group => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return $group;
                }
            }
        }

        return 'global';
    }

    private function isApiLikePath(string $path): bool
    {
        return SecurityBlockResponder::isApiLikePath($path);
    }

    private function isMutatingRequest(string $method): bool
    {
        return !in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isCriticalRoute(string $routeGroup): bool
    {
        return in_array($routeGroup, SecurityConfig::CRITICAL_ROUTE_GROUPS, true);
    }

    private function isHighTrafficRoute(string $routeGroup): bool
    {
        return in_array($routeGroup, SecurityConfig::HIGH_TRAFFIC_ROUTE_GROUPS, true);
    }

    private function shouldEnforceExistingBan(array $ban, string $routeGroup, string $method): bool
    {
        $type = (string) ($ban['ban_type'] ?? 'soft');
        if ($type === 'hard') {
            return true;
        }

        return $this->isCriticalRoute($routeGroup) || $this->isMutatingRequest($method);
    }

    private function shouldApplyQuarantineDelay(string $routeGroup, string $method): bool
    {
        return $this->isCriticalRoute($routeGroup) || $this->isMutatingRequest($method);
    }

    private function shouldBlockRateLimit(
        int $reqCount,
        int $limit,
        int $score,
        string $routeGroup,
        string $method
    ): bool {
        if ($limit > 0 && $reqCount >= (int) ceil($limit * SecurityConfig::RATE_LIMIT_HARD_BLOCK_MULTIPLIER)) {
            return true;
        }

        return $this->isCriticalRoute($routeGroup)
            && $this->isMutatingRequest($method)
            && $score >= SecurityConfig::SCORE_BLOCK;
    }

    private function shouldBlockBurst(
        float $rps,
        float $threshold,
        int $score,
        string $routeGroup,
        string $method
    ): bool {
        if ($threshold > 0.0 && $rps >= ($threshold * SecurityConfig::BURST_HARD_BLOCK_MULTIPLIER)) {
            return true;
        }

        return $this->isCriticalRoute($routeGroup)
            && $this->isMutatingRequest($method)
            && $score >= SecurityConfig::SCORE_BLOCK;
    }

    private function shouldPersistTrafficCounters(
        string $ip,
        string $routeGroup,
        string $path,
        bool $rateLimitExceeded,
        bool $isBurst,
        int $score
    ): bool {
        if ($rateLimitExceeded
            || $isBurst
            || $score >= SecurityConfig::SCORE_RATE_LIMIT
            || $this->isCriticalRoute($routeGroup)
            || $this->isMutatingRequest((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
        ) {
            return true;
        }

        if ($this->isHighTrafficRoute($routeGroup)) {
            return $this->oncePerClientWindow(
                'clean_counter',
                $ip . ':' . $routeGroup . ':' . parse_url($path, PHP_URL_PATH),
                SecurityConfig::CLEAN_COUNTER_PERSIST_INTERVAL_SECONDS
            );
        }

        return false;
    }

    private function shouldAnalyzeBehavior(
        string $routeGroup,
        string $method,
        int $score,
        bool $rateLimitExceeded,
        bool $isBurst
    ): bool {
        return $rateLimitExceeded
            || $isBurst
            || $score >= SecurityConfig::SCORE_RATE_LIMIT
            || ($this->isCriticalRoute($routeGroup) && $this->isMutatingRequest($method));
    }

    private function shouldEscalatePenalty(
        string $routeGroup,
        string $method,
        int $score,
        string $eventType
    ): bool {
        if ($this->isCriticalRoute($routeGroup) || $this->isMutatingRequest($method)) {
            return true;
        }

        if (in_array($eventType, ['scanner_detected', 'replay_attack', 'distributed_pattern_detected'], true)) {
            return true;
        }

        return $score >= SecurityConfig::SCORE_QUARANTINE;
    }

    private function oncePerClientWindow(string $prefix, string $keyMaterial, int $ttl): bool
    {
        $key = 'sec_' . $prefix . '_' . hash('sha256', $keyMaterial);

        if (function_exists('apcu_add')) {
            return apcu_add($key, 1, $ttl);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $now = time();
        $_SESSION['_sec_once'] ??= [];
        if ((int) ($_SESSION['_sec_once'][$key] ?? 0) > $now) {
            return false;
        }

        $_SESSION['_sec_once'][$key] = $now + $ttl;
        if (count($_SESSION['_sec_once']) > 64) {
            $_SESSION['_sec_once'] = array_slice($_SESSION['_sec_once'], -64, null, true);
        }

        return true;
    }

    private function isNewRouteInCurrentWindow(string $ip, string $routeGroup, string $path): bool
    {
        $normalizedPath = parse_url($path, PHP_URL_PATH) ?: '/';
        $window = (string) floor(time() / 3600);
        $key = 'sec_seen_route_' . hash('sha256', $ip . ':' . $window . ':' . $routeGroup . ':' . $normalizedPath);

        if (function_exists('apcu_add')) {
            return apcu_add($key, 1, 3700);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $_SESSION['_sec_seen_routes'] ??= [];
        $sessionKey = $routeGroup . ':' . $normalizedPath;
        $currentWindow = $_SESSION['_sec_seen_routes_window'] ?? '';
        if ($currentWindow !== $window) {
            $_SESSION['_sec_seen_routes_window'] = $window;
            $_SESSION['_sec_seen_routes'] = [];
        }

        if (isset($_SESSION['_sec_seen_routes'][$sessionKey])) {
            return false;
        }

        $_SESSION['_sec_seen_routes'][$sessionKey] = true;
        return true;
    }

    /**
     * Carrega o perfil de risco da rota com cache APCu.
     */
    private function loadRouteProfile(string $routeGroup): array
    {
        $cacheKey = 'sec_rp_' . $routeGroup;

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }

        $profile = $this->store->getRouteProfile($routeGroup)
                ?? $this->store->getRouteProfile('global')
                ?? [];

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $profile, SecurityConfig::CACHE_ROUTE_PROFILE_TTL);
        }

        return $profile;
    }
}
