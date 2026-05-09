<?php
/**
 * PipoCine Developer Portal
 * Pagina comercial da API para desenvolvedores.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PipoCine API - planos para desenvolvedores criarem experiencias de streaming.">
    <title>PipoCine API - Planos para Desenvolvedores</title>

    <style>
        :root {
            --bg: #050507;
            --surface: #0f1014;
            --surface-2: #15161b;
            --line: rgba(255, 255, 255, 0.1);
            --line-soft: rgba(255, 255, 255, 0.065);
            --text: #ffffff;
            --muted: #a1a1aa;
            --soft: #71717a;
            --accent: #e50914;
            --accent-2: #ff2631;
            --success: #22c55e;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        a {
            color: inherit;
        }

        .container {
            width: min(1120px, calc(100% - 48px));
            margin: 0 auto;
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(5, 5, 7, 0.84);
            border-bottom: 1px solid var(--line-soft);
            backdrop-filter: blur(18px);
        }

        .nav-inner {
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand-mark {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--accent);
            font-weight: 900;
        }

        .brand span:last-child {
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .nav-cta {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            color: #fff !important;
            background: var(--accent);
            border-radius: 8px;
        }

        .hero {
            padding: 92px 0 64px;
            border-bottom: 1px solid var(--line-soft);
            background:
                linear-gradient(180deg, rgba(229, 9, 20, 0.08), transparent 42%),
                var(--bg);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.65fr);
            gap: 56px;
            align-items: end;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid rgba(229, 9, 20, 0.24);
            border-radius: 999px;
            color: #ffb4b8;
            background: rgba(229, 9, 20, 0.08);
            font-size: 0.78rem;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .hero h1 {
            max-width: 720px;
            font-size: 4.1rem;
            line-height: 1.02;
            font-weight: 850;
            letter-spacing: 0;
            margin-bottom: 20px;
        }

        .hero-copy {
            max-width: 650px;
            color: var(--muted);
            font-size: 1.06rem;
            margin-bottom: 30px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.93rem;
            font-weight: 750;
            border: 1px solid transparent;
        }

        .btn-primary {
            color: #fff;
            background: var(--accent);
        }

        .btn-primary:hover {
            background: var(--accent-2);
        }

        .btn-secondary {
            color: #fff;
            background: transparent;
            border-color: var(--line);
        }

        .hero-summary {
            background: rgba(15, 16, 20, 0.86);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line-soft);
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-row span:first-child {
            color: var(--muted);
            font-size: 0.86rem;
        }

        .summary-row strong {
            color: #fff;
            font-size: 0.9rem;
            text-align: right;
        }

        .section {
            padding: 72px 0;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 28px;
            margin-bottom: 26px;
        }

        .section-heading h2 {
            font-size: 2.25rem;
            line-height: 1.12;
            letter-spacing: 0;
        }

        .section-heading p {
            max-width: 420px;
            color: var(--muted);
            font-size: 0.94rem;
        }

        .plan-grid {
            display: grid;
            grid-template-columns: 0.85fr 1.15fr;
            gap: 18px;
            align-items: stretch;
        }

        .plan-card {
            display: flex;
            flex-direction: column;
            min-height: 100%;
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .plan-card.paid {
            background: linear-gradient(180deg, rgba(229, 9, 20, 0.12), rgba(15, 16, 20, 0.98) 36%);
            border-color: rgba(229, 9, 20, 0.36);
        }

        .plan-label {
            color: var(--soft);
            font-size: 0.75rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
        }

        .plan-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .plan-card h3 {
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .plan-badge {
            color: #fff;
            background: var(--accent);
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.68rem;
            font-weight: 850;
        }

        .plan-description {
            color: var(--muted);
            font-size: 0.9rem;
            min-height: 44px;
            margin-bottom: 20px;
        }

        .price {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 6px;
        }

        .price strong {
            font-size: 2.5rem;
            line-height: 1;
            letter-spacing: 0;
        }

        .price span {
            color: var(--muted);
            font-weight: 700;
        }

        .price-note {
            color: var(--soft);
            font-size: 0.82rem;
            margin-bottom: 22px;
        }

        .plan-card .btn {
            width: 100%;
            margin-top: auto;
            margin-bottom: 22px;
        }

        .plan-list {
            list-style: none;
            display: grid;
            gap: 11px;
        }

        .plan-list li {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 10px;
            color: #e4e4e7;
            font-size: 0.9rem;
        }

        .check {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: var(--success);
            background: rgba(34, 197, 94, 0.1);
            font-size: 0.74rem;
            font-weight: 900;
        }

        .comparison-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
        }

        .comparison {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        .comparison th,
        .comparison td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--line-soft);
            text-align: left;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .comparison tr:last-child th,
        .comparison tr:last-child td {
            border-bottom: 0;
        }

        .comparison thead th {
            color: #fff;
            background: #111217;
            font-size: 0.82rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .comparison tbody th {
            color: #fff;
            font-weight: 700;
            width: 36%;
        }

        .comparison td {
            color: var(--muted);
        }

        .comparison td strong {
            color: #fff;
        }

        .paid-col {
            background: rgba(229, 9, 20, 0.045);
        }

        .included {
            color: var(--success);
            font-weight: 850;
        }

        .not-included {
            color: #696973;
        }

        .feature-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .feature {
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .feature h3 {
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .feature p {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .footer {
            padding: 34px 0;
            border-top: 1px solid var(--line-soft);
            color: var(--soft);
            font-size: 0.82rem;
        }

        @media (max-width: 860px) {
            .container {
                width: min(100% - 32px, 1120px);
            }

            .nav-links a:not(.nav-cta) {
                display: none;
            }

            .hero {
                padding-top: 64px;
            }

            .hero-grid,
            .plan-grid,
            .feature-strip {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2.85rem;
            }

            .section-heading h2 {
                font-size: 1.9rem;
            }

            .section-heading {
                align-items: start;
                flex-direction: column;
            }
        }

        @media (max-width: 520px) {
            .hero h1 {
                font-size: 2.25rem;
            }

            .section-heading h2 {
                font-size: 1.65rem;
            }

            .hero-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .plan-card {
                padding: 20px;
            }

            .price strong {
                font-size: 2.1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="container nav-inner">
            <a href="/developer" class="brand" aria-label="PipoCine API">
                <span class="brand-mark">P</span>
                <strong>PipoCine</strong>
                <span>API</span>
            </a>
            <div class="nav-links">
                <a href="#planos">Planos</a>
                <a href="#comparacao">Comparacao</a>
                <a href="#recursos">Recursos</a>
                <a class="nav-cta" href="#planos">Ver planos</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <span class="eyebrow">PipoCine para desenvolvedores</span>
                    <h1>Crie produtos de streaming com uma API simples e previsivel.</h1>
                    <p class="hero-copy">
                        Escolha entre um plano gratuito para prototipos e um plano pago para apps em producao. Limites claros, recursos essenciais e sem informacao escondida.
                    </p>
                    <div class="hero-actions">
                        <a href="#planos" class="btn btn-primary">Comparar planos</a>
                        <a href="#comparacao" class="btn btn-secondary">Ver tabela completa</a>
                    </div>
                </div>

                <aside class="hero-summary" aria-label="Resumo dos planos">
                    <div class="summary-row">
                        <span>Plano gratuito</span>
                        <strong>Teste e prototipos</strong>
                    </div>
                    <div class="summary-row">
                        <span>Plano pago</span>
                        <strong>R$ 20,99 / mes</strong>
                    </div>
                    <div class="summary-row">
                        <span>Upgrade</span>
                        <strong>A qualquer momento</strong>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section" id="planos">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <h2>Planos separados por momento do projeto</h2>
                    </div>
                    <p>Use o gratuito para validar a integracao. Migre para o pago quando precisar de volume, dominios e suporte.</p>
                </div>

                <div class="plan-grid">
                    <article class="plan-card">
                        <span class="plan-label">Gratuito</span>
                        <div class="plan-title-row">
                            <h3>Sandbox</h3>
                        </div>
                        <p class="plan-description">Para explorar a API, testar endpoints e montar uma prova de conceito.</p>
                        <div class="price">
                            <strong>R$ 0</strong>
                            <span>/ mes</span>
                        </div>
                        <p class="price-note">Sem cartao. Limites reduzidos.</p>
                        <a href="#" class="btn btn-secondary" onclick="alert('Em breve!'); return false;">Comecar gratis</a>
                        <ul class="plan-list">
                            <li><span class="check">✓</span><span>1 projeto</span></li>
                            <li><span class="check">✓</span><span>1 chave de API</span></li>
                            <li><span class="check">✓</span><span>5 mil requisicoes mensais</span></li>
                            <li><span class="check">✓</span><span>Ambiente de testes</span></li>
                        </ul>
                    </article>

                    <article class="plan-card paid">
                        <span class="plan-label">Pago</span>
                        <div class="plan-title-row">
                            <h3>Developer Pro</h3>
                            <span class="plan-badge">Recomendado</span>
                        </div>
                        <p class="plan-description">Para produtos em producao que precisam de escala, monitoramento e controle operacional.</p>
                        <div class="price">
                            <strong>R$ 20,99</strong>
                            <span>/ mes</span>
                        </div>
                        <p class="price-note">Cancele quando quiser. Sem taxa de instalacao.</p>
                        <a href="#" class="btn btn-primary" onclick="alert('Em breve!'); return false;">Assinar Pro</a>
                        <ul class="plan-list">
                            <li><span class="check">✓</span><span>2 projetos independentes</span></li>
                            <li><span class="check">✓</span><span>3 chaves de API por projeto</span></li>
                            <li><span class="check">✓</span><span>200 mil requisicoes mensais</span></li>
                            <li><span class="check">✓</span><span>350 GB de trafego mensal</span></li>
                            <li><span class="check">✓</span><span>Analytics, logs e suporte prioritario</span></li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="comparacao">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <h2>Compare os recursos</h2>
                    </div>
                    <p>Uma tabela objetiva para decidir rapido, sem lista longa de itens repetidos.</p>
                </div>

                <div class="comparison-wrap">
                    <table class="comparison">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Sandbox gratuito</th>
                                <th class="paid-col">Developer Pro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Projetos</th>
                                <td>1 projeto</td>
                                <td class="paid-col"><strong>2 projetos</strong></td>
                            </tr>
                            <tr>
                                <th>Chaves de API</th>
                                <td>1 chave</td>
                                <td class="paid-col"><strong>3 por projeto</strong></td>
                            </tr>
                            <tr>
                                <th>Requisicoes mensais</th>
                                <td>5 mil</td>
                                <td class="paid-col"><strong>200 mil</strong></td>
                            </tr>
                            <tr>
                                <th>Trafego mensal</th>
                                <td>10 GB</td>
                                <td class="paid-col"><strong>350 GB</strong></td>
                            </tr>
                            <tr>
                                <th>Dominios autorizados</th>
                                <td class="not-included">Nao incluso</td>
                                <td class="paid-col"><strong>2 por projeto</strong></td>
                            </tr>
                            <tr>
                                <th>Dashboard e analytics</th>
                                <td>Basico</td>
                                <td class="paid-col"><span class="included">Completo</span></td>
                            </tr>
                            <tr>
                                <th>Logs detalhados</th>
                                <td class="not-included">Nao incluso</td>
                                <td class="paid-col"><span class="included">Incluso</span></td>
                            </tr>
                            <tr>
                                <th>Suporte</th>
                                <td>Comunidade</td>
                                <td class="paid-col"><strong>Prioritario</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="section" id="recursos">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <h2>O essencial para operar bem</h2>
                    </div>
                    <p>Recursos mantidos intencionalmente simples para reduzir configuracao e acelerar o desenvolvimento.</p>
                </div>

                <div class="feature-strip">
                    <article class="feature">
                        <h3>Autenticacao integrada</h3>
                        <p>Controle de acesso para apps de streaming sem montar uma camada de autenticacao do zero.</p>
                    </article>
                    <article class="feature">
                        <h3>Controle de uso</h3>
                        <p>Limites claros por projeto, logs e visibilidade sobre consumo da API.</p>
                    </article>
                    <article class="feature">
                        <h3>Protecao de producao</h3>
                        <p>Dominios autorizados e protecao anti-abuso para manter seu produto estavel.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            PipoCine API. Planos para desenvolvedores.
        </div>
    </footer>
</body>
</html>
