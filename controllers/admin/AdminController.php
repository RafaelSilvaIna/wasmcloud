<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Admin\AdminModel;
use Services\Admin\AdminAuthService;

final class AdminController
{
    public function __construct(private AdminAuthService $auth, private AdminModel $model)
    {
    }

    public function handle(string $action, string $method): void
    {
        if (!$this->auth->isRequestAllowed()) {
            $this->json(['success' => false, 'error' => 'IP nao autorizado.'], 403);
        }

        if ($action === 'auth/login' && $method === 'POST') {
            $data = $this->input();
            $result = $this->auth->login((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));
            $this->json($result, !empty($result['success']) ? 200 : 401);
        }

        if ($action === 'auth/status' && $method === 'GET') {
            $this->json($this->auth->status());
        }

        if ($action === 'auth/logout' && $method === 'POST') {
            $this->json($this->auth->logout());
        }

        if ($action === 'dashboard' && $method === 'GET') {
            try {
                $admin = $this->auth->requireAdmin();
            } catch (\RuntimeException) {
                $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
            }

            $this->json([
                'success' => true,
                'admin' => [
                    'id' => (int) $admin['id'],
                    'email' => $admin['email'],
                    'display_name' => $admin['display_name'] ?? 'Administrador',
                ],
                'stats' => $this->model->dashboardStats(),
            ]);
        }

        $this->json(['success' => false, 'error' => 'Rota admin nao encontrada.'], 404);
    }

    private function input(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
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
