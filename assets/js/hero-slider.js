/**
 * PIPOCINE — Hero Slider  |  Netflix-Style  |  v3.0
 * Endpoint: /api/v2/trending?limite=8
 * Requisitos: Swiper.js, Lucide (opicional), hero-slider.css
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── Configuração ────────────────────────────────────────────────
    const API_URL        = '/api/v2/trending?limite=8';
    const AUTOPLAY_DELAY = 9000; // ms entre slides

    const sliderWrapper  = document.getElementById('hero-slider-wrapper');
    const template       = document.getElementById('hero-slide-template');
    const navPrev        = document.querySelector('.hero-nav--prev');
    const navNext        = document.querySelector('.hero-nav--next');

    let swiperInstance   = null;
    let progressTimer    = null;

    // ── Helpers ─────────────────────────────────────────────────────
    const sanitize = (str) => {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    };

    const formatTipo = (tipo) => {
        if (!tipo) return '';
        return tipo.charAt(0).toUpperCase() + tipo.slice(1).toLowerCase();
    };

    // ── Animação da barra de progresso ─────────────────────────────
    const startProgress = (slide) => {
        clearTimeout(progressTimer);
        const fill = slide?.querySelector('.hero-progress-fill');
        if (!fill) return;
        fill.style.transition = 'none';
        fill.style.width = '0%';

        // Força reflow antes de aplicar a transição
        void fill.offsetWidth;
        fill.style.transition = `width ${AUTOPLAY_DELAY}ms linear`;
        fill.style.width = '100%';
    };

    const resetProgress = (slide) => {
        const fill = slide?.querySelector('.hero-progress-fill');
        if (!fill) return;
        fill.style.transition = 'none';
        fill.style.width = '0%';
    };

    // ── Erro visual ─────────────────────────────────────────────────
    const showError = (msg) => {
        if (!sliderWrapper) return;
        sliderWrapper.innerHTML = `
            <div class="swiper-slide hero-slide hero-slide--skeleton">
                <div class="hero-backdrop"><div class="hero-skeleton-bg"></div></div>
                <div class="hero-content">
                    <div class="hero-info-area" style="color:rgba(255,255,255,.5);font-size:.85rem;padding-top:20px;">
                        ${sanitize(msg)}
                    </div>
                </div>
            </div>`;
    };

    // ── Render de slides ────────────────────────────────────────────
    const renderSlides = (items) => {
        if (!sliderWrapper || !template) return;
        sliderWrapper.innerHTML = '';

        items.forEach((item) => {
            if (!item) return;
            const clone = template.content.cloneNode(true);

            // 1. Tipo de conteúdo
            const typeText = clone.querySelector('.type-text');
            if (typeText) typeText.textContent = formatTipo(item.tipo);

            // 2. Backdrop
            const backdropImg = clone.querySelector('.backdrop-img');
            const bgUrl = item.backdrop
                || (Array.isArray(item.galeria) && item.galeria[0])
                || '';
            if (backdropImg) {
                backdropImg.src = bgUrl;
                backdropImg.alt = item.titulo || '';
            }

            // 3. Logo ou Título
            const logoEl        = clone.querySelector('.hero-logo');
            const logoContainer = clone.querySelector('.hero-logo-container');

            if (item.logo && logoEl) {
                logoEl.src = item.logo;
                logoEl.alt = item.titulo || 'Logo';
                logoEl.onerror = () => {
                    // Fallback para título se logo quebrar
                    logoEl.remove();
                    _insertTitle(logoContainer, item.titulo);
                };
            } else if (logoEl && logoContainer) {
                logoEl.remove();
                _insertTitle(logoContainer, item.titulo);
            }

            // 4. Score
            const scoreEl = clone.querySelector('.score-value');
            const nota = parseFloat(item.nota);
            if (scoreEl) {
                if (!isNaN(nota) && nota > 0) {
                    scoreEl.textContent = nota.toFixed(1);
                } else {
                    clone.querySelector('.meta-score')?.remove();
                    clone.querySelectorAll('.meta-dot')[0]?.remove();
                }
            }

            // 5. Ano
            const yearEl = clone.querySelector('.meta-year');
            if (yearEl) {
                yearEl.textContent = item.ano || '';
                if (!item.ano) {
                    yearEl.previousElementSibling?.remove(); // remove dot
                    yearEl.remove();
                }
            }

            // 6. Classificação
            const certEl = clone.querySelector('.meta-cert');
            if (certEl) {
                const cert = item.classificacao;
                if (cert && cert !== 'L') {
                    certEl.textContent = cert;
                } else {
                    certEl.textContent = 'L';
                }
            }

            // 7. Gêneros (máx. 3)
            const genreTagsEl = clone.querySelector('.meta-genre-tags');
            const genDot      = clone.querySelector('.meta-dot--genres');
            const gens        = Array.isArray(item.generos) ? item.generos : [];

            if (genreTagsEl && gens.length > 0) {
                gens.slice(0, 3).forEach(g => {
                    const tag = document.createElement('span');
                    tag.className   = 'genre-tag';
                    tag.textContent = g;
                    genreTagsEl.appendChild(tag);
                });
            } else {
                genDot?.remove();
                genreTagsEl?.remove();
            }

            // 8. Sinopse
            const synopsisEl = clone.querySelector('.hero-synopsis');
            if (synopsisEl) {
                const sinopse = item.sinopse || '';
                synopsisEl.textContent = sinopse;
                if (!sinopse) synopsisEl.remove();
            }

            // 9. Duração / Temporadas
            const durationEl = clone.querySelector('.hero-duration');
            if (durationEl) {
                if (item.duracao) {
                    durationEl.textContent = item.duracao;
                } else {
                    durationEl.remove();
                }
            }

            // 10. Botão Assistir — armazena dados no atributo data-* para funcionar com clones do Swiper loop
            const watchBtn = clone.querySelector('.hero-btn--primary');
            if (watchBtn) {
                const id   = item.id || item.slug || null;
                const tipo = (item.tipo || 'filme').toLowerCase();
                if (id) {
                    watchBtn.dataset.href = `/${tipo}/${id}`;
                } else {
                    watchBtn.disabled = true;
                    watchBtn.style.opacity = '0.4';
                }
            }

            // 11. Botão Mais Infos — armazena tmdbId no atributo data-* para funcionar com clones do Swiper loop
            const infoBtn = clone.querySelector('.hero-btn--info');
            if (infoBtn) {
                const tmdbId = item.id_tmdb || null;
                if (tmdbId) {
                    infoBtn.dataset.href = `/info=${tmdbId}`;
                } else {
                    infoBtn.style.display = 'none';
                }
            }

            sliderWrapper.appendChild(clone);
        });

        _initSwiper();
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Delegação de eventos: funciona nos slides originais E nos clones gerados pelo Swiper loop
        const sliderEl = document.getElementById('pipo-hero-slider');
        if (sliderEl) {
            sliderEl.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-href]');
                if (btn && btn.dataset.href) {
                    window.location.href = btn.dataset.href;
                }
            });
        }
    };

    // ── Helper: inserir título quando não há logo ────────────────
    const _insertTitle = (container, titulo) => {
        if (!container || !titulo) return;
        const len     = titulo.length;
        const titleEl = document.createElement('h2');
        let cls = 'hero-text-title';
        if      (len >= 35) cls += ' hero-text-title--xlong';
        else if (len >= 22) cls += ' hero-text-title--long';
        titleEl.className   = cls;
        titleEl.textContent = titulo;
        container.appendChild(titleEl);
    };

    // ── Inicialização do Swiper ──────────────────────────────────
    const _initSwiper = () => {
        if (typeof Swiper === 'undefined') {
            console.warn('[PipoCine Hero] Swiper não encontrado.');
            return;
        }

        swiperInstance = new Swiper('#pipo-hero-slider', {
            loop:  true,
            speed: 850,

            autoplay: {
                delay: AUTOPLAY_DELAY,
                disableOnInteraction: false,
                pauseOnMouseEnter:    true,
            },

            pagination: {
                el:        '.hero-pagination',
                clickable: true,
            },

            navigation: false,

            grabCursor:    true,
            simulateTouch: true,
            touchRatio:    1,
            touchAngle:    45,
            threshold:     10,

            keyboard: { enabled: true },

            effect:      'fade',
            fadeEffect:  { crossFade: true },

            observer:       true,
            observeParents: true,

            a11y: {
                prevSlideMessage: 'Slide anterior',
                nextSlideMessage: 'Próximo slide',
            },

            on: {
                init() {
                    const active = this.slides[this.activeIndex];
                    startProgress(active);
                },

                slideChangeTransitionStart() {
                    // Reinicia o zoom do backdrop
                    const active = this.slides[this.activeIndex];
                    if (active) {
                        const img = active.querySelector('.backdrop-img');
                        if (img) {
                            img.style.animation = 'none';
                            void img.offsetHeight;
                            img.style.animation = '';
                        }
                        // Reinicia a animação do conteúdo
                        const infoArea = active.querySelector('.hero-info-area');
                        if (infoArea) {
                            infoArea.style.animation = 'none';
                            void infoArea.offsetHeight;
                            infoArea.style.animation = '';
                        }
                    }

                    // Reseta progress de todos os slides
                    this.slides.forEach(s => resetProgress(s));
                },

                slideChangeTransitionEnd() {
                    const active = this.slides[this.activeIndex];
                    startProgress(active);
                },

                autoplayPause() {
                    const active = this.slides[this.activeIndex];
                    const fill   = active?.querySelector('.hero-progress-fill');
                    if (fill) fill.style.transitionPlayState = 'paused';
                },

                autoplayResume() {
                    const active = this.slides[this.activeIndex];
                    const fill   = active?.querySelector('.hero-progress-fill');
                    if (fill) fill.style.transitionPlayState = 'running';
                },
            },
        });

        // Setas customizadas
        navPrev?.addEventListener('click', () => swiperInstance?.slidePrev());
        navNext?.addEventListener('click', () => swiperInstance?.slideNext());
    };

    // ── Fetch dos dados ─────────────────────────────────────────────
    const loadHeroContent = async () => {
        try {
            const res = await fetch(API_URL);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            const items = data?.resultados ?? [];
            if (!data?.sucesso || items.length === 0) {
                showError('Nenhum conteúdo em destaque encontrado.');
                return;
            }

            // Filtra itens incompletos (garantia dupla no frontend)
            const valid = items.filter(item =>
                item &&
                item.logo &&
                (item.backdrop || (Array.isArray(item.galeria) && item.galeria.length > 0)) &&
                item.sinopse && item.sinopse.length >= 30
            );

            if (valid.length === 0) {
                showError('Sem destaques disponíveis no momento.');
                return;
            }

            renderSlides(valid);

        } catch (err) {
            console.error('[PipoCine Hero]', err);
            showError('Erro ao carregar os destaques.');
        }
    };

    loadHeroContent();
});
