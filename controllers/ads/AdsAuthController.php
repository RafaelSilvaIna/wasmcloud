<?php
declare(strict_types=1);

namespace Controllers\Ads;

use Services\Ads\AdsAuthService;

final class AdsAuthController
{
    public function __construct(private readonly AdsAuthService $service) {}

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->service->register($data));
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->service->login((string)($data['email'] ?? ''), (string)($data['password'] ?? '')));
    }

    public function link(): void
    {
        $this->json($this->service->linkCurrentToPipocine());
    }

    public function onboarding(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->service->completeOnboarding($data));
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
