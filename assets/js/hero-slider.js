document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/api/v2/conteudo?categoria=em_alta&limite=5';
    const sliderWrapper = document.getElementById('hero-slider-wrapper');
    const template = document.getElementById('hero-slide-template');

    // Mapeador de classificação indicativa para a imagem sprite
    const ratingClassMap = {
        'L': 'rating-l', 'Livre': 'rating-l', '0': 'rating-l',
        '10': 'rating-10', '12': 'rating-12', '14': 'rating-14',
        '16': 'rating-16', '18': 'rating-18'
    };

    const loadHeroContent = async () => {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Falha na resposta da rede');

            const data = await response.json();
            
            // Verifica com segurança se temos um Array de resultados
            if (data && data.sucesso && Array.isArray(data.resultados) && data.resultados.length > 0) {
                // Filtra itens sem um backdrop válido
                const validItems = data.resultados.filter(item => {
                    if (!item) return false;
                    const hasBackdrop = item.backdrop && typeof item.backdrop === 'string' && item.backdrop.trim() !== '';
                    const hasGallery  = Array.isArray(item.gallery)  && item.gallery.length  > 0 && item.gallery[0];
                    const hasGaleria  = Array.isArray(item.galeria)  && item.galeria.length  > 0 && item.galeria[0];
                    const hasPoster   = item.poster  && typeof item.poster  === 'string' && item.poster.trim()  !== '';
                    return hasBackdrop || hasGallery || hasGaleria || hasPoster;
                });
                
                if (validItems.length > 0) {
                    renderSlides(validItems);
                } else {
                    showError('Nenhum conteúdo com imagem de capa encontrado.');
                }
            } else {
                showError('Nenhum conteúdo em alta encontrado.');
            }
        } catch (error) {
            console.error('Erro ao carregar conteúdo Hero:', error);
            showError('Erro de ligação ao carregar tendências.');
        }
    };

    // Fallback de erro visual elegante
    const showError = (msg) => {
        if (sliderWrapper) {
            sliderWrapper.innerHTML = `
                <div class="swiper-slide hero-slide hero-slide--loading">
                    <div class="hero-content" style="text-align:center;">
                        <i data-lucide="alert-triangle" width="40" height="40" style="color:#ff3b30; margin-bottom:10px;"></i>
                        <p style="color:#ff3b30; font-size:1.1rem; font-weight:500;">${msg}</p>
                    </div>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    const renderSlides = (items) => {
        if (!sliderWrapper || !template) return;

        sliderWrapper.innerHTML = ''; // Remove o loader

        items.forEach(item => {
            if (!item) return; // Segurança extra caso venha um item null
            
            const slide = template.content.cloneNode(true);

            // 1. BACKDROP SEGURO
            let backdropUrl = item.backdrop || '';
            if (!backdropUrl && Array.isArray(item.gallery) && item.gallery.length > 0) {
                backdropUrl = item.gallery[0];
            } else if (!backdropUrl && Array.isArray(item.galeria) && item.galeria.length > 0) {
                backdropUrl = item.galeria[0];
            }
            
            const backdropImg = slide.querySelector('.backdrop-img');
            if (backdropImg) {
                backdropImg.src = backdropUrl || '';
                backdropImg.alt = `Fundo ${item.titulo || ''}`;
            }

            // 2. LOGO / TÍTULO EM TEXTO SEGURO
            const logoEl = slide.querySelector('.hero-logo');
            const logoContainer = slide.querySelector('.hero-logo-container');
            if (item.logo && logoEl) {
                logoEl.src = item.logo;
                logoEl.alt = item.titulo || 'Logo';
            } else if (logoEl && logoContainer) {
                logoEl.remove();
                const titleEl = document.createElement('h1');
                titleEl.className = 'hero-text-title';
                titleEl.innerText = item.titulo || 'Sem Título';
                logoContainer.appendChild(titleEl);
            }

            // 3. CLASSIFICAÇÃO INDICATIVA
            const ratingIcon = slide.querySelector('.meta-rating');
            if (ratingIcon) {
                const ratingClass = ratingClassMap[item.classificacao] || 'rating-l';
                ratingIcon.classList.add(ratingClass);
                ratingIcon.title = `Classificação: ${item.classificacao || 'Livre'}`;
            }

            // 4. GÊNEROS SEGURO
            const genresEl = slide.querySelector('.meta-genres');
            if (genresEl) {
                let generosArr = [];
                if (Array.isArray(item.generos)) generosArr = item.generos;
                else if (Array.isArray(item.genres)) generosArr = item.genres;
                
                genresEl.innerText = generosArr.length > 0 ? generosArr.slice(0, 2).join(' • ') : 'Conteúdo';
            }

            // 5. ANO E NOTA
            const yearEl = slide.querySelector('.meta-year');
            if (yearEl) yearEl.innerText = item.ano || '';

            const scoreEl = slide.querySelector('.score-value');
            if (scoreEl) {
                const nota = parseFloat(item.nota);
                scoreEl.innerText = (isNaN(nota) || nota <= 0) ? 'N/A' : nota.toFixed(1);
            }

            // 6. SINOPSE
            const synopsisEl = slide.querySelector('.hero-synopsis');
            if (synopsisEl) {
                synopsisEl.innerText = item.sinopse || 'Sinopse indisponível de momento para este conteúdo.';
            }

            // 7. BOTÃO ASSISTIR — usa slug ou id para navegar
            const watchBtn = slide.querySelector('.btn-watch');
            if (watchBtn) {
                const slug = item.slug || item.id || null;
                const tipo = item.tipo || item.type || 'filme';
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
                    watchBtn.title = 'Link indisponível';
                }
            }

            sliderWrapper.appendChild(slide);
        });

        initializeSwiper();

        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    const initializeSwiper = () => {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#pipo-hero-slider', {
                loop: true,
                speed: 800,
                autoplay: { delay: 7000, disableOnInteraction: false },
                pagination: { el: '.swiper-pagination', clickable: true },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                keyboard: { enabled: true },
                effect: 'fade',
                fadeEffect: { crossFade: true },
                observer: true,
                observeParents: true
            });
        }
    };

    loadHeroContent();
});