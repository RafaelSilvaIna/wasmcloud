<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/v4/EvoPayClient.php';
require_once __DIR__ . '/../models/v4/PlatformUserModel.php';
require_once __DIR__ . '/../models/v4/SubscriptionModel.php';
require_once __DIR__ . '/../services/v4/SubscriptionService.php';

use Helpers\V4\EvoPayClient;
use Models\V4\PlatformUserModel;
use Models\V4\SubscriptionModel;
use Services\V4\SubscriptionService;

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST ?: $_GET ?: [];
}

try {
    $service = new SubscriptionService(
        new SubscriptionModel($pdo),
        new PlatformUserModel($pdo),
        new EvoPayClient(require __DIR__ . '/../config/evopay.php')
    );

    echo json_encode($service->handleWebhook($payload));
} catch (Throwable $e) {
    error_log('[EvoPay webhook] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno no webhook.']);
}
