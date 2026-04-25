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
    <title>PipoCine - Entrar</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, rgba(10,12,16,0.8) 0%, var(--bg-base) 100%), url('https://image.tmdb.org/t/p/original/wPU78OPN4BYEgWYdX84A65WJw7.jpg') center/cover no-repeat;
            padding: 20px;
        }
        .login-box {
            background-color: rgba(18, 21, 28, 0.95);
            backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 16px;
            border: 1px solid var(--border-subtle);
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .login-logo {
            height: 70px;
            margin-bottom: 30px;
        }
        .cineveo-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 100%;
            background-color: var(--color-secondary);
            color: #fff;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all var(--transition-fast);
            margin-top: 20px;
        }
        .cineveo-btn:hover {
            transform: scale(1.02);
            background-color: var(--color-secondary-hover);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <img src="/assets/img/logo-pipocine.png" alt="PipoCine" class="login-logo">
            <h2 style="color: var(--text-pure); margin-bottom: 10px;">Bem-vindo de volta!</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">O PipoCine faz parte da rede Cineveo.</p>
            
            <a href="https://cineveo.com/login?redirect=https://pipocine.site/home" class="cineveo-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Entrar com Cineveo
            </a>
            
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 25px;">
                Ao entrar, você concorda com os Termos de Serviço da plataforma.
            </p>
        </div>
    </div>
</body>
</html>