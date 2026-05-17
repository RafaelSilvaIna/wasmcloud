<?php

declare(strict_types=1);

namespace Controllers\Suporte;

use Services\Suporte\SupportMessageService;
use Services\Suporte\SupportChatService;
use Services\Suporte\SupportImageService;
use Helpers\Suporte\SupportSession;
use Helpers\Suporte\SupportRateLimit;

final class SupportMessageController
{
    public function __construct(
        private SupportMessageService $messageService,
        private SupportChatService    $chatService,
        private SupportImageService   $imageService
    ) {}

    public function handle(string $action, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            match ($action) {
                'messages/poll'   => $this->poll($method),
                'messages/send'   => $this->send($method),
                'messages/typing' => $this->typing($method),
                default           => $this->notFound(),
            };
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function poll(string $method): void
    {
        if ($method !== 'GET') { $this->methodNotAllowed(); return; }

        $chat = $this->resolveChat();
        if (!$chat) return;

        $afterId  = (int) ($_GET['after'] ?? 0);
        $messages = $this->messageService->poll((int) $chat['id'], $afterId);

        // Mark as read for user side
        if (!empty($messages)) {
            $this->chatService->markRead((int) $chat['id'], 'user');
        }

        // Typing indicator for user
        $typing = $this->isAdminTyping((int) $chat['id']);

        echo json_encode([
            'success'  => true,
            'messages' => $messages,
            'typing'   => $typing,
            'status'   => $chat['status'],
        ]);
    }

    private function send(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $chat = $this->resolveChat();
        if (!$chat) return;

        if ($chat['status'] === 'closed') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Chat encerrado. Abra um novo atendimento.']);
            return;
        }

        // Rate limit
        $limitKey = SupportSession::fromRequest() ?? SupportSession::clientIp();
        if (!SupportRateLimit::check($limitKey)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Muitas mensagens. Aguarde um momento.']);
            return;
        }

        $chatId      = (int) $chat['id'];
        $senderName  = SupportSession::displayName();
        $userId      = SupportSession::authenticatedUserId();
        // Use user full name if authenticated and chat has it
        if ($userId && $chat['guest_name'] !== 'Visitante') {
            $senderName = $chat['guest_name'];
        }

        $imageToken  = null;
        $plaintext   = '';
        $body        = [];

        // Handle multipart (file upload)
        if (!empty($_FILES['image']['tmp_name'])) {
            try {
                $imageToken = $this->imageService->upload($_FILES['image'], $chatId);
            } catch (\RuntimeException $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            $plaintext = trim($_POST['body'] ?? '');
            $body      = $_POST;
        } else {
            $body      = $this->body();
            $plaintext = trim($body['body'] ?? '');
        }

        if ($plaintext === '' && $imageToken === null) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Mensagem vazia.']);
            return;
        }

        $replyToId = isset($body['reply_to']) ? (int) $body['reply_to'] : null;
        $msgId     = $this->messageService->send($chatId, 'user', $senderName, $plaintext ?: '[imagem]', $imageToken, $replyToId);

        echo json_encode(['success' => true, 'message_id' => $msgId]);
    }

    private function typing(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $chat = $this->resolveChat();
        if (!$chat) return;

        // Insert / replace typing indicator with 5s TTL
        global $pdo;
        if ($pdo) {
            $stmt = $pdo->prepare("
                REPLACE INTO support_typing (chat_id, sender, expires_at)
                VALUES (?, 'user', DATE_ADD(NOW(), INTERVAL 5 SECOND))
            ");
            $stmt->execute([(int) $chat['id']]);
        }

        echo json_encode(['success' => true]);
    }

    // -----------------------------------------------------------------------

    private function resolveChat(): ?array
    {
        $token = SupportSession::fromRequest();
        if (!$token) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Token de sessao ausente.']);
            return null;
        }
        $chat = $this->chatService->resolveByToken($token);
        if (!$chat) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']);
            return null;
        }
        return $chat;
    }

    private function isAdminTyping(int $chatId): bool
    {
        global $pdo;
        if (!$pdo) return false;
        $stmt = $pdo->prepare("
            SELECT 1 FROM support_typing WHERE chat_id = ? AND sender = 'admin' AND expires_at > NOW() LIMIT 1
        ");
        $stmt->execute([$chatId]);
        return (bool) $stmt->fetchColumn();
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

    private function notFound(): void { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Endpoint nao encontrado.']); }
    private function methodNotAllowed(): void { http_response_code(405); echo json_encode(['success' => false, 'error' => 'Metodo nao permitido.']); }
}
