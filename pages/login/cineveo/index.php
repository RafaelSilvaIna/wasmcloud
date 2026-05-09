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
    <title>PipoCine - Entrar com Cineveo</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="login-container">
        <section class="login-side-image">
            <div class="hero-text-wrapper">
                <h2>Conecte sua conta<br>Cineveo ao Pipocine.</h2>
            </div>
        </section>
        <section class="login-side-form">
            <div class="form-box platform-auth">
                <div class="brand-pair standalone">
                    <img src="/assets/img/cineveo.png" alt="Cineveo">
                    <span>x</span>
                    <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
                </div>
                <h1>Entrar com Cineveo</h1>
                <p class="subtitle">Este metodo depende da sua sessao ativa do Cineveo e da permissao de cookies de terceiros no navegador.</p>
                <a class="btn-submit link-button" href="/login">Continuar pelo login</a>
                <div class="login-links">
                    <a href="/login/plataforma">Entrar com Pipocine</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
