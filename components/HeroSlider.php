<section class="hero-slider swiper" id="pipo-hero-slider">
    <div class="swiper-wrapper" id="hero-slider-wrapper">
        
        <div class="swiper-slide hero-slide hero-slide--loading">
            <div class="hero-content" style="text-align: center;">
                <div class="hero-loader-spinner"></div>
                <p style="color:rgba(255,255,255,0.6); font-size:0.9rem; margin-top:20px;">Procurando o melhor conteúdo...</p>
            </div>
        </div>

    </div>

    <div class="swiper-pagination"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
</section>

<template id="hero-slide-template">
    <div class="swiper-slide hero-slide">
        <div class="hero-backdrop">
            <img src="" alt="Fundo" class="backdrop-img" loading="lazy">
        </div>
        
        <div class="hero-content">
            <div class="hero-logo-container">
                <img src="" alt="Título" class="hero-logo" loading="lazy">
            </div>

            <div class="hero-metadata">
                <span class="meta-rating rating-icon"></span>
                <span class="meta-genres"></span>
                <span class="meta-year"></span>
                <span class="meta-score">
                    <i data-lucide="star" width="14" height="14"></i>
                    <span class="score-value"></span>
                </span>
            </div>

            <p class="hero-synopsis"></p>

            <div class="hero-actions">
                <button class="btn-primary btn-watch">
                    <i data-lucide="play" width="18" height="18"></i>
                    Assistir
                </button>
            </div>
        </div>
    </div>
</template>