<?php
declare(strict_types=1);

namespace Models\V2;

use PDO;
use PDOException;

/**
 * InfoModel — busca dados locais do conteúdo (filme ou série) para a página de detalhes.
 */
class InfoModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Busca as informações base do conteúdo pelo id_tmdb.
     * Tenta encontrar filme ou série sem precisar que o tipo seja informado.
     */
    public function getByTmdbId(int $tmdbId): ?array {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, id_tmdb, titulo, sinopse, poster, capa, nota, data_lancamento, tipo, generos
                 FROM conteudo
                 WHERE id_tmdb = ?
                 LIMIT 1"
            );
            $stmt->execute([$tmdbId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("[InfoModel] Erro ao buscar conteúdo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica se o conteúdo possui links disponíveis (dublado ou legendado).
     */
    public function hasLinks(int $tmdbId, string $tipo): bool {
        try {
            $isSerie = ($tipo === 'serie');

            // Verifica links dublados
            $stmtDub = $this->db->prepare("SELECT 1 FROM links WHERE id_tmdb = ? LIMIT 1");
            $stmtDub->execute([$tmdbId]);
            if ($stmtDub->fetchColumn()) return true;

            // Verifica links legendados
            $stmtLeg = $this->db->prepare("SELECT 1 FROM links_legendados WHERE id_tmdb = ? LIMIT 1");
            $stmtLeg->execute([$tmdbId]);
            if ($stmtLeg->fetchColumn()) return true;

            return false;
        } catch (PDOException $e) {
            error_log("[InfoModel] Erro ao verificar links: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca conteúdos relacionados pelo mesmo gênero principal (excluindo o atual).
     */
    public function getRelated(int $tmdbId, string $tipo, string $genero, int $limit = 12): array {
        try {
            // Extrai o primeiro gênero
            $generos = array_map('trim', explode(',', $genero));
            $genPrincipal = $generos[0] ?? '';

            if (empty($genPrincipal)) {
                $stmt = $this->db->prepare(
                    "SELECT id, id_tmdb, titulo, poster, nota, data_lancamento, tipo
                     FROM conteudo
                     WHERE id_tmdb != ? AND tipo = ? AND id_tmdb IS NOT NULL
                     ORDER BY nota DESC
                     LIMIT ?"
                );
                $stmt->execute([$tmdbId, $tipo, $limit]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT id, id_tmdb, titulo, poster, nota, data_lancamento, tipo
                     FROM conteudo
                     WHERE id_tmdb != ? AND tipo = ? AND generos LIKE ? AND id_tmdb IS NOT NULL
                     ORDER BY nota DESC
                     LIMIT ?"
                );
                $stmt->execute([$tmdbId, $tipo, '%' . $genPrincipal . '%', $limit]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[InfoModel] Erro ao buscar relacionados: " . $e->getMessage());
            return [];
        }
    }
}
