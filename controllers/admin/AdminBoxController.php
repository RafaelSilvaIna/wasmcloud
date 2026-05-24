<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminBoxService;

final class AdminBoxController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminBoxService $box
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $admin = $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        if ($action === 'box' && $method === 'GET') {
            $this->json($this->box->dashboard());
        }

        if ($action === 'box/search-users' && $method === 'GET') {
            $this->json($this->box->searchUsers((string) ($_GET['q'] ?? '')));
        }

        if ($action === 'box/send' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $result = $this->box->send((int) $admin['id'], $data);
            $this->json($result, !empty($result['success']) ? 200 : 400);
        }

        $this->json(['success' => false, 'error' => 'Rota da Box administrativa nao encontrada.'], 404);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
