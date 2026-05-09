<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\QrLoginService;

class QrLoginController
{
    private QrLoginService $service;

    public function __construct(QrLoginService $service)
    {
        $this->service = $service;
    }

    public function handle(string $action, string $method): void
    {
        if ($action === 'qr-login/create' && $method === 'POST') {
            \ResponseUtil::json($this->service->createChallenge($this->baseUrl()));
        }

        if ($action === 'qr-login/poll' && $method === 'POST') {
            $data = $this->json();
            \ResponseUtil::json($this->service->poll((string) ($data['token'] ?? ''), (string) ($_COOKIE['pipocine_qr_verifier'] ?? '')));
        }

        if ($action === 'qr-login/approve' && $method === 'POST') {
            $this->requireAuth();
            $data = $this->json();
            \ResponseUtil::json($this->service->approve((string) ($data['token'] ?? ''), (int) $_SESSION['user_id'], (string) ($_SESSION['auth_provider'] ?? '')));
        }

        if ($action === 'qr-login/settings' && $method === 'GET') {
            $this->requireAuth();
            \ResponseUtil::json($this->service->settings((int) $_SESSION['user_id']));
        }

        if ($action === 'qr-login/settings' && $method === 'POST') {
            $this->requireAuth();
            $data = $this->json();
            \ResponseUtil::json($this->service->setEnabled((int) $_SESSION['user_id'], !empty($data['enabled'])));
        }

        \ResponseUtil::json(['success' => false, 'error' => 'Rota QR Code nao encontrada'], 404);
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            \ResponseUtil::json(['success' => false, 'error' => 'Usuario nao autenticado'], 401);
        }
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
}
