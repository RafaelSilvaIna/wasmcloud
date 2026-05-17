<?php

declare(strict_types=1);

namespace Models\Suporte;

final class SupportChatModel
{
    private static bool $schemaChecked = false;

    public function __construct(private \PDO $db)
    {
        $this->ensureOperationalColumns();
    }

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
                SET status = 'open',
                    reopened_at = CASE WHEN status = 'closed' THEN NOW() ELSE reopened_at END,
                    resolved_at = NULL,
                    resolved_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$chatId]);
        } else {
            $stmt = $this->db->prepare("UPDATE support_chats SET status = ? WHERE id = ?");
            $stmt->execute([$status, $chatId]);
        }
    }

    public function markAdminJoined(int $chatId, string $adminName): bool
    {
        $stmt = $this->db->prepare("
            UPDATE support_chats
               SET admin_joined_at = COALESCE(admin_joined_at, NOW()),
                   assigned_admin = COALESCE(assigned_admin, ?)
             WHERE id = ?
               AND admin_joined_at IS NULL
        ");
        $stmt->execute([$adminName, $chatId]);
        return $stmt->rowCount() > 0;
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
    public function listForAdmin(?string $status, int $limit = 60, int $offset = 0, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if ($status) {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = "(c.subject LIKE ? OR c.guest_name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $like = '%' . trim($search) . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        array_push($params, $limit, $offset);

        $stmt = $this->db->prepare("
            SELECT c.*,
                   u.full_name   AS user_full_name,
                   u.email       AS user_email,
                   u.avatar_url  AS user_avatar,
                   (
                    SELECT body_encrypted
                      FROM support_messages lm
                     WHERE lm.chat_id = c.id
                     ORDER BY lm.id DESC
                     LIMIT 1
                   ) AS last_body_encrypted,
                   (
                    SELECT iv
                      FROM support_messages lm
                     WHERE lm.chat_id = c.id
                     ORDER BY lm.id DESC
                     LIMIT 1
                   ) AS last_body_iv,
                   (
                    SELECT sender
                      FROM support_messages lm
                     WHERE lm.chat_id = c.id
                     ORDER BY lm.id DESC
                     LIMIT 1
                   ) AS last_sender
            FROM support_chats c
            LEFT JOIN platform_users u ON u.id = c.user_id
            {$whereSql}
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

    private function ensureOperationalColumns(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $this->db->exec("ALTER TABLE support_chats ADD COLUMN admin_joined_at DATETIME NULL");
        } catch (\Throwable) {}

        try {
            $this->db->exec("ALTER TABLE support_chats ADD COLUMN assigned_admin VARCHAR(120) NULL");
        } catch (\Throwable) {}

        try {
            $this->db->exec("CREATE INDEX idx_support_chats_status_updated ON support_chats (status, updated_at)");
        } catch (\Throwable) {}
    }
}
