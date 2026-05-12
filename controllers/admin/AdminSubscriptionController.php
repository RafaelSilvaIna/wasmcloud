<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminSubscriptionService;

final class AdminSubscriptionController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminSubscriptionService $subscriptions
    ) {
    }

    public function handle(string $action, string $method): void
    {
        try {
            $admin = $this->auth->requireAdmin();
        } catch (\RuntimeException) {
            $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
        }

        if ($action === 'subscriptions' && $method === 'GET') {
            $this->json($this->subscriptions->dashboard());
        }

        if ($action === 'subscriptions/search-users' && $method === 'GET') {
            $this->json($this->subscriptions->searchUsers((string) ($_GET['q'] ?? '')));
        }

        if ($action === 'subscriptions/grant-courtesy' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $this->json($this->subscriptions->grantCourtesy(
                (int) $admin['id'],
                (int) ($data['user_id'] ?? 0),
                (int) ($data['duration_days'] ?? 20),
                (string) ($data['reason'] ?? '')
            ));
        }

        $this->json(['success' => false, 'error' => 'Rota de assinaturas nao encontrada.'], 404);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
