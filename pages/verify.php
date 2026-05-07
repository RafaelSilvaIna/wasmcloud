<?php
require_once __DIR__ . '/../database/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /home');
    exit;
}

$verifyToken = preg_replace('/[^a-fA-F0-9]/', '', (string)($_GET['verify'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PipoCine - Confirme o codigo da verificacao em duas etapas.">
    <title>PipoCine - Verificacao</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/assets/css/verify.css">
</head>
<body>
    <main class="verify-shell">
        <section class="verify-panel" aria-labelledby="verify-title">
            <div class="verify-topbar">
                <a class="verify-brand" href="/login" aria-label="Voltar para o login">
                    <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
                </a>
                <span class="verify-pill">2FA</span>
            </div>

            <header class="verify-header">
                <div class="verify-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        <path d="M12 15v2"></path>
                    </svg>
                </div>
                <div class="verify-heading">
                    <span class="verify-kicker">Verificacao segura</span>
                    <h1 id="verify-title">Confirme seu acesso</h1>
                    <p class="verify-subtitle" id="verify-copy">Digite o codigo de 6 digitos do Google Authenticator.</p>
                </div>
            </header>

            <div id="verify-alert" class="verify-alert" role="alert"></div>

            <form id="verify-form" autocomplete="one-time-code">
                <input type="hidden" id="verify-token" value="<?= htmlspecialchars($verifyToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="verify-code-section">
                    <label class="code-label">Codigo temporario</label>
                    <div class="code-inputs" aria-label="Codigo de verificacao">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="one-time-code">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                    </div>
                </div>

                <div class="backup-field" id="backup-field">
                    <label for="backup-code">Codigo de backup</label>
                    <input type="text" id="backup-code" placeholder="Ex: 12345678" inputmode="numeric" autocomplete="off">
                </div>

                <label class="remember-row">
                    <input type="checkbox" id="remember-device">
                    <span>Lembrar este dispositivo por 30 dias</span>
                </label>

                <button type="submit" class="verify-submit" id="verify-submit">
                    <span id="verify-submit-text">Verificar e entrar</span>
                    <span class="verify-loader" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="verify-actions">
                <button type="button" id="lost-code-btn">Perdi o codigo</button>
                <a href="/login" id="back-login">Voltar ao login</a>
            </footer>
        </section>
    </main>

    <script src="/assets/js/verify.js"></script>
</body>
</html>
