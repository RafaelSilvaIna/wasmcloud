<?php
declare(strict_types=1);

namespace Controllers\Ads;

use Services\Ads\AdsCampaignService;

final class AdsCampaignController
{
    public function __construct(private readonly AdsCampaignService $service) {}

    public function list(int $accountId): void
    {
        $this->json(['success' => true, 'campaigns' => $this->service->list($accountId)]);
    }

    public function statusBoard(int $accountId): void
    {
        $this->json($this->service->statusBoard($accountId));
    }

    public function draft(int $accountId): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->service->createDraft($accountId, (string) ($data['creative_type'] ?? '')));
    }

    public function deleteDraft(int $accountId, string $draftToken): void
    {
        $this->json($this->service->deleteDraft($accountId, $draftToken));
    }

    public function uploadImage(int $accountId, string $draftToken): void
    {
        $this->json($this->service->uploadImage($accountId, $draftToken, $_FILES['image'] ?? []));
    }

    public function videoToken(int $accountId, string $draftToken): void
    {
        $this->json($this->service->prepareVideoUpload($accountId, $draftToken));
    }

    public function completeVideo(int $accountId, string $draftToken): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->json($this->service->completeVideoUpload($accountId, $draftToken, $data));
    }

    public function details(int $accountId, string $draftToken): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->service->saveDetails($accountId, $draftToken, $data));
    }

    public function submit(int $accountId, string $draftToken): void
    {
        $this->json($this->service->submit($accountId, $draftToken));
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
