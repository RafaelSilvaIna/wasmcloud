<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../../database/db.php';
}

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
    <title>PipoCine - Criar conta</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="login-container">
        <section class="login-side-image">
            <div class="hero-text-wrapper">
                <h2>Sua sala pronta<br>para maratonar.</h2>
            </div>
        </section>

        <section class="login-side-form">
            <div class="form-box platform-auth" data-auth-mode="register">
                <p class="step-kicker" id="step-kicker">Passo 1 de 3</p>
                <h1 id="auth-title">Crie sua conta</h1>
                <p class="subtitle" id="auth-subtitle">Informe seu email para comecar.</p>
                <div class="auth-progress" id="auth-progress" aria-hidden="true">
                    <span class="active"></span>
                    <span></span>
                    <span></span>
                </div>

                <div id="error-alert" class="error-msg" role="alert">
                    <span id="error-text"></span>
                </div>

                <form id="platform-register-form" autocomplete="on">
                    <div class="auth-step active" data-step="email">
                        <div class="input-group">
                            <input type="text" id="email" name="identifier" placeholder=" " required autocomplete="username" inputmode="email">
                            <label for="email">Email ou numero de celular</label>
                        </div>
                    </div>

                    <div class="auth-step" data-step="password">
                        <button type="button" class="back-step" data-back-register>Alterar e-mail</button>
                        <div class="selected-email" id="selected-email"></div>
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder=" " autocomplete="new-password">
                            <label for="password">Senha</label>
                        </div>
                        <div class="input-group">
                            <input type="password" id="password-confirmation" name="password_confirmation" placeholder=" " autocomplete="new-password">
                            <label for="password-confirmation">Confirmar senha</label>
                        </div>
                    </div>

                    <div class="auth-step" data-step="name">
                        <button type="button" class="back-step" data-back-register>Alterar senha</button>
                        <div class="input-group">
                            <input type="text" id="full-name" name="full_name" placeholder=" " autocomplete="name">
                            <label for="full-name">Nome completo</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="btn-submit">
                        <span id="btn-text">Continuar</span>
                        <span class="loader" id="btn-loader"></span>
                    </button>
                </form>

                <div class="login-links">
                    <a href="/login">Ja tenho conta</a>
                </div>
            </div>
        </section>
    </main>

    <script src="/assets/js/platform-register.js"></script>
</body>
</html>
