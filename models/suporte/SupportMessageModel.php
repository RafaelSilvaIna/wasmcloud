<?php

declare(strict_types=1);

namespace Models\Suporte;

final class SupportMessageModel
{
    public function __construct(private \PDO $db) {}

    /** Insert an encrypted message. Returns new message id. */
    public function insert(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO support_messages
                (chat_id, sender, sender_name, body_encrypted, iv,
                 has_image, image_token, reply_to_message_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['chat_id'],
            $data['sender'],
            $data['sender_name'],
            $data['body_encrypted'],
            $data['iv'],
            $data['has_image']             ? 1 : 0,
            $data['image_token']           ?? null,
            $data['reply_to_message_id']   ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Get messages for a chat after a given id (polling). Returns raw encrypted rows. */
    public function fetchAfter(int $chatId, int $afterId = 0, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*,
                   r.sender_name AS reply_sender_name,
                   r.body_encrypted AS reply_body_encrypted,
                   r.iv AS reply_iv
            FROM support_messages m
            LEFT JOIN support_messages r ON r.id = m.reply_to_message_id
            WHERE m.chat_id = ? AND m.id > ?
            ORDER BY m.id ASC
            LIMIT ?
        ");
        $stmt->execute([$chatId, $afterId, $limit]);
        return $stmt->fetchAll();
    }

    /** Get all messages for a chat (admin full view). */
    public function fetchAll(int $chatId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*,
                   r.sender_name AS reply_sender_name,
                   r.body_encrypted AS reply_body_encrypted,
                   r.iv AS reply_iv
            FROM support_messages m
            LEFT JOIN support_messages r ON r.id = m.reply_to_message_id
            WHERE m.chat_id = ?
            ORDER BY m.id ASC
        ");
        $stmt->execute([$chatId]);
        return $stmt->fetchAll();
    }

    /** Get a single message by id. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM support_messages WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Get the last message id for a chat. */
    public function lastId(int $chatId): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(id), 0) FROM support_messages WHERE chat_id = ?
        ");
        $stmt->execute([$chatId]);
        return (int) $stmt->fetchColumn();
    }

    /** Count messages in a chat. */
    public function count(int $chatId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM support_messages WHERE chat_id = ?");
        $stmt->execute([$chatId]);
        return (int) $stmt->fetchColumn();
    }
}
