<?php
/**
 * PÁGINA: main.php
 * Landing Page pública do PipoCine — Minimalista & Profissional
 * Se o usuário já estiver logado, redireciona para /home.
 */
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#050508">
    <meta name="description" content="PipoCine — Sua plataforma de streaming definitiva. Filmes, séries, animes e documentários de todas as plataformas em um só lugar.">
    <meta name="keywords" content="pipocine, streaming, filmes, séries, animes, assistir online">
    
    <title>PipoCine — Filmes, Séries e muito mais</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/main.css">
</head>

<body>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         NAVIGATION
         ═════════════════════════════════════════════════════════════════════════════ -->
    <nav class="main-nav" id="main-nav">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <a href="/" class="nav-logo">
                <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
            </a>
            <a href="/login" class="nav-cta">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Entrar
            </a>
        </div>
    </nav>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         HERO SECTION
         ═════════════════════════════════════════════════════════════════════════════ -->
    <section class="main-hero" id="main-hero">
        <div class="main-hero__bg">
            <img src="https://image.tmdb.org/t/p/original/uDgy6hyPd82kOHh6I95FLtLnj6p.jpg"
                 alt="Experiência cinematográfica"
                 loading="eager"
                 id="hero-bg-img">
        </div>

        <div class="main-hero__content">
            <h1 class="main-hero__title" data-reveal>
                Tudo o que você ama.<br>
                <span>Em um só lugar.</span>
            </h1>

            <p class="main-hero__subtitle" data-reveal data-delay="200">
                Filmes, séries, animes e documentários das maiores plataformas do mundo —
                reunidos em uma experiência única, feita para você.
            </p>

            <div class="main-hero__actions" data-reveal data-delay="400">
                <a href="/login" class="btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Começar Agora
                </a>
                <a href="#features" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                    Explorar
                </a>
            </div>
        </div>
    </section>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         INTERACTIVE FEATURES SECTION — Tabs com preview visual
         ═════════════════════════════════════════════════════════════════════════════ -->
    <section class="interactive-features" id="features">
        <div class="container">
            <div class="section-header" data-reveal>
                <span class="section-label">Experiência</span>
                <h2 class="section-title">Tudo que você precisa</h2>
                <p class="section-desc">Uma plataforma completa pensada para oferecer a melhor experiência de streaming.</p>
            </div>

            <div class="features-showcase" data-reveal data-delay="200">
                <!-- Tabs Navigation -->
                <div class="features-tabs">
                    <button class="feature-tab active" data-tab="catalogo" data-reveal data-delay="0">
                        <div class="tab-icon">
                            <img src="/assets/svg/movie.svg" alt="Catálogo" width="24" height="24">
                        </div>
                        <div class="tab-content">
                            <span class="tab-title">Catálogo Imenso</span>
                            <span class="tab-desc">Milhares de títulos</span>
                        </div>
                    </button>

                    <button class="feature-tab" data-tab="perfis" data-reveal data-delay="100">
                        <div class="tab-icon">
                            <img src="/assets/svg/users.svg" alt="Perfis" width="24" height="24">
                        </div>
                        <div class="tab-content">
                            <span class="tab-title">Perfis Personalizados</span>
                            <span class="tab-desc">Para toda a família</span>
                        </div>
                    </button>

                    <button class="feature-tab" data-tab="infantil" data-reveal data-delay="200">
                        <div class="tab-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2z"/>
                                <path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="tab-content">
                            <span class="tab-title">Modo Infantil</span>
                            <span class="tab-desc">Conteúdo seguro</span>
                        </div>
                    </button>

                    <button class="feature-tab" data-tab="dispositivos" data-reveal data-delay="300">
                        <div class="tab-icon">
                            <img src="/assets/svg/tv.svg" alt="Dispositivos" width="24" height="24">
                        </div>
                        <div class="tab-content">
                            <span class="tab-title">Multi-Dispositivo</span>
                            <span class="tab-desc">Assista em qualquer lugar</span>
                        </div>
                    </button>

                    <button class="feature-tab" data-tab="busca" data-reveal data-delay="400">
                        <div class="tab-icon">
                            <img src="/assets/svg/search.svg" alt="Busca" width="24" height="24">
                        </div>
                        <div class="tab-content">
                            <span class="tab-title">Busca Inteligente</span>
                            <span class="tab-desc">Encontre rápido</span>
                        </div>
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="features-panels">
                    <div class="feature-panel active" id="panel-catalogo">
                        <div class="panel-visual">
                            <img src="https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=800&h=500&fit=crop" alt="Catálogo" loading="lazy">
                            <div class="panel-overlay">
                                <div class="floating-cards">
                                    <div class="float-card">10K+ Filmes</div>
                                    <div class="float-card">5K+ Séries</div>
                                    <div class="float-card">Animes</div>
                                    <div class="float-card">Docs</div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-info">
                            <h3>Catálogo Imenso</h3>
                            <p>Milhares de filmes, séries, animes e documentários atualizados diariamente das melhores plataformas do mundo. De clássicos atemporais aos lançamentos mais recentes, temos algo para todos os gostos.</p>
                            <ul class="panel-features">
                                <li>Atualizações diárias</li>
                                <li>Todos os gêneros</li>
                                <li>Conteúdo legendado e dublado</li>
                                <li>Qualidade HD, Full HD e 4K</li>
                            </ul>
                        </div>
                    </div>

                    <div class="feature-panel" id="panel-perfis">
                        <div class="panel-visual">
                            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&h=500&fit=crop" alt="Perfis" loading="lazy">
                            <div class="panel-overlay">
                                <div class="floating-avatars">
                                    <div class="float-avatar" style="background: linear-gradient(135deg, #e50914, #ff6b6b);">A</div>
                                    <div class="float-avatar" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">B</div>
                                    <div class="float-avatar" style="background: linear-gradient(135deg, #22c55e, #4ade80);">C</div>
                                    <div class="float-avatar" style="background: linear-gradient(135deg, #a855f7, #c084fc);">K</div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-info">
                            <h3>Perfis Personalizados</h3>
                            <p>Cada membro da família tem seu próprio perfil com recomendações personalizadas, histórico de visualização e lista de favoritos individuais.</p>
                            <ul class="panel-features">
                                <li>Até 5 perfis por conta</li>
                                <li>Recomendações personalizadas</li>
                                <li>Listas individuais</li>
                                <li>Histórico separado</li>
                            </ul>
                        </div>
                    </div>

                    <div class="feature-panel" id="panel-infantil">
                        <div class="panel-visual">
                            <img src="https://images.unsplash.com/photo-1560167016-022b78a0258e?w=800&h=500&fit=crop" alt="Modo Infantil" loading="lazy">
                            <div class="panel-overlay">
                                <div class="safety-badge">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <span>Seguro para crianças</span>
                                </div>
                            </div>
                        </div>
                        <div class="panel-info">
                            <h3>Modo Infantil</h3>
                            <p>Conteúdo seguro e filtrado especialmente para crianças, com controle parental integrado e uma interface dedicada e amigável.</p>
                            <ul class="panel-features">
                                <li>Filtro de conteúdo automático</li>
                                <li>Interface colorida e amigável</li>
                                <li>Desenhos e filmes educativos</li>
                                <li>Controle parental</li>
                            </ul>
                        </div>
                    </div>

                    <div class="feature-panel" id="panel-dispositivos">
                        <div class="panel-visual">
                            <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&h=500&fit=crop" alt="Dispositivos" loading="lazy">
                            <div class="panel-overlay">
                                <div class="device-icons">
                                    <div class="dev-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        <span>TV</span>
                                    </div>
                                    <div class="dev-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                                        <span>Celular</span>
                                    </div>
                                    <div class="dev-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="12" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
                                        <span>Tablet</span>
                                    </div>
                                    <div class="dev-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
                                        <span>PC</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-info">
                            <h3>Multi-Dispositivo</h3>
                            <p>Assista no computador, celular, tablet ou TV. Interface responsiva e otimizada para todas as telas, com sincronização automática de progresso.</p>
                            <ul class="panel-features">
                                <li>Sincronização entre dispositivos</li>
                                <li>Continue assistindo de onde parou</li>
                                <li>Interface adaptativa</li>
                                <li>Suporte a Chromecast</li>
                            </ul>
                        </div>
                    </div>

                    <div class="feature-panel" id="panel-busca">
                        <div class="panel-visual">
                            <img src="https://images.unsplash.com/photo-1512314889357-e157c22f938d?w=800&h=500&fit=crop" alt="Busca" loading="lazy">
                            <div class="panel-overlay">
                                <div class="search-preview">
                                    <div class="search-bar">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <span>Ação, comédia, drama...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-info">
                            <h3>Busca Inteligente</h3>
                            <p>Encontre qualquer título rapidamente por nome, gênero, ano ou plataforma com nossa busca avançada e filtros inteligentes.</p>
                            <ul class="panel-features">
                                <li>Busca por voz</li>
                                <li>Filtros avançados</li>
                                <li>Sugestões em tempo real</li>
                                <li>Histórico de buscas</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         ANIMATED PLATFORMS SECTION — SVG Path Animations
         ═════════════════════════════════════════════════════════════════════════════ -->
    <section class="animated-platforms" id="platforms">
        <!-- Background Animation -->
        <div class="platforms-animation-bg">
            <img src="/assets/animations/particle-network.svg" alt="" class="anim-bg-network" loading="lazy">
        </div>
        
        <div class="container">
            <div class="section-header" data-reveal>
                <span class="section-label">Integrações</span>
                <h2 class="section-title">Suas plataformas favoritas</h2>
                <p class="section-desc">Conteúdo reunido dos maiores serviços de streaming em um só lugar.</p>
            </div>

            <!-- Connection Paths Animation -->
            <div class="platforms-connections" data-reveal data-delay="100">
                <img src="/assets/animations/connection-paths.svg" alt="" class="anim-connections" loading="lazy">
            </div>

            <!-- Platform Cards with Orbit Animation -->
            <div class="platforms-orbit-container" data-reveal data-delay="200">
                <div class="orbit-visual">
                    <img src="/assets/animations/orbit-rings.svg" alt="" class="anim-orbit" loading="lazy">
                </div>
                
                <div class="platforms-cards-layer">
                    <?php
                    $platforms = [
                        ['nome' => 'Netflix',     'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg', 'class' => 'netflix'],
                        ['nome' => 'Prime Video', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg', 'class' => 'prime'],
                        ['nome' => 'Disney+',     'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg', 'class' => 'disney'],
                        ['nome' => 'HBO Max',     'logo' => 'https://upload.wikimedia.org/wikipedia/commons/c/ce/Max_logo.svg', 'class' => 'hbo'],
                        ['nome' => 'Apple TV+',   'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/28/Apple_TV_Plus_Logo.svg', 'class' => 'apple'],
                        ['nome' => 'Paramount+',  'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a5/Paramount_Plus_logo.svg', 'class' => 'paramount'],
                    ];
                    foreach ($platforms as $i => $p): ?>
                    <div class="platform-orbital-card <?= $p['class'] ?>" title="<?= htmlspecialchars($p['nome']) ?>" style="--index: <?= $i ?>">
                        <div class="card-glow"></div>
                        <div class="card-inner">
                            <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy" onerror="this.style.display='none'">
                        </div>
                        <div class="card-particles">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Streaming Waves Footer -->
            <div class="platforms-waves" data-reveal data-delay="300">
                <img src="/assets/animations/streaming-waves.svg" alt="" class="anim-waves" loading="lazy">
            </div>
        </div>
    </section>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         SHOWCASE SECTION
         ═════════════════════════════════════════════════════════════════════════════ -->
    <section class="main-showcase" id="showcase">
        <div class="container">
            <div class="showcase-grid">
                <div class="showcase-visual" data-reveal>
                    <img src="https://image.tmdb.org/t/p/original/56v2KjBlYj4kBpmOsgj5MIYv6hI.jpg"
                         alt="Experiência cinematográfica"
                         loading="lazy">
                </div>
                <div class="showcase-content" data-reveal data-delay="200">
                    <h2>Experiência Cinematográfica em Casa</h2>
                    <p>Interface premium pensada em cada detalhe para que assistir seus filmes e séries favoritos seja uma experiência única.</p>
                    <ul class="checklist">
                        <li>Player profissional com controle total</li>
                        <li>Continue de onde parou automaticamente</li>
                        <li>Recomendações inteligentes por perfil</li>
                        <li>Busca avançada por título, gênero ou ano</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         CTA SECTION
         ═════════════════════════════════════════════════════════════════════════════ -->
    <section class="main-cta" id="cta">
        <div class="container">
            <div class="cta-content" data-reveal>
                <h2>Pronto para começar?</h2>
                <p>Entre agora e descubra o melhor do entretenimento.</p>
                <a href="/login" class="btn-primary" style="font-size: 16px; padding: 18px 40px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Entrar no PipoCine
                </a>
            </div>
        </div>
    </section>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         FOOTER
         ═════════════════════════════════════════════════════════════════════════════ -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-logo">
                <img src="/assets/img/logo-pipocine.png" alt="PipoCine">
            </div>
            <div class="footer-links">
                <a href="/login">Entrar</a>
                <a href="#">Termos de Uso</a>
                <a href="#">Privacidade</a>
                <a href="#">Contato</a>
            </div>
            <p class="footer-copy">&copy; <?= date('Y') ?> PipoCine. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- ═════════════════════════════════════════════════════════════════════════════
         SCRIPTS
         ═════════════════════════════════════════════════════════════════════════════ -->
    <script src="/assets/main.js"></script>

</body>
</html>
