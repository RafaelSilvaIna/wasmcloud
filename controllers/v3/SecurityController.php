<?php
/**
 * CONTROLLER: SecurityController (v3)
 *
 * Endpoints REST:
 *
 *   Código de login (autenticado — exige sessão):
 *     GET  /api/v3/security/code/status     → status do código do usuário
 *     POST /api/v3/security/code/save       → cria/substitui o código
 *     POST /api/v3/security/code/remove     → remove o código
 *
 *   Autenticação por código (público — página de login):
 *     POST /api/v3/auth/code/login          → autentica via código de 4 dígitos
 *
 *   QR Code (público — página de login):
 *     POST /api/v3/auth/qr/generate         → gera nova sessão QR
 *     GET  /api/v3/auth/qr/poll?token=...   → polling de status
 *     POST /api/v3/auth/qr/confirm          → confirma QR (dispositivo autenticado)
 */

declare(strict_types=1);

class SecurityController
{
    private SecurityService $service;

    public function __construct(SecurityService $service)
    {
        $this->service = $service;
    }

    // ──────────────────────────────────────────────────────────────────────
    // UTILITÁRIO: resposta JSON
    // ──────────────────────────────────────────────────────────────────────
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }

    private function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CÓDIGO DE LOGIN — painel de configurações (requer sessão)
    // ──────────────────────────────────────────────────────────────────────

    public function codeStatus(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        $status = $this->service->getCodeStatus($userId);
        $this->json(['success' => true, 'data' => $status]);
    }

    public function codeSave(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        $body = $this->getBody();
        $code = trim($body['code'] ?? '');

        $result = $this->service->saveCode($userId, $code);
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function codeRemove(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        $result = $this->service->removeCode($userId);
        $this->json($result, $result['success'] ? 200 : 404);
    }

    // ──────────────────────────────────────────────────────────────────────
    // AUTENTICAÇÃO POR CÓDIGO — página de login (público)
    // ──────────────────────────────────────────────────────────────────────

    public function codeLogin(): void
    {
        $body = $this->getBody();
        $code = trim($body['code'] ?? '');

        if (!preg_match('/^\d{4}$/', $code)) {
            $this->json(['success' => false, 'message' => 'O código deve ter 4 dígitos numéricos.'], 422);
        }

        $result = $this->service->authenticateByCode(
            $code,
            $this->clientIp(),
            $this->userAgent()
        );

        $this->json($result, $result['success'] ? 200 : 401);
    }

    // ──────────────────────────────────────────────────────────────────────
    // QR CODE — endpoints públicos
    // ──────────────────────────────────────────────────────────────────────

    public function qrGenerate(): void
    {
        $result = $this->service->generateQrSession(
            $this->clientIp(),
            $this->userAgent()
        );
        $this->json($result);
    }

    public function qrPoll(): void
    {
        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            $this->json(['success' => false, 'message' => 'Token obrigatório.'], 400);
        }

        $result = $this->service->pollQrSession($token);
        $this->json($result);
    }

    public function qrConfirm(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Você precisa estar autenticado para confirmar o QR.'], 401);
        }

        $body  = $this->getBody();
        $token = trim($body['token'] ?? '');

        if (empty($token)) {
            $this->json(['success' => false, 'message' => 'Token obrigatório.'], 400);
        }

        $result = $this->service->confirmQrSession($token, $userId, $this->clientIp());
        $this->json($result, $result['success'] ? 200 : 400);
    }
}
