<?php
declare(strict_types=1);

namespace Controllers\Cdn;

use Services\Ads\AdsCreativeCdnService;

final class AdsCdnController
{
    public function __construct(private readonly AdsCreativeCdnService $service) {}

    public function creative(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            http_response_code(405);
            return;
        }

        try {
            if (!$this->service->serve($token)) {
                http_response_code(404);
                echo 'Criativo não encontrado.';
            }
        } catch (\Throwable $e) {
            error_log('[Ads CDN] ' . $e->getMessage());
            http_response_code(502);
            echo 'Falha ao preparar criativo.';
        }
    }
}
