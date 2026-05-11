<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Admin\AdminModel;
use Services\Admin\AdminAuthService;
use Services\Admin\AdminUserModerationService;

final class AdminController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminModel $model,
        private ?AdminUserModerationService $moderation = null
    )
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

        if ($action === 'session' && $method === 'GET') {
            $sessionInfo = $this->auth->sessionInfo();
            if (!$sessionInfo) {
                $this->json(['success' => false, 'error' => 'Sessao nao encontrada.'], 401);
            }
            $this->json([
                'success' => true,
                'session' => $sessionInfo,
            ]);
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

        if (str_starts_with($action, 'users') && $this->moderation) {
            try {
                $admin = $this->auth->requireAdmin();
            } catch (\RuntimeException) {
                $this->json(['success' => false, 'error' => 'Admin nao autenticado.'], 401);
            }

            if ($action === 'users' && $method === 'GET') {
                $this->json($this->moderation->list((string) ($_GET['q'] ?? '')));
            }

            if (preg_match('/^users\/(\d+)$/', $action, $m) && $method === 'GET') {
                $result = $this->moderation->details((int) $m[1]);
                $this->json($result, !empty($result['success']) ? 200 : 404);
            }

            if (preg_match('/^users\/(\d+)\/suspend$/', $action, $m) && $method === 'POST') {
                $data = $this->input();
                $result = $this->moderation->suspend(
                    (int) $m[1],
                    (int) $admin['id'],
                    (string) ($data['reason'] ?? ''),
                    (int) ($data['duration_minutes'] ?? 0)
                );
                if (!empty($result['success'])) {
                    $this->model->audit((int) $admin['id'], 'admin_user_suspended', $this->auth->requestIp(), $_SERVER['HTTP_USER_AGENT'] ?? '', [
                        'user_id' => (int) $m[1],
                        'reason' => (string) ($data['reason'] ?? ''),
                        'duration_minutes' => (int) ($data['duration_minutes'] ?? 0),
                    ]);
                }
                $this->json($result, !empty($result['success']) ? 200 : 400);
            }

            if (preg_match('/^users\/(\d+)\/ban$/', $action, $m) && $method === 'POST') {
                $data = $this->input();
                $result = $this->moderation->ban((int) $m[1], (int) $admin['id'], (string) ($data['reason'] ?? ''));
                if (!empty($result['success'])) {
                    $this->model->audit((int) $admin['id'], 'admin_user_banned', $this->auth->requestIp(), $_SERVER['HTTP_USER_AGENT'] ?? '', [
                        'user_id' => (int) $m[1],
                        'reason' => (string) ($data['reason'] ?? ''),
                    ]);
                }
                $this->json($result, !empty($result['success']) ? 200 : 400);
            }

            if (preg_match('/^users\/(\d+)\/reactivate$/', $action, $m) && $method === 'POST') {
                $data = $this->input();
                $result = $this->moderation->reactivate((int) $m[1], (int) $admin['id'], (string) ($data['reason'] ?? ''));
                if (!empty($result['success'])) {
                    $this->model->audit((int) $admin['id'], 'admin_user_reactivated', $this->auth->requestIp(), $_SERVER['HTTP_USER_AGENT'] ?? '', [
                        'user_id' => (int) $m[1],
                        'reason' => (string) ($data['reason'] ?? ''),
                    ]);
                }
                $this->json($result, !empty($result['success']) ? 200 : 400);
            }
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
