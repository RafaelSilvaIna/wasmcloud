<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/v4/SubscriptionModel.php';

use Models\V4\SubscriptionModel;

$paymentId = (int) ($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) {
    header('Location: /plan/checkout');
    exit;
}

$model = new SubscriptionModel($pdo);
$model->ensureSchema();
$payment = $model->paymentByIdForUser($paymentId, (int) $_SESSION['user_id']);

if (!$payment || !in_array($payment['status'], ['pending', 'paid'], true) || empty($payment['qr_code'])) {
    header('Location: /plan/checkout');
    exit;
}

function pixMoney($value): string {
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function pixDate(?string $date): string {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

$amount = pixMoney($payment['amount']);
$payload = json_decode((string) ($payment['checkout_payload'] ?? '{}'), true) ?: [];
$isRenewalPayment = (($payload['purpose'] ?? '') === 'renewal');
$txid = htmlspecialchars((string) ($payment['provider_txid'] ?? ''), ENT_QUOTES, 'UTF-8');
$qrImage = htmlspecialchars((string) ($payment['qr_code_image'] ?? ''), ENT_QUOTES, 'UTF-8');
$qrCode = htmlspecialchars((string) ($payment['qr_code'] ?? ''), ENT_QUOTES, 'UTF-8');
$expiresAt = pixDate($payment['expires_at'] ?? null);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Pagamento Pix</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="plan-shell plan-pix-shell" id="pix-payment-page" data-payment-id="<?= (int) $payment['id'] ?>">
        <section class="pix-payment-layout" aria-label="Pagamento Pix">
            <aside class="pix-summary-card">
                <p class="plan-kicker">Plano Gold</p>
                <h1><?= $isRenewalPayment ? 'Renovacao Pix' : 'Pagamento Pix' ?></h1>
                <p class="pix-lead"><?= $isRenewalPayment ? 'Escaneie o QR Code ou copie o codigo Pix para renovar seu plano. A nova validade sera aplicada depois da confirmacao.' : 'Escaneie o QR Code ou copie o codigo Pix. A confirmacao acontece automaticamente nesta pagina.' ?></p>

                <div class="pix-summary-list">
                    <div>
                        <span>Operacao</span>
                        <strong><?= $isRenewalPayment ? 'Renovacao Gold' : 'Assinatura Gold' ?></strong>
                    </div>
                    <div>
                        <span>Valor</span>
                        <strong><?= $amount ?></strong>
                    </div>
                    <div>
                        <span>Validade</span>
                        <strong><?= htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <span>TXID</span>
                        <strong title="<?= $txid ?>"><?= htmlspecialchars(substr($txid, 0, 22), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <p class="plan-note">Mantenha esta guia aberta. Assim que o Pix for confirmado, sua assinatura sera ativada automaticamente.</p>
            </aside>

            <section class="pix-qr-panel">
                <div class="qr-card pix-qr-card">
                    <img src="<?= $qrImage ?>" alt="QR Code Pix do Plano Gold">
                </div>

                <div class="pix-copy-area">
                    <label for="pix-code">Pix copia e cola</label>
                    <textarea id="pix-code" class="pix-code" readonly><?= $qrCode ?></textarea>
                </div>

                <div class="plan-actions-row pix-actions">
                    <button class="plan-action gold" id="copy-pix-btn" type="button"><i data-lucide="copy"></i>Copiar codigo Pix</button>
                </div>
            </section>
        </section>
    </main>

    <div class="plan-alert" id="plan-alert">
        <div class="plan-alert-card">
            <h2 class="plan-alert-title" id="plan-alert-title">Aviso</h2>
            <p class="plan-alert-text" id="plan-alert-text"></p>
            <div class="plan-actions-row" id="plan-alert-actions"></div>
        </div>
    </div>

    <script src="/assets/js/plan-pix.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
