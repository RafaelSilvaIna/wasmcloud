<?php
/**
 * PlatformService
 *
 * Orquestra a validação de plataforma via TMDB Watch Providers:
 *  1. Busca lote do banco via PlatformModel
 *  2. Para cada item executa cURL multi em /watch/providers (paralelo)
 *  3. Filtra os que têm o provider_id da marca no Brasil
 *  4. Enriquece os aprovados com TMDBHelper::getRichData (poster, nota, etc.)
 *  5. Retorna resultado paginado
 *
 * Estratégia de lote:
 *  - Taxa de aprovação esperada ~25-40%; para garantir a quantidade
 *    solicitada, buscamos 3× mais itens do banco e retornamos apenas `$perPage`.
 */
class PlatformService {
    private const API_KEY  = 'dc6299fd1adb4e32cf16017eecb33295';
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private const IMG_BASE = 'https://image.tmdb.org/t/p/';

    /** Mapeamento marca → provider_id TMDB */
    private const BRANDS = [
        'netflix'   => ['id' => 8,   'nome' => 'Netflix',       'cor' => '#e50914'],
        'prime'     => ['id' => 119, 'nome' => 'Prime Video',   'cor' => '#00a8e0'],
        'disney'    => ['id' => 337, 'nome' => 'Disney+',       'cor' => '#0063e5'],
        'max'       => ['id' => 384, 'nome' => 'Max',           'cor' => '#002be7'],
        'globoplay' => ['id' => 307, 'nome' => 'Globoplay',     'cor' => '#e30000'],
        'appletv'   => ['id' => 350, 'nome' => 'Apple TV+',     'cor' => '#f5f5f7'],
        'paramount' => ['id' => 531, 'nome' => 'Paramount+',    'cor' => '#0064ff'],
    ];

    private PlatformModel $model;

    public function __construct(PlatformModel $model) {
        $this->model = $model;
    }

    /**
     * Valida e retorna conteúdos da plataforma solicitada de forma paginada.
     *
     * @param string      $marca    Chave da marca (ex.: 'netflix')
     * @param string|null $tipo     'filme' | 'serie' | null
     * @param int         $page     Página lógica (1-based)
     * @param int         $perPage  Itens por página (máx 48)
     * @return array{
     *   marca: array,
     *   pagina: int,
     *   por_pagina: int,
     *   tem_mais: bool,
     *   total_banco: int,
     *   resultados: array
     * }
     */
    public function get(string $marca, ?string $tipo, int $page, int $perPage): array {
        $brandInfo  = self::BRANDS[$marca] ?? null;
        if (!$brandInfo) {
            throw new InvalidArgumentException("Marca '{$marca}' nao suportada.");
        }

        $providerId = $brandInfo['id'];
        $perPage    = min(48, max(1, $perPage));
        $page       = max(1, $page);
        $totalBanco = $this->model->countAll($tipo);

        // Busca 3× para compensar taxa de rejeição (~70% não passam na validação)
        $batchSize = $perPage * 3;
        $approved  = [];
        $offset    = 0;
        $maxPasses = 8; // Evita loop infinito
        $passes    = 0;

        // Avança o offset base de acordo com a página solicitada
        // Estimativa: em média 33% de aprovação, então cada "página lógica"
        // consome ~(perPage * 3) registros do banco
        $baseOffset = ($page - 1) * ($perPage * 3);

        while (count($approved) < $perPage && $passes < $maxPasses) {
            $currentOffset = $baseOffset + $offset;

            if ($currentOffset >= $totalBanco) {
                break; // Acabou o banco
            }

            $lot      = $this->model->getLotForValidation($tipo, $batchSize, $currentOffset);
            $filtered = $this->filterByProvider($lot, $providerId);

            foreach ($filtered as $item) {
                if (count($approved) >= $perPage) break;
                $approved[] = $item;
            }

            $offset += $batchSize;
            $passes++;
        }

        // Enriquece com dados do TMDB (poster HD, nota, sinopse, etc.)
        $enriched = $this->enrich($approved);

        // Estima se há mais páginas (heurística conservadora)
        $estimatedRemaining = $totalBanco - ($baseOffset + $batchSize * $passes);
        $temMais            = $estimatedRemaining > 0;

        return [
            'marca'       => $brandInfo,
            'pagina'      => $page,
            'por_pagina'  => $perPage,
            'tem_mais'    => $temMais,
            'total_banco' => $totalBanco,
            'resultados'  => array_values($enriched),
        ];
    }

    // ─── Validação via TMDB Watch Providers (cURL multi paralelo) ────────────

    private function filterByProvider(array $items, int $providerId): array {
        if (empty($items)) return [];

        $mh      = curl_multi_init();
        $handles = [];

        foreach ($items as $idx => $item) {
            $tmdbType = ($item['tipo'] === 'serie') ? 'tv' : 'movie';
            $url      = self::BASE_URL . "/{$tmdbType}/{$item['id_tmdb']}/watch/providers"
                      . "?api_key=" . self::API_KEY;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = $ch;
        }

        // Executa todas as requisições em paralelo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.5);
        } while ($running > 0);

        $approved = [];
        foreach ($handles as $idx => $ch) {
            $raw      = curl_multi_getcontent($ch);
            $data     = $raw ? json_decode($raw, true) : null;
            $approved = $this->checkProvider($data, $providerId, $items[$idx], $approved);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $approved;
    }

    /**
     * Verifica se o provider_id está presente nos provedores do Brasil.
     * Aceita flatrate, rent ou buy para maximizar a cobertura.
     */
    private function checkProvider(?array $data, int $providerId, array $item, array &$approved): array {
        if (!$data || empty($data['results']['BR'])) {
            return $approved;
        }

        $br       = $data['results']['BR'];
        $allTypes = array_merge(
            $br['flatrate'] ?? [],
            $br['rent']     ?? [],
            $br['buy']      ?? [],
            $br['ads']      ?? []
        );

        foreach ($allTypes as $provider) {
            if ((int)($provider['provider_id'] ?? 0) === $providerId) {
                $approved[] = $item;
                break;
            }
        }

        return $approved;
    }

    // ─── Enriquecimento via TMDBHelper::getRichData ───────────────────────────

    private function enrich(array $items): array {
        if (empty($items)) return [];

        $richData = TMDBHelper::getRichData($items);
        $result   = [];

        foreach ($items as $idx => $item) {
            $tmdb   = $richData[$idx] ?? null;
            $poster = $this->resolvePoster($tmdb, $item);
            $nota   = $tmdb['vote_average'] ?? (float)($item['nota'] ?? 0);

            $result[] = [
                'id'              => $item['id'],
                'id_tmdb'         => $item['id_tmdb'],
                'tipo'            => $item['tipo'],
                'titulo'          => $tmdb['title'] ?? $tmdb['name'] ?? $item['titulo'],
                'poster'          => $poster,
                'nota'            => round((float)$nota, 1),
                'data_lancamento' => $tmdb['release_date'] ?? $tmdb['first_air_date'] ?? $item['data_lancamento'] ?? null,
                'sinopse'         => mb_strimwidth($tmdb['overview'] ?? '', 0, 200, '...'),
                'generos'         => array_map(fn($g) => $g['name'], $tmdb['genres'] ?? []),
            ];
        }

        return $result;
    }

    private function resolvePoster(?array $tmdb, array $item): string {
        // Prefere poster do TMDB (qualidade garantida)
        if (!empty($tmdb['poster_path'])) {
            return self::IMG_BASE . 'w342' . $tmdb['poster_path'];
        }
        // Fallback para o poster do banco local
        if (!empty($item['poster'])) {
            return $item['poster'];
        }
        return '';
    }

    /**
     * Retorna os metadados de uma marca pelo slug.
     */
    public static function getBrandInfo(string $marca): ?array {
        return self::BRANDS[$marca] ?? null;
    }

    /**
     * Lista todas as marcas suportadas (para validação e listagem).
     */
    public static function allBrands(): array {
        return self::BRANDS;
    }
}
