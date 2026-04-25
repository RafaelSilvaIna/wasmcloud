<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Início</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <script src="/assets/js/theme-manager.js"></script>
</head>
<body>

    <?php require_once __DIR__ . '/../components/Header.php'; ?>

    <main style="padding-top: 0;">
        <div style="height: 70vh; background: linear-gradient(to bottom, rgba(10,12,16,0.2) 0%, var(--bg-base) 100%), url('https://image.tmdb.org/t/p/original/wPU78OPN4BYEgWYdX84A65WJw7.jpg') center/cover no-repeat; position: relative;">
            <div style="position: absolute; bottom: 20%; left: 40px;">
                <h1 style="font-size: 3.5rem; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">Filme em Destaque</h1>
                <p style="font-size: 1.2rem; max-width: 600px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">Uma descrição épica para testar o fundo transparente do nosso novo header incrível.</p>
                <div style="margin-top: 20px; display: flex; gap: 15px;">
                    <button class="btn-primary">▶ Assistir Agora</button>
                    <button class="btn-primary" style="background-color: rgba(109, 109, 110, 0.7);">Mais Informações</button>
                </div>
            </div>
        </div>

        <div style="height: 1500px; padding: 40px;">
            <h2>Adicionados Recentemente</h2>
            <p>Faça scroll para baixo para testar o efeito do cabeçalho!</p>
            <br>
            <button id="theme-toggle" class="btn-primary" style="background-color: var(--color-secondary);">Testar Troca de Tema</button>
        </div>
    </main>

    <script src="/assets/js/header.js"></script>
</body>
</html>