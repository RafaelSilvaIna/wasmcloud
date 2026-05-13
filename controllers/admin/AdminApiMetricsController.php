<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminApiMetricsService;

final class AdminApiMetricsController
{
    public function __construct(
        private AdminAuthService     $auth,
        private AdminApiMetricsService $metrics
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        // GET /api/admin/api-metrics/dashboard?range=1w
        if ($action === 'api-metrics/dashboard' && $method === 'GET') {
            $range = (string) ($_GET['range'] ?? '1w');
            $this->json($this->metrics->dashboard($range));
        }

        $this->json(['success' => false, 'error' => 'Rota de metricas de API nao encontrada.'], 404);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
