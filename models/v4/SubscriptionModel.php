<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;

class SubscriptionModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        $stmt = $this->db->query("SHOW TABLES LIKE 'subscription_payments'");
        if ($stmt && $stmt->fetchColumn()) {
            $checked = true;
            return;
        }

        $sql = file_get_contents(__DIR__ . '/../../database/create_subscriptions_tables.sql');
        if ($sql !== false) {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $this->db->exec($statement);
            }
        }
        $checked = true;
    }

    public function plan(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE code = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$code]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        return $plan ?: null;
    }

    public function activeSubscription(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, p.name AS plan_name, p.price AS plan_price, p.duration_days AS plan_duration_days, p.device_limit, p.profile_limit, p.family_member_limit, p.benefits_json
            FROM user_subscriptions s
            JOIN subscription_plans p ON p.code = s.plan_code
            WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()
            ORDER BY s.expires_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        return $subscription ?: null;
    }

    public function activeFamilyBenefit(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    fm.id AS membership_id,
                    fm.owner_user_id,
                    fm.member_user_id,
                    fm.accepted_at,
                    owner.full_name AS owner_name,
                    owner.email AS owner_email,
                    s.id AS owner_subscription_id,
                    s.plan_code,
                    s.expires_at AS owner_subscription_expires_at,
                    p.name AS plan_name,
                    p.device_limit,
                    p.profile_limit,
                    p.family_member_limit,
                    p.benefits_json
                FROM family_memberships fm
                JOIN platform_users owner ON owner.id = fm.owner_user_id
                JOIN user_subscriptions s
                  ON s.user_id = fm.owner_user_id
                 AND s.status = 'active'
                 AND s.expires_at > NOW()
                JOIN subscription_plans p
                  ON p.code = s.plan_code
                 AND p.is_active = 1
                 AND p.family_member_limit > 0
                WHERE fm.member_user_id = ?
                  AND fm.status = 'active'
                ORDER BY s.expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $benefit = $stmt->fetch(PDO::FETCH_ASSOC);
            return $benefit ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function expireOldSubscriptions(): void
    {
        $this->db->exec("UPDATE user_subscriptions SET status = 'expired' WHERE status = 'active' AND expires_at <= NOW()");
        $this->db->exec("UPDATE subscription_payments SET status = 'expired' WHERE status = 'pending' AND expires_at <= NOW()");
    }

    public function pendingPayment(int $userId, string $planCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM subscription_payments
            WHERE user_id = ? AND plan_code = ? AND status = 'pending' AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, $planCode]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $payment ?: null;
    }

    public function createPayment(int $userId, string $planCode, float $amount, string $expiresAt, array $checkoutPayload): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_payments
                (user_id, plan_code, amount, status, expires_at, checkout_payload)
            VALUES (?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([$userId, $planCode, $amount, $expiresAt, json_encode($checkoutPayload)]);
        return (int) $this->db->lastInsertId();
    }

    public function attachPix(int $paymentId, string $txid, string $qrCode, string $qrCodeImage, array $payload): void
    {
        $stmt = $this->db->prepare("
            UPDATE subscription_payments
            SET provider_txid = ?, qr_code = ?, qr_code_image = ?, pix_payload = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$txid, $qrCode, $qrCodeImage, json_encode($payload), $paymentId]);
    }

    public function paymentByIdForUser(int $paymentId, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM subscription_payments WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $payment ?: null;
    }

    public function paymentByTxid(string $txid): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM subscription_payments WHERE provider_txid = ? LIMIT 1");
        $stmt->execute([$txid]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $payment ?: null;
    }

    public function markPaymentPaid(int $paymentId, array $payload = []): void
    {
        $stmt = $this->db->prepare("
            UPDATE subscription_payments
            SET status = 'paid', paid_at = COALESCE(paid_at, NOW()), pix_payload = COALESCE(?, pix_payload), updated_at = NOW()
            WHERE id = ? AND status IN ('pending','paid')
        ");
        $stmt->execute([json_encode($payload), $paymentId]);
    }

    public function cancelPayment(int $paymentId, int $userId, string $reason): bool
    {
        $stmt = $this->db->prepare("
            UPDATE subscription_payments
            SET status = 'canceled', canceled_at = NOW(), cancel_reason = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        return $stmt->execute([$reason, $paymentId, $userId]) && $stmt->rowCount() > 0;
    }

    public function createActivationToken(int $userId, int $paymentId, string $tokenHash, string $sessionHash, string $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_activation_tokens
                (user_id, payment_id, token_hash, session_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $paymentId,
            $tokenHash,
            $sessionHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function activationToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM subscription_activation_tokens
            WHERE token_hash = ? AND consumed_at IS NULL AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        return $token ?: null;
    }

    public function consumeActivationToken(int $tokenId): void
    {
        $stmt = $this->db->prepare("UPDATE subscription_activation_tokens SET consumed_at = NOW() WHERE id = ?");
        $stmt->execute([$tokenId]);
    }

    public function activateSubscription(int $userId, string $planCode, float $amount, int $paymentId, int $durationDays): int
    {
        $startedAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE user_subscriptions
                SET status = 'expired', updated_at = NOW()
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);

            $stmt = $this->db->prepare("
                INSERT INTO user_subscriptions
                    (user_id, plan_code, status, started_at, expires_at, amount_paid, payment_id)
                VALUES (?, ?, 'active', ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $planCode, $startedAt, $expiresAt, $amount, $paymentId]);
            $subscriptionId = (int) $this->db->lastInsertId();

            $this->db->commit();
            return $subscriptionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function renewActiveSubscription(int $userId, string $planCode, float $amount, int $paymentId, int $durationDays): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT id, payment_id, expires_at
                FROM user_subscriptions
                WHERE user_id = ? AND plan_code = ? AND status = 'active' AND expires_at > NOW()
                ORDER BY expires_at DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$userId, $planCode]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subscription) {
                $this->db->rollBack();
                return $this->activateSubscription($userId, $planCode, $amount, $paymentId, $durationDays);
            }

            $subscriptionId = (int) $subscription['id'];
            if ((int) ($subscription['payment_id'] ?? 0) === $paymentId) {
                $this->db->commit();
                return $subscriptionId;
            }

            $baseTs = max(time(), strtotime((string) $subscription['expires_at']));
            $renewedUntil = date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days', $baseTs));

            $stmt = $this->db->prepare("
                UPDATE user_subscriptions
                SET expires_at = ?, amount_paid = ?, payment_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$renewedUntil, $amount, $paymentId, $subscriptionId]);

            $this->db->commit();
            return $subscriptionId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function paymentHistory(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, s.id AS subscription_id, s.started_at, s.expires_at AS subscription_expires_at
            FROM subscription_payments p
            LEFT JOIN user_subscriptions s ON s.payment_id = p.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function subscriptionHistory(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, p.name AS plan_name
            FROM user_subscriptions s
            JOIN subscription_plans p ON p.code = s.plan_code
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function event(?int $userId, ?int $paymentId, ?int $subscriptionId, string $type, array $payload = []): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_events
                (user_id, payment_id, subscription_id, event_type, payload, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $paymentId,
            $subscriptionId,
            $type,
            json_encode($payload),
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
