<?php
/**
 * TrendingModel
 * Busca conteúdos lançados recentemente com alta popularidade/nota.
 * Estratégia: lançados nos últimos 18 meses + nota >= 6.0, ordenados
 * por score composto (nota DESC, data_lancamento DESC).
 */
class TrendingModel {
    private $db;

    private const KIDS_ALLOWED_GENRES = [
        'AnimaÃ§Ã£o', 'FamÃ­lia', 'ComÃ©dia', 'Aventura', 'Fantasia', 'MÃºsica'
    ];

    private const KIDS_BLOCKED_GENRES = [
        'Terror', 'Suspense', 'Crime', 'Guerra', 'Mistério', 'Drama'
    ];

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Retorna conteúdos recentes e populares.
     *
     * @param  int    $limit      Máximo de itens a retornar (pré-filtro TMDB, busca 3x)
     * @param  bool   $isKids     Perfil infantil — aplica filtros de segurança
     * @param  string $tipo       'filme' | 'serie' | null (ambos)
     * @return array
     */
    public function getTrending(int $limit = 20, bool $isKids = false, ?string $tipo = null): array {
        // Busca um lote maior para permitir reranking personalizado antes do enriquecimento TMDB.
        $fetchLimit = $isKids ? $limit * 10 : $limit * 5;

        $sql  = "SELECT id, id_tmdb, titulo, poster, capa, nota, data_lancamento, tipo, generos
                 FROM conteudo
                 WHERE id_tmdb IS NOT NULL
                   AND id_tmdb != ''
                   AND nota >= 6.0
                   AND data_lancamento >= DATE_SUB(NOW(), INTERVAL 18 MONTH)";

        $params = [];

        if ($tipo !== null) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }

        if ($isKids) {
            foreach (self::KIDS_BLOCKED_GENRES as $blocked) {
                $sql .= " AND (generos NOT LIKE ? OR generos IS NULL)";
                $params[] = '%' . $blocked . '%';
            }
        }

        $sql .= " ORDER BY nota DESC, data_lancamento DESC LIMIT " . (int)$fetchLimit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
