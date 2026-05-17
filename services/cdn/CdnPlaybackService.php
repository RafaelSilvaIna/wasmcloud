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

        $blockReason = $this->blockedSourceReason($sourceUrl);
        if ($blockReason !== null) {
            return [
                'enabled' => false,
                'mode' => 'source_passthrough',
                'reason' => $blockReason,
            ];
        }

        $claims = [
            'id' => $id,
            'type' => in_array($type, ['serie', 'series', 'tv'], true) ? 'serie' : 'filme',
            's' => max(1, $season),
            'e' => max(1, $episode),
            'audio' => in_array($audio, ['dub', 'leg'], true) ? $audio : 'dub',
        ];

        $videoToken = $this->tokens->issue($claims, 'video');
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
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function blockedSourceReason(string $sourceUrl): ?string
    {
        $host = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));
        if ($host === '') {
            return 'Fonte sem host valido. Usando fonte original resolvida.';
        }

        $blockedHosts = [
            'fontedecanais',
            '58ioec50wtok71.com',
        ];

        foreach ($blockedHosts as $needle) {
            if (str_contains($host, $needle)) {
                return 'Fonte bloqueia acesso server-side/FFmpeg com 403. Usando fonte original resolvida.';
            }
        }

        return null;
    }
}
