<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\FamilyBoxService;

class FamilyBoxController
{
    private FamilyBoxService $service;

    public function __construct(FamilyBoxService $service)
    {
        $this->service = $service;
    }

    public function handle(string $action, string $method, int $userId): void
    {
        if ($action === 'box/summary' && $method === 'GET') {
            \ResponseUtil::json($this->service->summary($userId));
        }

        if ($action === 'box/items' && $method === 'GET') {
            \ResponseUtil::json($this->service->inbox($userId));
        }

        if ($action === 'box/read' && $method === 'POST') {
            $data = $this->json();
            \ResponseUtil::json($this->service->markRead($userId, (int) ($data['id'] ?? 0)));
        }

        if ($action === 'box/family/accept' && $method === 'POST') {
            $data = $this->json();
            $result = $this->service->accept($userId, (int) ($data['id'] ?? 0));
            \ResponseUtil::json($result, $result['success'] ? 200 : 400);
        }

        if ($action === 'box/family/decline' && $method === 'POST') {
            $data = $this->json();
            $result = $this->service->decline($userId, (int) ($data['id'] ?? 0));
            \ResponseUtil::json($result, $result['success'] ? 200 : 400);
        }

        if ($action === 'family/dashboard' && $method === 'GET') {
            \ResponseUtil::json($this->service->familyDashboard($userId));
        }

        if ($action === 'family/invite' && $method === 'POST') {
            $data = $this->json();
            $result = $this->service->invite($userId, (string) ($data['email'] ?? ''));
            \ResponseUtil::json($result, $result['success'] ? 201 : 400);
        }

        if ($action === 'family/remove' && $method === 'POST') {
            $data = $this->json();
            $result = $this->service->removeMember($userId, (int) ($data['member_id'] ?? 0));
            \ResponseUtil::json($result, $result['success'] ? 200 : 400);
        }

        \ResponseUtil::json(['success' => false, 'error' => 'Rota da Box nao encontrada'], 404);
    }

    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
