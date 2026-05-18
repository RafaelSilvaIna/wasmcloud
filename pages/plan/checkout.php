<?php
declare(strict_types=1);

$name = htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['user_email'] ?? (str_contains((string) ($_SESSION['username'] ?? ''), '@') ? $_SESSION['username'] : ''), ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($_SESSION['user_phone'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Checkout Gold</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="plan-shell">
        <header class="plan-topbar plan-topbar-minimal">
            <span class="plan-pill"><i data-lucide="lock-keyhole"></i>Checkout seguro</span>
        </header>

        <section class="checkout-layout">
            <div class="plan-panel">
                <p class="plan-kicker">Plano Gold</p>
                <h1>Finalize sua assinatura</h1>
                <p class="plan-subtitle">Confirme seus dados para gerar o QR Code Pix. A confirmação fica nesta guia, então mantenha a página aberta até a ativação.</p>

                <form class="checkout-form" id="plan-checkout-form">
                    <input type="hidden" name="plan" value="gold">
                    <input type="hidden" id="payment-id" value="">

                    <div class="plan-field">
                        <label for="checkout-name">Nome completo</label>
                        <input id="checkout-name" name="name" value="<?= $name ?>" required autocomplete="name">
                    </div>

                    <div class="plan-field">
                        <label for="checkout-email">E-mail</label>
                        <input id="checkout-email" name="email" value="<?= $email ?>" type="email" autocomplete="email">
                    </div>

                    <div class="plan-field">
                        <label for="checkout-phone">Telefone</label>
                        <input id="checkout-phone" name="phone" value="<?= $phone ?>" autocomplete="tel">
                    </div>

                    <label class="plan-check">
                        <input type="checkbox" name="accepted_terms" required>
                        <span>Estou ciente e concordo com as politicas de privacidade e as politicas de assinatura do Pipocine.</span>
                    </label>

                    <button class="plan-action gold" type="submit"><i data-lucide="qr-code"></i>Gerar QR Code Pix</button>
                </form>

                <div class="pix-box" id="pix-box">
                    <div class="qr-card">
                        <img id="pix-qr-image" alt="QR Code Pix">
                    </div>
                    <textarea id="pix-code" class="pix-code" readonly></textarea>
                    <div class="plan-actions-row">
                        <button class="plan-secondary" id="copy-pix-btn" type="button">Copiar codigo Pix</button>
                    </div>
                    <p class="plan-note">Verificamos o pagamento periodicamente com baixo consumo. Se voce fechar esta guia, a ativação automatica pode ser interrompida.</p>
                </div>
            </div>

            <aside class="plan-card gold" style="min-height:auto">
                <div class="plan-card-head">
                    <h2 class="plan-name">Gold</h2>
                    <span class="plan-pill">R$ 20,99 / mes</span>
                </div>
                <ul class="plan-benefits">
                    <li><i data-lucide="smartphone"></i><span>Mobile Pipocine e downloads offline</span></li>
                    <li><i data-lucide="monitor-smartphone"></i><span>4 dispositivos e 8 perfis</span></li>
                    <li><i data-lucide="user-plus"></i><span>Até 3 membros na família com benefícios específicos</span></li>
                    <li><i data-lucide="shield-check"></i><span>Segurança avançada para perfis</span></li>
                    <li><i data-lucide="badge-x"></i><span>Sem anúncios e suporte prioritário</span></li>
                </ul>
            </aside>
        </section>
    </main>

    <div class="plan-alert" id="plan-alert">
        <div class="plan-alert-card">
            <h2 class="plan-alert-title" id="plan-alert-title">Aviso</h2>
            <p class="plan-alert-text" id="plan-alert-text"></p>
            <div class="plan-actions-row" id="plan-alert-actions"></div>
        </div>
    </div>

    <script src="/assets/js/plan-checkout.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
