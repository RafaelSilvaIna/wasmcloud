<?php
declare(strict_types=1);

namespace Models\V4;

use PDO;

final class AccountStatusModel
{
    public function __construct(private PDO $db)
    {
    }

    public function currentStatus(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name,
                   COALESCE(moderation_status, 'active') AS moderation_status,
                   moderation_reason,
                   moderation_until,
                   moderated_at
            FROM platform_users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
