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
    <meta name="description" content="PipoCine - Entre com sua conta Pipocine.">
    <title>PipoCine - Entrar</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/platform-login.css">
</head>
<body>
    <main class="login-container">
        <section class="login-side-image">
            <div class="hero-text-wrapper">
                <h2>A melhor experiencia<br>de cinema em casa.</h2>
            </div>
        </section>

        <section class="login-side-form">
            <div class="form-box platform-auth" data-auth-mode="login">
                <p class="step-kicker" id="step-kicker">Passo 1 de 2</p>
                <h1 id="auth-title">Informe seus dados para entrar</h1>
                <p class="subtitle" id="auth-subtitle">Ou crie uma conta.</p>
                <div class="auth-progress" id="auth-progress" aria-hidden="true">
                    <span class="active"></span>
                    <span></span>
                </div>

                <div id="error-alert" class="error-msg" role="alert">
                    <span id="error-text"></span>
                </div>

                <form id="platform-login-form" autocomplete="on">
                    <div class="auth-step active" data-step="email">
                        <div class="input-group">
                            <input type="text" id="email" name="identifier" placeholder=" " required autocomplete="username" inputmode="email">
                            <label for="email">Email ou numero de celular</label>
                        </div>
                    </div>

                    <div class="auth-step" data-step="password">
                        <button type="button" class="back-step" id="back-to-email">Alterar e-mail</button>
                        <div class="selected-email" id="selected-email"></div>
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder=" " autocomplete="current-password">
                            <label for="password">Senha</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="btn-submit">
                        <span id="btn-text">Continuar</span>
                        <span class="loader" id="btn-loader"></span>
                    </button>
                </form>

                <div class="login-links">
                    <a href="/login/plataforma/register">Criar conta</a>
                    <a href="/login/plataforma/methods">Outras formas de entrar</a>
                </div>
            </div>
        </section>
    </main>

    <div class="auth-modal" id="methods-modal" aria-hidden="true">
        <div class="auth-modal-panel" role="dialog" aria-modal="true" aria-labelledby="methods-title">
            <button type="button" class="modal-close" data-close-modal aria-label="Fechar">x</button>
            <p class="modal-eyebrow">Acesso alternativo</p>
            <h2 id="methods-title">Escolha outro metodo</h2>
            <p class="modal-copy">Use a conta Pipocine sempre que possivel. As opcoes abaixo ficam para recuperacao ou integrações externas.</p>
            <div class="method-list">
                <button type="button" class="method-btn primary-method" id="choose-cineveo">
                    <span>
                        <strong>Entrar com o Cineveo</strong>
                        <small>Conectar usando sua sessao Cineveo ativa.</small>
                    </span>
                    <b>Continuar</b>
                </button>
                <button type="button" class="method-btn disabled" disabled>
                    <span>
                        <strong>Entrar com QR Code</strong>
                        <small>Para TV e dispositivos pareados.</small>
                    </span>
                    <b>Em breve</b>
                </button>
                <button type="button" class="method-btn secondary-method" disabled>
                    <span>
                        <strong>Perdi a senha</strong>
                        <small>Recuperacao de acesso em desenvolvimento.</small>
                    </span>
                </button>
                <button type="button" class="method-btn secondary-method" disabled>
                    <span>
                        <strong>Suporte</strong>
                        <small>Fale com a equipe para resolver problemas de acesso.</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="auth-modal" id="cineveo-consent-modal" aria-hidden="true">
        <div class="auth-modal-panel consent-panel" role="dialog" aria-modal="true" aria-labelledby="consent-title">
            <div class="brand-pair">
                <img src="/assets/img/cineveo.png" alt="Cineveo">
                <span>x</span>
                <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
            </div>
            <h2 id="consent-title">Conectar sua conta Cineveo</h2>
            <p>Ao continuar, voce concorda que o Pipocine use dados da sua conta Cineveo, como e-mail e identificador de sessao, para prosseguir com o login.</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-modal>Cancelar</button>
                <button type="button" class="btn-primary" id="continue-cineveo">Continuar</button>
            </div>
        </div>
    </div>

    <div class="auth-modal" id="cookie-modal" aria-hidden="true">
        <div class="auth-modal-panel consent-panel" role="dialog" aria-modal="true" aria-labelledby="cookie-title">
            <div class="brand-pair">
                <img src="/assets/img/cineveo.png" alt="Cineveo">
                <span>x</span>
                <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
            </div>
            <h2 id="cookie-title">Permitir cookies de terceiros</h2>
            <p>Para entrar com Cineveo, permita cookies de terceiros neste navegador e mantenha sua conta ativa no site do Cineveo. Depois, tente verificar sua sessao.</p>
            <div id="cineveo-status" class="modal-status"></div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-modal>Cancelar</button>
                <button type="button" class="btn-primary" id="check-cineveo-session">Verificar sessao Cineveo</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/platform-login.js"></script>
</body>
</html>
