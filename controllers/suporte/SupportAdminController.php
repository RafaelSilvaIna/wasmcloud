<?php

declare(strict_types=1);

namespace Controllers\Suporte;

use Services\Suporte\SupportChatService;
use Services\Suporte\SupportMessageService;
use Services\Suporte\SupportImageService;

final class SupportAdminController
{
    public function __construct(
        private SupportChatService    $chatService,
        private SupportMessageService $messageService,
        private SupportImageService   $imageService,
        private string                $adminDisplayName
    ) {}

    public function handle(string $action, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Route: admin/chat/{id}/close|reopen  or  admin/chats  etc.
        $parts = explode('/', ltrim($action, '/'));
        // $parts[0] = 'admin', $parts[1] = 'chats'|'chat'|'messages'|'poll'|'stats'

        try {
            $segment = $parts[1] ?? '';

            if ($segment === 'chats' && $method === 'GET') {
                $this->listChats();
                return;
            }

            if ($segment === 'chat' && isset($parts[2])) {
                $chatId = (int) $parts[2];
                $sub    = $parts[3] ?? '';

                match ($sub) {
                    'close'  => $this->closeChat($chatId, $method),
                    'reopen' => $this->reopenChat($chatId, $method),
                    ''       => $this->getChat($chatId, $method),
                    default  => $this->notFound(),
                };
                return;
            }

            if ($segment === 'messages') {
                $sub = $parts[2] ?? '';
                match ($sub) {
                    'send'  => $this->sendMessage($method),
                    'reply' => $this->sendMessage($method),
                    default => $this->notFound(),
                };
                return;
            }

            if ($segment === 'poll' && $method === 'GET') {
                $this->poll();
                return;
            }

            if ($segment === 'stats' && $method === 'GET') {
                $this->stats();
                return;
            }

            $this->notFound();

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // -----------------------------------------------------------------------

    private function listChats(): void
    {
        $status = $_GET['status'] ?? null;
        if ($status && !in_array($status, ['open', 'pending', 'closed'], true)) $status = null;
        $page   = max(0, (int) ($_GET['page'] ?? 0));

        $data = $this->chatService->listForAdmin($status, $page);
        echo json_encode(['success' => true] + $data);
    }

    private function getChat(int $chatId, string $method): void
    {
        if ($method !== 'GET') { $this->methodNotAllowed(); return; }

        $chat = $this->chatService->resolveById($chatId);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        $messages = $this->messageService->fullHistory($chatId);
        $this->chatService->markRead($chatId, 'admin');

        // User details if authenticated
        $userInfo = null;
        if ($chat['user_id']) {
            $userInfo = $this->fetchUserInfo((int) $chat['user_id']);
        }

        // Typing indicator
        global $pdo;
        $typing = false;
        if ($pdo) {
            $s = $pdo->prepare("SELECT 1 FROM support_typing WHERE chat_id = ? AND sender = 'user' AND expires_at > NOW() LIMIT 1");
            $s->execute([$chatId]);
            $typing = (bool) $s->fetchColumn();
        }

        echo json_encode([
            'success'   => true,
            'chat'      => $chat,
            'messages'  => $messages,
            'user_info' => $userInfo,
            'typing'    => $typing,
        ]);
    }

    private function closeChat(int $chatId, string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }
        $chat = $this->chatService->resolveById($chatId);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        $this->chatService->close($chatId, $this->adminDisplayName);

        // Notify user with a system message
        $this->messageService->send(
            $chatId,
            'admin',
            'Sistema',
            'Seu atendimento foi encerrado. Obrigado por entrar em contato com o suporte do Pipocine!',
        );

        echo json_encode(['success' => true]);
    }

    private function reopenChat(int $chatId, string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }
        $chat = $this->chatService->resolveById($chatId);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        $this->chatService->reopen($chatId);

        $this->messageService->send(
            $chatId,
            'admin',
            'Sistema',
            'Seu atendimento foi reaberto. A equipe de suporte ira retornar em breve.',
        );

        echo json_encode(['success' => true]);
    }

    private function sendMessage(string $method): void
    {
        if ($method !== 'POST') { $this->methodNotAllowed(); return; }

        $imageToken = null;
        $plaintext  = '';
        $replyToId  = null;
        $chatId     = 0;

        if (!empty($_FILES['image']['tmp_name'])) {
            $chatId     = (int) ($_POST['chat_id'] ?? 0);
            $plaintext  = trim($_POST['body'] ?? '');
            $replyToId  = isset($_POST['reply_to']) ? (int) $_POST['reply_to'] : null;
            try {
                $imageToken = $this->imageService->upload($_FILES['image'], $chatId);
            } catch (\RuntimeException $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
        } else {
            $body      = $this->body();
            $chatId    = (int) ($body['chat_id'] ?? 0);
            $plaintext = trim($body['body'] ?? '');
            $replyToId = isset($body['reply_to']) ? (int) $body['reply_to'] : null;
        }

        if (!$chatId) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'chat_id obrigatorio.']); return; }

        $chat = $this->chatService->resolveById($chatId);
        if (!$chat) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Chat nao encontrado.']); return; }

        if ($plaintext === '' && $imageToken === null) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Mensagem vazia.']);
            return;
        }

        $msgId = $this->messageService->send($chatId, 'admin', $this->adminDisplayName, $plaintext ?: '[imagem]', $imageToken, $replyToId);

        echo json_encode(['success' => true, 'message_id' => $msgId]);
    }

    private function poll(): void
    {
        $since   = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 10);
        $updated = $this->chatService->pollUpdated($since);
        $counts  = $this->chatService->listForAdmin(null)['counts'];

        echo json_encode([
            'success'         => true,
            'updated_chat_ids' => $updated,
            'counts'          => $counts,
            'server_time'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function stats(): void
    {
        $data = $this->chatService->listForAdmin(null);
        echo json_encode(['success' => true, 'counts' => $data['counts'], 'unread' => $data['unread']]);
    }

    /** Fetch additional user data from platform_users. */
    private function fetchUserInfo(int $userId): ?array
    {
        global $pdo;
        if (!$pdo) return null;
        try {
            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.email, u.phone, u.avatar_url, u.created_at,
                       s.plan_type, s.expires_at AS plan_expires
                FROM platform_users u
                LEFT JOIN platform_subscriptions s ON s.user_id = u.id AND s.status = 'active'
                WHERE u.id = ? LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
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
