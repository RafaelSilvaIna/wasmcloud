<?php
declare(strict_types=1);

require_once __DIR__ . '/../../components/ads/AdsHeader.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title>Como funciona — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        :root {
            --bg: #05070d;
            --surface: rgba(255,255,255,.055);
            --surface-strong: rgba(255,255,255,.085);
            --line: rgba(255,255,255,.10);
            --line-strong: rgba(255,255,255,.16);
            --text: #f7f9ff;
            --muted: #9ca8bf;
            --soft: #dce7ff;
            --blue: #0a7aff;
            --violet: #7c5cff;
            --cyan: #39d8ff;
            --green: #34d399;
            --warning: #ffb454;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background:
                radial-gradient(circle at 10% 8%, rgba(10,122,255,.22), transparent 28%),
                radial-gradient(circle at 88% 12%, rgba(124,92,255,.22), transparent 24%),
                radial-gradient(circle at 72% 82%, rgba(57,216,255,.13), transparent 24%),
                var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .28;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 52px 52px;
            mask-image: linear-gradient(to bottom, #000, transparent 82%);
        }

        .orb {
            position: fixed;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            filter: blur(34px);
            opacity: .18;
            pointer-events: none;
            animation: drift 14s ease-in-out infinite;
        }

        .orb.one { left: -120px; top: 20%; background: var(--blue); }
        .orb.two { right: -110px; top: 28%; background: var(--violet); animation-delay: -5s; }

        @keyframes drift {
            50% { transform: translate3d(18px, -28px, 0) scale(1.08); }
        }

        .shell {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100% - 48px));
            margin: 0 auto;
        }

        .ads-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 28px 0;
        }

        .ads-brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            display: block;
        }

        .ads-topbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 16px;
            text-decoration: none;
            color: #fff;
            font-weight: 760;
            transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .btn:hover { transform: translateY(-2px); }

        .btn.primary {
            background: linear-gradient(135deg, var(--blue), var(--violet));
            box-shadow: 0 18px 44px rgba(10,122,255,.24);
        }

        .btn.ghost {
            border: 1px solid var(--line);
            background: rgba(255,255,255,.035);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 420px;
            gap: 34px;
            align-items: center;
            padding: 32px 0 34px;
        }

        .eyebrow {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-size: .74rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: #a8cbff;
        }

        h1 {
            max-width: 760px;
            margin: 18px 0 18px;
            font-size: clamp(2.25rem, 4.8vw, 4.3rem);
            line-height: .98;
            letter-spacing: -.06em;
        }

        .gradient {
            background: linear-gradient(120deg, #fff 0%, #bed9ff 40%, #b4a0ff 74%, #79ebff 100%);
            color: transparent;
            -webkit-background-clip: text;
            background-clip: text;
        }

        .lead {
            max-width: 690px;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .hero-points {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .hero-point {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 9px 12px;
            color: var(--soft);
            background: rgba(255,255,255,.035);
            font-size: .86rem;
        }

        .preview {
            position: relative;
            overflow: hidden;
            min-height: 420px;
            border: 1px solid var(--line);
            border-radius: 30px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.03)),
                rgba(255,255,255,.025);
            box-shadow: 0 28px 90px rgba(0,0,0,.38);
        }

        .preview::before {
            content: "";
            position: absolute;
            inset: -35%;
            background: conic-gradient(from 180deg, transparent, rgba(57,216,255,.22), transparent, rgba(124,92,255,.24), transparent);
            animation: spin 13s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .preview-inner {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .screen {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: rgba(5,7,13,.74);
        }

        .screen-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            color: var(--muted);
            font-size: .82rem;
        }

        .ad-frame {
            position: relative;
            min-height: 220px;
            display: flex;
            align-items: flex-end;
            padding: 18px;
            background:
                linear-gradient(to top, rgba(0,0,0,.90), transparent 68%),
                radial-gradient(circle at 74% 18%, rgba(57,216,255,.35), transparent 32%),
                linear-gradient(135deg, #17233d, #090d18 68%);
        }

        .ad-chip {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: .72rem;
            color: #dbeaff;
            background: rgba(255,255,255,.12);
        }

        .ad-copy strong { display: block; margin-bottom: 5px; }
        .ad-copy span { color: var(--muted); font-size: .88rem; }

        .ad-button {
            margin-top: 12px;
            display: inline-flex;
            border-radius: 999px;
            padding: 10px 13px;
            background: #fff;
            color: #07101f;
            font-weight: 800;
            font-size: .84rem;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .preview-stat {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            background: rgba(5,7,13,.68);
        }

        .preview-stat small {
            display: block;
            color: var(--muted);
            margin-bottom: 7px;
        }

        .preview-stat strong { font-size: 1.2rem; }

        .section {
            padding-top: 34px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-end;
            margin-bottom: 18px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(1.35rem, 2.4vw, 2rem);
            letter-spacing: -.03em;
        }

        .section-head p {
            max-width: 470px;
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .format-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .format-card,
        .step,
        .faq-item {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 24px;
        }

        .format-card {
            padding: 22px;
            transition: transform .2s ease, border-color .2s ease, background .2s ease;
        }

        .format-card:hover {
            transform: translateY(-4px);
            border-color: var(--line-strong);
            background: var(--surface-strong);
        }

        .format-card i { color: #abd0ff; }
        .format-card h3 { margin: 16px 0 9px; }
        .format-card p { margin: 0; color: var(--muted); line-height: 1.65; }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .step {
            position: relative;
            padding: 20px;
        }

        .step-number {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(10,122,255,.24), rgba(124,92,255,.24));
            color: #cfe2ff;
            font-weight: 850;
        }

        .step h3 { margin: 18px 0 9px; }
        .step p { margin: 0; color: var(--muted); line-height: 1.6; }

        .faq-list {
            display: grid;
            gap: 12px;
        }

        .faq-item {
            overflow: hidden;
        }

        .faq-button {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            color: var(--text);
            background: transparent;
            border: 0;
            padding: 20px 22px;
            text-align: left;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 760;
        }

        .faq-answer {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows .24s ease;
        }

        .faq-answer > div {
            overflow: hidden;
            color: var(--muted);
            line-height: 1.7;
            padding: 0 22px;
        }

        .faq-item.open .faq-answer {
            grid-template-rows: 1fr;
        }

        .faq-item.open .faq-answer > div {
            padding-bottom: 20px;
        }

        .faq-item svg {
            transition: transform .2s ease;
        }

        .faq-item.open svg {
            transform: rotate(180deg);
        }

        .cta-panel {
            margin: 34px 0 64px;
            display: flex;
            justify-content: space-between;
            gap: 22px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 30px;
            padding: 26px;
            background:
                linear-gradient(135deg, rgba(10,122,255,.17), rgba(124,92,255,.12)),
                rgba(255,255,255,.04);
        }

        .cta-panel h2 {
            margin: 0 0 8px;
            letter-spacing: -.03em;
        }

        .cta-panel p {
            margin: 0;
            color: var(--muted);
        }

        @media (max-width: 920px) {
            .hero { grid-template-columns: 1fr; }
            .preview { min-height: auto; }
            .steps { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 760px) {
            .shell { width: min(100% - 28px, 1180px); }
            .ads-topbar { padding: 18px 0; }
            .hero { padding-top: 18px; }
            .section-head,
            .cta-panel {
                flex-direction: column;
                align-items: flex-start;
            }
            .format-grid,
            .steps,
            .preview-stats {
                grid-template-columns: 1fr;
            }
            .btn { width: 100%; }
            .ads-topbar-actions .btn { width: auto; }
        }
    </style>
</head>
<body>
    <div class="orb one"></div>
    <div class="orb two"></div>
    <div class="shell">
        <?php AdsHeader::render('<a class="btn ghost" href="/ads/login">Entrar</a>'); ?>

        <main>
            <section class="hero">
                <div>
                    <span class="eyebrow"><i data-lucide="sparkles"></i> Antes de criar sua conta</span>
                    <h1>Seus anúncios alcançam <span class="gradient">todos os usuários do plano gratuito.</span></h1>
                    <p class="lead">
                        Campanhas aprovadas podem ser exibidas globalmente dentro do Pipocine para a audiência gratuita,
                        em formatos nativos, com controle criativo e métricas claras para cada decisão.
                    </p>
                    <div class="hero-points">
                        <span class="hero-point"><i data-lucide="globe-2"></i> Alcance global no plano gratuito</span>
                        <span class="hero-point"><i data-lucide="badge-check"></i> Revisão antes da veiculação</span>
                        <span class="hero-point"><i data-lucide="bar-chart-3"></i> Analytics por campanha</span>
                    </div>
                </div>

                <aside class="preview" aria-label="Prévia de anúncio">
                    <div class="preview-inner">
                        <div class="screen">
                            <div class="screen-top">
                                <span>Prévia do criativo</span>
                                <span>Vídeo • 20s</span>
                            </div>
                            <div class="ad-frame">
                                <span class="ad-chip">Anúncio</span>
                                <div class="ad-copy">
                                    <strong>Sua marca em destaque</strong>
                                    <span>Mensagem curta, visual forte e ação clara.</span>
                                    <div class="ad-button">Saiba mais</div>
                                </div>
                            </div>
                        </div>
                        <div class="preview-stats">
                            <div class="preview-stat"><small>Alcance</small><strong>Global</strong></div>
                            <div class="preview-stat"><small>Formato</small><strong>Imagem / vídeo</strong></div>
                            <div class="preview-stat"><small>Pulo</small><strong>Opcional</strong></div>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="section" aria-label="Formatos disponíveis">
                <div class="section-head">
                    <h2>O que você pode publicar</h2>
                    <p>As regras são simples o bastante para começar rápido e fortes o bastante para manter a experiência profissional.</p>
                </div>
                <div class="format-grid">
                    <article class="format-card">
                        <i data-lucide="image"></i>
                        <h3>Imagem ou vídeo</h3>
                        <p>Envie criativos estáticos ou vídeos objetivos de até 20 segundos.</p>
                    </article>
                    <article class="format-card">
                        <i data-lucide="mouse-pointer-click"></i>
                        <h3>Botão “Saiba mais”</h3>
                        <p>Adicione um link de redirecionamento para levar o interesse até sua página.</p>
                    </article>
                    <article class="format-card">
                        <i data-lucide="skip-forward"></i>
                        <h3>Controle de pulo</h3>
                        <p>Defina se o anúncio pode ser pulado ou se a exibição será obrigatória.</p>
                    </article>
                </div>
            </section>

            <section class="section" aria-label="Fluxo de publicação">
                <div class="section-head">
                    <h2>Do cadastro à campanha</h2>
                    <p>Um fluxo curto, com cada etapa deixando a próxima mais clara.</p>
                </div>
                <div class="steps">
                    <article class="step">
                        <span class="step-number">1</span>
                        <h3>Crie a conta</h3>
                        <p>Informe sua marca, email profissional e, se quiser, CNPJ.</p>
                    </article>
                    <article class="step">
                        <span class="step-number">2</span>
                        <h3>Vincule ao Pipocine</h3>
                        <p>Ganhe acesso mais simples e seguro com sua conta principal.</p>
                    </article>
                    <article class="step">
                        <span class="step-number">3</span>
                        <h3>Envie o criativo</h3>
                        <p>Escolha imagem ou vídeo, link e regra de pulo.</p>
                    </article>
                    <article class="step">
                        <span class="step-number">4</span>
                        <h3>Acompanhe</h3>
                        <p>Leia impressões, cliques, CTR, alcance e conversões.</p>
                    </article>
                </div>
            </section>

            <section class="section" aria-label="Dúvidas frequentes">
                <div class="section-head">
                    <h2>Dúvidas comuns</h2>
                    <p>As perguntas que quase todo anunciante precisa resolver antes de dar o primeiro passo.</p>
                </div>
                <div class="faq-list">
                    <article class="faq-item open">
                        <button class="faq-button" type="button">
                            <span>Onde meu anúncio aparece?</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="faq-answer"><div>Em inventário elegível do Pipocine para usuários do plano gratuito, com revisão de criativo e controle de frequência.</div></div>
                    </article>
                    <article class="faq-item">
                        <button class="faq-button" type="button">
                            <span>O que consigo medir?</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="faq-answer"><div>Impressões, cliques, CTR, alcance, conversões e desempenho por campanha.</div></div>
                    </article>
                    <article class="faq-item">
                        <button class="faq-button" type="button">
                            <span>Preciso ter CNPJ?</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="faq-answer"><div>Não. O CNPJ é opcional, mas ajuda a aumentar a credibilidade da conta comercial e facilita validações futuras.</div></div>
                    </article>
                </div>
            </section>

            <section class="cta-panel">
                <div>
                    <h2>Pronto para anunciar com contexto?</h2>
                    <p>Crie sua conta comercial e comece a preparar sua primeira campanha.</p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a class="btn primary" href="/ads/register">Criar conta comercial</a>
                    <a class="btn ghost" href="/ads/login">Já tenho conta</a>
                </div>
            </section>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.querySelectorAll('.faq-button').forEach((button) => {
                button.addEventListener('click', () => {
                    button.closest('.faq-item')?.classList.toggle('open');
                });
            });
        });
    </script>
</body>
</html>
