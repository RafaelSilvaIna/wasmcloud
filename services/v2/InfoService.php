<?php
declare(strict_types=1);

namespace Services\V2;

use Models\V2\InfoModel;
use TMDBHelper;

/**
 * InfoService — orquestra a busca de dados locais + enriquecimento via TMDB
 * para montar o payload completo da página de detalhes.
 */
class InfoService {
    private InfoModel   $model;
    private TMDBHelper  $tmdb;

    private const IMG_BASE_ORIGINAL = 'https://image.tmdb.org/t/p/original';
    private const IMG_BASE_W500     = 'https://image.tmdb.org/t/p/w500';
    private const IMG_BASE_W780     = 'https://image.tmdb.org/t/p/w780';
    private const IMG_BASE_W300     = 'https://image.tmdb.org/t/p/w300';

    public function __construct(InfoModel $model, TMDBHelper $tmdb) {
        $this->model = $model;
        $this->tmdb  = $tmdb;
    }

    /**
     * Constrói o payload completo de detalhes do conteúdo.
     *
     * @param int $tmdbId ID do TMDB
     * @return array|null Payload ou null se não encontrado
     */
    public function getFullDetails(int $tmdbId, ?string $tipoHint = null): ?array {
        // 1. Busca base local
        $base = $this->model->getByTmdbId($tmdbId, $tipoHint);
        if (!$base) return null;

        $tipo      = $base['tipo'];      // 'filme' | 'serie'
        $mediaType = ($tipo === 'serie') ? 'tv' : 'movie';

        // 2. Busca dados ricos no TMDB em paralelo:
        //    - Detalhes completos (credits, images, keywords, similar)
        //    - Se for série: info de temporadas
        $detailUrl = "/{$mediaType}/{$tmdbId}?append_to_response=credits,images,keywords,similar,videos,recommendations,external_ids&";
        $tmdbData  = $this->tmdb->fetch($detailUrl);

        // 3. Monta elenco principal (até 15)
        $cast = [];
        if (!empty($tmdbData['credits']['cast'])) {
            foreach (array_slice($tmdbData['credits']['cast'], 0, 15) as $member) {
                $cast[] = [
                    'name'       => $member['name'] ?? '',
                    'character'  => $member['character'] ?? '',
                    'profile'    => !empty($member['profile_path'])
                                    ? self::IMG_BASE_W300 . $member['profile_path']
                                    : null,
                ];
            }
        }

        // 4. Diretores / Criadores
        $crew = [];
        if (!empty($tmdbData['credits']['crew'])) {
            foreach ($tmdbData['credits']['crew'] as $member) {
                $job = $member['job'] ?? '';
                if (in_array($job, ['Director', 'Creator', 'Executive Producer', 'Screenplay'], true)) {
                    $crew[] = [
                        'name' => $member['name'] ?? '',
                        'job'  => $this->translateJob($job),
                    ];
                }
            }
            // Remove duplicados por nome
            $crew = array_values(array_unique($crew, SORT_REGULAR));
        }
        // Para séries, usa created_by
        if ($tipo === 'serie' && !empty($tmdbData['created_by'])) {
            foreach ($tmdbData['created_by'] as $creator) {
                $crew[] = ['name' => $creator['name'] ?? '', 'job' => 'Criador'];
            }
        }

        // 5. Géneros (TMDB tem nomes em PT-BR com language=pt-BR)
        $genres = [];
        if (!empty($tmdbData['genres'])) {
            foreach ($tmdbData['genres'] as $g) {
                $genres[] = $g['name'];
            }
        } elseif (!empty($base['generos'])) {
            $genres = array_map('trim', explode(',', $base['generos']));
        }

        // 6. Logo (PT-BR → EN → primeira disponível)
        $logo = null;
        if (!empty($tmdbData['images']['logos'])) {
            foreach ($tmdbData['images']['logos'] as $l) {
                if (($l['iso_639_1'] ?? '') === 'pt') { $logo = $l['file_path']; break; }
            }
            if (!$logo) {
                foreach ($tmdbData['images']['logos'] as $l) {
                    if (($l['iso_639_1'] ?? '') === 'en') { $logo = $l['file_path']; break; }
                }
            }
            if (!$logo) $logo = $tmdbData['images']['logos'][0]['file_path'] ?? null;
            if ($logo)  $logo = self::IMG_BASE_W500 . $logo;
        }

        // 7. Backdrops extras (galeria, até 8)
        $backdrops = [];
        if (!empty($tmdbData['images']['backdrops'])) {
            foreach (array_slice($tmdbData['images']['backdrops'], 0, 8) as $bd) {
                $backdrops[] = self::IMG_BASE_W780 . $bd['file_path'];
            }
        }

        // 8. Trailer (YouTube — PT-BR → EN)
        $trailer = null;
        if (!empty($tmdbData['videos']['results'])) {
            $trailers = array_filter($tmdbData['videos']['results'], fn($v) =>
                $v['type'] === 'Trailer' && $v['site'] === 'YouTube'
            );
            // Tenta PT-BR
            foreach ($trailers as $v) {
                if (($v['iso_639_1'] ?? '') === 'pt') { $trailer = $v['key']; break; }
            }
            // Fallback EN
            if (!$trailer) {
                foreach ($trailers as $v) {
                    $trailer = $v['key'];
                    break;
                }
            }
        }

        // 9. Palavras-chave (até 10)
        $keywords = [];
        $kwData = $tmdbData['keywords']['keywords'] ?? $tmdbData['keywords']['results'] ?? [];
        foreach (array_slice($kwData, 0, 10) as $kw) {
            $keywords[] = $kw['name'];
        }

        // 10. Recomendados TMDB (até 8)
        $recommendations = [];
        $recData = $tmdbData['recommendations']['results'] ?? [];
        foreach (array_slice($recData, 0, 8) as $rec) {
            if (empty($rec['poster_path'])) continue;
            $recommendations[] = [
                'tmdb_id'    => $rec['id'],
                'title'      => $rec['title'] ?? $rec['name'] ?? '',
                'poster'     => self::IMG_BASE_W300 . $rec['poster_path'],
                'vote'       => round($rec['vote_average'] ?? 0, 1),
                'type'       => isset($rec['title']) ? 'filme' : 'serie',
            ];
        }

        // Se TMDB não retornou recomendados suficientes, complementa com o BD local
        if (count($recommendations) < 6) {
            $localRelated = $this->model->getRelated($tmdbId, $tipo, $base['generos'] ?? '', 8);
            foreach ($localRelated as $rel) {
                $relatedPoster = $this->tmdbImageUrl($rel['poster'] ?? null, 'w300');
                if (!$relatedPoster) continue;

                $recommendations[] = [
                    'tmdb_id' => (int)$rel['id_tmdb'],
                    'title'   => $rel['titulo'],
                    'poster'  => $relatedPoster,
                    'vote'    => round((float)($rel['nota'] ?? 0), 1),
                    'type'    => $rel['tipo'],
                ];
            }
            // Remove duplicados por tmdb_id
            $seen = [];
            $recommendations = array_values(array_filter($recommendations, function($r) use (&$seen) {
                if (in_array($r['tmdb_id'], $seen, true)) return false;
                $seen[] = $r['tmdb_id'];
                return true;
            }));
        }

        // 11. Temporadas (apenas para séries)
        $seasons = [];
        if ($tipo === 'serie' && !empty($tmdbData['seasons'])) {
            foreach ($tmdbData['seasons'] as $s) {
                if ((int)$s['season_number'] === 0) continue; // exclui "Especiais"
                $seasons[] = [
                    'number'        => $s['season_number'],
                    'name'          => $s['name'],
                    'episode_count' => $s['episode_count'],
                    'air_date'      => $s['air_date'] ?? null,
                    'poster'        => $this->tmdbImageUrl($s['poster_path'] ?? null, 'w300'),
                    'overview'      => $s['overview'] ?? '',
                ];
            }
        }

        // 12. Metadados extras
        $runtime    = $tmdbData['runtime']
                      ?? $tmdbData['episode_run_time'][0]
                      ?? null;
        $status     = $this->translateStatus($tmdbData['status'] ?? '');
        $tagline    = $tmdbData['tagline'] ?? '';
        $voteAvg    = round((float)($tmdbData['vote_average'] ?? $base['nota'] ?? 0), 1);
        $voteCount  = $tmdbData['vote_count'] ?? 0;
        $releaseDate = $tmdbData['release_date']
                       ?? $tmdbData['first_air_date']
                       ?? $base['data_lancamento']
                       ?? null;
        $country    = $tmdbData['production_countries'][0]['name'] ?? null;
        $language   = $tmdbData['original_language'] ?? null;
        $totalSeasons   = $tmdbData['number_of_seasons']   ?? null;
        $totalEpisodes  = $tmdbData['number_of_episodes']  ?? null;
        $networks   = [];
        if (!empty($tmdbData['networks'])) {
            foreach ($tmdbData['networks'] as $net) {
                $networks[] = $net['name'];
            }
        }
        $productionCompanies = [];
        if (!empty($tmdbData['production_companies'])) {
            foreach (array_slice($tmdbData['production_companies'], 0, 3) as $pc) {
                $productionCompanies[] = $pc['name'];
            }
        }

        // 13. Verifica disponibilidade de links
        $hasLinks = $this->model->hasLinks($tmdbId, $tipo);
        $posterUrl = $this->tmdbImageUrl($tmdbData['poster_path'] ?? null, 'w500');
        $backdropUrl = $this->tmdbImageUrl($tmdbData['backdrop_path'] ?? null, 'original');

        // 14. Monta payload final
        return [
            'sucesso' => true,
            'dados'   => [
                'id_tmdb'              => $tmdbId,
                'titulo'               => $base['titulo'],
                'titulo_original'      => $tmdbData['original_title'] ?? $tmdbData['original_name'] ?? $base['titulo'],
                'tagline'              => $tagline,
                'sinopse'              => $tmdbData['overview'] ?? $base['sinopse'] ?? '',
                'poster'               => $posterUrl,
                'backdrop'             => $backdropUrl,
                'logo'                 => $logo,
                'backdrops'            => $backdrops,
                'trailer_key'          => $trailer,
                'tipo'                 => $tipo,
                'generos'              => $genres,
                'keywords'             => $keywords,
                'nota'                 => $voteAvg,
                'votos'                => $voteCount,
                'data_lancamento'      => $releaseDate,
                'duracao_minutos'      => $runtime,
                'status'               => $status,
                'pais_origem'          => $country,
                'idioma_original'      => $language,
                'total_temporadas'     => $totalSeasons,
                'total_episodios'      => $totalEpisodes,
                'redes_exibicao'       => $networks,
                'produtoras'           => $productionCompanies,
                'elenco'               => $cast,
                'equipe'               => $crew,
                'temporadas'           => $seasons,
                'recomendados'         => array_slice($recommendations, 0, 12),
                'disponivel'           => $hasLinks,
            ],
        ];
    }

    // ─── Utilitários privados ────────────────────────────────────────────────

    private function translateJob(string $job): string {
        return match ($job) {
            'Director'           => 'Diretor',
            'Creator'            => 'Criador',
            'Executive Producer' => 'Produtor Executivo',
            'Screenplay'         => 'Roteirista',
            default              => $job,
        };
    }

    private function translateStatus(string $status): string {
        return match ($status) {
            'Released'        => 'Lançado',
            'In Production'   => 'Em Produção',
            'Post Production' => 'Pós-Produção',
            'Planned'         => 'Planejado',
            'Returning Series'=> 'Em Exibição',
            'Ended'           => 'Encerrado',
            'Canceled'        => 'Cancelado',
            default           => $status,
        };
    }

    private function tmdbImageUrl(?string $path, string $size = 'w500'): ?string {
        $path = trim((string)$path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'https://image.tmdb.org/t/p/')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return "https://image.tmdb.org/t/p/{$size}{$path}";
        }

        return null;
    }
}
