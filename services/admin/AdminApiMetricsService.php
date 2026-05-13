<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminApiMetricsModel;

final class AdminApiMetricsService
{
    public function __construct(private AdminApiMetricsModel $model)
    {
    }

    public function dashboard(string $range): array
    {
        [$since, $bucket] = $this->resolveRange($range);

        $summary     = $this->model->summary($since);
        $series      = $this->model->timeSeries($since, $bucket);
        $endpoints   = $this->model->topEndpoints($since, 15);
        $statusDist  = $this->model->statusDistribution($since);
        $groupDist   = $this->model->groupDistribution($since);
        $topIps      = $this->model->topIps($since, 10);
        $percentiles = $this->model->latencyPercentiles($since);
        $recentErrs  = $this->model->recentErrors($since, 20);
        $realtime    = $this->model->realtime(5);
        $throughput  = $this->model->throughputMinutes(60);

        return [
            'success'       => true,
            'range'         => $range,
            'since'         => $since,
            'summary'       => $summary,
            'series'        => $series,
            'top_endpoints' => $endpoints,
            'status_dist'   => $statusDist,
            'group_dist'    => $groupDist,
            'top_ips'       => $topIps,
            'percentiles'   => $percentiles,
            'recent_errors' => $recentErrs,
            'realtime'      => $realtime,
            'throughput'    => $throughput,
        ];
    }

    private function resolveRange(string $range): array
    {
        $map = [
            '1d' => ['-1 day',    'hour'],
            '5d' => ['-5 days',   'day'],
            '1w' => ['-1 week',   'day'],
            '1m' => ['-1 month',  'day'],
            '2m' => ['-2 months', 'day'],
            '1y' => ['-1 year',   'day'],
        ];

        [$modifier, $bucket] = $map[strtolower(trim($range))] ?? $map['1w'];
        return [date('Y-m-d H:i:s', strtotime($modifier)), $bucket];
    }
}
