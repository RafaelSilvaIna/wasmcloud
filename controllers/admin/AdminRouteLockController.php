<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminRouteLockService;

final class AdminRouteLockController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminRouteLockService $routes
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $admin = $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        try {
            if ($action === 'route-locks/routes' && $method === 'GET') {
                $this->json($this->routes->routes((string) ($_GET['q'] ?? '')));
            }

            if ($action === 'route-locks/logs' && $method === 'GET') {
                $this->json($this->routes->logs((string) ($_GET['range'] ?? '1d'), (int) ($_GET['limit'] ?? 80)));
            }

            if ($action === 'route-locks/lock' && $method === 'POST') {
                $this->json($this->routes->lock($this->body(), (int) $admin['id']));
            }

            if ($action === 'route-locks/unlock' && $method === 'POST') {
                $this->json($this->routes->unlock($this->body(), (int) $admin['id']));
            }

            if ($action === 'route-locks/delete' && $method === 'POST') {
                $this->json($this->routes->delete($this->body()));
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $this->json(['success' => false, 'error' => 'Rota de manutencao nao encontrada.'], 404);
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
