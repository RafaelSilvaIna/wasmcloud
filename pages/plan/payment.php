<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$token = (string) ($_GET['active'] ?? '');
if ($token === '' && str_starts_with($uri, '/plan/payment/active=')) {
    $token = substr($uri, strlen('/plan/payment/active='));
}
$token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Ativando assinatura</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="plan-shell">
        <section class="plan-panel" id="payment-activation" data-token="<?= $token ?>" style="max-width:680px;margin:80px auto;text-align:center">
            <span class="plan-pill"><i data-lucide="shield-check"></i>Token valido por 1 hora</span>
            <h1 data-title style="margin-top:24px">Ativando sua assinatura</h1>
            <p class="plan-subtitle" data-text style="margin-left:auto;margin-right:auto">Estamos validando o token de ativação nesta sessão. Não compartilhe este link.</p>
            <div class="plan-actions-row" data-actions style="display:none;justify-content:center;margin-top:24px">
                <a class="plan-secondary" href="/plan/checkout">Voltar ao checkout</a>
                <a class="plan-secondary" href="/settings?section=support">Falar com suporte</a>
            </div>
        </section>
    </main>
    <script src="/assets/js/plan-payment.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
