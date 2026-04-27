<?php
// components/ContentCard.php
?>

<template id="pipo-card-skeleton-template">
    <div class="pipo-card pipo-skeleton"></div>
</template>

<template id="pipo-card-template">
    <div class="pipo-card" tabindex="0">
        
        <img src="" alt="Capa" class="pipo-card-poster" loading="lazy">
        
        <div class="pipo-card-overlay"></div>

        <div class="pipo-card-info">
            <h3 class="pipo-card-title"></h3>
            
            <div class="pipo-card-actions">
                <button class="btn-circle btn-play-mini" title="Assistir"><i data-lucide="play" width="16" height="16" fill="currentColor"></i></button>
                <button class="btn-circle btn-sec" title="Adicionar à Minha Lista"><i data-lucide="plus" width="16" height="16"></i></button>
                <button class="btn-circle btn-sec" title="Gostei"><i data-lucide="thumbs-up" width="14" height="14"></i></button>
                <button class="btn-circle btn-sec btn-more" title="Mais Detalhes"><i data-lucide="chevron-down" width="18" height="18"></i></button>
            </div>
            
            <div class="pipo-card-meta">
                <span class="match-score"><span class="score-value"></span>% Relevante</span>
                <span class="rating-icon rating-mini"></span>
                <span class="release-year"></span>
            </div>
            
            <div class="pipo-card-genres"></div>
        </div>
        
    </div>
</template>