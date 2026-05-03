<?php
declare(strict_types=1);

namespace Models\V2;

use PDO;
use PDOException;

/**
 * SearchModel — busca conteudos locais no banco do Cineveo com filtros.
 *
 * A query principal busca os campos necessarios para enriquecer via TMDB
 * e verificar a disponibilidade de links, seguindo a mesma logica do InfoModel.
 */
class SearchModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Busca conteudos por titulo com filtros opcionais.
     *
     * @param string      $query   Termo de busca (nome do conteudo)
     * @param string|null $tipo    'filme' | 'serie' | null (ambos)
     * @param string|null $genero  Genero em texto livre
     * @param int|null    $ano     Ano de lancamento
     * @param string      $ordem   'relevancia' | 'nota' | 'recente' | 'antigo'
     * @param int         $limite  Max de resultados
     * @param int         $offset  Paginacao
     * @return array{total: int, itens: array}
     */
    public function search(
        string  $query,
        ?string $tipo   = null,
        ?string $genero = null,
        ?int    $ano    = null,
        string  $ordem  = 'relevancia',
        int     $limite = 24,
        int     $offset = 0
    ): array {
        try {
            $conditions = ['(titulo LIKE :q OR titulo_original LIKE :q2)'];
            $params     = [
                ':q'  => '%' . $query . '%',
                ':q2' => '%' . $query . '%',
            ];

            if ($tipo && in_array($tipo, ['filme', 'serie'], true)) {
                $conditions[] = 'tipo = :tipo';
                $params[':tipo'] = $tipo;
            }

            if ($genero) {
                $conditions[] = 'generos LIKE :generos';
                $params[':generos'] = '%' . $genero . '%';
            }

            if ($ano) {
                $conditions[] = 'YEAR(data_lancamento) = :ano';
                $params[':ano'] = $ano;
            }

            $where = 'WHERE ' . implode(' AND ', $conditions);

            $orderSql = match($ordem) {
                'nota'      => 'nota DESC',
                'recente'   => 'data_lancamento DESC',
                'antigo'    => 'data_lancamento ASC',
                default     => 'CASE WHEN titulo LIKE :exact THEN 0 ELSE 1 END, nota DESC',
            };

            // Para relevancia, precisamos do parametro :exact
            if ($ordem === 'relevancia') {
                $params[':exact'] = $query . '%';
            }

            // Conta total sem LIMIT para paginacao
            $countSql = "SELECT COUNT(*) FROM conteudo {$where}";
            $countStmt = $this->db->prepare($countSql);
            // Bind apenas os params do WHERE (sem :exact para contagem)
            foreach ($params as $key => $val) {
                if ($key !== ':exact') $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            if ($total === 0) return ['total' => 0, 'itens' => []];

            $sql = "SELECT id, id_tmdb, titulo, titulo_original, tipo, poster, capa,
                           nota, data_lancamento, generos, sinopse
                    FROM conteudo
                    {$where}
                    ORDER BY {$orderSql}
                    LIMIT :lim OFFSET :off";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'total' => $total,
                'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ];
        } catch (PDOException $e) {
            error_log('[SearchModel] Erro: ' . $e->getMessage());
            return ['total' => 0, 'itens' => []];
        }
    }

    /**
     * Retorna a lista de generos distintos presentes no banco para popular o filtro.
     */
    public function getGenres(): array {
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT genero FROM conteudo WHERE genero IS NOT NULL AND genero != '' ORDER BY genero"
            );
            $raw    = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $genres = [];
            foreach ($raw as $g) {
                foreach (array_map('trim', explode(',', $g)) as $genre) {
                    if ($genre) $genres[$genre] = true;
                }
            }
            ksort($genres);
            return array_keys($genres);
        } catch (PDOException $e) {
            error_log('[SearchModel] Erro ao buscar generos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica se um conteudo tem links disponiveis (mesmo criterio do InfoModel).
     */
    public function hasLinks(int $tmdbId): bool {
        try {
            $s = $this->db->prepare('SELECT 1 FROM links WHERE id_tmdb = ? LIMIT 1');
            $s->execute([$tmdbId]);
            if ($s->fetchColumn()) return true;

            $s = $this->db->prepare('SELECT 1 FROM links_legendados WHERE id_tmdb = ? LIMIT 1');
            $s->execute([$tmdbId]);
            return (bool) $s->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
