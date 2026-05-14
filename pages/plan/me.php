<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/v4/EvoPayClient.php';
require_once __DIR__ . '/../../models/v4/PlatformUserModel.php';
require_once __DIR__ . '/../../models/v4/SubscriptionModel.php';
require_once __DIR__ . '/../../services/v4/SubscriptionService.php';

use Helpers\V4\EvoPayClient;
use Models\V4\PlatformUserModel;
use Models\V4\SubscriptionModel;
use Services\V4\SubscriptionService;

$service = new SubscriptionService(
    new SubscriptionModel($pdo),
    new PlatformUserModel($pdo),
    new EvoPayClient(require __DIR__ . '/../../config/evopay.php')
);
$dashboard = $service->dashboard((int) $_SESSION['user_id']);
$active = $dashboard['active'] ?? [];
$isCourtesy = (($active['source'] ?? 'paid') === 'admin_courtesy');
$paidPayments = array_values(array_filter($dashboard['payments'] ?? [], static function (array $payment): bool {
    return ($payment['status'] ?? '') === 'paid';
}));
$expiresAt = !empty($active['expires_at']) ? strtotime((string) $active['expires_at']) : null;
$daysLeft = $expiresAt ? max(0, (int) ceil(($expiresAt - time()) / 86400)) : null;

function brDate(?string $date): string {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function brMoney($value): string {
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Minha Assinatura</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="me-shell">

        <header class="me-header">
            <div>
                <p class="me-eyebrow">Minha assinatura</p>
                <h1 class="me-heading"><?= htmlspecialchars($active['plan_name'] ?? 'Plano Gold', ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <?php if ($isCourtesy): ?>
                <a class="plan-action gold compact" href="/plan">
                    <i data-lucide="arrow-up-right"></i>Assinar plano real
                </a>
            <?php endif; ?>
        </header>

        <?php if ($isCourtesy): ?>
            <div class="me-notice">
                <i data-lucide="sparkles"></i>
                <p>
                    <strong>Cortesia ativa &mdash; termina em <?= $daysLeft !== null ? $daysLeft . ' dia' . ($daysLeft === 1 ? '' : 's') : 'breve' ?>.</strong>
                    Para manter o acesso, assine o plano real do PipoCine.
                </p>
            </div>
        <?php endif; ?>

        <div class="me-grid">

            <section class="plan-panel me-status-panel">
                <div class="me-section-label">
                    Status do plano
                    <span class="plan-origin <?= $isCourtesy ? 'courtesy' : 'paid' ?>">
                        <?= $isCourtesy ? 'Cortesia' : 'Ativo' ?>
                    </span>
                </div>

                <ul class="me-stat-list">
                    <li>
                        <span>Validade</span>
                        <strong><?= brDate($active['expires_at'] ?? null) ?></strong>
                    </li>
                    <li>
                        <span>Dispositivos</span>
                        <strong><?= (int) ($active['device_limit'] ?? 4) ?></strong>
                    </li>
                    <li>
                        <span>Perfis</span>
                        <strong><?= (int) ($active['profile_limit'] ?? 8) ?></strong>
                    </li>
                    <?php if (!$isCourtesy): ?>
                    <li>
                        <span>Valor mensal</span>
                        <strong><?= brMoney($active['amount_paid'] ?? 0) ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>

                <a class="plan-benefits-link" href="/plan">
                    <i data-lucide="external-link"></i>
                    <span>Ver beneficios do plano</span>
                </a>
            </section>

            <section class="plan-panel me-history-panel">
                <div class="me-section-label">Historico de pagamentos</div>

                <?php if (empty($paidPayments)): ?>
                    <p class="me-empty">Nenhum pagamento confirmado ainda.</p>
                <?php else: ?>
                    <ul class="me-payment-list">
                        <?php foreach ($paidPayments as $payment): ?>
                            <li>
                                <span class="me-pay-date"><?= brDate($payment['paid_at'] ?? $payment['created_at'] ?? null) ?></span>
                                <span class="payment-status-paid">Pago</span>
                                <span class="me-pay-amount"><?= brMoney($payment['amount'] ?? 0) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

        </div>
    </main>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
