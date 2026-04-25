<?php
require_once __DIR__ . '/../database/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PipoCine - Entre com suas credenciais para acessar a melhor experiência de cinema em casa.">
    <title>PipoCine - Entrar</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-side-image">
            <div class="hero-text-wrapper">
                <h2>A melhor experiência<br>de cinema em casa.</h2>
            </div>
        </div>
        
        <div class="login-side-form">
            <div class="form-box">
                <div class="logo-box">
                    <img src="/assets/img/logo-pipocine.png" alt="PipoCine Logo">
                </div>
                
                <h1>Entrar</h1>
                <p class="subtitle">Use suas credenciais Cineveo para entrar.</p>

                <div id="error-alert" class="error-msg" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span id="error-text"></span>
                </div>

                <form id="login-form" autocomplete="on">
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder=" " required autocomplete="email">
                        <label for="email">E-mail</label>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
                        <label for="password">Senha</label>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="btn-submit">
                        <span id="btn-text">Entrar na Plataforma</span>
                        <span class="loader" id="btn-loader"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/login.js"></script>
</body>
</html>