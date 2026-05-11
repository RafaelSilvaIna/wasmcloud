<?php
declare(strict_types=1);

namespace Hooks\V4;

use PDO;

final class AccountStatusHook
{
    public static function enforce(PDO $pdo): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['auth_provider'] ?? '') !== 'pipocine') {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        if (str_starts_with($uri, '/assets/') || str_starts_with($uri, '/error')) {
            return;
        }

        $status = self::statusForUser($pdo, (int) $_SESSION['user_id']);
        if (!$status || $status['moderation_status'] === 'active') {
            return;
        }

        if ($status['moderation_status'] === 'suspended' && !empty($status['moderation_until']) && strtotime((string) $status['moderation_until']) <= time()) {
            self::reactivateExpiredSuspension($pdo, (int) $_SESSION['user_id']);
            return;
        }

        $_SESSION['account_restriction'] = [
            'status' => $status['moderation_status'],
            'reason' => $status['moderation_reason'] ?: 'Violacao das regras da plataforma.',
            'until' => $status['moderation_until'],
            'moderated_at' => $status['moderated_at'],
        ];

        if (str_starts_with($uri, '/api/')) {
            self::clearAuthenticatedUser();
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Conta indisponivel.',
                'restriction' => $_SESSION['account_restriction'],
            ]);
            exit;
        }

        $target = $status['moderation_status'] === 'banned' ? '/error/banned' : '/error/suspended';
        self::clearAuthenticatedUser();
        header('Location: ' . $target);
        exit;
    }

    private static function clearAuthenticatedUser(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['user_email'],
            $_SESSION['user_phone'],
            $_SESSION['full_name'],
            $_SESSION['profile_pic_url'],
            $_SESSION['profile_id'],
            $_SESSION['profile_name'],
            $_SESSION['profile_image'],
            $_SESSION['profile_is_kids'],
            $_SESSION['auth_provider']
        );

        setcookie('pipocine_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function statusForUser(PDO $pdo, int $userId): ?array
    {
        try {
            self::ensureModerationColumns($pdo);
            $stmt = $pdo->prepare("
                SELECT COALESCE(moderation_status, 'active') AS moderation_status,
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
        } catch (\Throwable) {
            return null;
        }
    }

    private static function ensureModerationColumns(PDO $pdo): void
    {
        foreach ([
            "ALTER TABLE platform_users ADD COLUMN moderation_status ENUM('active','suspended','banned') NOT NULL DEFAULT 'active' AFTER status",
            "ALTER TABLE platform_users ADD COLUMN moderation_reason VARCHAR(500) NULL AFTER moderation_status",
            "ALTER TABLE platform_users ADD COLUMN moderation_until DATETIME NULL AFTER moderation_reason",
            "ALTER TABLE platform_users ADD COLUMN moderated_by INT NULL AFTER moderation_until",
            "ALTER TABLE platform_users ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by",
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\Throwable) {
            }
        }
    }

    private static function reactivateExpiredSuspension(PDO $pdo, int $userId): void
    {
        $stmt = $pdo->prepare("
            UPDATE platform_users
            SET moderation_status = 'active',
                moderation_reason = NULL,
                moderation_until = NULL,
                moderated_at = NOW()
            WHERE id = ? AND moderation_status = 'suspended'
        ");
        $stmt->execute([$userId]);
        unset($_SESSION['account_restriction']);
    }
}
