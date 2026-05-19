<?php

declare(strict_types=1);

namespace Hooks\V4;

use Models\V4\SubscriptionModel;

class SubscriptionHook
{
    public static function enforcePlanAccess(\PDO $pdo): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/webhooks/') || str_starts_with($uri, '/assets/')) {
            return;
        }

        if (str_starts_with($uri, '/plan') && empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            return;
        }

        require_once __DIR__ . '/../../models/v4/SubscriptionModel.php';
        $model = new SubscriptionModel($pdo);
        $model->ensureSchema();
        $model->expireOldSubscriptions();

        $active = $model->activeSubscription((int) $_SESSION['user_id']);
        $isPaidActive = $active && (($active['source'] ?? 'paid') === 'paid') && (($active['plan_code'] ?? '') !== 'casual');
        $familyBenefit = $model->activeFamilyBenefit((int) $_SESSION['user_id']);

        if ($isPaidActive && (in_array($uri, ['/plan', '/plan/', '/plan/checkout', '/plan/pix', '/plan/payment'], true) || str_starts_with($uri, '/plan/payment/active='))) {
            header('Location: /plan/me');
            exit;
        }

        if (!$active && !$familyBenefit && $uri === '/plan/me') {
            header('Location: /plan');
            exit;
        }
    }
}
