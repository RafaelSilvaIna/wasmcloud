/**
 * content-card.js — PipoRail (Versão Ultra Minimalista - Estilo Pôster)
 * Trilhos de conteúdo focados 100% na arte do filme/série.
 *
 * Melhorias implementadas:
 *  1. Título movido para baixo do card (fora do pôster).
 *  2. Remoção completa de painéis e textos sobrepostos.
 *  3. Inclusão de um botão Play centralizado no template DOM.
 *  4. Lógica JS enxuta, removendo cálculos de dados que não aparecem mais.
 */

class PipoRail {
    /**
     * @param {string} containerId   ID do div host
     * @param {string} title         Título do trilho
     * @param {string} apiCategory   Categoria para a API
     * @param {number} limit         Máximo de itens
     * @param {object} opts          { isTop10: boolean }
     */
    constructor(containerId, title, apiCategory, limit = 18, opts = {}) {
        this.host = document.getElementById(containerId);
        this.title = title;
        this.apiCategory = apiCategory;
        this.limit = limit;
        this.isTop10 = !!opts.isTop10;

        this.TMDB = 'https://image.tmdb.org/t/p/';

        // Delays mantidos curtos para interatividade fluida
        this.ENTER_DELAY = 200;   // ms — Rápido, pois agora só mostraremos o botão Play
        this.LEAVE_DELAY = 150;   // ms — Saída suave
        this.EDGE_RATIO = 1.1;   // Proporção para considerar "borda"

        this._activeCard = null;

        // Gerenciamento de Templates
        this._skelTpl = document.getElementById(
            this.isTop10 ? 'pipo-card-top10-skeleton-template' : 'pipo-card-skeleton-template'
        ) || PipoRail._createSkeletonTemplate(this.isTop10);

        this._cardTpl = document.getElementById(
            this.isTop10 ? 'pipo-card-top10-template' : 'pipo-card-template'
        ) || PipoRail._createCardTemplate(this.isTop10);

        if (this.host) this._build();
        else console.warn(`[PipoRail] Container #${containerId} não encontrado.`);
    }

    /* ──────────────────────────────────────────────────────────────────
       BUILD — Cria a estrutura inicial
    ────────────────────────────────────────────────────────────────── */
    _build() {
        this.host.className = 'slick-section';
        this.host.innerHTML = `
            <div class="slick-header">
                <h2 class="slick-title">${this._esc(this.title)}</h2>
                <nav class="slick-nav" aria-label="Navegação do trilho">
                    <button class="slick-nav-btn js-prev" aria-label="Anterior">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <button class="slick-nav-btn js-next" aria-label="Próximo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </nav>
            </div>
            <div class="slick-track" id="${this.host.id}__track"></div>
        `;

        this.track = document.getElementById(`${this.host.id}__track`);
        this._bindNav();
        this._showSkeletons(this.isTop10 ? 6 : 6);
        PipoRail._enqueueFetch(() => this._fetch());
    }

    /* ──────────────────────────────────────────────────────────────────
       Navegação Horizontal Suave
    ────────────────────────────────────────────────────────────────── */
    _bindNav() {
        const scroll = (dir) => {
            const amount = this.track.clientWidth * 0.75;
            this.track.scrollBy({ left: dir * amount, behavior: 'smooth' });
        };
        this.host.querySelector('.js-prev')?.addEventListener('click', () => scroll(-1));
        this.host.querySelector('.js-next')?.addEventListener('click', () => scroll(1));
    }

    /* ──────────────────────────────────────────────────────────────────
       HOVER — Controla o estado de foco do card
    ────────────────────────────────────────────────────────────────── */
    _bindHover(item, card) {
        item._enterTimer = null;
        item._leaveTimer = null;
        item._active = false;

        item.addEventListener('mouseenter', () => {
            clearTimeout(item._leaveTimer);

            item._enterTimer = setTimeout(() => {
                if (this._activeCard && this._activeCard !== item) {
                    this._deactivate(this._activeCard, true);
                }
                this._activate(item, card);
                this._activeCard = item;
            }, this.ENTER_DELAY);
        });

        item.addEventListener('mouseleave', () => {
            clearTimeout(item._enterTimer);

            if (item._active) {
                item._leaveTimer = setTimeout(() => {
                    this._deactivate(item, false);
                    if (this._activeCard === item) this._activeCard = null;
                }, this.LEAVE_DELAY);
            }
        });

        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this._openDetail(card.dataset);
            }
        });
    }

    _activate(item, card) {
        this._detectEdge(item, card);
        item._active = true;
        item.classList.add('is-active');
        card.classList.add('is-hovered');
        card.setAttribute('aria-expanded', 'true');
    }

    _deactivate(item, instant) {
        const card = item.querySelector('.slick-card');
        item._active = false;

        if (instant && card) {
            card.style.transition = 'none';
            card.classList.remove('is-hovered', 'edge-left', 'edge-right');
            void card.offsetWidth;
            card.style.transition = '';
        } else if (card) {
            card.classList.remove('is-hovered', 'edge-left', 'edge-right');
        }

        item.classList.remove('is-active');
        card?.setAttribute('aria-expanded', 'false');
    }

    /* ──────────────────────────────────────────────────────────────────
       DETECÇÃO DE BORDA HORIZONTAL
    ────────────────────────────────────────────────────────────────── */
    _detectEdge(item, card) {
        const trackRect = this.track.getBoundingClientRect();
        const cardRect = item.getBoundingClientRect();

        card.classList.remove('edge-left', 'edge-right');
        const threshold = cardRect.width * this.EDGE_RATIO;

        if (cardRect.left - trackRect.left < threshold) {
            card.classList.add('edge-left');
        } else if (trackRect.right - cardRect.right < threshold) {
            card.classList.add('edge-right');
        }
    }

    /* ──────────────────────────────────────────────────────────────────
       API E RENDERIZAÇÃO
    ────────────────────────────────────────────────────────────────── */
    _showSkeletons(n) {
        this.track.innerHTML = '';
        for (let i = 0; i < n; i++) {
            this.track.appendChild(this._skelTpl.content.cloneNode(true));
        }
    }

    async _fetch() {
        try {
            let url = this.isTop10
                ? `/api/v2/trending?limite=${this.limit}&tipo=${this.apiCategory === 'top10_series' ? 'serie' : 'filme'}`
                : `/api/v2/conteudo?categoria=${this.apiCategory}&limite=${this.limit}`;

            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            const items = data.resultados || data.items || [];

            if (data.sucesso && items.length > 0) {
                this._render(items);
            } else {
                this.track.innerHTML = '<p class="slick-empty">Nenhum conteúdo disponível nesta seção.</p>';
            }
        } catch (err) {
            console.error(`[PipoRail "${this.title}"]`, err);
            this.track.innerHTML = '<p class="slick-error">Não foi possível carregar os títulos.</p>';
        }
    }

    static _enqueueFetch(task) {
        if (!this._queue) {
            this._queue = [];
            this._activeFetches = 0;
            this._maxFetches = 4;
        }

        this._queue.push(task);
        this._drainFetchQueue();
    }

    static _drainFetchQueue() {
        if (!this._queue || this._activeFetches >= this._maxFetches) return;

        const task = this._queue.shift();
        if (!task) return;

        this._activeFetches += 1;
        const run = () => {
            Promise.resolve()
                .then(task)
                .catch(() => {})
                .finally(() => {
                    this._activeFetches = Math.max(0, this._activeFetches - 1);
                    this._drainFetchQueue();
                });
        };

        if ('requestIdleCallback' in window && this._activeFetches > 2) {
            window.requestIdleCallback(run, { timeout: 350 });
        } else {
            run();
        }
    }

    _render(items) {
        this.track.innerHTML = '';

        items.forEach((item, idx) => {
            if (!item) return;

            const node = this._cardTpl.content.cloneNode(true);
            const item_wrap = node.querySelector('.slick-item');
            const card = node.querySelector('.slick-card');

            if (!item_wrap || !card) return;

            // Dados do Dataset
            card.dataset.id = item.id_tmdb || item.id || '';
            card.dataset.tipo = item.tipo || 'filme';
            card.dataset.title = item.titulo || '';

            // Poster Principal
            const poster = node.querySelector('.poster');
            if (poster) {
                poster.src = this._img(this._bestPoster(item), 'w342');
                poster.alt = item.titulo || '';
                poster.onerror = () => { poster.style.display = 'none'; };
            }

            // Textos: Apenas Título, Ranking e Badge permaneceram
            const rankEl = node.querySelector('.rank-number');
            const badge = node.querySelector('.slick-badge');
            // Nota: O título agora tem a classe .card-title-outside
            const titleEl = node.querySelector('.card-title-outside');

            if (rankEl) rankEl.textContent = String(idx + 1);
            if (badge) badge.textContent = this._badgeLabel(item);
            if (titleEl) titleEl.textContent = item.titulo || '';

            // Delegação de cliques no card inteiro
            card.addEventListener('click', () => this._openDetail(card.dataset));

            // Acoplar Eventos de Hover
            this._bindHover(item_wrap, card);
            this.track.appendChild(node);
        });
    }

    /* ──────────────────────────────────────────────────────────────────
       HELPERS E FORMATADORES
    ────────────────────────────────────────────────────────────────── */
    _openDetail(dataset) {
        const id = dataset.id;
        const tipo = (dataset.tipo === 'serie' || dataset.tipo === 'tv') ? 'serie' : 'movie';
        if (id) window.location.href = `/view?id=${id}&type=${tipo}`;
    }

    _img(path, size = 'w342') {
        if (!path || typeof path !== 'string') return '';
        const p = path.trim();
        if (!p) return '';
        if (p.startsWith('http://') || p.startsWith('https://')) return p;
        if (p.startsWith('/')) {
            if (p.match(/^\/[a-zA-Z_\-]+\//)) return p;
            return `${this.TMDB}${size}${p}`;
        }
        return `${this.TMDB}${size}/${p}`;
    }

    _bestPoster(item) {
        return item.capa || item.poster || item.imagem || '';
    }

    _badgeLabel(item) {
        const t = (item.tipo || '').toLowerCase();
        return (t === 'serie' || t === 'tv') ? 'SÉRIE' : 'FILME';
    }

    _esc(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[m]);
    }

    /* ──────────────────────────────────────────────────────────────────
       TEMPLATES DOM (Ultra Limpos e Focados no Pôster)
    ────────────────────────────────────────────────────────────────── */
    static _createSkeletonTemplate(isTop10) {
        const tpl = document.createElement('template');
        tpl.innerHTML = isTop10
            ? `<div class="slick-item slick-item--top10">
                   <div class="slick-card slick-card--top10 slick-skeleton"></div>
                   <div class="card-title-skeleton" style="height:14px; width:60%; background:#333; margin-top:8px; border-radius:4px;"></div>
               </div>`
            : `<div class="slick-item">
                   <div class="slick-card slick-skeleton"></div>
                   <div class="card-title-skeleton" style="height:14px; width:70%; background:#333; margin-top:8px; border-radius:4px;"></div>
               </div>`;
        return tpl;
    }

    static _createCardTemplate(isTop10) {
        const tpl = document.createElement('template');

        // Botão de Play centralizado (baseado na sua imagem)
        const playOverlay = `
            <div class="play-overlay" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <circle cx="12" cy="12" r="11" fill="rgba(255,255,255,0.85)"/>
                    <polygon points="10,8 16,12 10,16" fill="#000"/>
                </svg>
            </div>`;

        tpl.innerHTML = isTop10 ? `
            <div class="slick-item slick-item--top10">
                <div class="slick-card slick-card--top10" tabindex="0" role="button">
                    <span class="rank-number" aria-hidden="true"></span>
                    <div class="slick-thumb slick-thumb--top10">
                        <img src="" alt="" class="poster" loading="lazy">
                        <span class="slick-badge"></span>
                        ${playOverlay}
                    </div>
                </div>
                <!-- Título fora do card (Abaixo do pôster) -->
                <h3 class="card-title-outside"></h3>
            </div>`
            : `
            <div class="slick-item">
                <div class="slick-card" tabindex="0" role="button">
                    <div class="slick-thumb">
                        <img src="" alt="" class="poster" loading="lazy">
                        ${playOverlay}
                    </div>
                    <span class="slick-badge"></span>
                </div>
                <!-- Título fora do card (Abaixo do pôster) -->
                <h3 class="card-title-outside"></h3>
            </div>`;

        return tpl;
    }
}
