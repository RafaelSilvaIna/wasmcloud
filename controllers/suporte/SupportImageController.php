<?php

declare(strict_types=1);

namespace Controllers\Suporte;

use Services\Suporte\SupportImageService;

final class SupportImageController
{
    public function __construct(private SupportImageService $imageService) {}

    /** GET /api/suporte/image/{token} */
    public function serve(string $token): void
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            http_response_code(400);
            echo 'Token invalido.';
            return;
        }

        if (!$this->imageService->serve($token)) {
            http_response_code(404);
            echo 'Imagem nao encontrada ou expirada.';
        }
    }
}
