<?php
require_once __DIR__ . '/../database/db.php';

// Verificação de segurança: Redireciona para o login se não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Detecta se o perfil ativo é infantil
$isKidsProfile = isset($_SESSION['profile_is_kids']) ? (bool) $_SESSION['profile_is_kids'] : false;

// Caso profile_is_kids não esteja na sessão ainda (perfis selecionados antes desta atualização),
// busca diretamente do banco de dados como fallback
if (!isset($_SESSION['profile_is_kids']) && isset($_SESSION['profile_id'])) {
    require_once __DIR__ . '/../models/ProfileModel.php';
    $profileModel = new ProfileModel($pdo);
    $profileData = $profileModel->findById((int) $_SESSION['profile_id']);
    if ($profileData) {
        $isKidsProfile = (bool) (int) $profileData['is_kids'];
        $_SESSION['profile_is_kids'] = $isKidsProfile; // Popula a sessão para próximas chamadas
    }
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <link rel="stylesheet" href="/assets/css/hero-slider.css">
    <link rel="stylesheet" href="/assets/css/content-card.css">

    <style>
        /* ── Layout principal da Home ──────────────────────────────────────────── */

        /* Compensa o header fixo: o hero começa LOGO abaixo do topo */
        .main-content {
            padding-top: 0;
            /* O hero é full-bleed — vai de borda a borda */
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
            background: linear-gradient(to bottom,
                    transparent 0%,
                    var(--bg-base) 100%);
            z-index: 5;
            /* acima do backdrop, abaixo do conteúdo */
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

        /* Badge de perfil infantil */
        .kids-profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #f9a825, #ff6f00);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 20px;
            margin: 12px 4% 0;
            text-transform: uppercase;
        }

        .kids-profile-badge svg {
            width: 13px;
            height: 13px;
        }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/../components/Header.php'; ?>

    <main class="main-content">

        <?php require_once __DIR__ . '/../components/HeroSlider.php'; ?>

        <?php require_once __DIR__ . '/../components/ContentCard.php'; ?>

        <?php if ($isKidsProfile): ?>
            <!-- Badge visual de perfil infantil -->
            <div class="kids-profile-badge">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2z" />
                    <path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z" />
                </svg>
                Modo Infantil Ativo
            </div>
        <?php endif; ?>

        <div class="content-rails-container">

            <?php if ($isKidsProfile): ?>
                <!-- Trilhos para Perfil Infantil -->
                <div id="rail-kids-animacao"></div>
                <div id="rail-kids-familia"></div>
                <div id="rail-kids-comedia"></div>
                <div id="rail-kids-fantasia"></div>
                <div id="rail-kids-musica"></div>
            <?php else: ?>
                <!-- Trilhos para Perfil Adulto -->
                <div id="rail-series"></div>
                <div id="rail-filmes"></div>
                <div id="rail-animes"></div>
                <div id="rail-terror"></div>
                <div id="rail-documentarios"></div>
                <div id="rail-comedia"></div>
                <div id="rail-romance"></div>
                <div id="rail-ficcao"></div>
            <?php endif; ?>

        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

    <script src="/assets/js/header.js"></script>
    <script src="/assets/js/hero-slider.js"></script>
    <script src="/assets/js/content-card.js"></script>

    <script>
        // Expõe o tipo de perfil para uso no JS (ex.: hero-slider, futuras validações)
        window.PIPO_IS_KIDS = <?= $isKidsProfile ? 'true' : 'false' ?>;

        document.addEventListener('DOMContentLoaded', () => {
            // Inicializa ícones Lucide
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Configuração dos Trilhos de Conteúdo
            if (typeof PipoRail !== 'undefined') {

                if (window.PIPO_IS_KIDS) {
                    // ── Trilhos para Perfil Infantil ──────────────────────────
                    new PipoRail('rail-kids-animacao', 'Animações e Desenhos', 'animacao_filmes', 18);
                    new PipoRail('rail-kids-familia', 'Para toda a Família', 'familia_filmes', 18);
                    new PipoRail('rail-kids-comedia', 'Diversão Garantida', 'comedia_filmes', 18);
                    new PipoRail('rail-kids-fantasia', 'Mundos de Fantasia', 'fantasia_filmes', 18);
                    new PipoRail('rail-kids-musica', 'Músicas e Aventuras', 'musica_filmes', 18);
                } else {
                    // ── Trilhos para Perfil Adulto ────────────────────────────
                    // 1. Séries
                    new PipoRail('rail-series', 'Séries para Maratonar', 'top_series', 18);
                    // 2. Filmes
                    new PipoRail('rail-filmes', 'Filmes de Sucesso', 'top_filmes', 18);
                    // 3. Animes (Usando categoria animação)
                    new PipoRail('rail-animes', 'Animes e Animações', 'animacao_filmes', 18);
                    // 4. Terror e Suspense
                    new PipoRail('rail-terror', 'Terror e Suspense', 'terror_filmes', 18);
                    // 5. Documentários
                    new PipoRail('rail-documentarios', 'Documentários', 'documentario_filmes', 18);
                    // 6. Comédia
                    new PipoRail('rail-comedia', 'Para dar Boas Risadas', 'comedia_filmes', 18);
                    // 7. Romance
                    new PipoRail('rail-romance', 'Romances Inesquecíveis', 'romance_filmes', 18);
                    // 8. Ficção Científica
                    new PipoRail('rail-ficcao', 'Ficção Científica', 'ficcao_filmes', 18);
                }
            }
        });
    </script>

</body>

</html>