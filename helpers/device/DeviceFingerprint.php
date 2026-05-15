<?php

declare(strict_types=1);

namespace Helpers\Device;

/**
 * DeviceFingerprint
 *
 * Gera um identificador robusto de dispositivo combinando múltiplas camadas:
 *   1. Cookie persistente (gerado na 1ª visita, válido por 1 ano)
 *   2. IP parcial  (primeiros 3 octetos — preserva privacidade)
 *   3. User-Agent  (hashed)
 *   4. Accept-Language header
 *   5. Plataforma extraída do UA (Windows / macOS / Android / iOS / Linux / TV)
 *
 * O device_id final é SHA-256 da concatenação dessas camadas, garantindo
 * que colisões acidentais sejam extremamente improváveis sem depender de
 * um único vetor que possa ser facilmente falsificado.
 *
 * NOTA: O cookie é HttpOnly e SameSite=Lax; não armazena dados sensíveis.
 */
final class DeviceFingerprint
{
    private const COOKIE_NAME   = '_pdid';
    private const COOKIE_TTL    = 31_536_000; // 1 ano em segundos
    private const COOKIE_DOMAIN = '';          // domínio atual

    // ─────────────────────────────────────────────────────────────────────────
    // Ponto de entrada público
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna o device_id determinístico para o dispositivo atual.
     * Também persiste o cookie caso ainda não exista.
     */
    public static function resolve(): string
    {
        $cookieToken   = self::resolveCookieToken();
        $ipPartial     = self::partialIp();
        $uaHash        = self::uaHash();
        $langPrint     = self::langPrint();
        $platformToken = self::platformToken();

        // Combinação das camadas → device_id final
        return hash('sha256', implode('|', [
            $cookieToken,
            $ipPartial,
            $uaHash,
            $langPrint,
            $platformToken,
        ]));
    }

    /**
     * Retorna os primeiros 3 octetos do IP (ex: "187.22.100").
     * Funciona com IPv4 e IPv4-mapeado-em-IPv6.
     */
    public static function partialIp(): string
    {
        $ip = self::realIp();

        // IPv4-mapeado-em-IPv6 → converte para IPv4
        if (str_starts_with($ip, '::ffff:')) {
            $ip = substr($ip, 7);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3));
        }

        // IPv6 — usa os primeiros 4 grupos
        $groups = explode(':', $ip);
        return implode(':', array_slice($groups, 0, 4));
    }

    /**
     * SHA-256 do User-Agent — identifica navegador/versão/OS.
     */
    public static function uaHash(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ua);
    }

    /**
     * Rótulo legível do dispositivo (ex: "Chrome no Windows").
     * Usado apenas para exibição, não para fingerprint.
     */
    public static function deviceLabel(): string
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Browser
        $browser = match (true) {
            str_contains($ua, 'edg/')        => 'Edge',
            str_contains($ua, 'opr/')
                || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/')    => 'Firefox',
            str_contains($ua, 'safari/')
                && !str_contains($ua, 'chrome') => 'Safari',
            str_contains($ua, 'chrome/')     => 'Chrome',
            str_contains($ua, 'samsung')     => 'Samsung Browser',
            str_contains($ua, 'webos')       => 'WebOS Browser',
            str_contains($ua, 'tizen')       => 'Tizen Browser',
            default                          => 'Navegador',
        };

        // Plataforma
        $platform = match (true) {
            str_contains($ua, 'smart-tv')
                || str_contains($ua, 'googletv')
                || str_contains($ua, 'android tv')
                || str_contains($ua, 'webos')
                || str_contains($ua, 'tizen')
                || str_contains($ua, 'hbbtv')   => 'TV',
            str_contains($ua, 'ipad')            => 'iPad',
            str_contains($ua, 'iphone')          => 'iPhone',
            str_contains($ua, 'android')
                && str_contains($ua, 'mobile')  => 'Android',
            str_contains($ua, 'android')         => 'Tablet Android',
            str_contains($ua, 'macintosh')
                || str_contains($ua, 'mac os x') => 'Mac',
            str_contains($ua, 'windows')         => 'Windows',
            str_contains($ua, 'linux')           => 'Linux',
            default                              => 'Dispositivo',
        };

        return "{$browser} em {$platform}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve (ou cria) o token de cookie persistente.
     */
    private static function resolveCookieToken(): string
    {
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $token = $_COOKIE[self::COOKIE_NAME];
            // Valida formato: 64 hex chars
            if (preg_match('/^[0-9a-f]{64}$/', $token)) {
                return $token;
            }
        }

        // Gera novo token seguro
        $token = bin2hex(random_bytes(32));

        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $token, [
                'expires'  => time() + self::COOKIE_TTL,
                'path'     => '/',
                'domain'   => self::COOKIE_DOMAIN,
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        $_COOKIE[self::COOKIE_NAME] = $token;
        return $token;
    }

    /**
     * Extrai o Accept-Language para adicionar entropia ao fingerprint.
     */
    private static function langPrint(): string
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        // Pega apenas os primeiros 20 chars para consistência
        return substr(strtolower($lang), 0, 20);
    }

    /**
     * Token baseado na plataforma detectada via UA.
     */
    private static function platformToken(): string
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        return match (true) {
            str_contains($ua, 'windows')                 => 'win',
            str_contains($ua, 'macintosh')               => 'mac',
            str_contains($ua, 'ipad')                    => 'ipad',
            str_contains($ua, 'iphone')                  => 'iphone',
            str_contains($ua, 'android') && str_contains($ua, 'mobile') => 'android_mobile',
            str_contains($ua, 'android')                 => 'android_tablet',
            str_contains($ua, 'linux')                   => 'linux',
            str_contains($ua, 'smart-tv')
                || str_contains($ua, 'googletv')
                || str_contains($ua, 'hbbtv')
                || str_contains($ua, 'tizen')
                || str_contains($ua, 'webos')            => 'tv',
            default                                      => 'unknown',
        };
    }

    /**
     * Detecta o IP real considerando proxies comuns.
     */
    private static function realIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            $val = $_SERVER[$key] ?? '';
            if ($val === '') {
                continue;
            }

            // X-Forwarded-For pode ter lista; pega o primeiro
            $ip = trim(explode(',', $val)[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
