<?php
declare(strict_types=1);

namespace Services\V2;

use Models\V2\SearchModel;
use TMDBHelper;

/**
 * SearchService — orquestra busca local + enriquecimento via TMDB.
 *
 * Segue a mesma logica do InfoService/ContentService:
 *  1. Busca itens no banco Cineveo pelo titulo
 *  2. Enriquece em paralelo via cURL multi no TMDB (poster, backdrop, nota, generos)
 *  3. Verifica disponibilidade de links para cada item
 *  4. Retorna payload normalizado para o frontend
 */
class SearchService {
    private SearchModel $model;
    private TMDBHelper  $tmdb;

    private const IMG_W500     = 'https://image.tmdb.org/t/p/w500';
    private const IMG_W300     = 'https://image.tmdb.org/t/p/w300';
    private const IMG_ORIGINAL = 'https://image.tmdb.org/t/p/original';

    public function __construct(SearchModel $model, TMDBHelper $tmdb) {
        $this->model = $model;
        $this->tmdb  = $tmdb;
    }

    /**
     * Executa a busca completa e retorna o payload para o frontend.
     */
    public function search(
        string  $query,
        ?string $tipo   = null,
        ?string $genero = null,
        ?int    $ano    = null,
        string  $ordem  = 'relevancia',
        int     $pagina = 1,
        int     $por_pagina = 24
    ): array {
        $query = trim($query);
        if (strlen($query) < 1) {
            return ['sucesso' => false, 'erro' => 'Termo de busca invalido.'];
        }

        $offset = ($pagina - 1) * $por_pagina;

        // 1. Busca local
        $result = $this->model->search($query, $tipo, $genero, $ano, $ordem, $por_pagina, $offset);

        if ($result['total'] === 0) {
            return [
                'sucesso'     => true,
                'dados'       => [],
                'total'       => 0,
                'pagina'      => $pagina,
                'total_paginas' => 0,
                'query'       => $query,
            ];
        }

        $itens = $result['itens'];

        // 2. Enriquece via TMDB em paralelo (cURL multi — mesma logica do ContentService)
        $tmdbData = $this->enrichWithTMDB($itens);

        // 3. Monta payload final
        $dados = [];
        foreach ($itens as $i => $item) {
            $t      = $tmdbData[$i] ?? [];
            $tmdbId = (int) ($item['id_tmdb'] ?? 0);
            $isTv   = ($item['tipo'] === 'serie');

            // Poster: banco local (apenas se for URL absoluta) com fallback TMDB
            $poster = (!empty($item['poster']) && str_starts_with($item['poster'], 'http'))
                ? $item['poster']
                : (!empty($t['poster_path']) ? self::IMG_W300 . $t['poster_path'] : null);

            // Backdrop: banco local (apenas se for URL absoluta) com fallback TMDB
            $backdrop = (!empty($item['capa']) && str_starts_with($item['capa'], 'http'))
                ? $item['capa']
                : (!empty($t['backdrop_path']) ? self::IMG_ORIGINAL . $t['backdrop_path'] : null);

            // Nota TMDB tem prioridade sobre a do banco
            $nota = round((float)($t['vote_average'] ?? $item['nota'] ?? 0), 1);

            // Generos do TMDB (ja em PT-BR) com fallback do banco
            $generos = [];
            if (!empty($t['genres'])) {
                foreach ($t['genres'] as $g) $generos[] = $g['name'];
            } elseif (!empty($item['genero'])) {
                $generos = array_map('trim', explode(',', $item['genero']));
            }

            // Ano de lancamento
            $releaseDate = $t['release_date'] ?? $t['first_air_date'] ?? $item['data_lancamento'] ?? null;
            $ano_lancamento = $releaseDate ? (int) substr($releaseDate, 0, 4) : null;

            // Status de disponibilidade de links
            $disponivel = $tmdbId ? $this->model->hasLinks($tmdbId) : false;

            $dados[] = [
                'id_tmdb'    => $tmdbId,
                'tipo'       => $item['tipo'],
                'titulo'     => $item['titulo'],
                'poster'     => $poster,
                'backdrop'   => $backdrop,
                'nota'       => $nota,
                'generos'    => $generos,
                'ano'        => $ano_lancamento,
                'disponivel' => $disponivel,
                'sinopse'    => $t['overview'] ?? $item['sinopse'] ?? '',
            ];
        }

        return [
            'sucesso'       => true,
            'dados'         => $dados,
            'total'         => $result['total'],
            'pagina'        => $pagina,
            'total_paginas' => (int) ceil($result['total'] / $por_pagina),
            'query'         => $query,
        ];
    }

    /**
     * Retorna lista de generos para o filtro da pagina de busca.
     */
    public function getGenres(): array {
        return $this->model->getGenres();
    }

    // ─── Privado ─────────────────────────────────────────────────────────────

    /**
     * Enriquece um array de itens com dados do TMDB via cURL multi (paralelo).
     * Mesma abordagem do TMDBHelper::getRichData() mas para busca.
     */
    private function enrichWithTMDB(array $itens): array {
        $mh      = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($itens as $index => $item) {
            if (empty($item['id_tmdb'])) continue;

            $type = ($item['tipo'] === 'serie') ? 'tv' : 'movie';
            $url  = 'https://api.themoviedb.org/3/' . $type . '/' . $item['id_tmdb']
                  . '?api_key=dc6299fd1adb4e32cf16017eecb33295&language=pt-BR&append_to_response=genres';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 4,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = $ch;
        }

        if (empty($handles)) return $results;

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $index => $ch) {
            $response      = curl_multi_getcontent($ch);
            $results[$index] = $response ? (json_decode($response, true) ?? []) : [];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $results;
    }
}
