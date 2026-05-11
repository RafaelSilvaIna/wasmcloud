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
$active = $dashboard['active'];

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
            <span class="plan-pill"><i data-lucide="calendar-clock"></i>Lembrete mensal ativo</span>
        </header>

        <section class="plan-hero">
            <div>
                <p class="plan-kicker">Minha assinatura</p>
                <h1 class="plan-title"><?= htmlspecialchars($active['plan_name'] ?? 'Plano Gold', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="plan-subtitle">Acompanhe validade, beneficios e historico de pagamentos. Renovacoes sao mensais via Pix.</p>
            </div>
        </section>

        <section class="me-grid">
            <article class="plan-panel">
                <h2>Status do plano</h2>
                <div class="metric-grid">
                    <div class="metric"><span>Status</span><strong>Ativo</strong></div>
                    <div class="metric"><span>Inicio</span><strong><?= brDate($active['started_at'] ?? null) ?></strong></div>
                    <div class="metric"><span>Validade</span><strong><?= brDate($active['expires_at'] ?? null) ?></strong></div>
                    <div class="metric"><span>Valor pago</span><strong><?= brMoney($active['amount_paid'] ?? 0) ?></strong></div>
                    <div class="metric"><span>Dispositivos</span><strong><?= (int) ($active['device_limit'] ?? 4) ?></strong></div>
                    <div class="metric"><span>Perfis</span><strong><?= (int) ($active['profile_limit'] ?? 8) ?></strong></div>
                </div>

                <ul class="plan-benefits">
                    <?php foreach (($active['benefits'] ?? []) as $benefit): ?>
                        <li><i data-lucide="check-circle-2"></i><span><?= htmlspecialchars((string) $benefit, ENT_QUOTES, 'UTF-8') ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <article class="plan-panel">
                <h2>Historico de pagamentos</h2>
                <table class="history-table">
                    <thead>
                        <tr><th>Data</th><th>Status</th><th>Valor</th><th>TXID</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dashboard['payments'] as $payment): ?>
                        <tr>
                            <td><?= brDate($payment['created_at'] ?? null) ?></td>
                            <td><?= htmlspecialchars((string) $payment['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= brMoney($payment['amount'] ?? 0) ?></td>
                            <td><?= htmlspecialchars(substr((string) ($payment['provider_txid'] ?? '-'), 0, 18), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dashboard['payments'])): ?>
                        <tr><td colspan="4">Nenhum pagamento registrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </article>
        </section>
    </main>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
