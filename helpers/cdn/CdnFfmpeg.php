<?php

declare(strict_types=1);

namespace Helpers\Cdn;

final class CdnFfmpeg
{
    public static function binary(): ?string
    {
        $configured = trim((string) getenv('FFMPEG_PATH'));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\xampp\\ffmpeg\\bin\\ffmpeg.exe',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) || ($candidate === 'ffmpeg' && self::commandExists($candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    public static function audioFilters(string $profile): array
    {
        return match ($profile) {
            'smart_eq' => [
                'highpass=f=55',
                'bass=f=120:g=1.8',
                'equalizer=f=1600:t=q:w=0.95:g=2.2',
                'treble=f=6800:g=1.4',
                'acompressor=threshold=-18dB:knee=18dB:ratio=3:attack=6:release=240',
                'volume=1.04',
            ],
            'virtual_surround' => [
                'highpass=f=70',
                'treble=f=7200:g=1.2',
                'adelay=18|18',
                'acompressor=threshold=-16dB:knee=20dB:ratio=4:attack=8:release=280',
            ],
            'safe_boost' => [
                'acompressor=threshold=-20dB:knee=24dB:ratio=8:attack=3:release=220',
                'volume=1.45',
            ],
            default => [],
        };
    }

    private static function commandExists(string $command): bool
    {
        $lookup = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';
        $result = [];
        $code = 1;
        @exec($lookup . ' ' . escapeshellarg($command) . ' 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $result, $code);
        return $code === 0 && $result !== [];
    }
}
