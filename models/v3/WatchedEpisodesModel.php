<?php

declare(strict_types=1);

namespace Models\V3;

use PDO;

class WatchedEpisodesModel
{
    public function __construct(private PDO $dbPipocine) {}

    public function toggle(int $profileId, int $userId, int $serieId, int $season, int $episode): array
    {
        $exists = $this->existsPipocine($profileId, $serieId, $season, $episode);

        if ($exists) {
            $this->deletePipocine($profileId, $serieId, $season, $episode);
            return ['watched' => false];
        }

        $this->upsertPipocine($profileId, $userId, $serieId, $season, $episode);
        return ['watched' => true];
    }

    public function mark(int $profileId, int $userId, int $serieId, int $season, int $episode): array
    {
        $this->upsertPipocine($profileId, $userId, $serieId, $season, $episode);
        return ['watched' => true];
    }

    public function unmark(int $profileId, int $serieId, int $season, int $episode): array
    {
        $this->deletePipocine($profileId, $serieId, $season, $episode);
        return ['watched' => false];
    }

    public function getMap(int $profileId, int $serieId, int $season): array
    {
        $stmt = $this->dbPipocine->prepare(
            'SELECT episode FROM pip_profile_watched_episodes WHERE profile_id = ? AND serie_id = ? AND season = ?'
        );
        $stmt->execute([$profileId, $serieId, $season]);

        $out = [];
        foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $out[(int) $row['episode']] = true;
        }
        return $out;
    }

    public function isWatched(int $profileId, int $serieId, int $season, int $episode): bool
    {
        return $this->existsPipocine($profileId, $serieId, $season, $episode);
    }

    private function existsPipocine(int $profileId, int $serieId, int $season, int $episode): bool
    {
        $stmt = $this->dbPipocine->prepare(
            'SELECT 1 FROM pip_profile_watched_episodes WHERE profile_id = ? AND serie_id = ? AND season = ? AND episode = ? LIMIT 1'
        );
        $stmt->execute([$profileId, $serieId, $season, $episode]);
        return (bool) $stmt->fetchColumn();
    }

    private function upsertPipocine(int $profileId, int $userId, int $serieId, int $season, int $episode): void
    {
        $stmt = $this->dbPipocine->prepare(
            'INSERT INTO pip_profile_watched_episodes (profile_id, user_id, serie_id, season, episode)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE watched_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$profileId, $userId, $serieId, $season, $episode]);
    }

    private function deletePipocine(int $profileId, int $serieId, int $season, int $episode): void
    {
        $stmt = $this->dbPipocine->prepare(
            'DELETE FROM pip_profile_watched_episodes WHERE profile_id = ? AND serie_id = ? AND season = ? AND episode = ? LIMIT 1'
        );
        $stmt->execute([$profileId, $serieId, $season, $episode]);
    }
}
