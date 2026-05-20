<?php

/**
 * TrendingService
 * Enriquece os dados do banco com informacoes do TMDB e aplica verificacao de qualidade.
 */
class TrendingService {
    private $model;

    private const KIDS_ALLOWED_CERTIFICATIONS = ['L', '10', '12'];
    private const KIDS_ALLOWED_GENRES = ['animacao', 'familia', 'comedia', 'aventura', 'fantasia', 'musica'];
    private const KIDS_BLOCKED_GENRES = ['terror', 'suspense', 'crime', 'guerra', 'misterio', 'drama'];

    public function __construct($model) {
        $this->model = $model;
    }

    public function fetchTrending(int $limit = 12, bool $isKids = false, ?string $tipo = null): array {
        $rawItems = $this->model->getTrending($limit, $isKids, $tipo);
        if (empty($rawItems)) return [];

        $tmdbData = TMDBHelper::getRichData($rawItems);
        $result = [];

        foreach ($rawItems as $index => $item) {
            if (count($result) >= $limit) break;

            $t = $tmdbData[$index] ?? [];

            $sinopse = trim($t['overview'] ?? '');
            if (strlen($sinopse) < 30) continue;

            $logo = null;
            if (isset($t['images']['logos'])) {
                foreach ($t['images']['logos'] as $l) {
                    if (!empty($l['file_path']) && in_array($l['iso_639_1'], ['pt', 'en'], true)) {
                        $logo = 'https://image.tmdb.org/t/p/w500' . $l['file_path'];
                        break;
                    }
                }
                if (!$logo) {
                    foreach ($t['images']['logos'] as $l) {
                        if (!empty($l['file_path'])) {
                            $logo = 'https://image.tmdb.org/t/p/w500' . $l['file_path'];
                            break;
                        }
                    }
                }
            }
            if (!$logo) continue;

            $backdrops = [];
            if (isset($t['images']['backdrops'])) {
                $sorted = $t['images']['backdrops'];
                usort($sorted, fn($a, $b) => (empty($a['iso_639_1']) ? -1 : 1) - (empty($b['iso_639_1']) ? -1 : 1));
                foreach (array_slice($sorted, 0, 6) as $b) {
                    if (!empty($b['file_path'])) {
                        $backdrops[] = 'https://image.tmdb.org/t/p/original' . $b['file_path'];
                    }
                }
            }
            if (empty($backdrops)) continue;

            $poster = !empty($item['poster']) ? $item['poster'] : null;
            $certification = $this->extractCertification($item, $t);

            $atores = [];
            if (isset($t['credits']['cast'])) {
                foreach (array_slice($t['credits']['cast'], 0, 8) as $actor) {
                    if (!empty($actor['profile_path'])) {
                        $atores[] = [
                            'nome' => $actor['name'],
                            'personagem' => $actor['character'],
                            'imagem' => 'https://image.tmdb.org/t/p/w200' . $actor['profile_path']
                        ];
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

            $duracao = null;
            if ($item['tipo'] === 'filme') {
                $duracao = !empty($t['runtime']) ? (int)$t['runtime'] . ' min' : null;
            } else {
                $seasons = $t['number_of_seasons'] ?? null;
                $duracao = $seasons ? $seasons . ' temporada' . ($seasons > 1 ? 's' : '') : null;
            }

            $result[] = [
                'id' => $item['id'],
                'id_tmdb' => $item['id_tmdb'],
                'tipo' => $item['tipo'],
                'titulo' => $item['titulo'],
                'sinopse' => $sinopse,
                'ano' => !empty($item['data_lancamento']) ? substr($item['data_lancamento'], 0, 4) : '',
                'nota' => round((float)($t['vote_average'] ?? $item['nota']), 1),
                'classificacao' => $certification ?? 'L',
                'generos' => $generos,
                'atores' => $atores,
                'capa' => $poster,
                'backdrop' => $backdrops[0],
                'galeria' => $backdrops,
                'logo' => $logo,
                'duracao' => $duracao,
                'popularidade' => round((float)($t['popularity'] ?? 0), 1),
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
