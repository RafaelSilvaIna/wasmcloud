<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminUsageMetricsModel;

final class AdminUsageMetricsService
{
    public function __construct(private AdminUsageMetricsModel $metrics)
    {
        $this->metrics->ensureSchema();
    }

    public function dashboard(string $range): array
    {
        [$since, $bucket] = $this->range($range);

        return [
            'success' => true,
            'range' => $range,
            'since' => $since,
            'summary' => $this->metrics->summary($since),
            'series' => $this->metrics->timeSeries($since, $bucket),
            'top_routes' => $this->metrics->topRoutes($since),
            'realtime' => $this->metrics->realtime(5),
        ];
    }

    private function range(string $range): array
    {
        $range = strtolower(trim($range));
        $map = [
            '1d' => ['-1 day', 'hour'],
            '5d' => ['-5 days', 'day'],
            '1w' => ['-1 week', 'day'],
            '1m' => ['-1 month', 'day'],
            '2m' => ['-2 months', 'day'],
            '1y' => ['-1 year', 'day'],
        ];

        [$modifier, $bucket] = $map[$range] ?? $map['1w'];
        return [date('Y-m-d H:i:s', strtotime($modifier)), $bucket];
    }
}
