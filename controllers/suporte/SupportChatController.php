<?php

declare(strict_types=1);

namespace Controllers\Suporte;

use Services\Suporte\SupportChatService;
use Helpers\Suporte\SupportSession;

final class SupportChatController
{
    public function __construct(private SupportChatService $chatService) {}

    public function handle(string $action, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            match ($action) {
                'chat/create'         => $this->create($method),
                'chat/status'         => $this->status($method),
                'chat/sync'           => $this->sync($method),
                'chat/read'           => $this->read($method),
                'chat/token-for-user' => $this->tokenForUser($method),
                default               => $this->notFound(),
            };
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function create(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $body    = $this->body();
        $subject = trim(strip_tags($body['subject'] ?? 'Duvida geral'));
        if ($subject === '') $subject = 'Duvida geral';

        $userId    = SupportSession::authenticatedUserId();
        $guestName = trim(strip_tags($body['guest_name'] ?? '')) ?: null;

        $result = $this->chatService->create($subject, $userId, $guestName);

        echo json_encode(['success' => true] + $result);
    }

    private function status(string $method): void
    {
        if ($method !== 'GET') { $this->methodNotAllowed(); return; }

        $token = SupportSession::fromRequest();
        if (!$token) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Token invalido.']); return; }

        $chat = $this->chatService->resolveByToken($token);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        echo json_encode(['success' => true, 'chat' => [
            'id'         => $chat['id'],
            'status'     => $chat['status'],
            'subject'    => $chat['subject'],
            'guest_name' => $chat['guest_name'],
            'unread'     => (int) $chat['unread_user'],
            'created_at' => $chat['created_at'],
        ]]);
    }

    private function sync(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $userId = SupportSession::authenticatedUserId();
        if (!$userId) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Autenticacao necessaria.']); return; }

        $body  = $this->body();
        $token = trim($body['session_token'] ?? '');

        if (!$token || !\Helpers\Suporte\SupportSession::isValid($token)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Token invalido.']);
            return;
        }

        $ok = $this->chatService->syncToUser($token, $userId, SupportSession::displayName());
        echo json_encode(['success' => $ok]);
    }

    private function read(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $token = SupportSession::fromRequest();
        if (!$token) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Token invalido.']); return; }

        $chat = $this->chatService->resolveByToken($token);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        $this->chatService->markRead((int) $chat['id'], 'user');
        echo json_encode(['success' => true]);
    }

    /**
     * GET /api/suporte/chat/token-for-user?chat_id=X
     * Returns the session_token for a chat that belongs to the authenticated user.
     * Used by the client when navigating directly to /suporte?view=chat&id=X without a localStorage token.
     */
    private function tokenForUser(string $method): void
    {
        if ($method !== 'GET') { $this->methodNotAllowed(); return; }

        $userId = SupportSession::authenticatedUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Autenticacao necessaria.']);
            return;
        }

        $chatId = (int) ($_GET['chat_id'] ?? 0);
        if (!$chatId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'chat_id ausente.']);
            return;
        }

        $chat = $this->chatService->resolveByIdAndUser($chatId, $userId);
        if (!$chat) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
            return;
        }

        echo json_encode([
            'success'       => true,
            'session_token' => $chat['session_token'],
            'status'        => $chat['status'],
            'subject'       => $chat['subject'],
        ]);
    }

    private function body(): array
    {
        static $data = null;
        if ($data === null) {
            $raw  = file_get_contents('php://input');
            $data = $raw ? (json_decode($raw, true) ?? $_POST) : $_POST;
        }
        return $data;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint nao encontrado.']);
    }

    private function methodNotAllowed(): void
    {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Metodo nao permitido.']);
    }
}
