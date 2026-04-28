// assets/js/exhibition.js

class ExhibitionManager {
    static async init() {
        const params = new URLSearchParams(window.location.search);
        this.tmdbId = params.get('id');
        this.type = params.get('type') || 'movie';
        this.season = parseInt(params.get('s')) || 1;
        this.episode = parseInt(params.get('e')) || 1;

        if (!this.tmdbId) return;

        await this.loadExhibitionData();
        this.setupEventListeners();
    }

    static async loadExhibitionData() {
        try {
            // Chamada à tua API v2 de Exibição
            const res = await fetch(`/api/v2/exhibition?id=${this.tmdbId}&type=${this.type}&s=${this.season}&e=${this.episode}`);
            const data = await res.json();

            if (data.sucesso) {
                this.renderHero(data.dados.content_info);
                this.renderCast(data.dados.content_info.tmdb_id, data.dados.content_info.type);
                
                if (data.dados.content_info.type === 'serie' || data.dados.content_info.type === 'tv') {
                    document.getElementById('exhibition-series-container').style.display = 'block';
                    this.renderSeasonMenu(data.dados.seasons_available);
                    this.renderEpisodes(data.dados.episode_metadata, data.dados.seasons_available);
                }
            }
        } catch (err) { console.error("Erro na Exibição:", err); }
    }

    static renderHero(meta) {
        const container = document.getElementById('exhibition-hero-container');
        const tpl = document.getElementById('tpl-hero').innerHTML;
        
        container.innerHTML = tpl
            .replace('${backdrop}', `https://image.tmdb.org/t/p/original${meta.backdrop}`)
            .replace('${title}', meta.title)
            .replace('${overview}', meta.overview)
            .replace('${year}', meta.type === 'movie' ? 'Filme' : 'Série')
            .replace('${rating}', '4K')
            .replace('${type_label}', 'Pipocine Original');
            
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    static renderSeasonMenu(seasons) {
        const dropdown = document.getElementById('season-dropdown');
        dropdown.innerHTML = seasons.map(s => `
            <div class="season-opt" onclick="ExhibitionManager.changeSeason(${s.season_number})">
                Temporada ${s.season_number}
            </div>
        `).join('');
    }

    static async renderEpisodes(currentEp, seasons) {
        // Nota: Para ser 100% Netflix, aqui podes fazer uma nova chamada 
        // para listar todos os episódios da temporada selecionada
        const grid = document.getElementById('episodes-grid');
        const tpl = document.getElementById('tpl-episode-card').innerHTML;
        
        // Exemplo: Renderizando o episódio atual (a API v2 deve retornar a lista da temporada)
        grid.innerHTML = tpl
            .replace('${still_path}', currentEp.still_path)
            .replace('${name}', currentEp.name)
            .replace('${episode_number}', currentEp.episode)
            .replace('${overview}', currentEp.overview);

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    static async renderCast(id, type) {
        const grid = document.getElementById('cast-grid');
        const tpl = document.getElementById('tpl-cast-card').innerHTML;
        
        // Chamada à API de Conteúdo que já tens para trazer os créditos
        const mediaType = (type === 'serie') ? 'tv' : 'movie';
        const res = await fetch(`https://api.themoviedb.org/3/${mediaType}/${id}/credits?api_key=dc6299fd1adb4e32cf16017eecb33295&language=pt-BR`);
        const data = await res.json();

        grid.innerHTML = data.cast.slice(0, 12).map(actor => {
            return tpl
                .replace('${profile_path}', actor.profile_path ? `https://image.tmdb.org/t/p/w185${actor.profile_path}` : '/assets/img/no-avatar.png')
                .replace('${name}', actor.name)
                .replace('${character}', actor.character);
        }).join('');
    }

    static setupEventListeners() {
        const btn = document.getElementById('season-btn');
        if (btn) {
            btn.onclick = () => document.getElementById('season-dropdown').classList.toggle('open');
        }
    }

    static changeSeason(num) {
        const url = new URL(window.location);
        url.searchParams.set('s', num);
        url.searchParams.set('e', 1);
        window.location.href = url.href;
    }

    static startPlayback() {
        // Lógica para abrir o player modal ou redirecionar
        PipoNotification.success("Iniciando reprodução...");
    }
}

document.addEventListener('DOMContentLoaded', () => ExhibitionManager.init());