<?php
class ContentService {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function fetchCategory($category, $limit, bool $isKids = false) {
        $localData = $this->model->getByCategory($category, $limit, $isKids);
        if (empty($localData)) return [];

        $tmdbData = TMDBHelper::getRichData($localData);
        $result = [];

        foreach ($localData as $index => $item) {
            $t = $tmdbData[$index] ?? [];
            $certification = 'L';

            if ($item['tipo'] === 'filme' && isset($t['release_dates']['results'])) {
                foreach ($t['release_dates']['results'] as $rd) {
                    if ($rd['iso_3166_1'] === 'BR' && !empty($rd['release_dates'][0]['certification'])) {
                        $certification = $rd['release_dates'][0]['certification'];
                        break;
                    }
                }
            } elseif ($item['tipo'] === 'serie' && isset($t['content_ratings']['results'])) {
                foreach ($t['content_ratings']['results'] as $cr) {
                    if ($cr['iso_3166_1'] === 'BR') {
                        $certification = $cr['rating'];
                        break;
                    }
                }
            }

            $atores = [];
            if (isset($t['credits']['cast'])) {
                foreach (array_slice($t['credits']['cast'], 0, 10) as $actor) {
                    if (!empty($actor['profile_path'])) {
                        $atores[] = [
                            'nome' => $actor['name'],
                            'personagem' => $actor['character'],
                            'imagem' => 'https://image.tmdb.org/t/p/w200' . $actor['profile_path']
                        ];
                    }
                }
            }

            $logo = null;
            if (isset($t['images']['logos'])) {
                foreach ($t['images']['logos'] as $l) {
                    if ($l['iso_639_1'] === 'pt' || $l['iso_639_1'] === 'en') {
                        $logo = 'https://image.tmdb.org/t/p/w500' . $l['file_path'];
                        break;
                    }
                }
                if (!$logo && !empty($t['images']['logos'][0]['file_path'])) {
                    $logo = 'https://image.tmdb.org/t/p/w500' . $t['images']['logos'][0]['file_path'];
                }
            }

            $backdrops = [];
            if (isset($t['images']['backdrops'])) {
                foreach (array_slice($t['images']['backdrops'], 0, 5) as $b) {
                    $backdrops[] = 'https://image.tmdb.org/t/p/w780' . $b['file_path'];
                }
            }

            $generos = [];
            if (isset($t['genres'])) {
                foreach ($t['genres'] as $g) {
                    $generos[] = $g['name'];
                }
            }

            $result[] = [
                'id' => $item['id'],
                'id_tmdb' => $item['id_tmdb'],
                'tipo' => $item['tipo'],
                'titulo' => $item['titulo'],
                'sinopse' => $t['overview'] ?? '',
                'ano' => !empty($item['data_lancamento']) ? substr($item['data_lancamento'], 0, 4) : '',
                'classificacao' => $certification,
                'generos' => $generos,
                'atores' => $atores,
                'capa' => $item['poster'],
                'backdrop' => $item['capa'],
                'logo' => $logo,
                'galeria' => $backdrops,
                'nota' => $t['vote_average'] ?? $item['nota']
            ];
        }

        return $result;
    }
}