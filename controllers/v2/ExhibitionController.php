<?php
declare(strict_types=1);

namespace Controllers\V2;

use Services\V2\ExhibitionService;
use Utils\V2\ResponseUtil;

/**
 * Controlador que lida com as requisições HTTP para a página de exibição/player.
 */
class ExhibitionController {
    private ExhibitionService $service;

    public function __construct(ExhibitionService $service) {
        $this->service = $service;
    }

    /**
     * Endpoint principal para obter dados e URL de um conteúdo.
     */
    public function getExhibitionData(): void {
        // Captura e sanitiza os parâmetros de entrada
        $tmdbId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?: 'movie';
        $season = filter_input(INPUT_GET, 's', FILTER_VALIDATE_INT) ?: 0;
        $episode = filter_input(INPUT_GET, 'e', FILTER_VALIDATE_INT) ?: 0;

        // Validação básica
        if ($tmdbId <= 0) {
            ResponseUtil::error('ID do TMDB inválido ou ausente.', 400);
            return;
        }

        if (($type === 'series' || $type === 'tv') && ($season <= 0 || $episode <= 0)) {
            ResponseUtil::error('Para séries, é obrigatório informar a temporada (s) e o episódio (e).', 400);
            return;
        }

        // Processa a requisição via Service
        $data = $this->service->processExhibitionRequest($tmdbId, $type, $season, $episode);

        if ($data === null) {
            ResponseUtil::error('Conteúdo não encontrado na base de dados.', 404);
            return;
        }

        // Resposta de sucesso
        ResponseUtil::success('Dados recolhidos com sucesso.', $data);
    }
}