<?php
declare(strict_types=1);

$ownerName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Anunciante', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title>PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        :root {
            --bg:#05070d; --line:rgba(255,255,255,.10); --text:#f8fbff; --muted:#9aa6bd;
            --blue:#0a7aff; --violet:#8b5cf6; --cyan:#31d7ff; --green:#34d399;
        }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            margin:0; min-height:100vh; color:var(--text);
            font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:
                radial-gradient(circle at 15% 15%, rgba(10,122,255,.22), transparent 28%),
                radial-gradient(circle at 85% 10%, rgba(139,92,246,.20), transparent 24%),
                radial-gradient(circle at 70% 80%, rgba(49,215,255,.14), transparent 24%),
                var(--bg);
            overflow-x:hidden;
        }
        body::before {
            content:""; position:fixed; inset:0; pointer-events:none; opacity:.32;
            background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size:48px 48px; mask-image:linear-gradient(to bottom, black, transparent 78%);
        }
        .orb { position:fixed; width:360px; height:360px; border-radius:50%; filter:blur(26px); opacity:.26; pointer-events:none; animation:float 12s ease-in-out infinite; }
        .orb.one { left:-100px; top:18%; background:var(--blue); }
        .orb.two { right:-90px; top:28%; background:var(--violet); animation-delay:-4s; }
        @keyframes float { 50% { transform:translateY(-24px) translateX(18px) scale(1.06); } }
        .shell { width:min(1200px, calc(100% - 48px)); margin:0 auto; position:relative; z-index:1; }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:28px 0; }
        .brand img { width:52px; height:52px; object-fit:contain; display:block; }
        .pill { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid var(--line); border-radius:999px; color:var(--muted); background:rgba(255,255,255,.035); backdrop-filter:blur(12px); }
        .hero { min-height:calc(100vh - 98px); display:grid; grid-template-columns:minmax(0,1fr) 460px; gap:42px; align-items:center; padding:34px 0 68px; }
        .eyebrow { display:inline-flex; align-items:center; gap:8px; color:#9ec8ff; text-transform:uppercase; letter-spacing:.14em; font-size:.74rem; font-weight:800; }
        h1 { margin:18px 0; font-size:clamp(2.4rem,5vw,4.8rem); line-height:.94; letter-spacing:-.065em; }
        .gradient { background:linear-gradient(120deg,#fff 0%,#b8d7ff 38%,#9f8cff 72%,#7be8ff 100%); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .hero-copy { max-width:640px; color:var(--muted); font-size:1.08rem; line-height:1.75; }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:30px; }
        .btn { display:inline-flex; align-items:center; gap:9px; min-height:48px; padding:0 20px; border-radius:16px; color:#fff; text-decoration:none; font-weight:750; }
        .btn.primary { background:linear-gradient(135deg,var(--blue),var(--violet)); box-shadow:0 18px 45px rgba(10,122,255,.28); }
        .btn.ghost { border:1px solid var(--line); background:rgba(255,255,255,.045); }
        .hero-panel { position:relative; min-height:480px; border:1px solid var(--line); border-radius:30px; padding:22px; background:linear-gradient(180deg,rgba(255,255,255,.09),rgba(255,255,255,.035)); backdrop-filter:blur(18px); box-shadow:0 28px 90px rgba(0,0,0,.42); overflow:hidden; }
        .hero-panel::before { content:""; position:absolute; inset:-35%; background:conic-gradient(from 180deg,transparent,rgba(49,215,255,.22),transparent,rgba(139,92,246,.25),transparent); animation:spin 12s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .dashboard { position:relative; z-index:1; display:grid; gap:16px; }
        .metric-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .metric,.chart,.placement { border:1px solid var(--line); background:rgba(5,7,13,.72); border-radius:20px; }
        .metric { padding:16px; } .metric small { display:block; color:var(--muted); margin-bottom:8px; } .metric strong { font-size:1.28rem; }
        .chart { padding:18px; height:180px; position:relative; overflow:hidden; }
        .chart-bars { position:absolute; inset:58px 18px 18px; display:flex; align-items:flex-end; gap:10px; }
        .bar { flex:1; border-radius:999px 999px 8px 8px; background:linear-gradient(to top,var(--blue),var(--cyan)); animation:pulse 3.2s ease-in-out infinite; }
        .bar:nth-child(1){height:38%}.bar:nth-child(2){height:74%;animation-delay:-.5s}.bar:nth-child(3){height:48%;animation-delay:-1s}.bar:nth-child(4){height:88%;animation-delay:-1.5s}.bar:nth-child(5){height:61%;animation-delay:-2s}
        @keyframes pulse { 50% { filter:brightness(1.28); transform:translateY(-4px); } }
        .placement { padding:16px; display:flex; align-items:center; justify-content:space-between; gap:14px; }
        .placement span { color:var(--muted); } .status { color:var(--green); font-weight:700; }
        .strip { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; padding-bottom:78px; }
        .card { border:1px solid var(--line); background:rgba(255,255,255,.055); border-radius:24px; padding:22px; }
        .card i { color:#9ec8ff; } .card h2 { margin:16px 0 10px; font-size:1.05rem; } .card p { margin:0; color:var(--muted); line-height:1.6; font-size:.92rem; }
        @media (max-width:920px){.hero{grid-template-columns:1fr}.hero-panel{min-height:420px}.strip{grid-template-columns:1fr 1fr}}
        @media (max-width:640px){.shell{width:min(100% - 28px,1200px)}.topbar{gap:14px;align-items:flex-start;flex-direction:column}.metric-row,.strip{grid-template-columns:1fr}.hero{gap:28px;padding-top:10px}.hero-panel{min-height:430px}}
    </style>
</head>
<body>
    <div class="orb one"></div><div class="orb two"></div>
    <div class="shell">
        <header class="topbar">
            <div class="brand"><img src="/assets/img/ads/logo-icone.png" alt="PipoCine Ads"></div>
            <span class="pill"><i data-lucide="user-round"></i><?= $ownerName ?></span>
        </header>
        <main class="hero">
            <section>
                <span class="eyebrow"><i data-lucide="sparkles"></i> Plataforma para anunciantes</span>
                <h1>Publique campanhas que <span class="gradient">vivem dentro da atenção.</span></h1>
                <p class="hero-copy">PipoCine Ads conecta empresas, criadores e marcas ao público certo com campanhas integradas à experiência de streaming, segmentação inteligente e analytics completos para entender cada impressão, clique e conversão.</p>
                <div class="actions">
                    <a class="btn primary" href="/ads/presentation"><i data-lucide="rocket"></i> Começar com Ads</a>
                    <a class="btn ghost" href="#recursos"><i data-lucide="bar-chart-3"></i> Ver recursos</a>
                </div>
            </section>
            <aside class="hero-panel" aria-label="Prévia do painel de anúncios">
                <div class="dashboard">
                    <div class="metric-row">
                        <div class="metric"><small>Impressões</small><strong>1.2M</strong></div>
                        <div class="metric"><small>CTR</small><strong>4.8%</strong></div>
                        <div class="metric"><small>Conversões</small><strong>18.4K</strong></div>
                    </div>
                    <div class="chart"><strong>Desempenho ao vivo</strong><div class="chart-bars"><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span></div></div>
                    <div class="placement"><div><strong>Campanha Cinema Local</strong><br><span>Hero + Trilhos patrocinados</span></div><strong class="status">Ativa</strong></div>
                    <div class="placement"><div><strong>Público-alvo</strong><br><span>Ação, séries, 18–34</span></div><strong>Brasil</strong></div>
                </div>
            </aside>
        </main>
        <section class="strip" id="recursos" aria-label="Recursos do PipoCine Ads">
            <article class="card"><i data-lucide="target"></i><h2>Segmentação precisa</h2><p>Alcance públicos por interesse, comportamento de navegação, categoria e momento de consumo.</p></article>
            <article class="card"><i data-lucide="layout-dashboard"></i><h2>Formatos nativos</h2><p>Hero patrocinado, cards em trilhos, banners contextuais e campanhas desenhadas para não quebrar a experiência.</p></article>
            <article class="card"><i data-lucide="line-chart"></i><h2>Analytics completos</h2><p>Impressões, alcance, CTR, conversão, retenção e leitura clara do que realmente move resultado.</p></article>
            <article class="card"><i data-lucide="shield-check"></i><h2>Controle e segurança</h2><p>Revisão de campanhas, limites de frequência e governança para proteger audiência, marca e plataforma.</p></article>
        </section>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
