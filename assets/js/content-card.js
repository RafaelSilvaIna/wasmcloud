// assets/js/content-card.js

class PipoRail {
    /**
     * @param {string} containerId  - ID do elemento host
     * @param {string} title        - Título do trilho
     * @param {string} apiCategory  - Categoria para /api/v2/conteudo OU 'top10_filmes'/'top10_series'
     * @param {number} limit        - Quantidade máxima de itens
     * @param {object} opts         - { isTop10: bool }
     */
    constructor(containerId, title, apiCategory, limit = 15, opts = {}) {
        this.container   = document.getElementById(containerId);
        this.title       = title;
        this.apiCategory = apiCategory;
        this.limit       = limit;
        this.isTop10     = !!opts.isTop10;

        this.TMDB_IMG = 'https://image.tmdb.org/t/p/';

        this.skelTpl    = document.getElementById(
            this.isTop10 ? 'pipo-card-top10-skeleton-template' : 'pipo-card-skeleton-template'
        );
        this.cardTpl    = document.getElementById(
            this.isTop10 ? 'pipo-card-top10-template' : 'pipo-card-template'
        );

        this._init();
    }

    _init() {
        if (!this.container || !this.skelTpl || !this.cardTpl) return;

        this.container.className = 'pipo-rail-section';
        this.container.innerHTML = `
            <div class="pipo-rail-header">
                <h2 class="pipo-rail-title">${this.title}</h2>
                <div class="pipo-rail-nav" aria-hidden="true">
                    <button class="pipo-rail-nav-btn pipo-rail-nav-btn--prev" aria-label="Anterior">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <button class="pipo-rail-nav-btn pipo-rail-nav-btn--next" aria-label="Próximo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="pipo-rail-container" id="${this.container.id}-wrap"></div>
        `;

        this.wrap = document.getElementById(`${this.container.id}-wrap`);
        this._bindNav();
        this._renderSkeletons(this.isTop10 ? 5 : 6);
        this._fetchData();
    }

    _bindNav() {
        const prev = this.container.querySelector('.pipo-rail-nav-btn--prev');
        const next = this.container.querySelector('.pipo-rail-nav-btn--next');
        const scrollBy = () => this.wrap.clientWidth * 0.75;

        if (prev) prev.addEventListener('click', () => this.wrap.scrollBy({ left: -scrollBy(), behavior: 'smooth' }));
        if (next) next.addEventListener('click', () => this.wrap.scrollBy({ left:  scrollBy(), behavior: 'smooth' }));
    }

    _renderSkeletons(n) {
        this.wrap.innerHTML = '';
        for (let i = 0; i < n; i++) {
            this.wrap.appendChild(this.skelTpl.content.cloneNode(true));
        }
    }

    async _fetchData() {
        try {
            // Top 10: usa o endpoint /api/v2/trending com filtro de tipo
            let url;
            if (this.isTop10) {
                const tipo = this.apiCategory === 'top10_series' ? 'serie' : 'filme';
                url = `/api/v2/trending?limite=${this.limit}&tipo=${tipo}`;
            } else {
                url = `/api/v2/conteudo?categoria=${this.apiCategory}&limite=${this.limit}`;
            }

            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            const items = data.resultados || data.items || [];

            if (data.sucesso && items.length > 0) {
                this._renderCards(items);
            } else {
                this.wrap.innerHTML = '<p class="pipo-rail-empty">Conteudo indisponivel no momento.</p>';
            }
        } catch (err) {
            console.error(`[PipoRail] Erro no trilho "${this.title}":`, err);
            this.wrap.innerHTML = '<p class="pipo-rail-error">Erro ao carregar conteudo.</p>';
        }
    }

    _img(path, size = 'w342') {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        return `${this.TMDB_IMG}${size}${path}`;
    }

    _scoreLabel(nota) {
        const n = parseFloat(nota) || 0;
        if (n <= 0) return '';
        const pct = Math.round(n * 10);
        return `${pct}% Match`;
    }

    _typeBadge(item) {
        const t = (item.tipo || '').toLowerCase();
        if (t === 'serie' || t === 'tv') return 'SÉRIE';
        return 'FILME';
    }

    _ratingClass(classificacao) {
        const map = {
            'L': 'rating-l', 'Livre': 'rating-l', '0': 'rating-l',
            '10': 'rating-10', '12': 'rating-12',
            '14': 'rating-14', '16': 'rating-16', '18': 'rating-18'
        };
        return map[String(classificacao)] || 'rating-l';
    }

    _renderCards(items) {
        this.wrap.innerHTML = '';

        items.forEach((item, idx) => {
            if (!item) return;

            const node = this.cardTpl.content.cloneNode(true);
            const card = node.querySelector('.pipo-card');

            // Clique → view
            card.addEventListener('click', (e) => {
                // Não navegar se clicou no botão "Minha Lista"
                if (e.target.closest('.pipo-btn--list')) return;
                const type = (item.tipo === 'serie' || item.tipo === 'tv') ? 'serie' : 'movie';
                window.location.href = `/view?id=${item.id_tmdb}&type=${type}`;
            });

            // ── Top 10: número de ranking ──────────────────────────────────
            if (this.isTop10) {
                const rank = node.querySelector('.pipo-card-rank');
                if (rank) rank.textContent = String(idx + 1);
            }

            // ── Poster ────────────────────────────────────────────────────
            const poster = node.querySelector('.pipo-card-poster');
            if (poster) {
                poster.src = this._img(item.capa, 'w342');
                poster.alt = item.titulo || 'Capa';
            }

            // ── Hover thumb (backdrop 16:9) ────────────────────────────────
            const hoverImg = node.querySelector('.pipo-card-hover-img');
            if (hoverImg) {
                const backdrop = item.backdrop || item.capa_fundo || item.capa;
                hoverImg.src = this._img(backdrop, 'w780');
                hoverImg.alt = item.titulo || '';
            }

            // ── Badge tipo ─────────────────────────────────────────────────
            const badge = node.querySelector('.pipo-card-type-badge');
            if (badge) badge.textContent = this._typeBadge(item);

            // ── Score badge ────────────────────────────────────────────────
            const scoreBadge = node.querySelector('.pipo-card-score-badge');
            if (scoreBadge) scoreBadge.textContent = this._scoreLabel(item.nota);

            // ── Título no hover panel ──────────────────────────────────────
            const hoverTitle = node.querySelector('.pipo-card-hover-title');
            if (hoverTitle) hoverTitle.textContent = item.titulo || '';

            // ── Meta: ano ─────────────────────────────────────────────────
            const yearEl = node.querySelector('.meta-year');
            if (yearEl) yearEl.textContent = item.ano || '';

            // ── Meta: duração/temporadas ───────────────────────────────────
            const durEl = node.querySelector('.meta-duration');
            if (durEl) {
                const t = (item.tipo || '').toLowerCase();
                if (t === 'serie' || t === 'tv') {
                    durEl.textContent = item.temporadas ? `${item.temporadas}T` : '';
                } else {
                    durEl.textContent = item.duracao ? `${item.duracao}min` : '';
                }
            }

            // ── Classificação indicativa ───────────────────────────────────
            const ratingIcon = node.querySelector('.rating-icon');
            if (ratingIcon) ratingIcon.classList.add(this._ratingClass(item.classificacao));

            // ── Gêneros no hover panel ─────────────────────────────────────
            const genresEl = node.querySelector('.pipo-card-hover-genres');
            if (genresEl) {
                const gens = (item.generos || item.genres || []).slice(0, 3);
                genresEl.innerHTML = gens.map(g => `<span>${g}</span>`).join('');
            }

            // ── Match (verde) ──────────────────────────────────────────────
            const matchEl = node.querySelector('.meta-match');
            if (matchEl) matchEl.textContent = this._scoreLabel(item.nota);

            this.wrap.appendChild(node);
        });
    }
}
