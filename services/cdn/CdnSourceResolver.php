<?php

declare(strict_types=1);

namespace Services\Cdn;

final class CdnSourceResolver
{
    public function __construct(private ?\PDO $pdoCineveo)
    {
    }

    public function resolve(array $claims): array
    {
        if (!empty($claims['url'])) {
            $url = $this->sanitize((string) $claims['url']);
            if ($url === '') {
                throw new \RuntimeException('Fonte de midia vazia.');
            }

            return [
                'url' => $url,
                'media_type' => strtolower((string) ($claims['media_type'] ?? $this->detectMediaType($url))),
                'audio' => strtolower((string) ($claims['audio'] ?? '')),
                'origin' => (string) ($claims['origin'] ?? $this->originFromUrl($url)),
            ];
        }

        if (!$this->pdoCineveo) {
            throw new \RuntimeException('Banco cineveo indisponivel.');
        }

        $id = (int) ($claims['id'] ?? 0);
        $type = strtolower((string) ($claims['type'] ?? 'filme'));
        $season = max(1, (int) ($claims['s'] ?? 1));
        $episode = max(1, (int) ($claims['e'] ?? 1));
        $audio = strtolower((string) ($claims['audio'] ?? 'dub'));
        $isSerie = in_array($type, ['serie', 'series', 'tv'], true);

        $url = '';
        $audioUsed = $audio;

        if ($audio === 'dub') {
            $url = $this->lookupDubbed($id, $isSerie, $season, $episode);
        }

        if ($audio === 'leg' || ($audio === 'dub' && $url === '')) {
            $legendUrl = $this->lookupSubtitled($id, $isSerie, $season, $episode);
            if ($legendUrl !== '') {
                $url = $legendUrl;
                $audioUsed = 'leg';
            }
        }

        if ($url === '') {
            throw new \RuntimeException('Midia indisponivel.');
        }

        $url = $this->sanitize($url);
        $origin = $this->originFromUrl($url);
        $url = $this->resolveRedirect($url);

        return [
            'url' => $url,
            'media_type' => $this->detectMediaType($url),
            'audio' => $audioUsed,
            'origin' => $origin,
        ];
    }

    private function lookupDubbed(int $id, bool $isSerie, int $season, int $episode): string
    {
        if ($isSerie) {
            $stmt = $this->pdoCineveo->prepare(
                "SELECT url_video FROM links
                 WHERE id_tmdb = ? AND temporada = ? AND episodio = ?
                   AND tipo_conteudo IN ('serie','series','tv')
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $season, $episode]);
            return (string) ($stmt->fetchColumn() ?: '');
        }

        $stmt = $this->pdoCineveo->prepare(
            "SELECT url_video FROM links
             WHERE id_tmdb = ?
               AND tipo_conteudo = 'filme'
               AND url_video IS NOT NULL AND url_video != ''
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    private function lookupSubtitled(int $id, bool $isSerie, int $season, int $episode): string
    {
        if ($isSerie) {
            $stmt = $this->pdoCineveo->prepare(
                "SELECT url_video FROM links_legendados
                 WHERE id_tmdb = ? AND temporada = ? AND episodio = ?
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $season, $episode]);
            return (string) ($stmt->fetchColumn() ?: '');
        }

        $stmt = $this->pdoCineveo->prepare(
            "SELECT url_video FROM links_legendados
             WHERE id_tmdb = ?
               AND url_video IS NOT NULL AND url_video != ''
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    private function sanitize(string $url): string
    {
        $url = trim($url);
        $url = preg_replace('/\s+exist$/i', '', $url);
        $url = preg_replace('/\.mp4\.mp4$/i', '.mp4', $url);
        return trim((string) $url);
    }

    private function detectMediaType(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? $url);
        if (str_contains($path, '.m3u8')) return 'm3u8';
        if (str_contains($path, '.mp4')) return 'mp4';
        if (str_contains($path, '.mkv')) return 'mkv';
        if (str_contains($path, '.webm')) return 'webm';
        return 'auto';
    }

    private function resolveRedirect(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $needsResolve = $host === 'hubby.cx' || $host === 'hub.cx' || str_ends_with($host, '.hubby.cx') || str_ends_with($host, '.hub.cx');
        if (!$needsResolve || !function_exists('curl_init')) {
            return $url;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'PipocineMediaProxy/1.0 (+https://pipocine.site)',
            CURLOPT_HTTPHEADER => [
                'Range: bytes=0-0',
                'Accept: video/mp4,video/*;q=0.9,*/*;q=0.8',
            ],
        ]);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_errno($ch);
        curl_close($ch);

        return $error || empty($finalUrl) ? $url : $finalUrl;
    }

    private function originFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        return $scheme . '://' . $host;
    }
}
