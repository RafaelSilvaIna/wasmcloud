<?php
declare(strict_types=1);

namespace Controllers\V2;

use Services\V2\InfoService;
use ResponseUtil;

/**
 * InfoController — expõe o endpoint GET /api/v2/info?id={tmdb_id}
 */
class InfoController {
    private InfoService $service;

    public function __construct(InfoService $service) {
        $this->service = $service;
    }

    public function handle(): void {
        $tmdbId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $tipo = $_GET['type'] ?? $_GET['tipo'] ?? null;
        if ($tipo === 'movie') $tipo = 'filme';
        if ($tipo === 'tv') $tipo = 'serie';
        if (!in_array($tipo, ['filme', 'serie'], true)) {
            $tipo = null;
        }

        if (!$tmdbId || $tmdbId <= 0) {
            ResponseUtil::json([
                'sucesso' => false,
                'erro'    => 'Parâmetro "id" inválido ou ausente. Informe um ID TMDB válido.',
            ], 400);
            return;
        }

        $payload = $this->service->getFullDetails($tmdbId, $tipo);

        if ($payload === null) {
            ResponseUtil::json([
                'sucesso' => false,
                'erro'    => 'Conteúdo não encontrado na base de dados.',
            ], 404);
            return;
        }

        ResponseUtil::json($payload);
    }
}
