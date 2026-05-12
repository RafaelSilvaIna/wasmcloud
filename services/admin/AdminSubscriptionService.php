<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminSubscriptionModel;

final class AdminSubscriptionService
{
    public function __construct(private AdminSubscriptionModel $subscriptions)
    {
        $this->subscriptions->ensureSchema();
    }

    public function dashboard(): array
    {
        return [
            'success' => true,
            'summary' => $this->subscriptions->summary(),
            'series' => $this->subscriptions->revenueSeries(),
            'subscriptions' => $this->subscriptions->listSubscriptions(),
        ];
    }

    public function searchUsers(string $query): array
    {
        return [
            'success' => true,
            'users' => $this->subscriptions->findUsers($query),
        ];
    }

    public function grantCourtesy(int $adminId, int $userId, int $durationDays, string $reason): array
    {
        $reason = trim($reason) ?: 'Cortesia administrativa.';
        $durationDays = max(1, min(20, $durationDays));

        if ($adminId <= 0 || $userId <= 0) {
            return ['success' => false, 'error' => 'Administrador ou usuario invalido.'];
        }

        if (!$this->subscriptions->userById($userId)) {
            return ['success' => false, 'error' => 'Usuario nao encontrado.'];
        }

        $subscriptionId = $this->subscriptions->grantCourtesy($userId, $adminId, $durationDays, $reason);

        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'message' => 'Cortesia concedida por ate 20 dias.',
        ];
    }
}
