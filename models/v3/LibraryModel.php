<?php

declare(strict_types=1);

namespace Models\V3;

/**
 * LibraryModel — pip_user_library + pip_watch_history (banco pipcine)
 * Gerencia: salvar, curtir e histórico de visualizações por perfil.
 */
class LibraryModel
{
    public function __construct(private \PDO $db) {}

    // ── Toggle salvar ─────────────────────────────────────────
    public function toggleSave(int $profileId, int $userId, array $meta): array
    {
        $row = $this->getRow($profileId, $meta['content_id'], $meta['content_type']);

        if ($row) {
            $newVal = $row['is_saved'] ? 0 : 1;
            $stmt = $this->db->prepare(
                "UPDATE pip_user_library
                 SET is_saved = ?, saved_at = ?
                 WHERE id = ?"
            );
            $stmt->execute([$newVal, $newVal ? date('Y-m-d H:i:s') : null, $row['id']]);
            return ['saved' => (bool) $newVal];
        }

        $this->insertRow($profileId, $userId, $meta, saved: true, liked: false);
        return ['saved' => true];
    }

    // ── Toggle curtir ─────────────────────────────────────────
    public function toggleLike(int $profileId, int $userId, array $meta): array
    {
        $row = $this->getRow($profileId, $meta['content_id'], $meta['content_type']);

        if ($row) {
            $newVal = $row['is_liked'] ? 0 : 1;
            $stmt = $this->db->prepare(
                "UPDATE pip_user_library
                 SET is_liked = ?, liked_at = ?
                 WHERE id = ?"
            );
            $stmt->execute([$newVal, $newVal ? date('Y-m-d H:i:s') : null, $row['id']]);
            return ['liked' => (bool) $newVal];
        }

        $this->insertRow($profileId, $userId, $meta, saved: false, liked: true);
        return ['liked' => true];
    }

    // ── Status atual (saved/liked) para um conteúdo ───────────
    public function getStatus(int $profileId, int $contentId, string $contentType): array
    {
        $row = $this->getRow($profileId, $contentId, $contentType);
        return [
            'saved' => (bool) ($row['is_saved'] ?? false),
            'liked' => (bool) ($row['is_liked'] ?? false),
        ];
    }

    // ── Conteúdos salvos do perfil ────────────────────────────
    public function getSaved(int $profileId, int $limit = 60): array
    {
        $stmt = $this->db->prepare(
            "SELECT content_id, content_type, content_title, content_poster,
                    content_backdrop, content_year, saved_at
             FROM pip_user_library
             WHERE profile_id = ? AND is_saved = 1
             ORDER BY saved_at DESC
             LIMIT ?"
        );
        $stmt->execute([$profileId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Conteúdos curtidos do perfil ──────────────────────────
    public function getLiked(int $profileId, int $limit = 60): array
    {
        $stmt = $this->db->prepare(
            "SELECT content_id, content_type, content_title, content_poster,
                    content_backdrop, content_year, liked_at
             FROM pip_user_library
             WHERE profile_id = ? AND is_liked = 1
             ORDER BY liked_at DESC
             LIMIT ?"
        );
        $stmt->execute([$profileId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Registrar visualização ────────────────────────────────
    public function recordWatch(int $profileId, int $userId, array $meta): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pip_watch_history
                (profile_id, user_id, content_id, content_type, content_title,
                 content_poster, content_backdrop, content_year, season, episode)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $profileId,
            $userId,
            $meta['content_id'],
            $meta['content_type'],
            $meta['content_title']    ?? '',
            $meta['content_poster']   ?? '',
            $meta['content_backdrop'] ?? '',
            $meta['content_year']     ?? null,
            $meta['season']           ?? null,
            $meta['episode']          ?? null,
        ]);
    }

    // ── Histórico do perfil (distinct por conteúdo, mais recente) ─
    public function getHistory(int $profileId, int $limit = 60): array
    {
        $stmt = $this->db->prepare(
            "SELECT h1.content_id, h1.content_type, h1.content_title,
                    h1.content_poster, h1.content_backdrop, h1.content_year,
                    h1.season, h1.episode, h1.watched_at
             FROM pip_watch_history h1
             INNER JOIN (
                 SELECT content_id, content_type, MAX(watched_at) AS last_watch
                 FROM pip_watch_history
                 WHERE profile_id = ?
                 GROUP BY content_id, content_type
             ) h2 ON h1.content_id = h2.content_id
                  AND h1.content_type = h2.content_type
                  AND h1.watched_at = h2.last_watch
             WHERE h1.profile_id = ?
             ORDER BY h1.watched_at DESC
             LIMIT ?"
        );
        $stmt->execute([$profileId, $profileId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Helpers privados ──────────────────────────────────────
    private function getRow(int $profileId, int $contentId, string $contentType): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, is_saved, is_liked
             FROM pip_user_library
             WHERE profile_id = ? AND content_id = ? AND content_type = ?
             LIMIT 1"
        );
        $stmt->execute([$profileId, $contentId, $contentType]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function insertRow(
        int $profileId, int $userId, array $meta,
        bool $saved, bool $liked
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO pip_user_library
                (profile_id, user_id, content_id, content_type, content_title,
                 content_poster, content_backdrop, content_year,
                 is_saved, is_liked, saved_at, liked_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            $profileId,
            $userId,
            $meta['content_id'],
            $meta['content_type'],
            $meta['content_title']    ?? '',
            $meta['content_poster']   ?? '',
            $meta['content_backdrop'] ?? '',
            $meta['content_year']     ?? null,
            $saved ? 1 : 0,
            $liked ? 1 : 0,
            $saved ? $now : null,
            $liked ? $now : null,
        ]);
    }
}
