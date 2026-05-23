<?php
/**
 * PlatformModel
 *
 * Busca lotes de conteúdo do banco local para validação de plataforma
 * via TMDB Watch Providers. Não aplica filtro de provedor — isso é
 * responsabilidade do PlatformService.
 */
class PlatformModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Retorna um lote de conteúdos para validação externa via TMDB.
     *
     * @param string|null $tipo   'filme' | 'serie' | null (ambos)
     * @param int         $limit  Quantos registros buscar neste lote
     * @param int         $offset Deslocamento para paginação de lote
     * @return array
     */
    public function getLotForValidation(?string $tipo, int $limit, int $offset): array {
        $sql    = "SELECT id, id_tmdb, titulo, poster, capa, nota, data_lancamento, tipo, generos
                   FROM conteudo
                   WHERE id_tmdb IS NOT NULL
                     AND id_tmdb != ''
                     AND id_tmdb != '0'";
        $params = [];

        if ($tipo !== null) {
            $sql     .= ' AND tipo = ?';
            $params[] = $tipo;
        }

        // Ordena por nota para que os mais bem avaliados apareçam primeiro
        $sql .= ' ORDER BY nota DESC, id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta o total de registros disponíveis para estimar se há mais páginas.
     *
     * @param string|null $tipo
     * @return int
     */
    public function countAll(?string $tipo): int {
        $sql    = "SELECT COUNT(*) FROM conteudo
                   WHERE id_tmdb IS NOT NULL AND id_tmdb != '' AND id_tmdb != '0'";
        $params = [];

        if ($tipo !== null) {
            $sql     .= ' AND tipo = ?';
            $params[] = $tipo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
