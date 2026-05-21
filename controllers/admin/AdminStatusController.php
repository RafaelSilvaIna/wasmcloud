<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAuthService;
use Services\Admin\AdminStatusService;

final class AdminStatusController
{
    public function __construct(
        private AdminAuthService $auth,
        private AdminStatusService $status
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
            if ($action === 'status/components' && $method === 'GET') {
                $this->json($this->status->components());
            }

            if ($action === 'status/components' && $method === 'POST') {
                $this->json($this->status->saveComponent($this->body()));
            }

            if (preg_match('#^status/components/(\d+)/delete$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->deleteComponent((int) $m[1]));
            }

            if ($action === 'status/incidents' && $method === 'GET') {
                $this->json($this->status->incidents($_GET));
            }

            if ($action === 'status/incidents' && $method === 'POST') {
                $this->json($this->status->createIncident($this->body(), (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)$#', $action, $m) && $method === 'GET') {
                $this->json($this->status->incident((int) $m[1]));
            }

            if (preg_match('#^status/incidents/(\d+)/update$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->updateIncident((int) $m[1], $this->body(), (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/timeline$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->publishUpdate((int) $m[1], $this->body(), (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/status$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->quickStatus((int) $m[1], $this->body(), (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/resolve$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->resolveIncident((int) $m[1], (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/archive$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->archiveIncident((int) $m[1], (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/maintenance$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->convertToMaintenance((int) $m[1], (int) $admin['id']));
            }

            if (preg_match('#^status/incidents/(\d+)/delete$#', $action, $m) && $method === 'POST') {
                $this->json($this->status->deleteIncident((int) $m[1], (int) $admin['id']));
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $this->json(['success' => false, 'error' => 'Rota de status nao encontrada.'], 404);
    }

    private function body(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '', true);
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
