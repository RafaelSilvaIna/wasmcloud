<?php

declare(strict_types=1);

namespace Security\Whitelist;

use Security\Config\SecurityConfig;
use Security\Storage\DbSecurityStore;

/**
 * WhitelistChecker — Verifica se a requisição está na whitelist global.
 *
 * Ordem de verificação:
 *   1. IP exato (sec_whitelist entry_type = 'ip')
 *   2. Rede CIDR (entry_type = 'ip_network')
 *   3. ASN (entry_type = 'asn') — comparado com header X-ASN se disponível
 *   4. User-Agent prefix (entry_type = 'user_agent_prefix')
 *
 * Usa APCu como cache de primeiro nível. Fallback ao banco sem APCu.
 */
final class WhitelistChecker
{
    private const CACHE_KEY = 'sec_whitelist_v1';

    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Retorna true se a origem da requisição está na whitelist.
     */
    public function isWhitelisted(string $ip, string $userAgent = '', string $asn = ''): bool
    {
        $entries = $this->loadEntries();

        foreach ($entries as $entry) {
            if ($this->matchEntry($entry['entry_type'], $entry['entry_value'], $ip, $userAgent, $asn)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------

    private function matchEntry(
        string $type,
        string $value,
        string $ip,
        string $userAgent,
        string $asn
    ): bool {
        return match ($type) {
            'ip'               => $ip === $value,
            'ip_network'       => $this->ipInCidr($ip, $value),
            'asn'              => $asn !== '' && strtoupper($asn) === strtoupper($value),
            'user_agent_prefix'=> $userAgent !== ''
                                  && stripos($userAgent, $value) === 0,
            'cdn_range'        => $this->ipInCidr($ip, $value),
            default            => false,
        };
    }

    private function loadEntries(): array
    {
        // Tenta APCu
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch(self::CACHE_KEY, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }

        $entries = $this->store->getAllWhitelistEntries();

        if (function_exists('apcu_store')) {
            apcu_store(self::CACHE_KEY, $entries, SecurityConfig::CACHE_WHITELIST_TTL);
        }

        return $entries;
    }

    /**
     * Verifica se um IP (v4 ou v6) pertence a um bloco CIDR.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$network, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        $ipBin  = @inet_pton($ip);
        $netBin = @inet_pton($network);

        if ($ipBin === false || $netBin === false) {
            return false;
        }

        // IPv4 e IPv6 têm tamanhos diferentes
        $len = strlen($ipBin);
        if ($len !== strlen($netBin)) {
            return false;
        }

        $bits    = $len * 8;
        $hostBits = $bits - $prefix;

        if ($hostBits < 0 || $hostBits > $bits) {
            return false;
        }

        // Máscara de rede em binário
        $mask = str_repeat("\xff", (int) ($prefix / 8));
        $rem  = $prefix % 8;
        if ($rem > 0) {
            $mask .= chr(0xff & (0xff << (8 - $rem)));
        }
        $mask = str_pad($mask, $len, "\x00");

        return ($ipBin & $mask) === ($netBin & $mask);
    }
}
