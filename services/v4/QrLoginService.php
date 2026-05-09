<?php

declare(strict_types=1);

namespace Services\V4;

use Models\V4\PlatformUserModel;
use Models\V4\QrLoginModel;

class QrLoginService
{
    private QrLoginModel $qr;
    private PlatformUserModel $users;

    public function __construct(QrLoginModel $qr, PlatformUserModel $users)
    {
        $this->qr = $qr;
        $this->users = $users;
    }

    public function createChallenge(string $baseUrl): array
    {
        $token = bin2hex(random_bytes(32));
        $verifier = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 180);

        if (!$this->qr->createChallenge(hash('sha256', $token), hash('sha256', $verifier), $expiresAt)) {
            return ['success' => false, 'message' => 'Nao foi possivel gerar o QR Code.'];
        }

        setcookie('pipocine_qr_verifier', $verifier, time() + 180, '/', '', false, true);

        return [
            'success' => true,
            'token' => $token,
            'approve_url' => rtrim($baseUrl, '/') . '/login/qrcode/approve?token=' . rawurlencode($token),
            'expires_at' => $expiresAt,
            'expires_in' => 180
        ];
    }

    public function poll(string $token, string $verifier): array
    {
        $challenge = $this->getUsableChallenge($token);
        if (!$challenge) {
            return ['success' => false, 'status' => 'expired', 'message' => 'QR Code expirado.'];
        }

        if (!hash_equals((string) $challenge['verifier_hash'], hash('sha256', $verifier))) {
            return ['success' => false, 'status' => 'denied', 'message' => 'Este navegador nao iniciou o QR Code.'];
        }

        if ($challenge['status'] === 'pending') {
            return ['success' => true, 'status' => 'pending'];
        }

        if ($challenge['status'] !== 'approved' || empty($challenge['approved_user_id']) || empty($challenge['transfer_token_hash'])) {
            return ['success' => false, 'status' => $challenge['status']];
        }

        $user = $this->users->findById((int) $challenge['approved_user_id']);
        if (!$user) {
            return ['success' => false, 'status' => 'denied', 'message' => 'Usuario nao encontrado.'];
        }

        $session = $this->createAuthenticatedSession($user);
        if (!$session['success']) {
            return $session;
        }

        $this->qr->consumeChallenge((int) $challenge['id']);
        $this->qr->log((int) $user['id'], 'login_qrcode', 'success', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
        setcookie('pipocine_qr_verifier', '', time() - 3600, '/', '', false, true);

        return ['success' => true, 'status' => 'authenticated', 'redirect' => '/select-profile'];
    }

    public function approve(string $token, int $userId, string $authProvider): array
    {
        if ($authProvider !== 'pipocine') {
            return ['success' => false, 'message' => 'Entre com uma conta Pipocine para aprovar QR Code.'];
        }

        $settings = $this->qr->getSettings($userId);
        if (!$settings['enabled']) {
            return ['success' => false, 'message' => 'Login por QR Code esta desativado nesta conta.'];
        }

        $challenge = $this->getUsableChallenge($token);
        if (!$challenge || $challenge['status'] !== 'pending') {
            return ['success' => false, 'message' => 'QR Code expirado ou ja utilizado.'];
        }

        $ok = $this->qr->approveChallenge(
            (int) $challenge['id'],
            $userId,
            hash('sha256', bin2hex(random_bytes(32))),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->qr->log($userId, 'approve_qrcode', $ok ? 'success' : 'failed', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');

        return $ok
            ? ['success' => true, 'message' => 'Acesso aprovado. Volte ao outro dispositivo.']
            : ['success' => false, 'message' => 'Nao foi possivel aprovar este QR Code.'];
    }

    public function settings(int $userId): array
    {
        return [
            'success' => true,
            'settings' => $this->qr->getSettings($userId),
            'devices' => $this->qr->getSessions($userId),
            'logs' => $this->qr->getLogs($userId)
        ];
    }

    public function setEnabled(int $userId, bool $enabled): array
    {
        $ok = $this->qr->setEnabled($userId, $enabled);
        $this->qr->log($userId, 'settings_qrcode', $ok ? 'success' : 'failed', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', ['enabled' => $enabled]);

        return ['success' => $ok, 'enabled' => $enabled];
    }

    private function getUsableChallenge(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return null;
        }

        $this->qr->cleanupExpired();
        $challenge = $this->qr->findChallenge(hash('sha256', $token));

        if (!$challenge || !empty($challenge['consumed_at']) || strtotime((string) $challenge['expires_at']) < time()) {
            return null;
        }

        return $challenge;
    }

    private function createAuthenticatedSession(array $user): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);

        if (!$this->users->createSession((int) $user['id'], hash('sha256', $rawToken), $expiresAt)) {
            return ['success' => false, 'message' => 'Erro ao criar sessao.'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['email'] ?: $user['phone'];
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_phone'] = $user['phone'] ?? null;
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['profile_pic_url'] = $user['avatar_url'] ?? null;
        $_SESSION['session_id'] = session_id();
        $_SESSION['auth_provider'] = 'pipocine';

        setcookie('pipocine_token', $rawToken, time() + 2592000, '/', '', false, true);

        return ['success' => true];
    }
}
