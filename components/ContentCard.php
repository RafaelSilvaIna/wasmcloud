<?php
// components/ContentCard.php
// Templates HTML para cards de conteúdo.
// Versão Ultra Minimalista (Estilo Póster): Foco 100% na arte, título abaixo da imagem e Play centralizado.
?>

<!-- ═══════════════════════════════════════════════════════════════
     SKELETONS DE CARREGAMENTO (Simulando a imagem e o título)
═══════════════════════════════════════════════════════════════ -->

<template id="pipo-card-skeleton-template">
    <div class="slick-item">
        <div class="slick-card slick-skeleton" aria-hidden="true"></div>
        <!-- Simula o espaço do título que agora fica de fora -->
        <div class="card-title-skeleton" style="height:14px; width:70%; background:#333; margin-top:10px; border-radius:4px;"></div>
    </div>
</template>

<template id="pipo-card-top10-skeleton-template">
    <div class="slick-item slick-item--top10">
        <div class="slick-card slick-card--top10 slick-skeleton" aria-hidden="true"></div>
        <!-- Simula o espaço do título -->
        <div class="card-title-skeleton" style="height:14px; width:60%; background:#333; margin-top:10px; border-radius:4px;"></div>
    </div>
</template>


<!-- ═══════════════════════════════════════════════════════════════
     CARD PADRÃO (Ultra Minimalista)
     Estrutura:
       .slick-item   → Divisão na grelha
         .slick-card → O card com a imagem (cresce ao passar o rato)
           .slick-thumb → A imagem do filme
             .play-overlay → O botão de Play (invisível até passar o rato)
           .slick-badge → Etiqueta (Ex: FILME)
         .card-title-outside → O título do filme, posicionado por baixo
═══════════════════════════════════════════════════════════════ -->
<template id="pipo-card-template">
    <div class="slick-item">
        <div class="slick-card" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">

            <!-- Imagem Principal (Poster) e Botão de Play Centralizado -->
            <div class="slick-thumb">
                <img src="" alt="" class="poster" loading="lazy" decoding="async">
                
                <!-- Botão de Play (Aparece no Hover via CSS) -->
                <div class="play-overlay" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                        <!-- Fundo branco ligeiramente transparente -->
                        <circle cx="12" cy="12" r="11" fill="rgba(255,255,255,0.85)"/>
                        <!-- Seta preta de play -->
                        <polygon points="10,8 16,12 10,16" fill="#000"/>
                    </svg>
                </div>
            </div>

            <!-- Etiqueta de Categoria -->
            <span class="slick-badge" aria-hidden="true"></span>

        </div><!-- /.slick-card -->

        <!-- Título do Conteúdo (Agora fica fora do card, por baixo da imagem) -->
        <h3 class="card-title-outside"></h3>

    </div><!-- /.slick-item -->
</template>


<!-- ═══════════════════════════════════════════════════════════════
     CARD TOP 10 (Ultra Minimalista)
     Mantém a estrutura do card padrão, mas adiciona o número do ranking.
═══════════════════════════════════════════════════════════════ -->
<template id="pipo-card-top10-template">
    <div class="slick-item slick-item--top10">
        <div class="slick-card slick-card--top10" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">

            <!-- Número do Ranking Gigante (1, 2, 3...) -->
            <span class="rank-number" aria-hidden="true"></span>

            <!-- Imagem Principal (Poster), Badge e Botão de Play -->
            <div class="slick-thumb slick-thumb--top10">
                <img src="" alt="" class="poster" loading="lazy" decoding="async">

                <!-- Etiqueta de Categoria (dentro do thumb para posicionamento correto) -->
                <span class="slick-badge" aria-hidden="true"></span>

                <!-- Botão de Play -->
                <div class="play-overlay" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                        <circle cx="12" cy="12" r="11" fill="rgba(255,255,255,0.85)"/>
                        <polygon points="10,8 16,12 10,16" fill="#000"/>
                    </svg>
                </div>
            </div>

        </div><!-- /.slick-card -->

        <!-- Título do Conteúdo (Fora do card) -->
        <h3 class="card-title-outside"></h3>

    </div><!-- /.slick-item -->
</template>
