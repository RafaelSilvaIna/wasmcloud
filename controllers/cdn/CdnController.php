<?php

declare(strict_types=1);

namespace Controllers\Cdn;

use Helpers\Cdn\CdnHeaders;
use Middleware\Cdn\CdnSecurityMiddleware;
use Services\Cdn\CdnProxyStreamService;
use Services\Cdn\CdnSourceResolver;
use Services\Cdn\CdnStreamService;

require_once __DIR__ . '/../../helpers/cdn/CdnHeaders.php';
require_once __DIR__ . '/../../middleware/cdn/CdnSecurityMiddleware.php';
require_once __DIR__ . '/../../services/cdn/CdnProxyStreamService.php';
require_once __DIR__ . '/../../services/cdn/CdnSourceResolver.php';
require_once __DIR__ . '/../../services/cdn/CdnStreamService.php';

final class CdnController
{
    public function __construct(
        private CdnSecurityMiddleware $security,
        private CdnSourceResolver $resolver,
        private CdnStreamService $streamer,
        private ?CdnProxyStreamService $proxyStreamer = null
    ) {
        $this->proxyStreamer ??= new CdnProxyStreamService();
    }

    public function videoCdn(?string $token): void
    {
        $token = trim((string) $token);
        if ($token === '') {
            CdnHeaders::noStore();
            http_response_code(400);
            echo 'Token da CDN ausente.';
            return;
        }

        $claims = $this->security->authorize($token, 'video');
        if (!$claims) return;

        try {
            $source = $this->resolver->resolve($claims);
            $this->proxyStreamer->stream($source);
        } catch (\Throwable $ex) {
            error_log('[CDN proxy video] ' . $ex->getMessage());
            CdnHeaders::noStore();
            http_response_code(424);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Fonte de video indisponivel no proxy CDN.';
        }
    }

    public function video(string $token): void
    {
        $claims = $this->security->authorize($token, 'video');
        if (!$claims) return;

        try {
            $source = $this->resolver->resolve($claims);
            $this->streamer->streamVideoOnly($source['url'], $source['origin'] ?? null);
        } catch (\Throwable $ex) {
            error_log('[CDN video] ' . $ex->getMessage());
            CdnHeaders::noStore();
            http_response_code(502);
            echo 'Falha ao preparar video.';
        }
    }

    public function audio(string $profile, string $token): void
    {
        $profile = in_array($profile, ['standard', 'smart_eq', 'virtual_surround', 'safe_boost'], true) ? $profile : 'standard';
        $claims = $this->security->authorize($token, 'audio', $profile);
        if (!$claims) return;

        try {
            $source = $this->resolver->resolve($claims);
            $this->streamer->streamAudioOnly($source['url'], $profile, $source['origin'] ?? null);
        } catch (\Throwable $ex) {
            error_log('[CDN audio] ' . $ex->getMessage());
            CdnHeaders::noStore();
            http_response_code(502);
            echo 'Falha ao preparar audio.';
        }
    }
}
