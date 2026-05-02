<?php
/**
 * ARQUIVO: pages/minha-lista.php
 * Biblioteca do perfil: histórico, salvos e curtidos.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode('/minha-lista'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0c10">
    <title>Minha Lista — Pipocine</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/content-card.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:         #e50914;
            --accent-hover:   #f40612;
            --bg:             #0a0c10;
            --surface:        #12151c;
            --surface2:       #1a1e28;
            --surface3:       #232936;
            --text-pure:      #ffffff;
            --text-primary:   #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted:     #64748b;
            --border:         #1e293b;
            --border-strong:  #334155;
            --radius:         6px;
            --radius-lg:      12px;
            --max:            1280px;
            --transition:     0.2s ease;
        }

        html { background: var(--bg); }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            padding-top: 72px;
            padding-bottom: 80px;
        }

        /* ── HERO BANNER ─────────────────────────────────────── */
        .lib-hero {
            background: linear-gradient(135deg, #0f1520 0%, #0a0c10 60%);
            border-bottom: 1px solid var(--border);
            padding: 40px 0 36px;
            margin-bottom: 40px;
        }
        .lib-hero-inner {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        .lib-hero-title {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 700;
            color: var(--text-pure);
            letter-spacing: -.03em;
            line-height: 1.1;
        }
        .lib-hero-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .lib-stats {
            display: flex;
            gap: 24px;
            flex-shrink: 0;
        }
        .lib-stat {
            text-align: center;
        }
        .lib-stat-num {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-pure);
            line-height: 1;
        }
        .lib-stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-top: 4px;
        }

        /* ── TABS ────────────────────────────────────────────── */
        .lib-tabs-wrap {
            max-width: var(--max);
            margin: 0 auto 36px;
            padding: 0 40px;
        }
        .lib-tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }
        .lib-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 16px 12px;
            cursor: pointer;
            font-family: inherit;
            transition: color .2s ease, border-color .2s ease;
            margin-bottom: -1px;
        }
        .lib-tab svg { width: 16px; height: 16px; flex-shrink: 0; }
        .lib-tab:hover { color: var(--text-primary); }
        .lib-tab.active {
            color: var(--text-pure);
            border-bottom-color: var(--accent);
        }
        .lib-tab-count {
            background: var(--surface3);
            color: var(--text-secondary);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            line-height: 1.4;
        }
        .lib-tab.active .lib-tab-count {
            background: var(--accent);
            color: #fff;
        }

        /* ── CONTEUDO ─────────────────────────────────────────── */
        .lib-content {
            max-width: var(--max);
            margin: 0 auto;
            padding: 0 40px;
        }

        /* ── PAINEL DE SEÇÃO ──────────────────────────────────── */
        .lib-panel { display: none; }
        .lib-panel.active { display: block; }

        /* ── GRID DE CARDS ────────────────────────────────────── */
        .lib-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
            gap: 20px 14px;
        }

        /* Card individual da biblioteca */
        .lib-card {
            position: relative;
            cursor: pointer;
            border-radius: var(--radius);
            overflow: visible;
        }
        .lib-card-thumb {
            position: relative;
            aspect-ratio: 2 / 3;
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--surface2);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .lib-card:hover .lib-card-thumb {
            transform: scale(1.04);
            box-shadow: 0 12px 32px rgba(0,0,0,.7);
        }
        .lib-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }
        .lib-card-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s ease;
        }
        .lib-card:hover .lib-card-overlay { opacity: 1; }
        .lib-card-play {
            width: 48px; height: 48px;
            background: rgba(255,255,255,.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .lib-card-play svg { width: 20px; height: 20px; margin-left: 2px; }

        .lib-card-badge {
            position: absolute;
            top: 8px; left: 8px;
            background: rgba(0,0,0,.7);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 3px 6px;
            border-radius: 4px;
        }
        .lib-card-title {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lib-card-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── EMPTY STATE ──────────────────────────────────────── */
        .lib-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 24px;
            text-align: center;
            gap: 16px;
            color: var(--text-muted);
        }
        .lib-empty svg { width: 48px; height: 48px; opacity: .3; }
        .lib-empty-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .lib-empty-sub {
            font-size: 13px;
            color: var(--text-muted);
            max-width: 320px;
        }
        .lib-empty-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 4px;
            transition: background .2s ease;
        }
        .lib-empty-btn:hover { background: var(--accent-hover); }

        /* ── LOADER ─────────────────────────────────────────────── */
        .lib-loader {
            display: flex;
            justify-content: center;
            padding: 60px 0;
        }
        .lib-spinner {
            width: 32px; height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── RESPONSIVO ───────────────────────────────────────── */
        @media (max-width: 768px) {
            .lib-hero-inner { padding: 0 20px; }
            .lib-tabs-wrap  { padding: 0 16px; }
            .lib-content    { padding: 0 16px; }
            .lib-grid       { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 16px 10px; }
            .lib-stats      { gap: 16px; }
            .lib-stat-num   { font-size: 22px; }
        }

        @media (max-width: 480px) {
            .lib-grid { grid-template-columns: repeat(3, 1fr); gap: 12px 8px; }
            .lib-tabs { overflow-x: auto; scrollbar-width: none; }
            .lib-tabs::-webkit-scrollbar { display: none; }
            .lib-tab { white-space: nowrap; }
        }

        /* ── HISTORY DATE SEPARATOR ──────────────────────────── */
        .lib-date-sep {
            grid-column: 1 / -1;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .1em;
            padding: 8px 0 4px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../components/Header.php'; ?>

<!-- ── HERO ────────────────────────────────────────────── -->
<div class="lib-hero">
    <div class="lib-hero-inner">
        <div>
            <h1 class="lib-hero-title">Minha Lista</h1>
            <p class="lib-hero-sub">Seus filmes e séries salvos, curtidos e assistidos.</p>
        </div>
        <div class="lib-stats">
            <div class="lib-stat">
                <div class="lib-stat-num" id="stat-history">—</div>
                <div class="lib-stat-label">Assistidos</div>
            </div>
            <div class="lib-stat">
                <div class="lib-stat-num" id="stat-saved">—</div>
                <div class="lib-stat-label">Salvos</div>
            </div>
            <div class="lib-stat">
                <div class="lib-stat-num" id="stat-liked">—</div>
                <div class="lib-stat-label">Curtidos</div>
            </div>
        </div>
    </div>
</div>

<!-- ── TABS ─────────────────────────────────────────────── -->
<div class="lib-tabs-wrap">
    <div class="lib-tabs" role="tablist">
        <button class="lib-tab active" role="tab" aria-selected="true"
                onclick="Library.switchTab('history', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Continuar assistindo
            <span class="lib-tab-count" id="cnt-history">0</span>
        </button>
        <button class="lib-tab" role="tab" aria-selected="false"
                onclick="Library.switchTab('saved', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            Salvos
            <span class="lib-tab-count" id="cnt-saved">0</span>
        </button>
        <button class="lib-tab" role="tab" aria-selected="false"
                onclick="Library.switchTab('liked', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
            </svg>
            Curtidos
            <span class="lib-tab-count" id="cnt-liked">0</span>
        </button>
    </div>
</div>

<!-- ── PAINEIS DE CONTEUDO ──────────────────────────────── -->
<div class="lib-content">

    <!-- Loader -->
    <div class="lib-loader" id="lib-loader">
        <div class="lib-spinner"></div>
    </div>

    <!-- Histórico -->
    <div class="lib-panel active" id="panel-history" role="tabpanel">
        <div class="lib-grid" id="grid-history"></div>
    </div>

    <!-- Salvos -->
    <div class="lib-panel" id="panel-saved" role="tabpanel">
        <div class="lib-grid" id="grid-saved"></div>
    </div>

    <!-- Curtidos -->
    <div class="lib-panel" id="panel-liked" role="tabpanel">
        <div class="lib-grid" id="grid-liked"></div>
    </div>

</div>

<!-- ── TEMPLATES ────────────────────────────────────────── -->
<?php require_once __DIR__ . '/../components/ContentCard.php'; ?>

<script src="/assets/js/header.js"></script>
<script>
class Library {
    static data = { history: [], saved: [], liked: [] };
    static currentTab = 'history';

    static async init() {
        try {
            const res  = await fetch('/api/v3/library/all');
            const json = await res.json();
            if (!json.sucesso) throw new Error('Falha ao carregar biblioteca.');

            this.data = json.dados;
            document.getElementById('lib-loader').style.display = 'none';

            this.updateStats();
            this.renderAll();

        } catch (err) {
            document.getElementById('lib-loader').style.display = 'none';
            document.getElementById('grid-history').innerHTML = this.emptyState(
                'Não foi possível carregar sua lista.',
                'Tente novamente mais tarde.', false
            );
        }
    }

    static updateStats() {
        document.getElementById('stat-history').textContent = this.data.history.length;
        document.getElementById('stat-saved').textContent   = this.data.saved.length;
        document.getElementById('stat-liked').textContent   = this.data.liked.length;

        document.getElementById('cnt-history').textContent = this.data.history.length;
        document.getElementById('cnt-saved').textContent   = this.data.saved.length;
        document.getElementById('cnt-liked').textContent   = this.data.liked.length;
    }

    static renderAll() {
        this.renderGrid('history', this.data.history,  'Você não assistiu nada ainda.', 'Explore o catálogo e comece a assistir.');
        this.renderGrid('saved',   this.data.saved,    'Nenhum conteúdo salvo.',         'Salve filmes e séries para assistir depois.');
        this.renderGrid('liked',   this.data.liked,    'Nenhum conteúdo curtido.',        'Curta os conteúdos que você amou.');
    }

    static renderGrid(type, items, emptyTitle, emptySub) {
        const grid = document.getElementById(`grid-${type}`);
        if (!items.length) {
            grid.innerHTML = this.emptyState(emptyTitle, emptySub, true);
            return;
        }

        if (type === 'history') {
            grid.innerHTML = this.renderHistoryGrouped(items);
        } else {
            grid.innerHTML = items.map(item => this.cardHtml(item)).join('');
        }
    }

    static renderHistoryGrouped(items) {
        const groups = {};
        items.forEach(item => {
            const label = this.dateGroupLabel(item.watched_at);
            if (!groups[label]) groups[label] = [];
            groups[label].push(item);
        });

        return Object.entries(groups).map(([label, group]) =>
            `<div class="lib-date-sep">${this.esc(label)}</div>` +
            group.map(item => this.cardHtml(item, 'history')).join('')
        ).join('');
    }

    static cardHtml(item, type = '') {
        const id      = item.content_id;
        const ct      = item.content_type === 'serie' ? 'serie' : 'movie';
        const href    = `/view?id=${id}&type=${ct}`;
        const badge   = ct === 'serie' ? 'Série' : 'Filme';
        const poster  = item.content_poster
            ? item.content_poster
            : `https://via.placeholder.com/148x222/12151c/64748b?text=${this.esc(item.content_title?.slice(0,2) || '?')}`;
        const year    = item.content_year ? `<span class="lib-card-meta">${item.content_year}</span>` : '';

        return `
            <div class="lib-card" onclick="window.location.href='${href}'">
                <div class="lib-card-thumb">
                    <img src="${this.esc(poster)}" alt="${this.esc(item.content_title)}"
                         class="lib-card-img" loading="lazy"
                         onerror="this.src='https://via.placeholder.com/148x222/1a1e28/64748b?text=?'">
                    <div class="lib-card-overlay">
                        <div class="lib-card-play">
                            <svg viewBox="0 0 24 24" fill="black"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <span class="lib-card-badge">${badge}</span>
                </div>
                <div class="lib-card-title">${this.esc(item.content_title)}</div>
                ${year}
            </div>`;
    }

    static switchTab(tab, btn) {
        this.currentTab = tab;

        // Toggle tabs
        document.querySelectorAll('.lib-tab').forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');

        // Toggle panels
        document.querySelectorAll('.lib-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(`panel-${tab}`).classList.add('active');
    }

    static emptyState(title, sub, showBtn = true) {
        const btn = showBtn
            ? `<a href="/home" class="lib-empty-btn">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                   Explorar catálogo
               </a>`
            : '';
        return `
            <div class="lib-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <div class="lib-empty-title">${this.esc(title)}</div>
                <div class="lib-empty-sub">${this.esc(sub)}</div>
                ${btn}
            </div>`;
    }

    static dateGroupLabel(dateStr) {
        if (!dateStr) return 'Sem data';
        const d     = new Date(dateStr.replace(' ', 'T'));
        const now   = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const itemD = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const diff  = Math.floor((today - itemD) / 86400000);

        if (diff === 0) return 'Hoje';
        if (diff === 1) return 'Ontem';
        if (diff <= 7)  return 'Esta semana';
        if (diff <= 30) return 'Este mês';
        return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    }

    static esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

document.addEventListener('DOMContentLoaded', () => Library.init());
</script>

</body>
</html>
