document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/api/v2/conteudo?categoria=em_alta&limite=5';
    const sliderWrapper = document.getElementById('hero-slider-wrapper');
    const template = document.getElementById('hero-slide-template');

    const AUTOPLAY_DELAY = 8000;

    // Base TMDB para paths relativos
    const TMDB_IMG_BASE = 'https://image.tmdb.org/t/p/';

    const ratingClassMap = {
        'L': 'rating-l', 'Livre': 'rating-l', '0': 'rating-l',
        '10': 'rating-10', '12': 'rating-12', '14': 'rating-14',
        '16': 'rating-16', '18': 'rating-18'
    };

    // Formata URL de imagem — aceita path TMDB relativo ou URL completa
    const formatImg = (path, size = 'original') => {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        return `${TMDB_IMG_BASE}${size}${path}`;
    };

    // ── Carregamento ─────────────────────────────────────────────
    const loadHeroContent = async () => {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Falha na rede');
            const data = await response.json();

            if (data && data.sucesso && Array.isArray(data.resultados) && data.resultados.length > 0) {
                // Filtra itens que têm pelo menos uma imagem válida
                const validItems = data.resultados.filter(item => {
                    if (!item) return false;
                    return (
                        (item.backdrop && item.backdrop.trim()) ||
                        (Array.isArray(item.gallery)  && item.gallery[0]) ||
                        (Array.isArray(item.galeria)  && item.galeria[0]) ||
                        (item.poster  && item.poster.trim())
                    );
                });
                validItems.length > 0
                    ? renderSlides(validItems)
                    : showError('Nenhum conteúdo com imagem encontrado.');
            } else {
                showError('Nenhum conteúdo em alta encontrado.');
            }
        } catch (err) {
            console.error('Erro Hero:', err);
            showError('Erro ao carregar tendências.');
        }
    };

    // ── Fallback visual ──────────────────────────────────────────
    const showError = (msg) => {
        if (sliderWrapper) {
            sliderWrapper.innerHTML = `
                <div class="swiper-slide hero-slide hero-slide--loading">
                    <p style="color:#ff3b30;font-size:1rem;font-weight:500;">${msg}</p>
                </div>`;
        }
    };

    // ── Render de slides ─────────────────────────────────────────
    const renderSlides = (items) => {
        if (!sliderWrapper || !template) return;
        sliderWrapper.innerHTML = '';

        items.forEach(item => {
            if (!item) return;
            const slide = template.content.cloneNode(true);

            // 1. BACKDROP
            let backdropPath = item.backdrop || '';
            if (!backdropPath && Array.isArray(item.gallery) && item.gallery[0]) backdropPath = item.gallery[0];
            if (!backdropPath && Array.isArray(item.galeria) && item.galeria[0]) backdropPath = item.galeria[0];

            const backdropImg = slide.querySelector('.backdrop-img');
            if (backdropImg) {
                backdropImg.src  = formatImg(backdropPath, 'original');
                backdropImg.alt  = item.titulo || '';
            }

            // 2. LOGO ou TÍTULO EM TEXTO (hierarquia compacta)
            const logoEl        = slide.querySelector('.hero-logo');
            const logoContainer = slide.querySelector('.hero-logo-container');

            const logoUrl = item.logo
                || (Array.isArray(item.logos) && item.logos[0])
                || null;

            if (logoUrl && logoEl) {
                logoEl.src = formatImg(logoUrl, 'w500');
                logoEl.alt = item.titulo || 'Logo';
            } else if (logoEl && logoContainer) {
                // Remove o <img> placeholder e insere um <h1> compacto
                logoEl.remove();

                const titulo = item.titulo || 'Sem Título';
                const len    = titulo.length;

                const titleEl = document.createElement('h1');
                // Escala progressiva: quanto maior o texto, menor o font-size
                let cls = 'hero-text-title';
                if      (len >= 35) cls += ' hero-text-title--xlong';
                else if (len >= 22) cls += ' hero-text-title--long';
                titleEl.className = cls;
                titleEl.textContent = titulo;
                logoContainer.appendChild(titleEl);
            }

            // 3. CLASSIFICAÇÃO INDICATIVA
            const ratingIcon = slide.querySelector('.meta-rating');
            if (ratingIcon) {
                const cls = ratingClassMap[item.classificacao] || 'rating-l';
                ratingIcon.classList.add(cls);
                ratingIcon.title = `Classificação: ${item.classificacao || 'Livre'}`;
            }

            // 4. SCORE (estrela + nota)
            const scoreEl = slide.querySelector('.score-value');
            if (scoreEl) {
                const nota = parseFloat(item.nota);
                scoreEl.textContent = (isNaN(nota) || nota <= 0) ? '' : nota.toFixed(1);
                // Oculta o bloco de score se não houver nota
                if (!scoreEl.textContent) {
                    const scoreBlock = slide.querySelector('.meta-score');
                    const sep = scoreBlock?.previousElementSibling;
                    if (scoreBlock) scoreBlock.style.display = 'none';
                    if (sep && sep.classList.contains('meta-sep')) sep.style.display = 'none';
                }
            }

            // 5. ANO
            const yearEl = slide.querySelector('.meta-year');
            if (yearEl) yearEl.textContent = item.ano || '';

            // 6. GÊNEROS — pills individuais (máx. 2)
            const genreTagsEl = slide.querySelector('.meta-genre-tags');
            if (genreTagsEl) {
                let gens = [];
                if (Array.isArray(item.generos)) gens = item.generos;
                else if (Array.isArray(item.genres)) gens = item.genres;

                gens.slice(0, 2).forEach(g => {
                    const tag = document.createElement('span');
                    tag.className   = 'genre-tag';
                    tag.textContent = g;
                    genreTagsEl.appendChild(tag);
                });

                // Se não há gêneros, oculta o separador anterior
                if (gens.length === 0) {
                    const sep = genreTagsEl.previousElementSibling;
                    if (sep && sep.classList.contains('meta-sep')) sep.style.display = 'none';
                }
            }

            // 7. SINOPSE (2-3 linhas, controlado via CSS)
            const synopsisEl = slide.querySelector('.hero-synopsis');
            if (synopsisEl) {
                synopsisEl.textContent = item.sinopse || '';
                // Oculta se sinopse vazia
                if (!item.sinopse) synopsisEl.style.display = 'none';
            }

            // 8. BOTÃO ASSISTIR
            const watchBtn = slide.querySelector('.btn-watch');
            if (watchBtn) {
                const slug = item.slug || item.id || null;
                const tipo = (item.tipo || item.type || 'filme').toLowerCase();

                if (slug) {
                    watchBtn.addEventListener('click', () => {
                        window.location.href = `/${tipo}/${slug}`;
                    });
                } else if (item.link) {
                    watchBtn.addEventListener('click', () => {
                        window.location.href = item.link;
                    });
                } else {
                    watchBtn.disabled = true;
                    watchBtn.style.opacity = '0.5';
                }
            }

            sliderWrapper.appendChild(slide);
        });

        initSwiper();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    // ── Inicialização do Swiper ──────────────────────────────────
    const initSwiper = () => {
        if (typeof Swiper === 'undefined') return;

        new Swiper('#pipo-hero-slider', {
            loop: true,
            speed: 900,
            autoplay: {
                delay: AUTOPLAY_DELAY,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: false,   // setas escondidas — arrasto activado abaixo

            // Drag / swipe
            grabCursor: true,
            simulateTouch: true,
            touchRatio: 1,
            touchAngle: 45,
            threshold: 8,

            keyboard: { enabled: true },
            a11y: {
                prevSlideMessage: 'Slide anterior',
                nextSlideMessage: 'Próximo slide'
            },
            effect: 'fade',
            fadeEffect: { crossFade: true },
            observer: true,
            observeParents: true,

            on: {
                // Reinicia animação de zoom do backdrop ao mudar
                slideChange() {
                    const active = this.slides[this.activeIndex];
                    if (!active) return;
                    const img = active.querySelector('.backdrop-img');
                    if (img) {
                        img.style.animation = 'none';
                        void img.offsetHeight; // force reflow
                        img.style.animation  = '';
                    }
                }
            }
        });
    };

    loadHeroContent();
});