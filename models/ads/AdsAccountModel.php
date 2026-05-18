<?php
declare(strict_types=1);

namespace Models\Ads;

use PDO;

final class AdsAccountModel
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ads_accounts WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ads_accounts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ads_accounts WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByPipocineUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ads_accounts WHERE pipocine_user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(string $brand, ?string $cnpj, string $email, string $passwordHash, ?int $pipocineUserId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ads_accounts (brand_name, cnpj, email, password_hash, pipocine_user_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$brand, $cnpj, $email, $passwordHash, $pipocineUserId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function touchLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE ads_accounts SET last_login_at = NOW() WHERE id = ?')->execute([$id]);
    }

    public function linkPipocineUser(int $id, int $userId): void
    {
        $this->pdo->prepare('UPDATE ads_accounts SET pipocine_user_id = ? WHERE id = ?')->execute([$userId, $id]);
    }

    public function completeOnboarding(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ads_accounts
                SET logo_url = ?,
                    website_url = ?,
                    contact_name = ?,
                    phone_e164 = ?,
                    industry = ?,
                    company_size = ?,
                    business_description = ?,
                    onboarding_completed_at = NOW()
              WHERE id = ? AND onboarding_completed_at IS NULL'
        );
        $stmt->execute([
            $data['logo_url'],
            $data['website_url'],
            $data['contact_name'],
            $data['phone_e164'],
            $data['industry'],
            $data['company_size'],
            $data['business_description'],
            $id,
        ]);
    }

    public function claimFirstAdDemo(int $id): void
    {
        $this->pdo->prepare(
            'UPDATE ads_accounts
                SET first_ad_demo_claimed_at = COALESCE(first_ad_demo_claimed_at, NOW())
              WHERE id = ?'
        )->execute([$id]);
    }
}
