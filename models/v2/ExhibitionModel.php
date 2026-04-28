<?php
declare(strict_types=1);

namespace Models\V2;

use PDO;
use PDOException;

class ExhibitionModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function getContentBaseInfo(int $tmdbId, string $type): ?array {
        $tipoDB = ($type === 'series' || $type === 'tv') ? 'serie' : 'filme';
        $stmt = $this->db->prepare("SELECT id_tmdb, titulo, sinopse, poster, capa, tipo FROM conteudo WHERE id_tmdb = ? AND tipo = ? LIMIT 1");
        $stmt->execute([$tmdbId, $tipoDB]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getRawVideoLinks(int $tmdbId, string $type, int $season = 0, int $episode = 0): array {
        $isSerie = ($type === 'series' || $type === 'tv' || $type === 'serie');
        $links = ['dublado' => [], 'legendado' => []];

        try {
            $sqlDub = "SELECT url_video, qualidade FROM links WHERE id_tmdb = ?";
            if ($isSerie) $sqlDub .= " AND temporada = ? AND episodio = ?";
            $stmtDub = $this->db->prepare($sqlDub . " ORDER BY id DESC");
            $isSerie ? $stmtDub->execute([$tmdbId, $season, $episode]) : $stmtDub->execute([$tmdbId]);
            $links['dublado'] = $stmtDub->fetchAll(PDO::FETCH_ASSOC);

            $sqlLeg = "SELECT url_video, qualidade FROM links_legendados WHERE id_tmdb = ?";
            if ($isSerie) $sqlLeg .= " AND temporada = ? AND episodio = ?";
            $stmtLeg = $this->db->prepare($sqlLeg . " ORDER BY id DESC");
            $isSerie ? $stmtLeg->execute([$tmdbId, $season, $episode]) : $stmtLeg->execute([$tmdbId]);
            $links['legendado'] = $stmtLeg->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro ao buscar links de vídeo: " . $e->getMessage());
        }

        return $links;
    }
}