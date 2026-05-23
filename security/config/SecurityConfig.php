<?php

declare(strict_types=1);

namespace Security\Config;

/**
 * SecurityConfig — Constantes e thresholds da Global Security Layer.
 *
 * Todos os valores são configuráveis via constantes PHP.
 * Em produção, sobrescreva via um arquivo de configuração local antes do bootstrap.
 */
final class SecurityConfig
{
    // =========================================================================
    // SCORES DE AMEAÇA — Thresholds por nível de mitigação
    // =========================================================================

    /** Score mínimo para iniciar rate limiting adaptativo (nível 2) */
    public const SCORE_RATE_LIMIT    = 200;

    /** Score mínimo para aplicar delay artificial (nível 3) */
    public const SCORE_DELAY         = 650;

    /** Score mínimo para bloqueio temporário automático (nível 4) */
    public const SCORE_BLOCK         = 900;

    /** Score mínimo para quarentena agressiva (nível 5) */
    public const SCORE_QUARANTINE    = 980;

    /** Score máximo registrado */
    public const SCORE_MAX           = 1000;

    // =========================================================================
    // RATE LIMITING GLOBAL (req/min) — Fallback quando rota não está em sec_route_risk_profile
    // =========================================================================

    public const GLOBAL_RATE_CLEAN       = 600;
    public const GLOBAL_RATE_SUSPICIOUS  = 240;
    public const GLOBAL_RATE_HOSTILE     = 90;

    public const RATE_LIMIT_HARD_BLOCK_MULTIPLIER = 4.0;
    public const BURST_HARD_BLOCK_MULTIPLIER = 3.0;
    public const CLEAN_COUNTER_PERSIST_INTERVAL_SECONDS = 30;

    // =========================================================================
    // THRESHOLDS DE BURST
    // =========================================================================

    /** Requisições por segundo que configuram um burst global */
    public const BURST_RPS_GLOBAL    = 25.0;

    /** Conexões concorrentes máximas antes de classificar como abusivo */
    public const MAX_CONCURRENT      = 100;

    // =========================================================================
    // DELAYS ADAPTATIVOS (em microssegundos)
    // =========================================================================

    /** Delay para score 250–499 (500ms) */
    public const DELAY_LEVEL_3_US   = 75_000;

    /** Delay para score 500–749 (1.5s) */
    public const DELAY_LEVEL_4_US   = 200_000;

    /** Delay máximo em quarentena (3s) */
    public const DELAY_QUARANTINE_US = 500_000;

    // =========================================================================
    // PONTUAÇÃO POR EVENTO
    // =========================================================================

    public const SCORE_DELTA = [
        'rate_limit_exceeded'         => 8,
        'burst_detected'              => 12,
        'bot_pattern_detected'        => 15,
        'scraper_detected'            => 15,
        'scanner_detected'            => 20,
        'auth_flooding'               => 12,
        'invalid_user_agent'          => 5,
        'route_flooding'              => 15,
        'parallel_connection_abuse'   => 20,
        'replay_attack'               => 40,
        'challenge_failed'            => 8,
        'stream_abuse'                => 8,
        'search_abuse'                => 8,
        'anomaly_detected'            => 5,
        'distributed_pattern_detected'=> 40,
    ];

    // =========================================================================
    // DURAÇÃO DOS BANIMENTOS (segundos)
    // =========================================================================

    /** Soft ban: 15 minutos */
    public const BAN_SOFT_SECONDS   = 300;

    /** Hard ban: 24 horas */
    public const BAN_HARD_SECONDS   = 3_600;

    /** Shadow ban: 7 dias */
    public const BAN_SHADOW_SECONDS = 86_400;

    /** Quarentena: 1 hora */
    public const QUARANTINE_SECONDS = 600;

    // =========================================================================
    // CACHE (APCu TTL em segundos)
    // =========================================================================

    /** TTL do cache de whitelist */
    public const CACHE_WHITELIST_TTL     = 300;

    /** TTL do cache de reputação de IP */
    public const CACHE_REPUTATION_TTL   = 60;

    /** TTL do cache de rota de risco */
    public const CACHE_ROUTE_PROFILE_TTL = 600;

    /** TTL do cache de banimentos */
    public const CACHE_BAN_TTL          = 30;

    // =========================================================================
    // HEURÍSTICAS COMPORTAMENTAIS
    // =========================================================================

    /** User-agents que indicam bots conhecidos (prefixos) */
    public const BOT_UA_PREFIXES = [
        'python-requests',
        'go-http-client',
        'java/',
        'apache-httpclient',
        'libwww-perl',
        'curl/',
        'wget/',
        'scrapy/',
        'bot/',
        'crawl',
        'spider',
        'httpclient',
        'node-fetch',
        'got/',
        'mechanize',
    ];

    /** User-agents absolutamente inválidos (string vazia ou somente whitespace) */
    public const INVALID_UA_SCORE_DELTA = 30;

    /** Número máximo de rotas distintas por hora para ser classificado como scraper */
    public const SCRAPER_UNIQUE_ROUTES_PER_HOUR = 250;

    /** Máximo de tentativas de auth por minuto antes de classificar como auth flooding */
    public const AUTH_FLOOD_THRESHOLD = 60;

    public const CRITICAL_ROUTE_GROUPS = ['auth', 'recovery'];
    public const HIGH_TRAFFIC_ROUTE_GROUPS = ['stream', 'cdn', 'catalog', 'search', 'admin'];

    // =========================================================================
    // CHALLENGE
    // =========================================================================

    /** TTL do challenge emitido (segundos) */
    public const CHALLENGE_TTL_SECONDS  = 300;

    /** Máximo de tentativas de resolver um challenge */
    public const CHALLENGE_MAX_ATTEMPTS = 3;

    // =========================================================================
    // MODO FAIL-OPEN / FAIL-CLOSED
    // =========================================================================

    /**
     * Se true: se a camada de segurança lançar exceção, a requisição passa normalmente.
     * Se false: se a camada falhar, retorna 503.
     *
     * Recomendado: true em produção para evitar falso bloqueio.
     */
    public const FAIL_OPEN = true;

    // =========================================================================
    // GRUPOS DE ROTAS (prefixos para classificação)
    // =========================================================================

    public const ROUTE_GROUPS = [
        'auth'     => ['/api/auth/', '/api/v4/auth/', '/api/v4/qr-login/', '/auth/', '/login'],
        'stream'   => ['/player', '/api/v2/stream', '/api/v2/exhibition', '/api/v2/episode-url', '/cdn/video/', '/cdn/audio/'],
        'search'   => ['/busca', '/api/v2/busca'],
        'catalog'  => ['/api/v2/conteudo', '/api/v2/trending', '/api/v2/plataforma', '/api/v2/info'],
        'api_v4'   => ['/api/v4/'],
        'api_v3'   => ['/api/v3/'],
        'api_v2'   => ['/api/v2/'],
        'admin'    => ['/admin/', '/api/admin/', '/d2xs8d3sdfsegequ6249f'],
        'support'  => ['/suporte', '/api/suporte'],
        'profiles' => ['/api/profiles/'],
        'recovery' => ['/recuperar'],
        'devices'  => ['/api/devices/'],
        'cdn'      => ['/cdn/'],
    ];

    public static function secret(): string
    {
        $env = getenv('PIPOCINE_SECURITY_SECRET');
        if (is_string($env) && strlen($env) >= 32) {
            return $env;
        }

        return hash('sha256', __DIR__ . '|pipocine-security-v2');
    }
}
