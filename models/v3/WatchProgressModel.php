<?php

declare(strict_types=1);

namespace Models\V3;

use PDO;
use Throwable;

/**
 * WatchProgressModel
 * Persiste e consulta o progresso de reprodução de cada usuário.
 */
class WatchProgressModel
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────────────────
    // UPSERT — salva ou atualiza o progresso exato de reprodução
    // ──────────────────────────────────────────────────────────────────────

    /**
     * @param array{
     *   user_id: int,
     *   content_id: int,
     *   content_type: string,
     *   season: int,
     *   episode: int,
     *   progress_time: float,
     *   duration: float,
     *   content_title: string,
     *   content_poster: string,
     *   content_year: int|null,
     *   audio: string
     * } $data
     */
    public function upsert(array $data): bool
    {
        $sql = "
            INSERT INTO watch_progress
                (user_id, content_id, content_type, season, episode,
                 progress_time, duration, content_title, content_poster,
                 content_year, audio)
            VALUES
                (:user_id, :content_id, :content_type, :season, :episode,
                 :progress_time, :duration, :content_title, :content_poster,
                 :content_year, :audio)
            ON DUPLICATE KEY UPDATE
                progress_time  = VALUES(progress_time),
                duration       = VALUES(duration),
                content_title  = VALUES(content_title),
                content_poster = VALUES(content_poster),
                content_year   = VALUES(content_year),
                audio          = VALUES(audio),
                updated_at     = CURRENT_TIMESTAMP
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':user_id'        => $data['user_id'],
                ':content_id'     => $data['content_id'],
                ':content_type'   => $data['content_type'] === 'serie' ? 'serie' : 'filme',
                ':season'         => $data['season']   ?? 1,
                ':episode'        => $data['episode']  ?? 1,
                ':progress_time'  => round((float) ($data['progress_time'] ?? 0), 2),
                ':duration'       => round((float) ($data['duration']      ?? 0), 2),
                ':content_title'  => mb_substr((string) ($data['content_title']  ?? ''), 0, 255),
                ':content_poster' => mb_substr((string) ($data['content_poster'] ?? ''), 0, 500),
                ':content_year'   => isset($data['content_year']) ? (int) $data['content_year'] : null,
                ':audio'          => in_array($data['audio'] ?? '', ['dub', 'leg']) ? $data['audio'] : '',
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET — busca o progresso de um único conteúdo/episódio
    // ──────────────────────────────────────────────────────────────────────

    public function get(int $userId, int $contentId, string $contentType, int $season = 1, int $episode = 1): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM   watch_progress
            WHERE  user_id      = :uid
              AND  content_id   = :cid
              AND  content_type = :ct
              AND  season       = :s
              AND  episode      = :e
            LIMIT 1
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $contentId,
            ':ct'  => $contentType === 'serie' ? 'serie' : 'filme',
            ':s'   => $season,
            ':e'   => $episode,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CONTINUA ASSISTINDO — retorna conteúdos iniciados mas não finalizados
    // Critério: progress_time > 60s e percentual < 90%
    // ──────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContinueWatching(int $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM   watch_progress
            WHERE  user_id       = :uid
              AND  progress_time > 60
              AND  (duration = 0 OR (progress_time / duration) < 0.90)
            ORDER BY updated_at DESC
            LIMIT  :lim
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE — remove progresso (ex: usuário terminou e quer remover)
    // ──────────────────────────────────────────────────────────────────────

    public function delete(int $userId, int $contentId, string $contentType, int $season = 1, int $episode = 1): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM watch_progress
            WHERE user_id      = :uid
              AND content_id   = :cid
              AND content_type = :ct
              AND season       = :s
              AND episode      = :e
            LIMIT 1
        ");
        return $stmt->execute([
            ':uid' => $userId,
            ':cid' => $contentId,
            ':ct'  => $contentType === 'serie' ? 'serie' : 'filme',
            ':s'   => $season,
            ':e'   => $episode,
        ]);
    }
}
