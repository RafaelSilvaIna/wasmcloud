<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;

class PlatformUserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, password_hash, full_name, avatar_url, status
            FROM platform_users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, password_hash, full_name, avatar_url, status
            FROM platform_users
            WHERE phone = ?
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByIdentifier(?string $email, ?string $phone): ?array
    {
        if ($email) {
            return $this->findByEmail($email);
        }

        if ($phone) {
            return $this->findByPhone($phone);
        }

        return null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, phone, full_name, avatar_url, status
            FROM platform_users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function create(?string $email, ?string $phone, string $passwordHash, string $fullName): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_users (email, phone, password_hash, full_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$email, $phone, $passwordHash, $fullName]);

        return (int) $this->db->lastInsertId();
    }

    public function createSession(int $userId, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_user_sessions
                (user_id, token_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function createTwoFactorChallenge(int $userId, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO two_factor_login_challenges
                (user_id, token_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function getTwoFactorChallenge(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, token_hash, expires_at
            FROM two_factor_login_challenges
            WHERE token_hash = ? AND expires_at > NOW() AND consumed_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        return $challenge ?: null;
    }

    public function consumeTwoFactorChallenge(string $tokenHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE two_factor_login_challenges
            SET consumed_at = NOW()
            WHERE token_hash = ? AND consumed_at IS NULL
        ");

        return $stmt->execute([$tokenHash]);
    }
}
