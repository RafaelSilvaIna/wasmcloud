<?php

declare(strict_types=1);

namespace Helpers\Player;

final class PlayerCastRegistry
{
    public static function modes(): array
    {
        return [
            [
                'id' => 'tv_standard',
                'label' => 'Transmissao Padrao',
                'tier' => 'free',
                'description' => 'Usa a transmissao nativa do dispositivo quando disponivel.',
                'features' => [],
                'params' => [
                    'profile' => 'compatible',
                    'preferredAudioMode' => 'standard',
                    'preferredDataMode' => 'standard',
                    'targetLatency' => 'normal',
                    'buffer' => 'standard',
                    'hdr' => false,
                    'fps' => 30,
                    'spatialAudio' => false,
                    'reconnect' => false,
                    'reconnectAttempts' => 0,
                ],
            ],
        ];
    }
}
