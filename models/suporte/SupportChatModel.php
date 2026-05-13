<?php

declare(strict_types=1);

namespace Models\Suporte;

final class SupportChatModel
{
    public function __construct(private \PDO $db) {}

    /** Create a new chat, returns the new row id. */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO support_chats
                (session_token, user_id, guest_name, subject, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['session_token'],
            $data['user_id'],
            $data['guest_name']  ?? 'Visitante',
            $data['subject']     ?? 'Duvida geral',
            $data['ip_address']  ?? '',
            substr($data['user_agent'] ?? '', 0, 512),
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Find chat by session_token. */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM support_chats WHERE session_token = ? LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /** Find chat by ID. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM support_chats WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Find chat by ID belonging to a specific authenticated user. */
    public function findByIdAndUserId(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM support_chats WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Assign an authenticated user_id to a previously anonymous chat. */
    public function syncUser(int $chatId, int $userId, string $guestName): void
    {
        $stmt = $this->db->prepare("
            UPDATE support_chats SET user_id = ?, guest_name = ? WHERE id = ?
        ");
        $stmt->execute([$userId, $guestName, $chatId]);
    }

    /** Set chat status: open / pending / closed. */
    public function setStatus(int $chatId, string $status, ?string $resolvedBy = null): void
    {
        if ($status === 'closed') {
            $stmt = $this->db->prepare("
                UPDATE support_chats
                SET status = 'closed', resolved_at = NOW(), resolved_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$resolvedBy, $chatId]);
        } elseif ($status === 'open') {
            $stmt = $this->db->prepare("
                UPDATE support_chats
                SET status = 'open', reopened_at = NOW(), resolved_at = NULL, resolved_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$chatId]);
        } else {
            $stmt = $this->db->prepare("UPDATE support_chats SET status = ? WHERE id = ?");
            $stmt->execute([$status, $chatId]);
        }
    }

    /** Increment unread counter for a given side (admin or user). */
    public function incrementUnread(int $chatId, string $side): void
    {
        $col = $side === 'admin' ? 'unread_admin' : 'unread_user';
        $this->db->prepare("
            UPDATE support_chats SET {$col} = {$col} + 1, last_message_at = NOW()
            WHERE id = ?
        ")->execute([$chatId]);
    }

    /** Reset unread counter for a given side. */
    public function resetUnread(int $chatId, string $side): void
    {
        $col = $side === 'admin' ? 'unread_admin' : 'unread_user';
        $this->db->prepare("UPDATE support_chats SET {$col} = 0 WHERE id = ?")->execute([$chatId]);
    }

    /** List chats for admin panel with optional status filter. */
    public function listForAdmin(?string $status, int $limit = 60, int $offset = 0): array
    {
        $where = $status ? "WHERE c.status = ?" : "";
        $params = $status ? [$status, $limit, $offset] : [$limit, $offset];

        $stmt = $this->db->prepare("
            SELECT c.*,
                   u.full_name   AS user_full_name,
                   u.email       AS user_email,
                   u.avatar_url  AS user_avatar
            FROM support_chats c
            LEFT JOIN platform_users u ON u.id = c.user_id
            {$where}
            ORDER BY c.last_message_at DESC, c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count chats by status for admin stats. */
    public function countByStatus(): array
    {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) AS total FROM support_chats GROUP BY status
        ");
        $result = ['open' => 0, 'pending' => 0, 'closed' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    /** Count chats with unread messages for admin. */
    public function countUnreadForAdmin(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM support_chats WHERE unread_admin > 0");
        return (int) $stmt->fetchColumn();
    }

    /** Poll: return chat IDs updated after a given datetime. */
    public function pollUpdated(string $since): array
    {
        $stmt = $this->db->prepare("
            SELECT id FROM support_chats WHERE updated_at > ? ORDER BY updated_at DESC LIMIT 50
        ");
        $stmt->execute([$since]);
        return array_column($stmt->fetchAll(), 'id');
    }
}
