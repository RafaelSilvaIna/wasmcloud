<?php

declare(strict_types=1);

namespace Helpers\Player;

require_once __DIR__ . '/PlayerCastRegistry.php';

final class PlayerFeatureRegistry
{
    public static function build(bool $hasProAccess): array
    {
        return [
            'has_pro_access' => $hasProAccess,
            'audio' => self::withAccess(self::audioModes(), $hasProAccess),
            'data' => self::withAccess(self::dataModes(), $hasProAccess),
            'cast' => self::withAccess(PlayerCastRegistry::modes(), $hasProAccess),
        ];
    }

    private static function withAccess(array $modes, bool $hasProAccess): array
    {
        foreach ($modes as &$mode) {
            $mode['enabled'] = ($mode['tier'] === 'free') || $hasProAccess;
        }
        unset($mode);

        return $modes;
    }

    private static function audioModes(): array
    {
        return [
            [
                'id' => 'standard',
                'label' => 'Audio Padrao',
                'tier' => 'free',
                'description' => 'Som original da fonte, sem processamento.',
                'params' => [
                    'graph' => 'standard',
                    'gain' => 1.0,
                ],
            ],
            [
                'id' => 'smart_eq',
                'label' => 'Audio Equalizado Inteligente',
                'tier' => 'pro',
                'description' => 'Realca dialogos e equilibra graves/agudos.',
                'params' => [
                    'graph' => 'eq',
                    'gain' => 1.04,
                    'filters' => [
                        ['type' => 'highpass', 'frequency' => 55, 'q' => 0.7, 'gain' => 0],
                        ['type' => 'lowshelf', 'frequency' => 120, 'q' => 0.7, 'gain' => 1.8],
                        ['type' => 'peaking', 'frequency' => 1600, 'q' => 0.95, 'gain' => 2.2],
                        ['type' => 'highshelf', 'frequency' => 6800, 'q' => 0.7, 'gain' => 1.4],
                    ],
                    'compressor' => [
                        'threshold' => -18,
                        'knee' => 18,
                        'ratio' => 3,
                        'attack' => 0.006,
                        'release' => 0.24,
                    ],
                ],
            ],
            [
                'id' => 'virtual_surround',
                'label' => 'Audio Surround Virtual',
                'tier' => 'pro',
                'description' => 'Abre a imagem stereo com atraso curto e compressor.',
                'params' => [
                    'graph' => 'surround',
                    'gain' => 1.0,
                    'delay' => 0.018,
                    'crossGain' => 0.22,
                    'filters' => [
                        ['type' => 'highpass', 'frequency' => 70, 'q' => 0.7, 'gain' => 0],
                        ['type' => 'highshelf', 'frequency' => 7200, 'q' => 0.7, 'gain' => 1.2],
                    ],
                    'compressor' => [
                        'threshold' => -16,
                        'knee' => 20,
                        'ratio' => 4,
                        'attack' => 0.008,
                        'release' => 0.28,
                    ],
                ],
            ],
            [
                'id' => 'safe_boost',
                'label' => 'Volume Boost Seguro',
                'tier' => 'pro',
                'description' => 'Aumenta volume com compressao para reduzir distorcao.',
                'params' => [
                    'graph' => 'boost',
                    'gain' => 1.45,
                    'compressor' => [
                        'threshold' => -20,
                        'knee' => 24,
                        'ratio' => 8,
                        'attack' => 0.003,
                        'release' => 0.22,
                    ],
                ],
            ],
        ];
    }

    private static function dataModes(): array
    {
        return [
            [
                'id' => 'standard',
                'label' => 'Padrao',
                'tier' => 'free',
                'description' => 'HLS automatico com buffer equilibrado.',
                'params' => [
                    'strategy' => 'auto',
                    'maxBufferLength' => 30,
                ],
            ],
            [
                'id' => 'low',
                'label' => 'Baixo Consumo',
                'tier' => 'free',
                'description' => 'Economiza dados usando niveis baixos.',
                'params' => [
                    'strategy' => 'low',
                    'maxBufferLength' => 12,
                    'capLevel' => 0,
                ],
            ],
            [
                'id' => 'high',
                'label' => 'Alto Consumo',
                'tier' => 'pro',
                'description' => 'Prioriza o maior nivel disponivel.',
                'params' => [
                    'strategy' => 'highest',
                    'maxBufferLength' => 60,
                ],
            ],
            [
                'id' => 'medium',
                'label' => 'Medio Consumo',
                'tier' => 'pro',
                'description' => 'Boa qualidade com mais tolerancia a oscilacao.',
                'params' => [
                    'strategy' => 'medium',
                    'maxBufferLength' => 40,
                    'capRatio' => 0.66,
                ],
            ],
        ];
    }
}
