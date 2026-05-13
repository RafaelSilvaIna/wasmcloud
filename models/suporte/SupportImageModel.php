<?php

declare(strict_types=1);

namespace Models\Suporte;

final class SupportImageModel
{
    public function __construct(private \PDO $db) {}

    /** Register an uploaded image. Expiry = +24h. Returns the token. */
    public function register(string $token, string $filePath, int $chatId, string $mime, int $size): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO support_images (token, file_path, chat_id, mime_type, file_size, expires_at)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->execute([$token, $filePath, $chatId, $mime, $size]);
    }

    /** Resolve token to a valid, non-expired image record. */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM support_images WHERE token = ? AND expires_at > NOW() LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /** Delete all images expired before NOW(). Returns count of deleted rows. */
    public function deleteExpired(): int
    {
        $stmt = $this->db->query("SELECT file_path FROM support_images WHERE expires_at <= NOW()");
        $paths = array_column($stmt->fetchAll(), 'file_path');

        foreach ($paths as $path) {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        $del = $this->db->exec("DELETE FROM support_images WHERE expires_at <= NOW()");
        return (int) $del;
    }
}
