<?php

class RecommendationService
{
    private const PROFILE_CACHE_TTL = 90;
    private const MAX_PROFILE_SIGNALS = 600;

    private ?array $profileVector = null;

    public function __construct(private RecommendationModel $model)
    {
    }

    public function rerankCandidates(array $items, array $context = []): array
    {
        if (count($items) < 2) {
            return $items;
        }

        $items = $this->attachCandidateMetadata($items);
        $vector = $this->profile();
        $hasProfile = ((int) ($vector['signal_count'] ?? 0)) > 0;
        $scored = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $score = $this->baseScore($item, $index);
            if ($hasProfile) {
                $score += $this->personalScore($item, $vector);
                $score += $this->positiveAffinityBonus($item, $vector);
                $score -= $this->seenPenalty($item, $vector, $context);
            }

            $score += $this->deterministicJitter($item, $vector, $context);
            $scored[] = [
                'score' => $score,
                'index' => $index,
                'item' => $item,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            $scoreCmp = $b['score'] <=> $a['score'];
            return $scoreCmp !== 0 ? $scoreCmp : ($a['index'] <=> $b['index']);
        });

        return $this->diversify($scored, $context);
    }

    private function attachCandidateMetadata(array $items): array
    {
        $keys = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ($this->hasUsefulMetadata($item)) {
                continue;
            }

            $key = $this->itemKey($item);
            if ($key !== '') {
                $keys[$key] = $key;
            }
        }

        if (!$keys) {
            return $items;
        }

        $metadata = $this->model->contentMetadata(array_values($keys));
        if (!$metadata) {
            return $items;
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = $this->itemKey($item);
            if ($key === '' || empty($metadata[$key])) {
                continue;
            }

            $meta = $metadata[$key];
            foreach (['generos', 'data_lancamento', 'nota', 'tipo', 'titulo'] as $field) {
                if (empty($items[$index][$field]) && isset($meta[$field])) {
                    $items[$index][$field] = $meta[$field];
                }
            }
        }

        return $items;
    }

    private function hasUsefulMetadata(array $item): bool
    {
        return !empty($item['generos'])
            && ($this->itemYear($item) > 0 || $this->itemVote($item) > 0);
    }

    public function isPersonalized(): bool
    {
        return ((int) ($this->profile()['signal_count'] ?? 0)) > 0;
    }

    private function profile(): array
    {
        if ($this->profileVector !== null) {
            return $this->profileVector;
        }

        $profileId = (int) ($_SESSION['profile_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $cacheKey = 'piprec:v2:' . $profileId . ':' . $userId;

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $hit);
            if ($hit && is_array($cached)) {
                $this->profileVector = $cached;
                return $this->profileVector;
            }
        }

        $signals = $this->model->profileSignals($profileId, $userId);
        if (count($signals) > self::MAX_PROFILE_SIGNALS) {
            uasort($signals, static fn(array $a, array $b): int => ((float) ($b['weight'] ?? 0)) <=> ((float) ($a['weight'] ?? 0)));
            $signals = array_slice($signals, 0, self::MAX_PROFILE_SIGNALS, true);
        }

        $this->profileVector = $this->buildProfile($signals, $profileId, $userId);

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $this->profileVector, self::PROFILE_CACHE_TTL);
        }

        return $this->profileVector;
    }

    private function buildProfile(array $signals, int $profileId, int $userId): array
    {
        $vector = [
            'profile_id' => $profileId,
            'user_id' => $userId,
            'signal_count' => count($signals),
            'genres' => [],
            'types' => [],
            'decades' => [],
            'seen' => [],
            'positive' => [],
            'max_genre' => 1.0,
            'max_type' => 1.0,
            'max_decade' => 1.0,
        ];

        foreach ($signals as $signal) {
            $weight = max(0.05, (float) ($signal['weight'] ?? 0));
            $meta = is_array($signal['meta'] ?? null) ? $signal['meta'] : [];
            $type = $this->normalizeType((string) ($meta['tipo'] ?? $signal['content_type'] ?? 'filme'));
            $key = $this->contentKey((int) ($signal['content_id'] ?? 0), $type);
            $sources = is_array($signal['sources'] ?? null) ? $signal['sources'] : [];

            if ($key !== '') {
                $vector['seen'][$key] = true;
                if (isset($sources['library'])) {
                    $vector['positive'][$key] = true;
                }
            }

            $vector['types'][$type] = ($vector['types'][$type] ?? 0.0) + ($weight * 0.65);

            $year = $this->itemYear($meta);
            if ($year > 0) {
                $decade = (string) (intdiv($year, 10) * 10);
                $vector['decades'][$decade] = ($vector['decades'][$decade] ?? 0.0) + ($weight * 0.28);
            }

            foreach ($this->extractGenres($meta) as $genre) {
                $vector['genres'][$genre] = ($vector['genres'][$genre] ?? 0.0) + $weight;
            }
        }

        $vector['max_genre'] = max(1.0, ...array_values($vector['genres'] ?: [1.0]));
        $vector['max_type'] = max(1.0, ...array_values($vector['types'] ?: [1.0]));
        $vector['max_decade'] = max(1.0, ...array_values($vector['decades'] ?: [1.0]));

        return $vector;
    }

    private function baseScore(array $item, int $index): float
    {
        $vote = min(10.0, max(0.0, $this->itemVote($item)));
        $quality = $vote > 0 ? $vote / 10 : 0.48;
        $year = $this->itemYear($item);
        $recency = $year > 0 ? min(1.0, max(0.05, ($year - 1985) / 45)) : 0.35;
        $rank = 1 / sqrt($index + 1);

        return ($quality * 0.44) + ($recency * 0.18) + ($rank * 0.18);
    }

    private function personalScore(array $item, array $vector): float
    {
        $genres = $this->extractGenres($item);
        $genreScore = 0.0;
        foreach ($genres as $genre) {
            $genreScore += (float) ($vector['genres'][$genre] ?? 0.0);
        }
        $genreScore = min(1.5, $genreScore / max(1.0, (float) $vector['max_genre']));

        $type = $this->normalizeType((string) ($item['tipo'] ?? $item['type'] ?? $item['media_type'] ?? 'filme'));
        $typeScore = min(1.0, ((float) ($vector['types'][$type] ?? 0.0)) / max(1.0, (float) $vector['max_type']));

        $decadeScore = 0.0;
        $year = $this->itemYear($item);
        if ($year > 0) {
            $decade = (string) (intdiv($year, 10) * 10);
            $decadeScore = min(1.0, ((float) ($vector['decades'][$decade] ?? 0.0)) / max(1.0, (float) $vector['max_decade']));
        }

        return ($genreScore * 0.42) + ($typeScore * 0.16) + ($decadeScore * 0.07);
    }

    private function positiveAffinityBonus(array $item, array $vector): float
    {
        $key = $this->itemKey($item);
        if ($key === '' || empty($vector['positive'][$key])) {
            return 0.0;
        }

        return 0.06;
    }

    private function seenPenalty(array $item, array $vector, array $context): float
    {
        $key = $this->itemKey($item);
        if ($key === '' || empty($vector['seen'][$key])) {
            return 0.0;
        }

        $route = (string) ($context['route'] ?? '');
        $category = (string) ($context['category'] ?? '');
        if ($route === 'info') {
            return 0.04;
        }

        if ($category === 'recomendados') {
            return !empty($vector['positive'][$key]) ? 0.10 : 0.32;
        }

        return !empty($vector['positive'][$key]) ? 0.06 : 0.18;
    }

    private function diversify(array $scored, array $context): array
    {
        $limit = max(1, (int) ($context['limit'] ?? count($scored)));
        $shouldDiversify = in_array((string) ($context['route'] ?? ''), ['trending', 'platform', 'info'], true)
            || (string) ($context['category'] ?? '') === 'recomendados';

        if (!$shouldDiversify || count($scored) <= 8) {
            return array_map(static fn(array $row): array => $row['item'], $scored);
        }

        $maxPerGenre = max(3, (int) ceil($limit / 3));
        $result = [];
        $deferred = [];
        $genreCounts = [];

        foreach ($scored as $row) {
            $item = $row['item'];
            $genres = $this->extractGenres($item);
            $primary = $genres[0] ?? 'semgenero';

            if (count($result) < 2 || ($genreCounts[$primary] ?? 0) < $maxPerGenre) {
                $result[] = $item;
                $genreCounts[$primary] = ($genreCounts[$primary] ?? 0) + 1;
                continue;
            }

            $deferred[] = $item;
        }

        return array_merge($result, $deferred);
    }

    private function deterministicJitter(array $item, array $vector, array $context): float
    {
        $seed = implode('|', [
            (string) ($vector['profile_id'] ?? 0),
            (string) ($vector['user_id'] ?? 0),
            $this->itemKey($item),
            (string) ($context['route'] ?? ''),
            (string) ($context['category'] ?? ''),
        ]);

        $hash = (int) sprintf('%u', crc32($seed));
        return (($hash % 1000) / 1000) * 0.015;
    }

    private function itemKey(array $item): string
    {
        $id = (int) ($item['id_tmdb'] ?? $item['tmdb_id'] ?? $item['content_id'] ?? 0);
        if ($id <= 0) {
            return '';
        }

        $type = $this->normalizeType((string) ($item['tipo'] ?? $item['type'] ?? $item['content_type'] ?? 'filme'));
        return $this->contentKey($id, $type);
    }

    private function contentKey(int $id, string $type): string
    {
        if ($id <= 0) {
            return '';
        }

        return $id . ':' . $this->normalizeType($type);
    }

    private function extractGenres(array $item): array
    {
        $value = $item['generos'] ?? $item['genres'] ?? null;
        $raw = [];

        if (is_string($value)) {
            $raw = preg_split('/[,;|\/]+/', $value) ?: [];
        } elseif (is_array($value)) {
            foreach ($value as $genre) {
                if (is_array($genre)) {
                    $raw[] = (string) ($genre['name'] ?? $genre['nome'] ?? '');
                } else {
                    $raw[] = (string) $genre;
                }
            }
        }

        $genres = [];
        foreach ($raw as $genre) {
            $canonical = $this->canonicalGenre((string) $genre);
            if ($canonical !== '') {
                $genres[$canonical] = $canonical;
            }
        }

        return array_values($genres);
    }

    private function canonicalGenre(string $genre): string
    {
        $value = $this->normalizeText($genre);
        if ($value === '') {
            return '';
        }

        $map = [
            'animacao' => ['animacao', 'animation', 'anime'],
            'familia' => ['familia', 'family'],
            'comedia' => ['comedia', 'comedy'],
            'acao' => ['acao', 'action'],
            'aventura' => ['aventura', 'adventure'],
            'fantasia' => ['fantasia', 'fantasy'],
            'ficcao' => ['ficcao', 'sciencefiction', 'sci-fi', 'scifi'],
            'terror' => ['terror', 'horror'],
            'suspense' => ['suspense', 'thriller'],
            'romance' => ['romance'],
            'drama' => ['drama'],
            'crime' => ['crime'],
            'documentario' => ['documentario', 'documentary'],
            'misterio' => ['misterio', 'mystery'],
            'musica' => ['musica', 'music'],
            'guerra' => ['guerra', 'war'],
            'historia' => ['historia', 'history'],
        ];

        foreach ($map as $canonical => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    return $canonical;
                }
            }
        }

        return $value;
    }

    private function itemVote(array $item): float
    {
        foreach (['nota', 'vote', 'vote_average'] as $field) {
            if (isset($item[$field]) && is_numeric($item[$field])) {
                return (float) $item[$field];
            }
        }

        return 0.0;
    }

    private function itemYear(array $item): int
    {
        foreach (['data_lancamento', 'release_date', 'first_air_date', 'ano', 'content_year'] as $field) {
            $value = trim((string) ($item[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (preg_match('/(19|20)\d{2}/', $value, $match)) {
                return (int) $match[0];
            }
        }

        return 0;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['serie', 'series', 'tv'], true) ? 'serie' : 'filme';
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
    }
}
