<?php
/**
 * TrendingService
 * Enriquece os dados do banco com informações do TMDB e aplica
 * verificação de qualidade: só retorna itens com TODOS os campos
 * essenciais preenchidos (logo, galeria, sinopse, poster).
 */
class TrendingService {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Retorna trending limpos e completos.
     *
     * @param  int    $limit   Quantidade final desejada
     * @param  bool   $isKids  Filtro de perfil infantil
     * @param  string $tipo    'filme' | 'serie' | null
     * @return array
     */
    public function fetchTrending(int $limit = 12, bool $isKids = false, ?string $tipo = null): array {
        $rawItems = $this->model->getTrending($limit, $isKids, $tipo);
        if (empty($rawItems)) return [];

        $tmdbData = TMDBHelper::getRichData($rawItems);
        $result   = [];

        foreach ($rawItems as $index => $item) {
            if (count($result) >= $limit) break;

            $t = $tmdbData[$index] ?? [];

            // ── 1. Sinopse (obrigatória e com comprimento mínimo) ──────────
            $sinopse = trim($t['overview'] ?? '');
            if (strlen($sinopse) < 30) continue; // descarta sinopse vazia ou muito curta

            // ── 2. Logo (obrigatória para o hero) ─────────────────────────
            $logo = null;
            if (isset($t['images']['logos'])) {
                // Prioridade: PT-BR → EN → qualquer
                foreach ($t['images']['logos'] as $l) {
                    if (!empty($l['file_path']) && in_array($l['iso_639_1'], ['pt', 'en'])) {
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
            if (!$logo) continue; // descarta sem logo

            // ── 3. Galeria / Backdrop (pelo menos 1 imagem) ────────────────
            $backdrops = [];
            if (isset($t['images']['backdrops'])) {
                // Prefere backdrops sem idioma (null) — mais limpos
                $sorted = $t['images']['backdrops'];
                usort($sorted, fn($a, $b) => (empty($a['iso_639_1']) ? -1 : 1) - (empty($b['iso_639_1']) ? -1 : 1));
                foreach (array_slice($sorted, 0, 6) as $b) {
                    if (!empty($b['file_path'])) {
                        $backdrops[] = 'https://image.tmdb.org/t/p/original' . $b['file_path'];
                    }
                }
            }
            if (empty($backdrops)) continue; // descarta sem imagem de fundo

            // ── 4. Poster ─────────────────────────────────────────────────
            $poster = !empty($item['poster']) ? $item['poster'] : null;

            // ── 5. Classificação indicativa ───────────────────────────────
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

            // ── 6. Elenco (apenas com foto) ───────────────────────────────
            $atores = [];
            if (isset($t['credits']['cast'])) {
                foreach (array_slice($t['credits']['cast'], 0, 8) as $actor) {
                    if (!empty($actor['profile_path'])) {
                        $atores[] = [
                            'nome'       => $actor['name'],
                            'personagem' => $actor['character'],
                            'imagem'     => 'https://image.tmdb.org/t/p/w200' . $actor['profile_path']
                        ];
                    }
                }
            }

            // ── 7. Gêneros ────────────────────────────────────────────────
            $generos = [];
            if (isset($t['genres'])) {
                foreach ($t['genres'] as $g) {
                    $generos[] = $g['name'];
                }
            }

            // ── 8. Duração / Temporadas ───────────────────────────────────
            $duracao = null;
            if ($item['tipo'] === 'filme') {
                $duracao = !empty($t['runtime']) ? (int)$t['runtime'] . ' min' : null;
            } else {
                $seasons = $t['number_of_seasons'] ?? null;
                $duracao = $seasons ? $seasons . ' temporada' . ($seasons > 1 ? 's' : '') : null;
            }

            $result[] = [
                'id'             => $item['id'],
                'id_tmdb'        => $item['id_tmdb'],
                'tipo'           => $item['tipo'],
                'titulo'         => $item['titulo'],
                'sinopse'        => $sinopse,
                'ano'            => !empty($item['data_lancamento']) ? substr($item['data_lancamento'], 0, 4) : '',
                'nota'           => round((float)($t['vote_average'] ?? $item['nota']), 1),
                'classificacao'  => $certification,
                'generos'        => $generos,
                'atores'         => $atores,
                'capa'           => $poster,
                'backdrop'       => $backdrops[0],
                'galeria'        => $backdrops,
                'logo'           => $logo,
                'duracao'        => $duracao,
                'popularidade'   => round((float)($t['popularity'] ?? 0), 1),
            ];
        }

        return $result;
    }
}
