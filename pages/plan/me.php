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
    <main class="plan-shell">
        <header class="plan-topbar plan-topbar-minimal">
            <span class="plan-pill <?= $isCourtesy ? 'courtesy' : '' ?>">
                <i data-lucide="<?= $isCourtesy ? 'gift' : 'calendar-clock' ?>"></i>
                <?= $isCourtesy ? 'Cortesia temporaria' : 'Lembrete mensal ativo' ?>
            </span>
        </header>

        <section class="plan-hero">
            <div>
                <p class="plan-kicker">Minha assinatura</p>
                <h1 class="plan-title"><?= htmlspecialchars($active['plan_name'] ?? 'Plano Gold', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="plan-subtitle">
                    <?= $isCourtesy
                        ? 'Seu acesso atual foi concedido como cortesia administrativa. A cortesia e temporaria e nao substitui uma assinatura real.'
                        : 'Acompanhe validade, dados do plano e historico de pagamentos confirmados. Renovacoes sao mensais via Pix.' ?>
                </p>
            </div>
        </section>

        <?php if ($isCourtesy): ?>
            <section class="plan-courtesy-banner">
                <div>
                    <span><i data-lucide="sparkles"></i>Cortesia ativa</span>
                    <strong>Este plano termina em <?= $daysLeft !== null ? $daysLeft . ' dia' . ($daysLeft === 1 ? '' : 's') : 'breve' ?>.</strong>
                    <p>Para manter todos os recursos sem depender de uma liberacao temporaria, assine o plano real do PipoCine.</p>
                </div>
                <a class="plan-action gold compact" href="/plan"><i data-lucide="arrow-up-right"></i>Assinar plano real</a>
            </section>
        <?php endif; ?>

        <section class="me-grid">
            <article class="plan-panel">
                <div class="me-panel-head">
                    <div>
                        <span class="me-panel-eyebrow">Plano atual</span>
                        <h2>Status do plano</h2>
                    </div>
                    <span class="plan-origin <?= $isCourtesy ? 'courtesy' : 'paid' ?>">
                        <?= $isCourtesy ? 'Cortesia' : 'Assinatura real' ?>
                    </span>
                </div>
                <div class="metric-grid">
                    <div class="metric"><span>Status</span><strong>Ativo</strong></div>
                    <div class="metric"><span>Tipo</span><strong><?= $isCourtesy ? 'Cortesia' : 'Pago' ?></strong></div>
                    <div class="metric"><span>Inicio</span><strong><?= brDate($active['started_at'] ?? null) ?></strong></div>
                    <div class="metric"><span>Validade</span><strong><?= brDate($active['expires_at'] ?? null) ?></strong></div>
                    <div class="metric"><span>Valor pago</span><strong><?= brMoney($active['amount_paid'] ?? 0) ?></strong></div>
                    <div class="metric"><span>Dispositivos</span><strong><?= (int) ($active['device_limit'] ?? 4) ?></strong></div>
                    <div class="metric"><span>Perfis</span><strong><?= (int) ($active['profile_limit'] ?? 8) ?></strong></div>
                </div>

                <a class="plan-benefits-link" href="/plan">
                    <i data-lucide="external-link"></i>
                    <span>Conheca seus beneficios</span>
                </a>
            </article>

            <article class="plan-panel">
                <div class="me-panel-head">
                    <div>
                        <span class="me-panel-eyebrow">Somente pagamentos confirmados</span>
                        <h2>Historico de pagamentos</h2>
                    </div>
                </div>
                <table class="history-table">
                    <thead>
                        <tr><th>Data</th><th>Status</th><th>Valor</th><th>TXID</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paidPayments as $payment): ?>
                        <tr>
                            <td><?= brDate($payment['paid_at'] ?? $payment['created_at'] ?? null) ?></td>
                            <td><span class="payment-status-paid">Pago</span></td>
                            <td><?= brMoney($payment['amount'] ?? 0) ?></td>
                            <td><?= htmlspecialchars(substr((string) ($payment['provider_txid'] ?? '-'), 0, 18), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paidPayments)): ?>
                        <tr><td colspan="4">Nenhum pagamento confirmado registrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </article>
        </section>
    </main>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
