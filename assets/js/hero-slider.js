// assets/js/hero-slider.js

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. CONFIGURAÇÃO E REFERÊNCIAS
    const API_URL = '/api/v2/conteudo?categoria=em_alta&limite=5';
    const sliderWrapper = document.getElementById('hero-slider-wrapper');
    const template = document.getElementById('hero-slide-template');

    // Mapeador de classificação indicativa para classes CSS (Sprite)
    const ratingClassMap = {
        'L': 'rating-l', 'Livre': 'rating-l', '0': 'rating-l',
        '10': 'rating-10', '12': 'rating-12', '14': 'rating-14',
        '16': 'rating-16', '18': 'rating-18'
    };

    // 2. FUNÇÃO PRINCIPAL: Carregar dados
    const loadHeroContent = async () => {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Falha na resposta da rede');
            
            const data = await response.json();
            if (data.sucesso && data.resultados.length > 0) {
                renderSlides(data.resultados);
            } else {
                console.error('API não retornou resultados válidos');
            }
        } catch (error) {
            console.error('Erro ao carregar conteúdo Hero:', error);
            if (typeof PipoNotification !== 'undefined') {
                PipoNotification.error('Erro ao carregar o slide principal.');
            }
        }
    };

    // 3. FUNÇÃO: Renderizar Slides
    const renderSlides = (items) => {
        if (!sliderWrapper || !template) return;
        
        // Limpa o loader de placeholder
        sliderWrapper.innerHTML = '';
        
        // Itera sobre os itens e clona o template
        items.forEach(item => {
            const slide = template.content.cloneNode(true);
            
            // Backgrop
            slide.querySelector('.backdrop-img').src = item.gallery[0] || item.backdrop;
            slide.querySelector('.backdrop-img').alt = `Slide ${item.titulo}`;

            // Título / Logo
            const logoEl = slide.querySelector('.hero-logo');
            if (item.logo) {
                logoEl.src = item.logo;
                logoEl.alt = item.titulo;
            } else {
                // Se não houver logo, remove a imagem para não quebrar layout
                logoEl.remove();
            }

            // Metadata: Classificação Indicativa (Sprite CSS)
            const ratingIcon = slide.querySelector('.meta-rating');
            const ratingClass = ratingClassMap[item.classificacao] || 'rating-l'; // Default livre
            ratingIcon.classList.add(ratingClass);
            ratingIcon.title = `Classificação: ${item.classificacao}`;

            // Metadata: Géneros (Mostra apenas os 2 primeiros)
            slide.querySelector('.meta-genres').innerText = item.generos.slice(0, 2).join(' / ');

            // Metadata: Outros
            slide.querySelector('.meta-year').innerText = item.ano;
            slide.querySelector('.score-value').innerText = parseFloat(item.nota).toFixed(1);

            // Sinopse
            slide.querySelector('.hero-synopsis').innerText = item.sinopse || 'Sem sinopse disponível.';

            sliderWrapper.appendChild(slide);
        });

        // 4. FUNÇÃO: Inicializar Swiper
        initializeSwiper();
        
        // Inicializar os ícones do Lucide nos novos slides
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    // 5. FUNÇÃO: Inicializar Swiper.js
    const initializeSwiper = () => {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#pipo-hero-slider', {
                loop: true,
                speed: 800,
                autoplay: { delay: 6000, disableOnInteraction: false },
                pagination: { el: '.swiper-pagination', clickable: true },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                keyboard: { enabled: true },
                effect: 'fade', // Efeito de transição suave
                fadeEffect: { crossFade: true }
            });
        }
    };

    // START
    loadHeroContent();
});