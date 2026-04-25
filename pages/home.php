<?php
/* pages/home.php - Uma página de teste para validar o sistema de cores */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Sistema de Cores</title>
    
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <script src="/assets/js/theme-manager.js"></script>
</head>
<body>

    <header class="card" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <img src="/assets/img/logo-pipocine.png" alt="PipoCine Logo" style="height: 50px;">
        
        <nav>
            <a href="#" style="margin-right: 15px;">Início</a>
            <a href="#" style="margin-right: 15px;">Filmes</a>
            <button id="theme-toggle" class="btn-primary" style="background-color: var(--color-secondary); padding: 5px 10px;">Trocar Tema</button>
        </nav>
    </header>

    <main>
        <h1>Bem-vindo ao PipoCine</h1>
        <p style="color: var(--text-secondary);">Este é um exemplo profissional de como todas as páginas do aplicativo utilizarão as cores globais.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <div class="card">
                <div style="background-color: #333; height: 150px; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #777;">Poster</div>
                <h3>Nome do Filme</h3>
                <p style="font-size: 0.9em; color: var(--text-muted);">Ação, Aventura</p>
                <button class="btn-primary">Assistir</button>
            </div>
            
            <div class="card">
                <div style="background-color: #333; height: 150px; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #777;">Poster</div>
                <h3>Série Incrível</h3>
                <p style="font-size: 0.9em; color: var(--text-muted);">Drama, Sci-Fi</p>
                <span style="color: var(--color-primary); font-weight: bold;">⭐ 4.8</span>
            </div>
        </div>
    </main>

</body>
</html>