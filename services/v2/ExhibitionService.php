<?php
declare(strict_types=1);

namespace Services\V2;

use Models\V2\ExhibitionModel;
use Helpers\V2\TMDBHelper;

class ExhibitionService {
    private ExhibitionModel $model;
    private TMDBHelper $tmdbHelper;

    // Injetamos o Model e o TMDBHelper
    public function __construct(ExhibitionModel $model, TMDBHelper $tmdbHelper) {
        $this->model = $model;
        $this->tmdbHelper = $tmdbHelper;
    }

    public function processExhibitionRequest(int $tmdbId, string $type, int $season, int $episode): ?array {
        // 1. Verifica no banco de dados local
        $baseInfo = $this->model->getContentBaseInfo($tmdbId, $type);
        if (!$baseInfo) return null; 

        // 2. Busca URLs diretas no teu banco
        $links = $this->model->getRawVideoLinks($tmdbId, $type, $season, $episode);
        $mainVideoUrl = null;
        $videoQuality = null;
        $videoLanguage = null;

        if (!empty($links['dublado'])) {
            $mainVideoUrl = $links['dublado'][0]['url_video'];
            $videoQuality = $links['dublado'][0]['qualidade'];
            $videoLanguage = 'dublado';
        } elseif (!empty($links['legendado'])) {
            $mainVideoUrl = $links['legendado'][0]['url_video'];
            $videoQuality = $links['legendado'][0]['qualidade'];
            $videoLanguage = 'legendado';
        }

        // 3. Constrói o Payload Base
        $response = [
            'content_info' => [
                'tmdb_id' => $baseInfo['id_tmdb'],
                'title' => $baseInfo['titulo'],
                'overview' => $baseInfo['sinopse'], // Sinopse geral
                'poster' => $baseInfo['poster'],
                'type' => $baseInfo['tipo']
            ],
            'playback' => [
                'is_available' => !empty($mainVideoUrl),
                'message' => !empty($mainVideoUrl) ? 'URL pronta para exibição.' : 'URL indisponível no momento.',
                'raw_url' => $mainVideoUrl,
                'quality' => $videoQuality,
                'language' => $videoLanguage
            ]
        ];

        // 4. Se for série, busca os dados em TEMPO REAL no TMDB
        $isSerie = ($type === 'series' || $type === 'tv' || $type === 'serie');
        if ($isSerie) {
            // Busca o episódio atual em tempo real
            $epData = $this->tmdbHelper->getEpisodeMetadata($tmdbId, $season, $episode);
            
            // Busca a lista de temporadas em tempo real
            $seriesInfo = $this->tmdbHelper->getSeriesInfo($tmdbId);

            $response['episode_metadata'] = [
                'season' => $season,
                'episode' => $episode,
                'name' => $epData['name'] ?? "Episódio {$episode}",
                'overview' => $epData['overview'] ?? 'Sinopse indisponível.',
                'still_path' => isset($epData['still_path']) ? "https://image.tmdb.org/t/p/w780" . $epData['still_path'] : $baseInfo['capa'] // Usa a capa da série se o episódio não tiver
            ];

            $response['seasons_available'] = [];
            if (isset($seriesInfo['seasons'])) {
                foreach ($seriesInfo['seasons'] as $s) {
                    // Ignora episódios especiais (Temporada 0) para não poluir
                    if ($s['season_number'] > 0) {
                        $response['seasons_available'][] = [
                            'season_number' => $s['season_number'],
                            'name' => $s['name'],
                            'episode_count' => $s['episode_count']
                        ];
                    }
                }
            }
        }

        return $response;
    }
}