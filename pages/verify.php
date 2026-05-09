<?php
require_once __DIR__ . '/../database/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /home');
    exit;
}

$verifyToken = preg_replace('/[^a-fA-F0-9]/', '', (string)($_GET['verify'] ?? ''));

if (strlen($verifyToken) !== 64) {
    require_once __DIR__ . '/../components/NotContent.php';
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="PipoCine - Acesso incorreto.">
        <title>PipoCine - Acesso incorreto</title>
        <link rel="icon" type="image/png" href="/assets/img/favicon.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
        <link rel="stylesheet" href="/assets/css/not-content.css">
    </head>
    <body>
        <main class="not-content-shell">
            <?php renderNotContent([
                'eyebrow' => 'Acesso incorreto',
                'title' => 'Nao conseguimos verificar este acesso',
                'message' => 'As informacoes que o navegador enviou ao PipoCine nao foram suficientes para concluir este processo.',
                'detail' => 'Feche esta janela e reabra o site do PipoCine pela URL oficial.',
                'actionLabel' => 'Abrir pipocine.site',
                'actionHref' => 'https://pipocine.site',
            ]); ?>
        </main>
    </body>
    </html>
    <?php
    exit;
}
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
        <aside class="verify-flag" aria-label="PipoCine suporte">
            <div class="verify-flag-copy">
                <span>Acesso seguro</span>
                <p>Use o codigo do seu autenticador para continuar.</p>
            </div>

            <a href="#" class="verify-support-link">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"></path>
                </svg>
                Falar com suporte
            </a>
        </aside>

        <section class="verify-panel" aria-labelledby="verify-title">
            <header class="verify-header">
                <span class="verify-kicker">Verificacao em duas etapas</span>
                <h1 id="verify-title">Digite seu codigo</h1>
                <p class="verify-subtitle" id="verify-copy">Insira os 6 digitos do Google Authenticator.</p>
            </header>

            <div id="verify-alert" class="verify-alert" role="alert"></div>

            <form id="verify-form" autocomplete="one-time-code">
                <input type="hidden" id="verify-token" value="<?= htmlspecialchars($verifyToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="verify-code-section">
                    <label class="code-label">Codigo</label>
                    <div class="code-inputs" aria-label="Codigo de verificacao">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="one-time-code">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                        <input class="code-input" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1">
                    </div>
                </div>

                <button type="submit" class="verify-submit" id="verify-submit">
                    <span id="verify-submit-text">Verificar e entrar</span>
                    <svg class="submit-arrow" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M13 6l6 6-6 6"></path>
                    </svg>
                    <span class="verify-loader" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="verify-actions">
                <a href="#" class="verify-mobile-support">Falar com suporte</a>
                <a href="/login" id="back-login">Voltar ao login</a>
            </footer>
        </section>
    </main>

    <script src="/assets/js/verify.js"></script>
</body>
</html>
