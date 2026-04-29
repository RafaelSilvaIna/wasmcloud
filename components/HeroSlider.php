<section class="hero-slider swiper" id="pipo-hero-slider" aria-label="Destaques">
    <div class="swiper-wrapper" id="hero-slider-wrapper">

        <!-- Estado de carregamento inicial -->
        <div class="swiper-slide hero-slide hero-slide--skeleton">
            <div class="hero-backdrop">
                <div class="hero-skeleton-bg"></div>
            </div>
            <div class="hero-content">
                <div class="hero-info-area">
                    <div class="skeleton skeleton-logo"></div>
                    <div class="skeleton skeleton-meta"></div>
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text skeleton-text--short"></div>
                    <div class="skeleton skeleton-btn"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Paginação -->
    <div class="swiper-pagination hero-pagination"></div>

    <!-- Setas de navegação -->
    <button class="hero-nav hero-nav--prev" aria-label="Slide anterior">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button class="hero-nav hero-nav--next" aria-label="Próximo slide">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
</section>

<!-- Template de slide (clonado via JS) -->
<template id="hero-slide-template">
    <div class="swiper-slide hero-slide">

        <!-- Backdrop com overlay gradiente -->
        <div class="hero-backdrop">
            <img src="" alt="" class="backdrop-img" loading="eager" decoding="async">
            <div class="hero-vignette"></div>
        </div>

        <!-- Área de conteúdo alinhada à esquerda (Netflix) -->
        <div class="hero-content">
            <div class="hero-info-area">

                <!-- Tipo de conteúdo (Filme / Série) -->
                <div class="hero-type-label">
                    <span class="type-dot"></span>
                    <span class="type-text"></span>
                </div>

                <!-- Logo ou Título em texto -->
                <div class="hero-logo-container">
                    <img src="" alt="" class="hero-logo" loading="eager">
                </div>

                <!-- Metadata: score • ano • classificação • gêneros -->
                <div class="hero-metadata">
                    <span class="meta-score">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span class="score-value"></span>
                    </span>
                    <span class="meta-dot"></span>
                    <span class="meta-year"></span>
                    <span class="meta-dot"></span>
                    <span class="meta-cert"></span>
                    <span class="meta-dot meta-dot--genres"></span>
                    <span class="meta-genre-tags"></span>
                </div>

                <!-- Sinopse (3 linhas máx.) -->
                <p class="hero-synopsis"></p>

                <!-- Ações -->
                <div class="hero-actions">
                    <button class="hero-btn hero-btn--primary" aria-label="Assistir agora">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Assistir
                    </button>
                    <button class="hero-btn hero-btn--secondary hero-btn--info" aria-label="Mais informações">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Mais Infos
                    </button>
                </div>

                <!-- Duração ou Temporadas -->
                <span class="hero-duration"></span>

            </div>
        </div>

        <!-- Progress bar de autoplay -->
        <div class="hero-progress-bar">
            <div class="hero-progress-fill"></div>
        </div>

    </div>
</template>
