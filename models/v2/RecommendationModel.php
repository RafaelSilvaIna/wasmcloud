<?php

class RecommendationModel
{
    public function __construct(
        private PDO $profileDb,
        private PDO $contentDb
    ) {
    }

    public function profileSignals(int $profileId, int $userId): array
    {
        if ($profileId <= 0 && $userId <= 0) {
            return [];
        }

        $signals = [];
        $this->mergeLibrarySignals($signals, $profileId);
        $this->mergeHistorySignals($signals, $profileId);
        $this->mergeProgressSignals($signals, $userId);
        $this->mergeWatchedEpisodeSignals($signals, $profileId);

        if (!$signals) {
            return [];
        }

        $metadata = $this->contentMetadata(array_keys($signals));
        foreach ($signals as $key => &$signal) {
            $signal['meta'] = $metadata[$key] ?? null;
        }
        unset($signal);

        return $signals;
    }

    public function contentMetadata(array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            [$id] = $this->decodeKey($key);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if (!$ids) {
            return [];
        }

        $metadata = [];
        foreach (array_chunk(array_values($ids), 180) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $this->contentDb->prepare("
                SELECT id, id_tmdb, titulo, tipo, generos, nota, data_lancamento
                FROM conteudo
                WHERE id_tmdb IN ({$placeholders})
                  AND id_tmdb IS NOT NULL
                  AND id_tmdb != ''
            ");
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $type = $this->normalizeType((string) ($row['tipo'] ?? ''));
                $metadata[$this->key((int) $row['id_tmdb'], $type)] = $row;
            }
        }

        return $metadata;
    }

    private function mergeLibrarySignals(array &$signals, int $profileId): void
    {
        if ($profileId <= 0) {
            return;
        }

        try {
            $stmt = $this->profileDb->prepare("
                SELECT content_id, content_type, is_saved, is_liked, saved_at, liked_at
                FROM pip_user_library
                WHERE profile_id = ?
                  AND (is_saved = 1 OR is_liked = 1)
                ORDER BY COALESCE(liked_at, saved_at) DESC
                LIMIT 180
            ");
            $stmt->execute([$profileId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $weight = ((int) ($row['is_liked'] ?? 0) === 1 ? 4.5 : 0)
                    + ((int) ($row['is_saved'] ?? 0) === 1 ? 2.4 : 0);
                $date = (string) (($row['liked_at'] ?? null) ?: ($row['saved_at'] ?? ''));
                $this->addSignal($signals, (int) $row['content_id'], (string) $row['content_type'], $weight, 'library', $date);
            }
        } catch (Throwable) {
        }
    }

    private function mergeHistorySignals(array &$signals, int $profileId): void
    {
        if ($profileId <= 0) {
            return;
        }

        try {
            $stmt = $this->profileDb->prepare("
                SELECT content_id, content_type, MAX(watched_at) AS watched_at, COUNT(*) AS watches
                FROM pip_watch_history
                WHERE profile_id = ?
                GROUP BY content_id, content_type
                ORDER BY watched_at DESC
                LIMIT 220
            ");
            $stmt->execute([$profileId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $weight = 1.25 + min(4, (int) ($row['watches'] ?? 1)) * 0.22;
                $this->addSignal($signals, (int) $row['content_id'], (string) $row['content_type'], $weight, 'history', (string) ($row['watched_at'] ?? ''));
            }
        } catch (Throwable) {
        }
    }

    private function mergeProgressSignals(array &$signals, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $stmt = $this->profileDb->prepare("
                SELECT content_id, content_type, MAX(updated_at) AS updated_at,
                       MAX(progress_time) AS progress_time, MAX(duration) AS duration
                FROM watch_progress
                WHERE user_id = ?
                GROUP BY content_id, content_type
                ORDER BY updated_at DESC
                LIMIT 160
            ");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $duration = max(1.0, (float) ($row['duration'] ?? 0));
                $ratio = min(0.95, max(0.0, (float) ($row['progress_time'] ?? 0) / $duration));
                $weight = 1.1 + ($ratio * 1.8);
                $this->addSignal($signals, (int) $row['content_id'], (string) $row['content_type'], $weight, 'progress', (string) ($row['updated_at'] ?? ''));
            }
        } catch (Throwable) {
        }
    }

    private function mergeWatchedEpisodeSignals(array &$signals, int $profileId): void
    {
        if ($profileId <= 0) {
            return;
        }

        try {
            $stmt = $this->profileDb->prepare("
                SELECT serie_id, MAX(watched_at) AS watched_at, COUNT(*) AS episodes
                FROM pip_profile_watched_episodes
                WHERE profile_id = ?
                GROUP BY serie_id
                ORDER BY watched_at DESC
                LIMIT 120
            ");
            $stmt->execute([$profileId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $weight = 1.0 + min(12, (int) ($row['episodes'] ?? 1)) * 0.12;
                $this->addSignal($signals, (int) $row['serie_id'], 'serie', $weight, 'episodes', (string) ($row['watched_at'] ?? ''));
            }
        } catch (Throwable) {
        }
    }

    private function addSignal(array &$signals, int $contentId, string $type, float $weight, string $source, string $date = ''): void
    {
        if ($contentId <= 0 || $weight <= 0) {
            return;
        }

        $key = $this->key($contentId, $this->normalizeType($type));
        $decayed = $weight * $this->recencyDecay($date);

        if (!isset($signals[$key])) {
            $signals[$key] = [
                'content_id' => $contentId,
                'content_type' => $this->normalizeType($type),
                'weight' => 0.0,
                'sources' => [],
                'last_at' => $date,
                'meta' => null,
            ];
        }

        $signals[$key]['weight'] += $decayed;
        $signals[$key]['sources'][$source] = true;
        if ($date !== '' && ($signals[$key]['last_at'] === '' || strtotime($date) > strtotime((string) $signals[$key]['last_at']))) {
            $signals[$key]['last_at'] = $date;
        }
    }

    private function recencyDecay(string $date): float
    {
        $time = strtotime($date);
        if (!$time) {
            return 0.72;
        }

        $days = max(0, (time() - $time) / 86400);
        return max(0.25, 1 / (1 + ($days / 75)));
    }

    private function key(int $id, string $type): string
    {
        return $id . ':' . $this->normalizeType($type);
    }

    private function decodeKey(string $key): array
    {
        [$id, $type] = array_pad(explode(':', $key, 2), 2, 'filme');
        return [(int) $id, $this->normalizeType($type)];
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['serie', 'series', 'tv'], true) ? 'serie' : 'filme';
    }
}
