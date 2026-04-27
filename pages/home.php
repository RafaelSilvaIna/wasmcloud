<?php
require_once __DIR__ . '/../database/db.php';

// Verificação de segurança: Redireciona para o login se não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0c10">
    <title>PipoCine — Início</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
    
    <link rel="stylesheet" href="/assets/css/hero-slider.css">
    <link rel="stylesheet" href="/assets/css/content-card.css">
    
    <style>
        /* ── Layout principal da Home ──────────────────────────────────────────── */

        /* Compensa o header fixo: o hero começa LOGO abaixo do topo */
        .main-content {
            padding-top: 0; /* O hero é full-bleed — vai de borda a borda */
        }

        /* O Hero precisa de um gradiente extra na base para fundir
           suavemente com o fundo da página (--bg-base: #0a0c10).          */
        .hero-slider::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            /* Gradiente que "derrama" o hero no fundo da página */
            height: 160px;
            background: linear-gradient(
                to bottom,
                transparent 0%,
                var(--bg-base) 100%
            );
            z-index: 5;          /* acima do backdrop, abaixo do conteúdo */
            pointer-events: none;
        }

        /* Container dos trilhos: posicionamento normal no fluxo.
           O gradiente do hero já cria a ilusão de continuidade. */
        .content-rails-container {
            position: relative;
            z-index: 10;
            background-color: var(--bg-base);
            padding-top: 28px;
            padding-bottom: 120px;
        }

        @media (max-width: 768px) {
            .content-rails-container {
                padding-top: 16px;
                padding-bottom: 100px;
            }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/../components/Header.php'; ?>

    <main class="main-content">
        
        <?php require_once __DIR__ . '/../components/HeroSlider.php'; ?>
        
        <?php require_once __DIR__ . '/../components/ContentCard.php'; ?>
        
        <div class="content-rails-container">
            
            <div id="rail-series"></div>
            <div id="rail-filmes"></div>
            <div id="rail-animes"></div>
            <div id="rail-terror"></div>
            <div id="rail-documentarios"></div>
            <div id="rail-comedia"></div>
            <div id="rail-romance"></div>
            <div id="rail-ficcao"></div>

        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <script src="/assets/js/header.js"></script>
    <script src="/assets/js/hero-slider.js"></script>
    <script src="/assets/js/content-card.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Inicializa ícones Lucide
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Configuração dos Trilhos de Conteúdo
            // Sintaxe: new PipoRail(ID_DA_DIV, TÍTULO_VISÍVEL, CATEGORIA_API, LIMITE)
            if (typeof PipoRail !== 'undefined') {
                
                // 1. Séries
                new PipoRail('rail-series', 'Séries para Maratonar', 'top_series', 18);
                
                // 2. Filmes
                new PipoRail('rail-filmes', 'Filmes de Sucesso', 'top_filmes', 18);
                
                // 3. Animes (Usando categoria animação)
                new PipoRail('rail-animes', 'Animes e Animações', 'animacao_filmes', 18);
                
                // 4. Terror
                new PipoRail('rail-terror', 'Terror e Suspense', 'terror_filmes', 18);
                
                // 5. Documentário
                new PipoRail('rail-documentarios', 'Documentários', 'documentario_filmes', 18);
                
                // 6. Comédia
                new PipoRail('rail-comedia', 'Para dar Boas Risadas', 'comedia_filmes', 18);
                
                // 7. Romance
                new PipoRail('rail-romance', 'Romances Inesquecíveis', 'romance_filmes', 18);
                
                // 8. Ficção Científica
                new PipoRail('rail-ficcao', 'Ficção Científica', 'ficcao_filmes', 18);
            }
        });
    </script>

</body>
</html>