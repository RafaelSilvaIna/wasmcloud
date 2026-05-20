(function () {
    if (typeof Search === 'undefined') return;

    const typeLabels = { filme: 'Filmes', serie: 'Series' };

    Search.ensureFilterBar = function () {
        if (this.el.filterBar) return this.el.filterBar;

        const bar = document.createElement('div');
        bar.id = 'active-filter-bar';
        bar.className = 'active-filter-bar';
        bar.setAttribute('aria-live', 'polite');
        this.el.meta.parentNode.insertBefore(bar, this.el.meta);
        this.el.filterBar = bar;
        return bar;
    };

    Search.ensureFiltersToggle = function () {
        this.el.filtersToggle = document.getElementById('filters-toggle');
        this.el.filtersPanel = document.getElementById('filters-panel');
        this.el.filtersCount = document.getElementById('filters-count');

        this.filtersCollapsed = true;
        this.applyFiltersCollapsedState();
    };

    Search.readUrlState = function () {
        const params = new URLSearchParams(window.location.search);
        this.query = (params.get('q') || this.el.input.value || '').trim();
        this.tipo = params.get('tipo') || '';
        this.genero = params.get('genero') || '';
        this.ano = params.get('ano') || '';
        this.ordem = params.get('ordem') || 'relevancia';
        this.pagina = Math.max(parseInt(params.get('pagina') || '1', 10), 1);
    };

    Search.enhanceSelectMenus = function () {
        if (this.el.selectMenus) return;

        this.el.selectMenus = {
            genero: this.createSelectMenu(this.el.generoSel),
            ano: this.createSelectMenu(this.el.anoSel),
            ordem: this.createSelectMenu(this.el.sort),
        };

        document.addEventListener('click', e => {
            if (!e.target.closest('.select-menu-wrap')) {
                this.closeSelectMenus();
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                this.closeSelectMenus();
            }
        });
    };

    Search.createSelectMenu = function (select) {
        const wrap = document.createElement('div');
        wrap.className = 'select-menu-wrap';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'select-menu-trigger';
        button.setAttribute('aria-haspopup', 'menu');
        button.setAttribute('aria-expanded', 'false');

        const label = document.createElement('span');
        label.className = 'select-menu-label';

        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '2.4');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');
        icon.innerHTML = '<path d="M6 9l6 6 6-6"/>';

        const panel = document.createElement('div');
        panel.className = 'select-menu-panel';
        panel.setAttribute('role', 'menu');

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        wrap.appendChild(button);
        button.appendChild(label);
        button.appendChild(icon);
        wrap.appendChild(panel);
        select.classList.add('select-native-hidden');

        const menu = { select, wrap, button, label, panel };

        const openMenu = () => {
            const isOpen = wrap.classList.contains('open');
            this.closeSelectMenus();
            if (!isOpen) {
                this.renderSelectMenuOptions(menu);
                wrap.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
            }
        };

        button.addEventListener('click', e => {
            e.stopPropagation();
            openMenu();
        });

        button.addEventListener('contextmenu', e => {
            e.preventDefault();
            e.stopPropagation();
            openMenu();
        });

        panel.addEventListener('click', e => {
            const option = e.target.closest('[data-select-value]');
            if (!option) return;
            select.value = option.dataset.selectValue;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            this.closeSelectMenus();
        });

        return menu;
    };

    Search.renderSelectMenuOptions = function (menu) {
        const value = menu.select.value;
        menu.panel.innerHTML = Array.from(menu.select.options).map(option => {
            const selected = option.value === value;
            return `
                <button class="select-menu-option${selected ? ' selected' : ''}" type="button" role="menuitemradio" aria-checked="${selected ? 'true' : 'false'}" data-select-value="${this.esc(option.value)}">
                    ${this.esc(option.textContent)}
                </button>`;
        }).join('');
    };

    Search.updateSelectMenus = function () {
        if (!this.el.selectMenus) return;

        Object.values(this.el.selectMenus).forEach(menu => {
            const selected = menu.select.options[menu.select.selectedIndex];
            const defaultValue = menu.select.options[0] ? menu.select.options[0].value : '';
            const hasValue = menu.select.value !== defaultValue;
            menu.label.textContent = selected ? selected.textContent : '';
            menu.button.classList.toggle('active', hasValue);
            menu.renderedValue = menu.select.value;
            if (menu.wrap.classList.contains('open')) {
                this.renderSelectMenuOptions(menu);
            }
        });
    };

    Search.closeSelectMenus = function () {
        if (!this.el.selectMenus) return;

        Object.values(this.el.selectMenus).forEach(menu => {
            menu.wrap.classList.remove('open');
            menu.button.setAttribute('aria-expanded', 'false');
        });
    };

    Search.syncControls = function () {
        this.el.input.value = this.query;
        this.el.clear.classList.toggle('visible', this.query.length > 0);

        document.querySelectorAll('[data-filter="tipo"]').forEach(btn => {
            const active = btn.dataset.value === this.tipo;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        this.el.generoSel.value = this.genero;
        this.el.anoSel.value = this.ano;
        this.el.sort.value = this.ordem;
        this.el.generoSel.classList.toggle('active', !!this.genero);
        this.el.anoSel.classList.toggle('active', !!this.ano);
        this.updateSelectMenus();
        this.renderActiveFilters();
        this.updateFiltersCount();
    };

    Search.renderActiveFilters = function () {
        const bar = this.ensureFilterBar();
        const filters = [];

        if (this.tipo) filters.push({ key: 'tipo', label: typeLabels[this.tipo] || this.tipo });
        if (this.genero) filters.push({ key: 'genero', label: this.genero });
        if (this.ano) filters.push({ key: 'ano', label: this.ano });
        if (this.ordem && this.ordem !== 'relevancia') {
            const selected = this.el.sort.options[this.el.sort.selectedIndex];
            filters.push({ key: 'ordem', label: selected ? selected.textContent : this.ordem });
        }

        if (!filters.length) {
            bar.classList.remove('visible');
            bar.innerHTML = '';
            return;
        }

        bar.classList.add('visible');
        bar.innerHTML = filters.map(filter => `
            <button class="active-filter-pill" type="button" data-clear-filter="${this.esc(filter.key)}" aria-label="Remover filtro ${this.esc(filter.label)}">
                ${this.esc(filter.label)}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        `).join('') + '<button class="clear-filters-btn" type="button" data-clear-filter="all">Limpar filtros</button>';
    };

    Search.activeFilterCount = function () {
        let count = 0;
        if (this.tipo) count++;
        if (this.genero) count++;
        if (this.ano) count++;
        if (this.ordem && this.ordem !== 'relevancia') count++;
        return count;
    };

    Search.updateFiltersCount = function () {
        if (!this.el.filtersCount) return;

        const count = this.activeFilterCount();
        this.el.filtersCount.hidden = count === 0;
        this.el.filtersCount.textContent = String(count);
    };

    Search.applyFiltersCollapsedState = function () {
        if (!this.el.filtersToggle || !this.el.filtersPanel) return;

        this.el.filtersPanel.classList.toggle('is-collapsed', this.filtersCollapsed);
        this.el.filtersToggle.setAttribute('aria-expanded', this.filtersCollapsed ? 'false' : 'true');
        this.el.filtersToggle.querySelector('span').textContent = this.filtersCollapsed ? 'Mostrar filtros' : 'Ocultar filtros';
    };

    Search.toggleFiltersPanel = function () {
        this.filtersCollapsed = !this.filtersCollapsed;
        this.closeSelectMenus();
        this.applyFiltersCollapsedState();
    };

    Search.clearFilter = function (key) {
        if (key === 'all') {
            this.tipo = '';
            this.genero = '';
            this.ano = '';
            this.ordem = 'relevancia';
        } else if (key === 'ordem') {
            this.ordem = 'relevancia';
        } else {
            this[key] = '';
        }

        this.pagina = 1;
        this.syncControls();
        this.updateUrl();
        if (this.query) this.run();
        else this.renderInitial();
    };

    Search.init = async function () {
        this.ensureFilterBar();
        this.ensureFiltersToggle();
        await this.loadFilters();
        this.enhanceSelectMenus();
        this.readUrlState();
        this.bindEvents();
        this.syncControls();

        if (this.query) this.run();
        else this.renderInitial();
    };

    Search.bindEvents = function () {
        this.el.input.addEventListener('input', () => {
            const val = this.el.input.value.trim();
            this.el.clear.classList.toggle('visible', val.length > 0);
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.query = val;
                this.pagina = 1;
                this.updateUrl();
                if (val.length >= 1) this.run();
                else this.renderInitial();
            }, 260);
        });

        this.el.input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                clearTimeout(this.debounceTimer);
                this.query = this.el.input.value.trim();
                this.pagina = 1;
                this.updateUrl();
                if (this.query) this.run();
            }

            if (e.key === 'Escape') {
                this.el.clear.click();
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                this.el.input.focus();
            }
        });

        this.el.clear.addEventListener('click', () => {
            this.el.input.value = '';
            this.el.clear.classList.remove('visible');
            this.query = '';
            this.pagina = 1;
            this.el.input.focus();
            this.updateUrl();
            this.renderInitial();
        });

        document.querySelectorAll('[data-filter="tipo"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.tipo = btn.dataset.value;
                this.pagina = 1;
                this.syncControls();
                this.updateUrl();
                if (this.query) this.run();
            });
        });

        this.el.generoSel.addEventListener('change', () => {
            this.genero = this.el.generoSel.value;
            this.pagina = 1;
            this.syncControls();
            this.updateUrl();
            if (this.query) this.run();
        });

        this.el.anoSel.addEventListener('change', () => {
            this.ano = this.el.anoSel.value;
            this.pagina = 1;
            this.syncControls();
            this.updateUrl();
            if (this.query) this.run();
        });

        this.el.sort.addEventListener('change', () => {
            this.ordem = this.el.sort.value;
            this.pagina = 1;
            this.syncControls();
            this.updateUrl();
            if (this.query) this.run();
        });

        if (this.el.filtersToggle) {
            this.el.filtersToggle.addEventListener('click', () => this.toggleFiltersPanel());
        }

        this.ensureFilterBar().addEventListener('click', e => {
            const btn = e.target.closest('[data-clear-filter]');
            if (!btn) return;
            this.clearFilter(btn.dataset.clearFilter);
        });

        const hero = document.getElementById('search-hero');
        window.addEventListener('scroll', () => {
            hero.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    };

    Search.run = async function () {
        this.renderSkeleton();
        this.renderActiveFilters();
        this.updateUrl();

        const params = new URLSearchParams({ q: this.query, ordem: this.ordem, pagina: this.pagina });
        if (this.tipo) params.set('tipo', this.tipo);
        if (this.genero) params.set('genero', this.genero);
        if (this.ano) params.set('ano', this.ano);

        try {
            const res = await fetch('/api/v2/busca?' + params);
            const json = await res.json();

            if (!json.sucesso) {
                this.renderError();
                return;
            }

            this.total = json.total;
            this.totalPaginas = json.total_paginas;

            if (!json.dados || json.dados.length === 0) {
                this.renderEmpty();
                return;
            }

            this.renderResults(json.dados);
            this.renderMeta();
            this.renderPagination();
        } catch (_) {
            this.renderError();
        }
    };

    Search.updateUrl = function () {
        const url = new URL(window.location);
        const params = url.searchParams;

        this.query ? params.set('q', this.query) : params.delete('q');
        this.tipo ? params.set('tipo', this.tipo) : params.delete('tipo');
        this.genero ? params.set('genero', this.genero) : params.delete('genero');
        this.ano ? params.set('ano', this.ano) : params.delete('ano');
        this.ordem && this.ordem !== 'relevancia' ? params.set('ordem', this.ordem) : params.delete('ordem');
        this.pagina > 1 ? params.set('pagina', this.pagina) : params.delete('pagina');

        history.replaceState(null, '', url);
    };

    Search.renderInitial = function () {
        this.el.meta.style.display = 'none';
        this.el.pagination.style.display = 'none';
        this.renderActiveFilters();

        const terms = ['Acao', 'Suspense', 'Comedia', 'Drama', 'Terror', 'Animacao', 'Documentario', 'Romance'];
        this.el.container.innerHTML = `
            <div class="initial-state">
                <p class="initial-hint">Termos populares</p>
                <div class="trending-terms">
                    ${terms.map(t => `<button class="trending-term" type="button">${this.esc(t)}</button>`).join('')}
                </div>
                <div class="initial-content" id="initial-content">
                    ${this.renderInitialContentSkeleton()}
                </div>
            </div>`;

        this.el.container.querySelectorAll('.trending-term').forEach(btn => {
            btn.addEventListener('click', () => {
                this.el.input.value = btn.textContent;
                this.el.clear.classList.add('visible');
                this.query = btn.textContent;
                this.pagina = 1;
                this.updateUrl();
                this.run();
            });
        });

        this.loadInitialContent();
    };

    Search.renderInitialContentSkeleton = function () {
        const cards = Array.from({ length: 6 }, () => `
            <div class="skeleton-card">
                <div class="skeleton skeleton-thumb"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line-short"></div>
            </div>`).join('');

        return `
            <section class="initial-content-section" aria-label="Carregando sugestoes">
                <h2 class="initial-content-title">Em alta agora</h2>
                <div class="initial-content-placeholder">${cards}</div>
            </section>`;
    };

    Search.loadInitialContent = async function () {
        const host = document.getElementById('initial-content');
        if (!host) return;

        const sections = [
            { title: 'Em alta agora', category: 'em_alta', limit: 12 },
            { title: 'Melhores filmes', category: 'top_filmes', limit: 9 },
        ];

        try {
            const responses = await Promise.all(sections.map(section =>
                fetch(`/api/v2/conteudo?categoria=${encodeURIComponent(section.category)}&limite=${section.limit}&imagem=tmdb`)
                    .then(res => res.json())
                    .catch(() => null)
            ));

            const html = sections.map((section, index) => {
                const items = responses[index]?.resultados || [];
                return this.renderInitialContentSection(section.title, items);
            }).filter(Boolean).join('');

            if (html && document.getElementById('initial-content') === host) {
                host.innerHTML = html;
            }
        } catch (_) {
            host.innerHTML = '';
        }
    };

    Search.renderMeta = function () {
        const q = this.esc(this.query);
        this.el.count.innerHTML = `<strong>${this.total.toLocaleString('pt-BR')}</strong> resultado${this.total !== 1 ? 's' : ''} para <strong>"${q}"</strong>`;
        this.el.meta.style.display = 'flex';
        this.el.sort.value = this.ordem;
        this.renderActiveFilters();
    };

    Search.goPage = function (n) {
        if (n < 1 || n > this.totalPaginas) return;
        this.pagina = n;
        this.updateUrl();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        this.run();
    };

    Search.renderContentCard = function (item, opts = {}) {
        const isSerie = item.tipo === 'serie';
        const href = `/info=${item.id_tmdb}`;
        const poster = this.normalizeTmdbImage(item.poster || item.capa || '');
        const ano = item.ano || '';
        const genres = Array.isArray(item.generos) ? item.generos.slice(0, 2).join(', ') : '';
        const subline = [ano, genres].filter(Boolean).join(' | ');
        const unavailable = opts.showAvailability && item.disponivel === false;

        return `
            <a class="search-content-card slick-item" href="${this.esc(href)}" aria-label="${this.esc(item.titulo)}" data-component="ContentCard">
                <div class="slick-card${unavailable ? ' is-unavailable' : ''}" tabindex="-1" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="slick-thumb">
                        ${poster ? `<img
                            src="${this.esc(poster)}"
                            alt="${this.esc(item.titulo)}"
                            class="poster"
                            loading="lazy"
                            decoding="async">` : ''}
                        <div class="play-overlay" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                                <circle cx="12" cy="12" r="11" fill="rgba(255,255,255,0.85)"/>
                                <polygon points="10,8 16,12 10,16" fill="#000"/>
                            </svg>
                        </div>
                    </div>
                    <span class="slick-badge" aria-hidden="true">${isSerie ? 'SERIE' : 'FILME'}</span>
                    ${unavailable ? '<span class="search-card-unavailable">Em breve</span>' : ''}
                </div>
                <h3 class="card-title-outside">${this.esc(item.titulo)}</h3>
                ${subline ? `<p class="search-card-subline">${this.esc(subline)}</p>` : ''}
            </a>`;
    };

    Search.normalizeTmdbImage = function (url) {
        url = String(url || '').trim();
        if (!url) return '';
        if (url.includes('image.tmdb.org/t/p/')) return url;
        if (url.startsWith('/')) return `https://image.tmdb.org/t/p/w500${url}`;
        if (/^https?:\/\/(?:www\.)?pipocine\.site\//i.test(url)) return '';
        return url;
    };

    Search.renderInitialContentSection = function (title, items) {
        if (!Array.isArray(items) || items.length === 0) return '';

        return `
            <section class="initial-content-section" aria-label="${this.esc(title)}">
                <h2 class="initial-content-title">${this.esc(title)}</h2>
                <div class="initial-content-grid">${items.map(item => this.renderContentCard(item)).join('')}</div>
            </section>`;
    };

    Search.renderResults = function (items) {
        this.el.container.innerHTML = `<div class="results-grid">${items.map(item => this.renderContentCard(item, { showAvailability: true })).join('')}</div>`;
    };
})();
