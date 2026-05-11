<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\SubscriptionService;

class SubscriptionController
{
    private SubscriptionService $service;

    public function __construct(SubscriptionService $service)
    {
        $this->service = $service;
    }

    public function handle(string $action, string $method, int $userId): void
    {
        if ($action === 'subscription/state' && $method === 'GET') {
            \ResponseUtil::json($this->service->state($userId));
        }

        if ($action === 'subscription/checkout' && $method === 'POST') {
            \ResponseUtil::json($this->service->createCheckout($userId, $this->json(), $this->baseUrl()));
        }

        if ($action === 'subscription/payment-status' && $method === 'GET') {
            $paymentId = (int) ($_GET['payment_id'] ?? 0);
            \ResponseUtil::json($this->service->paymentStatus($userId, $paymentId, $this->sessionHash()));
        }

        if ($action === 'subscription/cancel' && $method === 'POST') {
            $data = $this->json();
            \ResponseUtil::json($this->service->cancel($userId, (int) ($data['payment_id'] ?? 0)));
        }

        if ($action === 'subscription/activate' && $method === 'POST') {
            $data = $this->json();
            \ResponseUtil::json($this->service->activate($userId, (string) ($data['token'] ?? ''), $this->sessionHash()));
        }

        \ResponseUtil::json(['success' => false, 'error' => 'Rota de assinatura nao encontrada'], 404);
    }

    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }

    private function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    private function sessionHash(): string
    {
        return hash('sha256', session_id() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
