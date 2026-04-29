<?php
// components/ContentCard.php
?>

<!-- Template: Skeleton de carregamento (card padrão) -->
<template id="pipo-card-skeleton-template">
    <div class="pipo-card pipo-skeleton"></div>
</template>

<!-- Template: Skeleton de carregamento (Top 10) -->
<template id="pipo-card-top10-skeleton-template">
    <div class="pipo-card pipo-card--top10 pipo-skeleton"></div>
</template>

<!-- Template: Card padrão -->
<template id="pipo-card-template">
    <div class="pipo-card" tabindex="0" role="button">

        <div class="pipo-card-image-wrapper">
            <img src="" alt="" class="pipo-card-poster" loading="lazy">
        </div>

        <!-- Badge tipo (Filme / Série) -->
        <span class="pipo-card-type-badge"></span>

        <div class="pipo-card-hover-panel">
            <div class="pipo-card-hover-top">
                <!-- Thumbnail maior no hover -->
                <div class="pipo-card-hover-thumb">
                    <img src="" alt="" class="pipo-card-hover-img" loading="lazy">
                    <div class="pipo-card-hover-gradient"></div>
                </div>
            </div>

            <div class="pipo-card-hover-body">
                <div class="pipo-card-hover-actions">
                    <button class="pipo-btn pipo-btn--play" title="Assistir" aria-label="Assistir">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                            <polygon points="5,3 19,12 5,21"/>
                        </svg>
                    </button>
                    <button class="pipo-btn pipo-btn--list" title="Minha Lista" aria-label="Adicionar à lista">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    <div class="pipo-card-score-badge"></div>
                </div>

                <h3 class="pipo-card-hover-title"></h3>

                <div class="pipo-card-hover-meta">
                    <span class="meta-match"></span>
                    <span class="meta-year"></span>
                    <span class="meta-duration"></span>
                    <span class="rating-icon rating-mini"></span>
                </div>

                <div class="pipo-card-hover-genres"></div>
            </div>
        </div>

    </div>
</template>

<!-- Template: Card Top 10 (número + poster sobreposto) -->
<template id="pipo-card-top10-template">
    <div class="pipo-card pipo-card--top10" tabindex="0" role="button">

        <span class="pipo-card-rank"></span>

        <div class="pipo-card-image-wrapper">
            <img src="" alt="" class="pipo-card-poster" loading="lazy">
        </div>

        <span class="pipo-card-type-badge"></span>

        <div class="pipo-card-hover-panel">
            <div class="pipo-card-hover-top">
                <div class="pipo-card-hover-thumb">
                    <img src="" alt="" class="pipo-card-hover-img" loading="lazy">
                    <div class="pipo-card-hover-gradient"></div>
                </div>
            </div>

            <div class="pipo-card-hover-body">
                <div class="pipo-card-hover-actions">
                    <button class="pipo-btn pipo-btn--play" title="Assistir" aria-label="Assistir">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                            <polygon points="5,3 19,12 5,21"/>
                        </svg>
                    </button>
                    <button class="pipo-btn pipo-btn--list" title="Minha Lista" aria-label="Adicionar à lista">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    <div class="pipo-card-score-badge"></div>
                </div>

                <h3 class="pipo-card-hover-title"></h3>

                <div class="pipo-card-hover-meta">
                    <span class="meta-match"></span>
                    <span class="meta-year"></span>
                    <span class="meta-duration"></span>
                    <span class="rating-icon rating-mini"></span>
                </div>

                <div class="pipo-card-hover-genres"></div>
            </div>
        </div>

    </div>
</template>
