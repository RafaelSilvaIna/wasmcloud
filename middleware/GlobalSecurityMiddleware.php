<?php

declare(strict_types=1);

namespace Middleware;

use PDO;
use Throwable;
use Security\GlobalSecurityLayer;

/**
 * GlobalSecurityMiddleware — Ponto de entrada da camada de segurança.
 *
 * Deve ser chamado em routes/index.php após as conexões de banco
 * estarem disponíveis ($pdo).
 *
 * Uso:
 *   \Middleware\GlobalSecurityMiddleware::handle($pdo);
 *
 * Carrega todos os arquivos da camada de segurança e executa o pipeline.
 * Se a conexão $pdo não estiver disponível, o middleware é ignorado (fail-open).
 */
final class GlobalSecurityMiddleware
{
    /**
     * Executa a camada de segurança global.
     * Deve ser chamado logo após o bootstrap de banco de dados.
     */
    public static function handle(?PDO $pdo): void
    {
        if ($pdo === null) {
            return;
        }

        static $handled = false;
        if ($handled) {
            return;
        }
        $handled = true;

        try {
            self::requireAll();
            $layer = new GlobalSecurityLayer($pdo);
            $layer->handle();
        } catch (Throwable) {
            // Fail-open: não interrompe a requisição em caso de erro interno
        }
    }

    // -------------------------------------------------------------------------

    private static function requireAll(): void
    {
        $base = __DIR__ . '/../security/';

        require_once $base . 'config/SecurityConfig.php';
        require_once $base . 'storage/DbSecurityStore.php';
        require_once $base . 'logger/SecurityLogger.php';
        require_once $base . 'whitelist/WhitelistChecker.php';
        require_once $base . 'reputation/IpReputationCache.php';
        require_once $base . 'engine/RiskScoreEngine.php';
        require_once $base . 'engine/BehavioralThreatDetector.php';
        require_once $base . 'engine/BurstDetectionAlgorithm.php';
        require_once $base . 'engine/DistributedPatternDetector.php';
        require_once $base . 'ratelimit/ClientRequestGuard.php';
        require_once $base . 'ratelimit/ContextualRateLimiter.php';
        require_once $base . 'mitigation/AdaptiveSlowdown.php';
        require_once $base . 'mitigation/BanManager.php';
        require_once $base . 'mitigation/QuarantineManager.php';
        require_once $base . 'mitigation/ChallengeManager.php';
        require_once $base . 'mitigation/SecurityBlockResponder.php';
        require_once $base . 'mitigation/ProgressivePenaltySystem.php';
        require_once $base . 'GlobalSecurityLayer.php';
        require_once __DIR__ . '/../components/SuspiciousActivityModal.php';
    }
}
