// assets/js/content-card.js

class PipoRail {
    constructor(containerId, title, apiCategory, limit = 15) {
        this.container = document.getElementById(containerId);
        this.title = title;
        this.apiCategory = apiCategory;
        this.limit = limit;

        this.TMDB_IMG_BASE = 'https://image.tmdb.org/t/p/';
        this.skeletonTpl = document.getElementById('pipo-card-skeleton-template');
        this.cardTpl = document.getElementById('pipo-card-template');

        this.init();
    }

    init() {
        if (!this.container || !this.skeletonTpl || !this.cardTpl) return;

        this.container.className = 'pipo-rail-section';
        this.container.innerHTML = `
            <div class="pipo-rail-header">
                <h2 class="pipo-rail-title">${this.title}</h2>
            </div>
            <div class="pipo-rail-container" id="${this.container.id}-wrapper"></div>
        `;
        this.wrapper = document.getElementById(`${this.container.id}-wrapper`);

        // Renderiza 6 Skeletons imediatamente
        this.renderSkeletons(6);
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
            // Utiliza a API v2 de conteúdo que já tens implementada
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
        this.wrapper.innerHTML = ''; // Limpa Skeletons

        items.forEach(item => {
            if (!item) return;
            const cardNode = this.cardTpl.content.cloneNode(true);
            const cardElement = cardNode.querySelector('.pipo-card');

            // ── LÓGICA DE CLIQUE (Redirecionamento para View) ─────────────────
            if (cardElement) {
                cardElement.addEventListener('click', () => {
                    // Mapeia o tipo para o padrão esperado pela página view.php
                    const type = (item.tipo === 'serie' || item.tipo === 'tv') ? 'serie' : 'movie';
                    // Redireciona para /view com ID do TMDB e Tipo
                    window.location.href = `/view?id=${item.id_tmdb}&type=${type}`;
                });
            }
            // ──────────────────────────────────────────────────────────────────

            // Pôster
            const imgEl = cardNode.querySelector('.pipo-card-poster');
            if (imgEl) imgEl.src = this.formatImg(item.capa, 'w500');

            // Título
            const titleEl = cardNode.querySelector('.pipo-card-title');
            if (titleEl) titleEl.innerText = item.titulo || '';

            // Meta: Nota
            const nota = parseFloat(item.nota) || 0;
            const scoreEl = cardNode.querySelector('.score-value');
            if (scoreEl) scoreEl.innerText = nota > 0 ? nota.toFixed(1) : 'N/A';

            // Meta: Classificação Indicativa (Sprite)
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
                genresWrap.innerText = gens.slice(0, 3).join(' • ');
            }

            this.wrapper.appendChild(cardNode);
        });

        // Aplica os ícones Lucide
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}