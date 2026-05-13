<?php

declare(strict_types=1);

namespace Services\Suporte;

use Models\Suporte\SupportChatModel;
use Helpers\Suporte\SupportSession;

final class SupportChatService
{
    public function __construct(private SupportChatModel $chatModel) {}

    /**
     * Create a new support chat.
     * Returns ['chat_id' => int, 'session_token' => string]
     */
    public function create(string $subject, ?int $userId, ?string $guestName): array
    {
        $token = SupportSession::generate();
        $name  = $guestName ?: ($userId ? SupportSession::displayName() : 'Visitante');

        $chatId = $this->chatModel->create([
            'session_token' => $token,
            'user_id'       => $userId,
            'guest_name'    => $name,
            'subject'       => $subject,
            'ip_address'    => SupportSession::clientIp(),
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        return ['chat_id' => $chatId, 'session_token' => $token];
    }

    /** Resolve a chat from a session token; null if not found. */
    public function resolveByToken(string $token): ?array
    {
        return $this->chatModel->findByToken($token);
    }

    /** Resolve a chat by ID; null if not found. */
    public function resolveById(int $id): ?array
    {
        return $this->chatModel->findById($id);
    }

    /** Resolve a chat by ID only if it belongs to the given authenticated user. */
    public function resolveByIdAndUser(int $id, int $userId): ?array
    {
        return $this->chatModel->findByIdAndUserId($id, $userId);
    }

    /**
     * Sync a previously anonymous chat to an authenticated user.
     * Called after login when localStorage contains a session_token.
     */
    public function syncToUser(string $token, int $userId, string $displayName): bool
    {
        $chat = $this->chatModel->findByToken($token);
        if (!$chat) return false;
        if ($chat['user_id']) return true; // already linked

        $this->chatModel->syncUser((int) $chat['id'], $userId, $displayName);
        return true;
    }

    /** Close a chat and record who resolved it. */
    public function close(int $chatId, string $resolvedBy): void
    {
        $this->chatModel->setStatus($chatId, 'closed', $resolvedBy);
    }

    /** Reopen a previously closed chat. */
    public function reopen(int $chatId): void
    {
        $this->chatModel->setStatus($chatId, 'open');
    }

    /** Admin list: all chats with optional status filter. */
    public function listForAdmin(?string $status, int $page = 0): array
    {
        $chats = $this->chatModel->listForAdmin($status, 60, $page * 60);
        $counts = $this->chatModel->countByStatus();
        $unread = $this->chatModel->countUnreadForAdmin();

        return ['chats' => $chats, 'counts' => $counts, 'unread' => $unread];
    }

    /** Mark messages as read for a side (admin or user). */
    public function markRead(int $chatId, string $side): void
    {
        $this->chatModel->resetUnread($chatId, $side);
    }

    /** Admin poll: return updated chat IDs since a given timestamp. */
    public function pollUpdated(string $since): array
    {
        return $this->chatModel->pollUpdated($since);
    }
}
