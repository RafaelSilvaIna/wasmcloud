<?php

declare(strict_types=1);

namespace Services\Suporte;

use Models\Suporte\SupportMessageModel;
use Models\Suporte\SupportChatModel;
use Helpers\Suporte\SupportCipher;

final class SupportMessageService
{
    private SupportCipher $cipher;

    public function __construct(
        private SupportMessageModel $messageModel,
        private SupportChatModel    $chatModel
    ) {
        $this->cipher = new SupportCipher();
    }

    /**
     * Send a message to a chat.
     * Returns the new message id.
     */
    public function send(
        int     $chatId,
        string  $sender,        // 'user' or 'admin'
        string  $senderName,
        string  $plaintext,
        ?string $imageToken = null,
        ?int    $replyToId  = null
    ): int {
        $enc = $this->cipher->encrypt($plaintext, $chatId);

        $msgId = $this->messageModel->insert([
            'chat_id'              => $chatId,
            'sender'               => $sender,
            'sender_name'          => $senderName,
            'body_encrypted'       => $enc['ciphertext'],
            'iv'                   => $enc['iv'],
            'has_image'            => $imageToken !== null,
            'image_token'          => $imageToken,
            'reply_to_message_id'  => $replyToId,
        ]);

        // Increment unread for the opposite side
        $this->chatModel->incrementUnread($chatId, $sender === 'user' ? 'admin' : 'user');

        return $msgId;
    }

    /**
     * Poll: return new decrypted messages after $afterId.
     */
    public function poll(int $chatId, int $afterId): array
    {
        $rows = $this->messageModel->fetchAfter($chatId, $afterId);
        return array_map(fn ($r) => $this->decryptRow($r, $chatId), $rows);
    }

    /**
     * Full history for admin (decrypted).
     */
    public function fullHistory(int $chatId): array
    {
        $rows = $this->messageModel->fetchAll($chatId);
        return array_map(fn ($r) => $this->decryptRow($r, $chatId), $rows);
    }

    /** Get the last message id in a chat. */
    public function lastId(int $chatId): int
    {
        return $this->messageModel->lastId($chatId);
    }

    /** Decrypt a raw DB row, adding 'body' plaintext field. */
    private function decryptRow(array $row, int $chatId): array
    {
        $row['body'] = $this->cipher->decrypt(
            (string) ($row['body_encrypted'] ?? ''),
            (string) ($row['iv'] ?? ''),
            $chatId
        );

        // Decrypt reply-to body if present
        if (!empty($row['reply_body_encrypted']) && !empty($row['reply_iv'])) {
            $row['reply_body'] = $this->cipher->decrypt(
                $row['reply_body_encrypted'],
                $row['reply_iv'],
                $chatId
            );
        } else {
            $row['reply_body'] = null;
        }

        // Strip encrypted fields from output
        unset($row['body_encrypted'], $row['iv'], $row['reply_body_encrypted'], $row['reply_iv']);

        return $row;
    }
}
