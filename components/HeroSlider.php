<section class="hero-slider swiper" id="pipo-hero-slider">
    <div class="swiper-wrapper" id="hero-slider-wrapper">

        <!-- Estado de carregamento -->
        <div class="swiper-slide hero-slide hero-slide--loading">
            <div class="hero-content">
                <div class="hero-loader-spinner"></div>
            </div>
        </div>

    </div>

    <div class="swiper-pagination"></div>
    <!-- Setas ocultas via CSS, mantidas para acessibilidade via teclado -->
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
</section>

<template id="hero-slide-template">
    <div class="swiper-slide hero-slide">

        <!-- Imagem de fundo -->
        <div class="hero-backdrop">
            <img src="" alt="" class="backdrop-img" loading="lazy">
        </div>

        <!-- Conteúdo principal -->
        <div class="hero-content">
            <div class="hero-info-area">

                <!-- Logo ou Título em texto (escala pelo JS) -->
                <div class="hero-logo-container">
                    <img src="" alt="" class="hero-logo" loading="lazy">
                </div>

                <!-- Metadata: ★ score • ano • [Genre] [Genre] -->
                <div class="hero-metadata">
                    <span class="meta-rating rating-icon"></span>
                    <span class="meta-score">
                        <i data-lucide="star" width="13" height="13"></i>
                        <span class="score-value"></span>
                    </span>
                    <span class="meta-sep">•</span>
                    <span class="meta-year"></span>
                    <span class="meta-sep">•</span>
                    <span class="meta-genre-tags"></span>
                </div>

                <!-- Sinopse (2-3 linhas) -->
                <p class="hero-synopsis"></p>

                <!-- Botão Assistir -->
                <div class="hero-actions">
                    <button class="btn-watch" aria-label="Assistir agora">
                        <i data-lucide="play" width="16" height="16"></i>
                        Assistir
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>