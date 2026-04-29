<?php
declare(strict_types=1);

namespace Controllers\V2;

use Services\V2\ExhibitionService;
use ResponseUtil; // Importa classe global para dentro do namespace Controllers\V2

class ExhibitionController {
    private ExhibitionService $service;

    public function __construct(ExhibitionService $service) {
        $this->service = $service;
    }

    public function getExhibitionData(): void {
        $tmdbId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
        $type = htmlspecialchars(filter_input(INPUT_GET, 'type', FILTER_DEFAULT) ?? 'movie', ENT_QUOTES, 'UTF-8');
        $season = filter_input(INPUT_GET, 's', FILTER_VALIDATE_INT) ?: 0;
        $episode = filter_input(INPUT_GET, 'e', FILTER_VALIDATE_INT) ?: 0;

        if ($tmdbId <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'ID do TMDB inválido ou ausente.'], 400);
            return;
        }

        if (($type === 'series' || $type === 'tv' || $type === 'serie') && ($season <= 0 || $episode <= 0)) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Para séries, informe temporada (s) e episódio (e).'], 400);
            return;
        }

        $data = $this->service->processExhibitionRequest($tmdbId, $type, $season, $episode);

        if ($data === null) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Conteúdo não encontrado na base de dados.'], 404);
            return;
        }

        ResponseUtil::json(['sucesso' => true, 'mensagem' => 'Dados recolhidos com sucesso.', 'dados' => $data]);
    }
}