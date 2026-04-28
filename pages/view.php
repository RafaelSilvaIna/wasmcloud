<?php
/**
 * ARQUIVO: pages/view.php
 * DESCRIÇÃO: Página principal de exibição de conteúdo (Filmes e Séries)
 */

// 1. VALIDAÇÃO DE AUTENTICAÇÃO
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para o login com o retorno para esta página
    $currentUrl = $_SERVER['REQUEST_URI'];
    header("Location: /login?redirect=" . urlencode($currentUrl));
    exit;
}

// 2. IMPORTAÇÃO DE TEMPLATES (Componentes anteriores)
// Aqui carregamos os esqueletos HTML <template> que criámos
require_once __DIR__ . '/../components/ExhibitionView.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistir - Pipocine</title>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* --- CSS EMBUTIDO (ESTILO PREMIUM PIPOCINE) --- */
        :root {
            --bg-color: #0a0a0a;
            --accent-color: #3498db;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
            --glass: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* Botão Voltar Flutuante */
        .pipo-back-btn {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 1000;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .pipo-back-btn:hover {
            background: white;
            color: black;
            transform: translateX(-5px);
        }

        /* Container de carregamento */
        #pipo-loader {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a0a;
        }

        /* Importamos aqui o CSS do componente que definimos anteriormente */
        <?php include __DIR__ . '/../assets/css/exhibition.css'; ?>
    </style>
</head>
<body>

    <a href="/home" class="pipo-back-btn">
        <i data-lucide="arrow-left"></i> Voltar para o Início
    </a>

    <div id="pipo-loader">
        <div class="loader-spinner"></div>
    </div>

    <main id="exhibition-render-target" style="display: none;">
        <div id="exhibition-hero-container"></div>
        <div id="exhibition-series-container"></div>
        <div id="exhibition-cast-container"></div>
    </main>

    <script>
        /* --- JS EMBUTIDO (LÓGICA DE INTEGRAÇÃO V2) --- */

        class ExhibitionPage {
            static async init() {
                const urlParams = new URLSearchParams(window.location.search);
                this.config = {
                    id: urlParams.get('id'),
                    type: urlParams.get('type') || 'movie',
                    s: urlParams.get('s') || 1,
                    e: urlParams.get('e') || 1
                };

                if (!this.config.id) {
                    window.location.href = '/home';
                    return;
                }

                await this.fetchAndRender();
            }

            static async fetchAndRender() {
                try {
                    // 1. Chamada à nossa API v2 Profissional
                    const response = await fetch(`/api/v2/exhibition?id=${this.config.id}&type=${this.config.type}&s=${this.config.s}&e=${this.config.e}`);
                    const result = await response.json();

                    if (!result.sucesso) throw new Error(result.erro);

                    // 2. Renderização dos Componentes
                    this.renderHero(result.dados.content_info, result.dados.playback);
                    
                    if (this.config.type === 'serie' || this.config.type === 'tv') {
                        this.renderSeriesData(result.dados);
                    }

                    this.renderCast(this.config.id, this.config.type);

                    // 3. Finalização
                    document.getElementById('pipo-loader').style.display = 'none';
                    document.getElementById('exhibition-render-target').style.display = 'block';
                    
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                } catch (error) {
                    console.error("Falha ao carregar página:", error);
                    alert("Conteúdo indisponível ou erro na conexão.");
                }
            }

            static renderHero(meta, playback) {
                const container = document.getElementById('exhibition-hero-container');
                const tpl = document.getElementById('tpl-hero').innerHTML;
                
                // Formatação profissional dos dados do Hero
                container.innerHTML = tpl
                    .replace('${backdrop}', `https://image.tmdb.org/t/p/original${meta.backdrop}`)
                    .replace('${title}', meta.title)
                    .replace('${overview}', meta.overview || 'Sinopse não disponível para este conteúdo.')
                    .replace('${year}', meta.type === 'movie' ? 'Filme' : 'Série')
                    .replace('${rating}', playback.quality || 'HD')
                    .replace('${type_label}', 'Original Pipocine');
            }

            static renderSeriesData(dados) {
                const container = document.getElementById('exhibition-series-container');
                container.style.display = 'block';

                // Renderiza Seletor de Temporadas
                const dropdown = document.getElementById('season-dropdown');
                if(dropdown) {
                    dropdown.innerHTML = dados.seasons_available.map(s => `
                        <div class="season-opt" onclick="ExhibitionPage.changeSeason(${s.season_number})">
                            Temporada ${s.season_number} (${s.episode_count} Eps)
                        </div>
                    `).join('');
                    document.getElementById('selected-season-label').innerText = `Temporada ${this.config.s}`;
                }

                // Renderiza Card do Episódio Atual
                const grid = document.getElementById('episodes-grid');
                const tpl = document.getElementById('tpl-episode-card').innerHTML;
                const ep = dados.episode_metadata;

                grid.innerHTML = tpl
                    .replace('${still_path}', ep.still_path)
                    .replace('${name}', ep.name)
                    .replace('${episode_number}', ep.episode)
                    .replace('${overview}', ep.overview);
            }

            static async renderCast(id, type) {
                const grid = document.getElementById('cast-grid');
                const tpl = document.getElementById('tpl-cast-card').innerHTML;
                const mediaType = (type === 'serie' || type === 'tv') ? 'tv' : 'movie';

                try {
                    const res = await fetch(`https://api.themoviedb.org/3/${mediaType}/${id}/credits?api_key=dc6299fd1adb4e32cf16017eecb33295&language=pt-BR`);
                    const data = await res.json();
                    
                    grid.innerHTML = data.cast.slice(0, 10).map(actor => {
                        const img = actor.profile_path ? `https://image.tmdb.org/t/p/w185${actor.profile_path}` : 'https://www.themoviedb.org/assets/2/v4/glyphicons/basic/glyphicons-basic-4-user-grey-d8fe57f12e290b84b8e390c5.svg';
                        return tpl
                            .replace('${profile_path}', img)
                            .replace('${name}', actor.name)
                            .replace('${character}', actor.character);
                    }).join('');
                } catch(e) { console.warn("Cast indisponível."); }
            }

            static changeSeason(s) {
                const url = new URL(window.location);
                url.searchParams.set('s', s);
                url.searchParams.set('e', 1);
                window.location.href = url.href;
            }

            static startPlayback() {
                // Aqui você integraria o seu Player de vídeo (ex: Video.js ou Plyr)
                alert("Redirecionando para o player seguro...");
            }
        }

        // Inicializa a página
        document.addEventListener('DOMContentLoaded', () => ExhibitionPage.init());
        
        // Toggle do menu de temporadas
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('season-dropdown');
            if (e.target.closest('#season-btn')) {
                dropdown.classList.toggle('open');
            } else if (!e.target.closest('.ex-season-selector')) {
                if (dropdown) dropdown.classList.remove('open');
            }
        });
    </script>
</body>
</html>