<?php

class ContentService {
    private $model;
    private ?RecommendationService $recommendations;

    private const KIDS_ALLOWED_CERTIFICATIONS = ['L', '10', '12'];
    private const KIDS_ALLOWED_GENRES = ['animacao', 'familia', 'comedia', 'aventura', 'fantasia', 'musica'];
    private const KIDS_BLOCKED_GENRES = ['terror', 'suspense', 'crime', 'guerra', 'misterio', 'drama'];

    public function __construct($model, ?RecommendationService $recommendations = null) {
        $this->model = $model;
        $this->recommendations = $recommendations;
    }

    public function fetchCategory($category, $limit, bool $isKids = false) {
        $localData = $this->model->getByCategory($category, $limit, $isKids);
        if (empty($localData)) return [];

        if ($this->recommendations) {
            $localData = $this->recommendations->rerankCandidates($localData, [
                'route' => 'conteudo',
                'category' => (string) $category,
                'limit' => (int) $limit,
            ]);
        }

        $hydrateLimit = $isKids ? max((int)$limit * 4, (int)$limit) : max((int)$limit * 2, (int)$limit);
        $localData = array_slice($localData, 0, min(count($localData), $hydrateLimit));

        $tmdbData = TMDBHelper::getRichData($localData);
        $result = [];

        foreach ($localData as $index => $item) {
            if (count($result) >= $limit) break;

            $t = $tmdbData[$index] ?? [];
            $certification = $this->extractCertification($item, $t);

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
                    if (($l['iso_639_1'] ?? '') === 'pt' || ($l['iso_639_1'] ?? '') === 'en') {
                        $logo = 'https://image.tmdb.org/t/p/w500' . $l['file_path'];
                        break;
                    }
                }
                if (!$logo && !empty($t['images']['logos'][0]['file_path'])) {
                    $logo = 'https://image.tmdb.org/t/p/w500' . $t['images']['logos'][0]['file_path'];
                }
            }

            $poster = !empty($t['poster_path'])
                ? 'https://image.tmdb.org/t/p/w500' . $t['poster_path']
                : null;

            $backdrop = !empty($t['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/original' . $t['backdrop_path']
                : null;

            $backdrops = [];
            if (isset($t['images']['backdrops'])) {
                foreach (array_slice($t['images']['backdrops'], 0, 5) as $b) {
                    if (!empty($b['file_path'])) {
                        $backdrops[] = 'https://image.tmdb.org/t/p/w780' . $b['file_path'];
                    }
                }
            }

            $generos = [];
            if (isset($t['genres'])) {
                foreach ($t['genres'] as $g) {
                    $generos[] = $g['name'];
                }
            }

            if ($isKids && !$this->isKidsSafe($certification, $generos)) {
                continue;
            }

            $result[] = [
                'id' => $item['id'],
                'id_tmdb' => $item['id_tmdb'],
                'tipo' => $item['tipo'],
                'titulo' => $item['titulo'],
                'sinopse' => $t['overview'] ?? '',
                'ano' => !empty($item['data_lancamento']) ? substr($item['data_lancamento'], 0, 4) : '',
                'classificacao' => $certification ?? 'L',
                'generos' => $generos,
                'atores' => $atores,
                'poster' => $poster,
                'capa' => $poster,
                'backdrop' => $backdrop,
                'logo' => $logo,
                'galeria' => $backdrops,
                'nota' => $t['vote_average'] ?? $item['nota']
            ];
        }

        return $result;
    }

    private function extractCertification(array $item, array $tmdb): ?string {
        if ($item['tipo'] === 'filme' && isset($tmdb['release_dates']['results'])) {
            foreach ($tmdb['release_dates']['results'] as $rd) {
                if (($rd['iso_3166_1'] ?? '') === 'BR' && !empty($rd['release_dates'][0]['certification'])) {
                    return trim((string)$rd['release_dates'][0]['certification']);
                }
            }
        }

        if ($item['tipo'] === 'serie' && isset($tmdb['content_ratings']['results'])) {
            foreach ($tmdb['content_ratings']['results'] as $cr) {
                if (($cr['iso_3166_1'] ?? '') === 'BR' && !empty($cr['rating'])) {
                    return trim((string)$cr['rating']);
                }
            }
        }

        return null;
    }

    private function isKidsSafe(?string $certification, array $genres): bool {
        $cert = strtoupper(trim((string)$certification));
        if ($cert === '') {
            return false;
        }

        $cert = preg_replace('/[^0-9A-Z]/', '', $cert);
        if (!in_array($cert, self::KIDS_ALLOWED_CERTIFICATIONS, true)) {
            return false;
        }

        $hasAllowedGenre = false;
        foreach ($genres as $genre) {
            $genre = $this->normalizeText((string)$genre);
            foreach (self::KIDS_BLOCKED_GENRES as $blocked) {
                if (str_contains($genre, $blocked)) {
                    return false;
                }
            }
            foreach (self::KIDS_ALLOWED_GENRES as $allowed) {
                if (str_contains($genre, $allowed)) {
                    $hasAllowedGenre = true;
                }
            }
        }

        return $hasAllowedGenre;
    }

    private function normalizeText(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
    }
}
