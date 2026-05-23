<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminPlayerLogModel;

final class AdminPlayerLogService
{
    public function __construct(private AdminPlayerLogModel $logs)
    {
        $this->logs->ensureSchema();
    }

    public function dashboard(string $range): array
    {
        $since = $this->since($range);

        return [
            'success' => true,
            'range' => $range,
            'summary' => $this->logs->summary($since),
            'stages' => $this->logs->byStage($since),
            'logs' => $this->logs->recent($since, 30),
        ];
    }

    public function updateStatus(int $id, string $status): array
    {
        if (!in_array($status, ['open', 'reviewing', 'resolved'], true)) {
            return ['success' => false, 'error' => 'Status invalido.'];
        }

        return ['success' => $this->logs->markStatus($id, $status)];
    }

    private function since(string $range): string
    {
        return match ($range) {
            '1d' => date('Y-m-d H:i:s', strtotime('-1 day')),
            '5d' => date('Y-m-d H:i:s', strtotime('-5 days')),
            '1m' => date('Y-m-d H:i:s', strtotime('-1 month')),
            '2m' => date('Y-m-d H:i:s', strtotime('-2 months')),
            '1y' => date('Y-m-d H:i:s', strtotime('-1 year')),
            default => date('Y-m-d H:i:s', strtotime('-7 days')),
        };
    }
}
