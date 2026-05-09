<?php
require_once __DIR__ . '/../../../database/db.php';

$token = preg_replace('/[^a-fA-F0-9]/', '', (string)($_GET['token'] ?? ''));
if (!isset($_SESSION['user_id'])) {
    header('Location: /login?next=' . rawurlencode('/login/qrcode/approve?token=' . $token));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Aprovar QR Code</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="qr-shell">
        <section class="qr-panel approve-panel">
            <p class="modal-eyebrow">Confirmação de acesso</p>
            <h1>Aprovar login?</h1>
            <p class="qr-copy">Você está permitindo que outro dispositivo entre na sua conta Pipocine. Aprove somente se foi você que iniciou este login.</p>
            <div id="approve-status" class="qr-status">Token pronto para validação segura.</div>
            <button class="btn-submit" id="approve-btn" type="button" data-token="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">Aprovar acesso</button>
            <div class="login-links">
                <a href="/home">Cancelar e voltar</a>
            </div>
        </section>
    </main>
    <script src="/assets/js/qrcode-approve.js"></script>
</body>
</html>
