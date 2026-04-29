<?php
declare(strict_types=1);

namespace Services\V2;

use Models\V2\ExhibitionModel;
use TMDBHelper; // CORREÇÃO: Diz ao PHP para usar o TMDBHelper global!

/**
 * Serviço que processa a lógica de entrega do conteúdo
 */
class ExhibitionService {
    private ExhibitionModel $model;
    private TMDBHelper $tmdbHelper;

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

        // 3. Busca logo no TMDB (PT-BR com fallback EN)
        $logo = null;
        $imagesData = $this->tmdbHelper->getContentImages($tmdbId, $type);
        if (isset($imagesData['logos']) && !empty($imagesData['logos'])) {
            // Prioridade: pt → en → primeiro disponível
            foreach ($imagesData['logos'] as $l) {
                if (($l['iso_639_1'] ?? '') === 'pt') { $logo = $l['file_path']; break; }
            }
            if (!$logo) {
                foreach ($imagesData['logos'] as $l) {
                    if (($l['iso_639_1'] ?? '') === 'en') { $logo = $l['file_path']; break; }
                }
            }
            if (!$logo) $logo = $imagesData['logos'][0]['file_path'] ?? null;
            if ($logo)  $logo = 'https://image.tmdb.org/t/p/w500' . $logo;
        }

        // 4. Constrói o Payload Base
        $response = [
            'content_info' => [
                'tmdb_id'  => $baseInfo['id_tmdb'],
                'title'    => $baseInfo['titulo'],
                'logo'     => $logo,
                'overview' => $baseInfo['sinopse'],
                'poster'   => $baseInfo['poster'],
                'backdrop' => $baseInfo['capa'],
                'type'     => $baseInfo['tipo']
            ],
            'playback' => [
                'is_available' => !empty($mainVideoUrl),
                'message'      => !empty($mainVideoUrl) ? 'URL pronta para exibição.' : 'URL indisponível no momento.',
                'raw_url'      => $mainVideoUrl,
                'quality'      => $videoQuality,
                'language'     => $videoLanguage
            ]
        ];

        // 4. Se for série, busca os dados em TEMPO REAL no TMDB
        $isSerie = ($type === 'series' || $type === 'tv' || $type === 'serie');
        if ($isSerie) {
            $seasonData  = $this->tmdbHelper->getSeasonEpisodes($tmdbId, $season);
            $seriesInfo  = $this->tmdbHelper->getSeriesInfo($tmdbId);

            // Todos os episódios da temporada
            $response['episodes'] = [];
            if (isset($seasonData['episodes'])) {
                foreach ($seasonData['episodes'] as $ep) {
                    $response['episodes'][] = [
                        'season'      => $season,
                        'episode'     => $ep['episode_number'],
                        'name'        => $ep['name'] ?? "Episódio {$ep['episode_number']}",
                        'overview'    => $ep['overview'] ?? 'Sinopse indisponível.',
                        'still_path'  => !empty($ep['still_path'])
                                            ? "https://image.tmdb.org/t/p/w780" . $ep['still_path']
                                            : $baseInfo['capa']
                    ];
                }
            }

            // Episódio atual (para destacar no player)
            $response['current_episode'] = $episode;

            // Temporadas disponíveis
            $response['seasons_available'] = [];
            if (isset($seriesInfo['seasons'])) {
                foreach ($seriesInfo['seasons'] as $s) {
                    if ($s['season_number'] > 0) {
                        $response['seasons_available'][] = [
                            'season_number' => $s['season_number'],
                            'name'          => $s['name'],
                            'episode_count' => $s['episode_count']
                        ];
                    }
                }
            }
        }

        return $response;
    }
}