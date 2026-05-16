<?php
class TMDBHelper {
    private const API_KEY = 'dc6299fd1adb4e32cf16017eecb33295';
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private const CACHE_TTL = 86400;

    /**
     * Static content API helper used by Home rails.
     * Uses cURL multi for cache misses and a file cache for TMDB payloads.
     */
    public static function getRichData(array $items) {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];
        $cacheHits = 0;
        $cacheMisses = 0;

        foreach ($items as $index => $item) {
            if (empty($item['id_tmdb'])) continue;

            $type = ($item['tipo'] === 'serie') ? 'tv' : 'movie';
            $url = self::BASE_URL . "/{$type}/{$item['id_tmdb']}?api_key=" . self::API_KEY . "&language=pt-BR&append_to_response=credits,images,release_dates,content_ratings";
            $cacheKey = self::cacheKey($url);

            $cached = self::readCache($cacheKey);
            if ($cached !== null) {
                $results[$index] = $cached;
                $cacheHits++;
                continue;
            }

            $cacheMisses++;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 3
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = ['handle' => $ch, 'cache_key' => $cacheKey];
        }

        if (!empty($handles)) {
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);
        }

        foreach ($handles as $index => $meta) {
            $ch = $meta['handle'];
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $decoded = json_decode($response ?: '', true);

            if ($httpCode === 200 && is_array($decoded)) {
                $results[$index] = $decoded;
                self::writeCache($meta['cache_key'], $decoded);
            } else {
                $results[$index] = [];
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        self::emitCacheHeader($cacheHits, $cacheMisses);
        return $results;
    }

    public function fetch(string $endpoint): ?array {
        $url = self::BASE_URL . $endpoint . "&api_key=" . self::API_KEY . "&language=pt-BR";
        return self::fetchJsonWithCache($url);
    }

    public function getSeriesInfo(int $tmdbId): ?array {
        return $this->fetch("/tv/{$tmdbId}?");
    }

    public function getEpisodeMetadata(int $tmdbId, int $season, int $episode): ?array {
        return $this->fetch("/tv/{$tmdbId}/season/{$season}/episode/{$episode}?");
    }

    public function getSeasonEpisodes(int $tmdbId, int $season): ?array {
        return $this->fetch("/tv/{$tmdbId}/season/{$season}?");
    }

    public function getContentImages(int $tmdbId, string $type): ?array {
        $mediaType = ($type === 'serie' || $type === 'series' || $type === 'tv') ? 'tv' : 'movie';
        $url = self::BASE_URL . "/{$mediaType}/{$tmdbId}/images?api_key=" . self::API_KEY . "&include_image_language=pt,en,null";

        return self::fetchJsonWithCache($url);
    }

    private static function fetchJsonWithCache(string $url): ?array {
        $cacheKey = self::cacheKey($url);
        $cached = self::readCache($cacheKey);
        if ($cached !== null) {
            self::emitCacheHeader(1, 0);
            return $cached;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::emitCacheHeader(0, 1);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return null;
        }

        self::writeCache($cacheKey, $decoded);
        return $decoded;
    }

    private static function cacheKey(string $url): string {
        return hash('sha256', $url);
    }

    private static function cacheDir(): string {
        $dir = dirname(__DIR__, 2) . '/data/cache/tmdb';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private static function cacheFile(string $key): string {
        return self::cacheDir() . '/' . $key . '.json';
    }

    private static function readCache(string $key): ?array {
        $file = self::cacheFile($key);
        if (!is_file($file) || (filemtime($file) + self::CACHE_TTL) < time()) {
            return null;
        }

        $raw = file_get_contents($file);
        $entry = json_decode($raw ?: '', true);

        if (!is_array($entry) || !array_key_exists('payload', $entry) || !is_array($entry['payload'])) {
            return null;
        }

        return $entry['payload'];
    }

    private static function writeCache(string $key, array $payload): void {
        $file = self::cacheFile($key);
        $tmp = $file . '.' . getmypid() . '.tmp';
        $entry = [
            'created_at' => time(),
            'payload' => $payload,
        ];

        file_put_contents($tmp, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        rename($tmp, $file);
    }

    private static function emitCacheHeader(int $hits, int $misses): void {
        if (headers_sent()) {
            return;
        }

        header('X-TMDB-Cache: hits=' . $hits . '; misses=' . $misses);
    }
}
