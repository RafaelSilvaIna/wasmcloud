<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;

class QrLoginModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getSettings(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT qr_login_enabled FROM platform_user_security WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['enabled' => $row ? (bool) $row['qr_login_enabled'] : false];
    }

    public function setEnabled(int $userId, bool $enabled): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_user_security (user_id, qr_login_enabled)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE qr_login_enabled = VALUES(qr_login_enabled), updated_at = NOW()
        ");

        return $stmt->execute([$userId, $enabled ? 1 : 0]);
    }

    public function createChallenge(string $tokenHash, string $verifierHash, string $expiresAt): bool
    {
        $this->cleanupExpired();

        $stmt = $this->db->prepare("
            INSERT INTO platform_qr_login_challenges
                (token_hash, verifier_hash, requester_ip, requester_user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $tokenHash,
            $verifierHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function findChallenge(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM platform_qr_login_challenges
            WHERE token_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        return $challenge ?: null;
    }

    public function approveChallenge(int $challengeId, int $userId, string $transferHash, string $scannerIp, string $scannerAgent): bool
    {
        $stmt = $this->db->prepare("
            UPDATE platform_qr_login_challenges
            SET status = 'approved',
                approved_user_id = ?,
                transfer_token_hash = ?,
                scanner_ip = ?,
                scanner_user_agent = ?,
                approved_at = NOW()
            WHERE id = ? AND status = 'pending' AND consumed_at IS NULL AND expires_at > NOW()
        ");

        return $stmt->execute([$userId, $transferHash, $scannerIp, substr($scannerAgent, 0, 255), $challengeId]);
    }

    public function consumeChallenge(int $challengeId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE platform_qr_login_challenges
            SET status = 'consumed', consumed_at = NOW()
            WHERE id = ? AND status = 'approved' AND consumed_at IS NULL
        ");

        return $stmt->execute([$challengeId]);
    }

    public function rejectChallenge(int $challengeId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE platform_qr_login_challenges
            SET status = 'rejected', consumed_at = NOW()
            WHERE id = ? AND status = 'pending' AND consumed_at IS NULL
        ");

        return $stmt->execute([$challengeId]);
    }

    public function log(int $userId, string $action, string $status, ?string $ipAddress = null, ?string $userAgent = null, ?array $details = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_qr_login_logs (user_id, action, status, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $action,
            $status,
            $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            substr($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            $details ? json_encode($details) : null
        ]);
    }

    public function getLogs(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT action, status, ip_address, user_agent, details, created_at
            FROM platform_qr_login_logs
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSessions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, ip_address, user_agent, created_at, expires_at
            FROM platform_user_sessions
            WHERE user_id = ? AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cleanupExpired(): void
    {
        $this->db->exec("
            UPDATE platform_qr_login_challenges
            SET status = 'expired', consumed_at = NOW()
            WHERE status = 'pending' AND expires_at <= NOW()
        ");
    }
}
