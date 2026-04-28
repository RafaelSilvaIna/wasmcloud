<?php
// components/ContentCard.php
?>

<template id="pipo-card-skeleton-template">
    <div class="pipo-card pipo-skeleton"></div>
</template>

<template id="pipo-card-template">
    <div class="pipo-card" tabindex="0">
        
        <div class="pipo-card-image-wrapper">
            <img src="" alt="Capa" class="pipo-card-poster" loading="lazy">
        </div>
        
        <div class="pipo-card-overlay">
            <button class="btn-play-center" title="Assistir">
                <i data-lucide="play" width="22" height="22" fill="currentColor"></i>
            </button>
        </div>

        <div class="pipo-card-info">
            <h3 class="pipo-card-title"></h3>
            
            <div class="pipo-card-meta">
                <span class="meta-rating">
                    <i data-lucide="star" width="12" height="12" fill="currentColor" style="color: #ffb800;"></i> 
                    <span class="score-value"></span>
                </span>
                <span class="meta-dot">&bull;</span>
                <span class="release-year"></span>
                <span class="meta-dot">&bull;</span>
                <span class="rating-icon rating-mini"></span>
            </div>
            
            <div class="pipo-card-genres"></div>
        </div>
        
    </div>
</template>