<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\Admin\AdminAdsReviewService;
use Services\Admin\AdminAuthService;

final class AdminAdsReviewController
{
    public function __construct(
        private readonly AdminAuthService $auth,
        private readonly AdminAdsReviewService $service
    ) {}

    public function handle(string $action, string $method): void
    {
        try {
            $admin = $this->auth->requireAdmin();
        } catch (\Throwable) {
            $this->json(['success' => false, 'error' => 'Admin não autenticado.'], 401);
        }

        $suffix = trim((string) preg_replace('#^ads-reviews/?#', '', $action), '/');
        if ($suffix === 'board' && $method === 'GET') {
            $this->json($this->service->board((string) ($_GET['filter'] ?? 'queue'), (string) ($_GET['q'] ?? '')));
        }
        if (preg_match('#^(\d+)$#', $suffix, $m) && $method === 'GET') {
            $result = $this->service->detail((int) $m[1]);
            $this->json($result, !empty($result['success']) ? 200 : 404);
        }
        if (preg_match('#^(\d+)/(start|approve|publish|request-changes|reject|pause|resume)$#', $suffix, $m) && $method === 'POST') {
            $data = $this->input();
            $id = (int) $m[1];
            $actionName = $m[2];
            $result = match ($actionName) {
                'start' => $this->service->startReview($id, (int) $admin['id']),
                'approve' => $this->service->approve($id, (int) $admin['id'], (string) ($data['public_note'] ?? ''), (string) ($data['internal_note'] ?? '')),
                'publish' => $this->service->publish($id, (int) $admin['id']),
                'request-changes' => $this->service->requestChanges($id, (int) $admin['id'], (string) ($data['public_note'] ?? ''), (string) ($data['internal_note'] ?? '')),
                'reject' => $this->service->reject($id, (int) $admin['id'], (string) ($data['public_note'] ?? ''), (string) ($data['internal_note'] ?? '')),
                'pause' => $this->service->pause($id, (int) $admin['id'], (string) ($data['public_note'] ?? '')),
                'resume' => $this->service->resume($id, (int) $admin['id'], (string) ($data['public_note'] ?? '')),
            };
            $this->json($result, !empty($result['success']) ? 200 : 400);
        }

        $this->json(['success' => false, 'error' => 'Rota de revisão não encontrada.'], 404);
    }

    private function input(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
