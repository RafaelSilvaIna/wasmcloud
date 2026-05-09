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
    <title>PipoCine - Métodos de Login</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="methods-shell">
        <section class="methods-panel">
            <div class="methods-intro">
                <h1>Escolha como fazer login</h1>
                <p>Use um método seguro para acessar sua conta neste dispositivo.</p>
                <a class="methods-back" href="/login">Voltar ao login</a>
            </div>

            <div class="methods-list">
                <a class="methods-option primary" href="/login/qrcode">
                    <span class="method-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h2v2h-2v-2Zm4 0h2v2h-2v-2Zm-4 4h2v2h-2v-2Zm4 0h2v2h-2v-2Z"/>
                        </svg>
                    </span>
                    <span>
                        <strong>Entrar com QR Code</strong>
                        <small>Escaneie usando um dispositivo já conectado.</small>
                    </span>
                </a>

                <a class="methods-option" href="/login/cineveo">
                    <span class="method-logo" aria-hidden="true"><img src="/assets/img/cineveo.png" alt=""></span>
                    <span>
                        <strong>Login com <img class="inline-cineveo" src="/assets/img/cineveo.png" alt="Cineveo"></strong>
                        <small>Conecte usando sua sessão ativa no Cineveo.</small>
                    </span>
                </a>

                <div class="methods-help">
                    <p>Precisa de ajuda?</p>
                    <a href="#">Falar com suporte</a>
                    <a href="#">Esqueci a senha</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
