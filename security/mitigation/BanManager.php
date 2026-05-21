<?php

declare(strict_types=1);

namespace Security\Mitigation;

use Security\Config\SecurityConfig;
use Security\Logger\SecurityLogger;
use Security\Storage\DbSecurityStore;

/**
 * BanManager — Gerencia banimentos ativos (soft / hard / shadow).
 *
 * - Soft ban  : bloqueia visivelmente com 429. Expira em 15 minutos.
 * - Hard ban  : bloqueia agressivamente com 403. Expira em 24 horas.
 * - Shadow ban: responde normalmente mas registra todas as ações e
 *               entrega conteúdo degradado sem avisar o cliente.
 *               Expira em 7 dias.
 *
 * Usa cache APCu para evitar consultas ao banco em cada requisição.
 */
final class BanManager
{
    private const CACHE_PREFIX = 'sec_ban_';

    public function __construct(
        private readonly DbSecurityStore $store,
        private readonly SecurityLogger  $logger
    ) {}

    /**
     * Verifica se o IP está banido.
     * Retorna null se livre, ou o registro do ban se ativo.
     */
    public function getActiveBan(string $ip): ?array
    {
        $cacheKey = self::CACHE_PREFIX . md5($ip);

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success) {
                return $cached === false ? null : $cached;
            }
        }

        $ban = $this->store->getActiveBan($ip);

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $ban ?? false, SecurityConfig::CACHE_BAN_TTL);
        }

        return $ban;
    }

    /**
     * Aplica um banimento ao IP com base no score atual.
     */
    public function applyAutomaticBan(string $ip, int $threatScore, string $reason = ''): string
    {
        $type     = $this->resolveBanType($threatScore);
        $duration = match ($type) {
            'hard'   => SecurityConfig::BAN_HARD_SECONDS,
            'shadow' => SecurityConfig::BAN_SHADOW_SECONDS,
            default  => SecurityConfig::BAN_SOFT_SECONDS,
        };

        $this->store->createBan($ip, $type, $reason ?: "Score automático: {$threatScore}", $duration, $threatScore);
        $this->invalidateCache($ip);
        $this->logger->block($ip, $type, $threatScore, $reason);

        $this->store->recordPenalty(
            $ip, 'soft_ban', 'high', 'auto_ban', $threatScore, $duration
        );

        return $type;
    }

    /**
     * Responde e encerra a requisição quando o IP está banido.
     * Shadow ban: continua silenciosamente.
     */
    public function enforceBan(array $ban): bool
    {
        if ($ban['ban_type'] === 'shadow') {
            // Shadow ban: deixa passar mas marca na sessão para degradação
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_sec_shadow_banned'] = true;
            }
            return false; // não encerra
        }

        $code    = $ban['ban_type'] === 'hard' ? 403 : 429;
        $message = $ban['ban_type'] === 'hard'
            ? 'Acesso permanentemente bloqueado.'
            : 'Muitas requisições. Tente novamente mais tarde.';

        $retry = null;
        if ($ban['expires_at']) {
            $retry = max(0, strtotime($ban['expires_at']) - time());
        }

        SecurityBlockResponder::block(
            (string) ($ban['ip_address'] ?? ''),
            $_SERVER['REQUEST_URI'] ?? '/',
            $code,
            $message,
            $retry ?? 5
        );
    }

    public function invalidateCache(string $ip): void
    {
        if (function_exists('apcu_delete')) {
            apcu_delete(self::CACHE_PREFIX . md5($ip));
        }
    }

    // -------------------------------------------------------------------------

    private function resolveBanType(int $score): string
    {
        if ($score >= SecurityConfig::SCORE_QUARANTINE) {
            return 'hard';
        }
        if ($score >= SecurityConfig::SCORE_BLOCK) {
            return 'soft';
        }
        return 'soft';
    }
}
