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
                'label' => 'Transmitir para TV',
                'tier' => 'free',
                'description' => 'Stereo padrao, latencia normal e compatibilidade ampla.',
                'features' => [
                    'Stereo padrao',
                    'Latencia normal',
                    'Buffer comum',
                    'Compatibilidade com TVs basicas',
                    'Sem HDR',
                    'Sem audio espacial',
                ],
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
            [
                'id' => 'smart_cast',
                'label' => 'Smart Cast',
                'tier' => 'pro',
                'description' => 'Mais bitrate, estabilidade e menor delay em TVs compativeis.',
                'features' => [
                    'Bitrate maior',
                    'Menos compressao',
                    'Melhor estabilidade',
                    'Audio equalizado',
                    'Menos delay',
                    'Reconexao automatica',
                ],
                'params' => [
                    'profile' => 'smart',
                    'preferredAudioMode' => 'smart_eq',
                    'preferredDataMode' => 'high',
                    'targetLatency' => 'reduced',
                    'buffer' => 'stable',
                    'hdr' => false,
                    'fps' => 30,
                    'spatialAudio' => false,
                    'reconnect' => true,
                    'reconnectAttempts' => 3,
                ],
            ],
            [
                'id' => 'ultra_cast',
                'label' => 'Ultra Cast',
                'tier' => 'pro',
                'description' => 'HDR/60 FPS quando a fonte e a TV suportarem, com buffer inteligente.',
                'features' => [
                    'HDR quando disponivel',
                    '60 FPS quando disponivel',
                    'Audio espacial',
                    'Dolby-like surround virtual',
                    'Buffer inteligente',
                    'Streaming adaptativo avancado',
                    'Otimizacao para internet lenta',
                ],
                'params' => [
                    'profile' => 'ultra',
                    'preferredAudioMode' => 'virtual_surround',
                    'preferredDataMode' => 'high',
                    'slowNetworkDataMode' => 'medium',
                    'targetLatency' => 'adaptive',
                    'buffer' => 'intelligent',
                    'hdr' => true,
                    'fps' => 60,
                    'spatialAudio' => true,
                    'reconnect' => true,
                    'reconnectAttempts' => 5,
                ],
            ],
        ];
    }
}
