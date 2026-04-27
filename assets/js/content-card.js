// assets/js/content-card.js

class PipoRail {
    constructor(containerId, title, apiCategory, limit = 15) {
        this.container = document.getElementById(containerId);
        this.title = title;
        this.apiCategory = apiCategory;
        this.limit = limit;
        
        // Templates e Base
        this.TMDB_IMG_BASE = 'https://image.tmdb.org/t/p/';
        this.skeletonTpl = document.getElementById('pipo-card-skeleton-template');
        this.cardTpl = document.getElementById('pipo-card-template');

        this.init();
    }

    init() {
        if (!this.container || !this.skeletonTpl || !this.cardTpl) return;
        
        // 1. Constrói o HTML estrutural do trilho
        this.container.className = 'pipo-rail-section';
        this.container.innerHTML = `
            <div class="pipo-rail-header">
                <h2 class="pipo-rail-title">${this.title}</h2>
            </div>
            <div class="pipo-rail-container" id="${this.container.id}-wrapper"></div>
        `;
        this.wrapper = document.getElementById(`${this.container.id}-wrapper`);

        // 2. Renderiza Skeletons imediatamente (Simula 6 cards a carregar)
        this.renderSkeletons(6);

        // 3. Busca os dados reais
        this.fetchData();
    }

    renderSkeletons(count) {
        this.wrapper.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const skel = this.skeletonTpl.content.cloneNode(true);
            this.wrapper.appendChild(skel);
        }
    }

    async fetchData() {
        try {
            const url = `/api/v2/conteudo?categoria=${this.apiCategory}&limite=${this.limit}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network error');
            
            const data = await response.json();
            if (data.sucesso && data.resultados.length > 0) {
                this.renderCards(data.resultados);
            } else {
                this.wrapper.innerHTML = '<p style="color:#666; padding:0 4%;">Conteúdo indisponível.</p>';
            }
        } catch (error) {
            console.error(`Erro ao carregar trilho ${this.title}:`, error);
            this.wrapper.innerHTML = '<p style="color:#ff3b30; padding:0 4%;">Erro ao carregar dados.</p>';
        }
    }

    formatImg(path, size = 'w342') {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        return `${this.TMDB_IMG_BASE}${size}${path}`;
    }

    renderCards(items) {
        this.wrapper.innerHTML = ''; // Apaga os skeletons
        
        items.forEach(item => {
            if (!item) return;
            const cardNode = this.cardTpl.content.cloneNode(true);
            
            // Poster
            const imgEl = cardNode.querySelector('.pipo-card-poster');
            if (imgEl) imgEl.src = this.formatImg(item.capa, 'w500');

            // Título
            const titleEl = cardNode.querySelector('.pipo-card-title');
            if (titleEl) titleEl.innerText = item.titulo || '';

            // Meta: Match Score (Calcula Relevância baseada na nota do TMDB)
            const nota = parseFloat(item.nota) || 0;
            const percentage = nota > 0 ? Math.round(nota * 10) : Math.floor(Math.random() * (99 - 70 + 1)) + 70; // Fake percent se 0
            const scoreEl = cardNode.querySelector('.score-value');
            if (scoreEl) scoreEl.innerText = percentage;

            // Meta: Classificação Indicativa
            const ratingClassMap = {
                'L': 'rating-l', 'Livre': 'rating-l', '0': 'rating-l',
                '10': 'rating-10', '12': 'rating-12', '14': 'rating-14',
                '16': 'rating-16', '18': 'rating-18'
            };
            const ratingIcon = cardNode.querySelector('.rating-icon');
            if (ratingIcon) {
                const rClass = ratingClassMap[item.classificacao] || 'rating-l';
                ratingIcon.classList.add(rClass);
            }

            // Meta: Ano
            const yearEl = cardNode.querySelector('.release-year');
            if (yearEl) yearEl.innerText = item.ano || '';

            // Meta: Gêneros
            const genresWrap = cardNode.querySelector('.pipo-card-genres');
            if (genresWrap) {
                const gens = item.generos || item.genres || [];
                // Cria spans para os 3 primeiros gêneros
                gens.slice(0, 3).forEach(g => {
                    const s = document.createElement('span');
                    s.innerText = g;
                    genresWrap.appendChild(s);
                });
            }

            this.wrapper.appendChild(cardNode);
        });

        // Renderiza os ícones do Lucide (Play, Plus, Thumb, Chevron) recém inseridos
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}