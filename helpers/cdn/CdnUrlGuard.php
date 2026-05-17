<?php

declare(strict_types=1);

namespace Helpers\Cdn;

final class CdnUrlGuard
{
    public static function assertAllowedExternalUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new \RuntimeException('Fonte de midia invalida.');
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new \RuntimeException('Fonte local bloqueada.');
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (!$records) {
            $records = [['ip' => gethostbyname($host)]];
        }

        foreach ($records as $record) {
            $ip = (string) ($record['ip'] ?? $record['ipv6'] ?? '');
            if ($ip !== '' && self::isPrivateIp($ip)) {
                throw new \RuntimeException('Fonte em rede privada bloqueada.');
            }
        }
    }

    private static function isPrivateIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
