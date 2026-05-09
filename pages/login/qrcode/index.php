<?php
require_once __DIR__ . '/../../../database/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /home');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Login por QR Code</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="qr-shell">
        <section class="qr-panel">
            <div class="qr-content">
                <p class="modal-eyebrow">Login por QR Code</p>
                <h1>Escaneie para entrar</h1>
                <p class="qr-copy">Abra a câmera em um dispositivo já conectado à sua conta Pipocine e aprove o acesso.</p>

                <div class="qr-status" id="qr-status">Aguardando aprovação em outro dispositivo.</div>
                <button class="btn-submit" id="qr-refresh" type="button">Gerar novo QR Code</button>
                <div class="login-links">
                    <a href="/login/plataforma/methods">Escolher outro método</a>
                    <a href="/login">Voltar ao login</a>
                </div>
            </div>

            <div class="qr-visual">
                <div class="qr-card">
                    <img id="qr-image" class="qr-image" alt="QR Code de login">
                    <div class="qr-loading" id="qr-loading">Gerando QR Code...</div>
                </div>
            </div>
        </section>
    </main>

    <script src="/assets/js/qrcode-login.js"></script>
</body>
</html>
