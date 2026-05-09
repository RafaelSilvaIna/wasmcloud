<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\PlatformAuthService;

class PlatformAuthController
{
    private PlatformAuthService $service;

    public function __construct(PlatformAuthService $service)
    {
        $this->service = $service;
    }

    public function handle(string $action, string $method): void
    {
        if (($action === 'auth/email' || $action === 'auth/identifier') && $method === 'POST') {
            $this->email();
            return;
        }

        if ($action === 'auth/register' && $method === 'POST') {
            $this->register();
            return;
        }

        if ($action === 'auth/login' && $method === 'POST') {
            $this->login();
            return;
        }

        if ($action === 'auth/verify-2fa' && $method === 'POST') {
            $this->verifyTwoFactor();
            return;
        }

        \ResponseUtil::json(['success' => false, 'error' => 'Rota de autenticacao nao encontrada'], 404);
    }

    private function email(): void
    {
        $data = $this->json();
        $result = $this->service->checkEmail((string) ($data['identifier'] ?? $data['email'] ?? ''));
        \ResponseUtil::json($result);
    }

    private function register(): void
    {
        $data = $this->json();
        $result = $this->service->register(
            (string) ($data['identifier'] ?? $data['email'] ?? ''),
            (string) ($data['password'] ?? ''),
            (string) ($data['password_confirmation'] ?? ''),
            (string) ($data['full_name'] ?? '')
        );

        \ResponseUtil::json($result, $result['success'] ? 201 : 400);
    }

    private function login(): void
    {
        $data = $this->json();
        $result = $this->service->login(
            (string) ($data['identifier'] ?? $data['email'] ?? ''),
            (string) ($data['password'] ?? ''),
            isset($data['device_token']) ? (string) $data['device_token'] : null
        );

        \ResponseUtil::json($result, $result['success'] ? 200 : 401);
    }

    private function verifyTwoFactor(): void
    {
        $data = $this->json();
        $result = $this->service->verifyTwoFactorLogin(
            (string) ($data['verify_token'] ?? $_COOKIE['pipocine_2fa_challenge'] ?? ''),
            (string) ($data['code'] ?? ''),
            !empty($data['remember_device']),
            isset($data['device_token']) ? (string) $data['device_token'] : null
        );

        \ResponseUtil::json($result, $result['success'] ? 200 : (!empty($result['blocked']) ? 429 : 400));
    }

    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
