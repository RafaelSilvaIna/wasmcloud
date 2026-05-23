<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminPlayerLogService;

final class AdminPlayerLogController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminPlayerLogService $logs
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        if ($action === 'player-logs/dashboard' && $method === 'GET') {
            $this->json($this->logs->dashboard((string) ($_GET['range'] ?? '1w')));
        }

        if ($action === 'player-logs/status' && $method === 'POST') {
            $payload = json_decode(file_get_contents('php://input') ?: '', true);
            if (!is_array($payload)) {
                $this->json(['success' => false, 'error' => 'Payload invalido.'], 422);
            }
            $this->json($this->logs->updateStatus((int) ($payload['id'] ?? 0), (string) ($payload['status'] ?? '')));
        }

        $this->json(['success' => false, 'error' => 'Rota de player logs nao encontrada.'], 404);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
