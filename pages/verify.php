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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="/assets/css/verify.css">
</head>
<body>
    <main class="verify-shell">
        <section class="verify-panel" aria-labelledby="verify-title">
            <div class="verify-topbar">
                <a class="verify-brand" href="/login" aria-label="Voltar para o login">
                    <span>Pipo</span><strong>CINE</strong>
                </a>
                <span class="verify-pill">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3z"></path>
                        <path d="M9.5 12l1.8 1.8 3.6-4"></path>
                    </svg>
                    2FA
                </span>
            </div>

            <header class="verify-header">
                <div class="verify-kicker">
                    <span class="verify-kicker-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3z"></path>
                            <path d="M12 8v5"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </span>
                    Verificacao em duas etapas
                </div>
                <h1 id="verify-title">Confirme seu acesso</h1>
                <p class="verify-subtitle" id="verify-copy">Digite o codigo de 6 digitos do Google Authenticator.</p>
            </header>

            <div id="verify-alert" class="verify-alert" role="alert"></div>

            <form id="verify-form" autocomplete="one-time-code">
                <input type="hidden" id="verify-token" value="<?= htmlspecialchars($verifyToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="verify-code-section">
                    <label class="code-label">Codigo de 6 digitos</label>
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
                    <span>
                        <strong>Lembrar este dispositivo por 30 dias</strong>
                        <small>Nao sera solicitado um novo codigo neste dispositivo.</small>
                    </span>
                </label>

                <button type="submit" class="verify-submit" id="verify-submit">
                    <svg class="submit-shield" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3z"></path>
                        <path d="M9.5 12l1.8 1.8 3.6-4"></path>
                    </svg>
                    <span id="verify-submit-text">Verificar e entrar</span>
                    <svg class="submit-arrow" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M13 6l6 6-6 6"></path>
                    </svg>
                    <span class="verify-loader" aria-hidden="true"></span>
                </button>
            </form>

            <div class="verify-separator"><span>ou</span></div>

            <div class="verify-help">
                <a href="#" class="verify-help-btn">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"></path>
                    </svg>
                    Falar com o suporte
                    <span>›</span>
                </a>
                <a href="#" class="verify-help-btn">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3l10 18H2L12 3z"></path>
                        <path d="M12 9v5"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                    Reportar um problema
                    <span>›</span>
                </a>
            </div>

            <footer class="verify-actions">
                <button type="button" id="lost-code-btn">Perdi o codigo</button>
                <a href="/login" id="back-login">Voltar ao login</a>
            </footer>
        </section>
    </main>

    <script src="/assets/js/verify.js"></script>
</body>
</html>
