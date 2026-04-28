<?php
// components/ExhibitionView.php
?>

<div id="exhibition-hero-container"></div>
<template id="tpl-hero">
    <div class="ex-hero">
        <div class="ex-hero-backdrop">
            <img src="${backdrop}" alt="Backdrop" class="ex-backdrop-img">
            <div class="ex-hero-overlay"></div>
        </div>
        <div class="ex-hero-content">
            <h1 class="ex-title">${title}</h1>
            <div class="ex-meta-row">
                <span class="ex-badge">${year}</span>
                <span class="ex-badge rating-badge">${rating}</span>
                <span class="ex-type-label">${type_label}</span>
            </div>
            <p class="ex-overview">${overview}</p>
            <div class="ex-hero-btns">
                <button class="btn-play-large" onclick="ExhibitionManager.startPlayback()">
                    <i data-lucide="play" fill="currentColor"></i> Assistir Agora
                </button>
            </div>
        </div>
    </div>
</template>

<div id="exhibition-series-container" style="display: none;">
    <div class="ex-series-header">
        <div class="ex-season-selector">
            <button class="ex-season-trigger" id="season-btn">
                <span id="selected-season-label">Temporada 1</span>
                <i data-lucide="chevron-down"></i>
            </button>
            <div class="ex-season-dropdown" id="season-dropdown"></div>
        </div>
    </div>
    <div class="ex-episodes-grid" id="episodes-grid"></div>
</div>

<template id="tpl-episode-card">
    <div class="ex-ep-card" data-ep="${episode_number}">
        <div class="ex-ep-thumb">
            <img src="${still_path}" alt="${name}">
            <div class="ex-ep-play-overlay"><i data-lucide="play"></i></div>
            <span class="ex-ep-number">${episode_number}</span>
        </div>
        <div class="ex-ep-info">
            <h4 class="ex-ep-title">${name}</h4>
            <p class="ex-ep-desc">${overview}</p>
        </div>
    </div>
</template>

<section class="ex-cast-section">
    <h3 class="ex-section-title">Elenco Principal</h3>
    <div class="ex-cast-grid" id="cast-grid"></div>
</section>

<template id="tpl-cast-card">
    <div class="ex-cast-card">
        <img src="${profile_path}" alt="${name}" class="ex-cast-img">
        <div class="ex-cast-meta">
            <span class="ex-cast-name">${name}</span>
            <span class="ex-cast-char">${character}</span>
        </div>
    </div>
</template>