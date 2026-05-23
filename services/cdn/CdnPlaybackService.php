<?php

declare(strict_types=1);

namespace Services\Cdn;

require_once __DIR__ . '/CdnTokenService.php';

final class CdnPlaybackService
{
    private const AUDIO_PROFILES = ['standard', 'smart_eq', 'virtual_surround', 'safe_boost'];

    public function __construct(private ?CdnTokenService $tokens = null)
    {
        $this->tokens ??= new CdnTokenService();
    }

    public function buildUrls(int $id, string $type, int $season, int $episode, string $audio, string $sourceUrl = ''): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'mode' => 'disabled',
                'reason' => 'CDN interna em tempo real desativada. Usando fonte original resolvida.',
            ];
        }

        $mediaType = $this->detectMediaType($sourceUrl);
        $origin = $this->originFromUrl($sourceUrl);

        $claims = [
            'id' => $id,
            'type' => in_array($type, ['serie', 'series', 'tv'], true) ? 'serie' : 'filme',
            's' => max(1, $season),
            'e' => max(1, $episode),
            'audio' => in_array($audio, ['dub', 'leg'], true) ? $audio : 'dub',
            'url' => $sourceUrl,
            'origin' => $origin,
            'media_type' => $mediaType,
        ];

        $videoToken = $this->tokens->issue($claims, 'video');

        if ($this->mode() !== 'legacy_split_ffmpeg') {
            return [
                'enabled' => true,
                'mode' => 'internal_origin_proxy',
                'video_url' => '/video/cdn?token=' . rawurlencode($videoToken),
                'audio_urls' => [],
                'media_type' => $mediaType,
                'expires_in' => $this->tokens->ttl(),
                'expires_at' => time() + $this->tokens->ttl(),
            ];
        }

        $audioUrls = [];

        foreach (self::AUDIO_PROFILES as $profile) {
            $audioUrls[$profile] = '/cdn/audio/' . rawurlencode($profile) . '/' .
                rawurlencode($this->tokens->issue($claims, 'audio', $profile)) . '.m4a';
        }

        return [
            'enabled' => true,
            'mode' => 'internal_realtime_split_mp4',
            'video_url' => '/cdn/video/' . rawurlencode($videoToken) . '.mp4',
            'audio_urls' => $audioUrls,
            'expires_in' => $this->tokens->ttl(),
            'expires_at' => time() + $this->tokens->ttl(),
        ];
    }

    private function isEnabled(): bool
    {
        $value = strtolower(trim((string) getenv('PIPOCINE_CDN_INTERNAL_ENABLED')));
        if ($value === '') {
            return true;
        }

        return !in_array($value, ['0', 'false', 'no', 'off'], true);
    }

    private function mode(): string
    {
        $mode = strtolower(trim((string) getenv('PIPOCINE_CDN_MODE')));
        return $mode === 'legacy_split_ffmpeg' ? $mode : 'internal_origin_proxy';
    }

    private function detectMediaType(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? $url);
        if (str_contains($path, '.m3u8')) return 'm3u8';
        if (str_contains($path, '.mp4')) return 'mp4';
        if (str_contains($path, '.mkv')) return 'mkv';
        if (str_contains($path, '.webm')) return 'webm';
        if (str_contains($path, '.m4a')) return 'm4a';
        if (str_contains($path, '.aac')) return 'aac';
        if (str_contains($path, '.ts')) return 'ts';
        return 'auto';
    }

    private function originFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        return (parse_url($url, PHP_URL_SCHEME) ?: 'https') . '://' . $host;
    }
}
