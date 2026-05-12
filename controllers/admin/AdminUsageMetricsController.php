<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminUsageMetricsService;

final class AdminUsageMetricsController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminUsageMetricsService $metrics
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        if ($action === 'metrics/usage' && $method === 'GET') {
            $this->json($this->metrics->dashboard((string) ($_GET['range'] ?? '1w')));
        }

        $this->json(['success' => false, 'error' => 'Rota de metricas nao encontrada.'], 404);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
