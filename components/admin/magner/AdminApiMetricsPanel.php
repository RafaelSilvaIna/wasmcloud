<?php
declare(strict_types=1);

final class AdminApiMetricsPanel
{
    public static function render(): void
    {
        ?>
        <style>
            /* ----------------------------------------------------------------
               Layout principal
            ---------------------------------------------------------------- */
            .aapi-page {
                display: grid;
                gap: 14px;
            }

            /* ----------------------------------------------------------------
               Toolbar
            ---------------------------------------------------------------- */
            .aapi-toolbar {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 10px;
                background: #0f131a;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 16px;
                flex-wrap: wrap;
            }

            .aapi-toolbar-title {
                margin: 0;
                color: #fff;
                font-size: 1.12rem;
            }

            .aapi-toolbar-sub {
                margin: 4px 0 0;
                color: #94a3b8;
                font-size: .83rem;
            }

            .aapi-filter {
                position: relative;
            }

            .aapi-filter-btn {
                min-height: 40px;
                border: 1px solid rgba(148, 163, 184, .20);
                border-radius: 8px;
                background: #111827;
                color: #fff;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 12px;
                cursor: pointer;
                font-weight: 780;
                font-size: .9rem;
            }

            .aapi-filter-menu {
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                width: 190px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #0f131a;
                box-shadow: 0 18px 60px rgba(0,0,0,.45);
                padding: 6px;
                display: none;
                z-index: 80;
            }

            .aapi-filter-menu.active { display: grid; }

            .aapi-filter-menu button {
                min-height: 36px;
                border: 0;
                border-radius: 6px;
                background: transparent;
                color: #cbd5e1;
                text-align: left;
                padding: 0 10px;
                cursor: pointer;
                font-weight: 720;
                font-size: .88rem;
            }

            .aapi-filter-menu button:hover,
            .aapi-filter-menu button.active {
                background: rgba(229, 9, 20, .14);
                color: #fff;
            }

            /* ----------------------------------------------------------------
               Banda realtime
            ---------------------------------------------------------------- */
            .aapi-realtime {
                border: 1px solid rgba(229, 9, 20, .22);
                border-radius: 8px;
                background: rgba(229, 9, 20, .07);
                padding: 13px 16px;
                color: #fecaca;
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 760;
                font-size: .88rem;
                flex-wrap: wrap;
            }

            /* ----------------------------------------------------------------
               Grade de cartoes de metricas
            ---------------------------------------------------------------- */
            .aapi-kpi-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 10px;
            }

            .aapi-kpi-card {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 10px;
                background: #0f131a;
                padding: 14px;
                display: grid;
                gap: 0;
            }

            .aapi-kpi-label {
                display: flex;
                align-items: center;
                gap: 7px;
                color: #94a3b8;
                font-size: .72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .aapi-kpi-label svg { width: 14px; height: 14px; flex-shrink: 0; }

            .aapi-kpi-value {
                display: block;
                margin-top: 10px;
                color: #fff;
                font-size: 1.45rem;
                font-weight: 800;
            }

            .aapi-kpi-sub {
                display: block;
                margin-top: 3px;
                color: #64748b;
                font-size: .73rem;
            }

            /* ----------------------------------------------------------------
               Cards de secoes
            ---------------------------------------------------------------- */
            .aapi-section-card {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 10px;
                background: #0f131a;
                padding: 16px;
            }

            .aapi-section-card h3 {
                margin: 0 0 14px;
                color: #fff;
                font-size: .97rem;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* ----------------------------------------------------------------
               Graficos SVG
            ---------------------------------------------------------------- */
            .aapi-chart-row {
                display: grid;
                grid-template-columns: 1.4fr 1fr;
                gap: 12px;
            }

            .aapi-svg {
                width: 100%;
                height: 220px;
                display: block;
                overflow: hidden;
            }

            /* garante que o fundo do card do grafico seja identico ao da imagem */
            .aapi-chart-row .aapi-section-card {
                background: #0c1117;
                padding: 18px 16px 14px;
            }

            .aapi-chart-row .aapi-section-card h3 {
                color: #cbd5e1;
                font-size: .9rem;
                font-weight: 600;
                margin-bottom: 10px;
            }

            /* ----------------------------------------------------------------
               Tabela de endpoints
            ---------------------------------------------------------------- */
            .aapi-table {
                width: 100%;
                border-collapse: collapse;
                font-size: .83rem;
            }

            .aapi-table thead th {
                color: #64748b;
                font-weight: 800;
                text-transform: uppercase;
                font-size: .7rem;
                letter-spacing: .04em;
                padding: 0 8px 10px;
                text-align: left;
                border-bottom: 1px solid rgba(148, 163, 184, .12);
            }

            .aapi-table tbody tr {
                border-bottom: 1px solid rgba(148, 163, 184, .08);
            }

            .aapi-table tbody tr:last-child { border-bottom: 0; }

            .aapi-table tbody td {
                padding: 8px;
                color: #e2e8f0;
                vertical-align: middle;
            }

            .aapi-table tbody td:not(:first-child) {
                color: #94a3b8;
                font-variant-numeric: tabular-nums;
            }

            .aapi-method-badge {
                display: inline-block;
                padding: 1px 7px;
                border-radius: 5px;
                font-size: .7rem;
                font-weight: 800;
            }

            .aapi-method-GET    { background: rgba(34,197,94,.15);  color: #4ade80; }
            .aapi-method-POST   { background: rgba(59,130,246,.15); color: #60a5fa; }
            .aapi-method-PUT,
            .aapi-method-PATCH  { background: rgba(234,179,8,.15);  color: #facc15; }
            .aapi-method-DELETE { background: rgba(239,68,68,.15);  color: #f87171; }

            /* ----------------------------------------------------------------
               Distribuicao de status codes
            ---------------------------------------------------------------- */
            .aapi-status-list { display: grid; gap: 8px; }

            .aapi-status-row { display: grid; grid-template-columns: 52px 1fr 56px; gap: 8px; align-items: center; }

            .aapi-status-code {
                font-weight: 800;
                font-size: .82rem;
                font-variant-numeric: tabular-nums;
            }

            .aapi-status-code.s2 { color: #4ade80; }
            .aapi-status-code.s3 { color: #60a5fa; }
            .aapi-status-code.s4 { color: #facc15; }
            .aapi-status-code.s5 { color: #f87171; }

            .aapi-bar-track {
                height: 6px;
                border-radius: 4px;
                background: rgba(148, 163, 184, .12);
                overflow: hidden;
            }

            .aapi-bar-fill {
                height: 100%;
                border-radius: 4px;
            }

            .aapi-bar-fill.s2 { background: #4ade80; }
            .aapi-bar-fill.s3 { background: #60a5fa; }
            .aapi-bar-fill.s4 { background: #facc15; }
            .aapi-bar-fill.s5 { background: #f87171; }

            /* ----------------------------------------------------------------
               Distribuicao por grupo
            ---------------------------------------------------------------- */
            .aapi-group-list { display: grid; gap: 8px; }

            .aapi-group-row { display: grid; grid-template-columns: 1fr 70px; gap: 8px; align-items: center; }

            .aapi-group-label { color: #e2e8f0; font-size: .84rem; font-weight: 700; }

            /* ----------------------------------------------------------------
               Percentis de latencia
            ---------------------------------------------------------------- */
            .aapi-pct-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }

            .aapi-pct-card {
                border: 1px solid rgba(148, 163, 184, .12);
                border-radius: 8px;
                background: #111827;
                padding: 12px;
                text-align: center;
            }

            .aapi-pct-label { color: #94a3b8; font-size: .72rem; font-weight: 800; text-transform: uppercase; }

            .aapi-pct-value { color: #fff; font-size: 1.3rem; font-weight: 800; display: block; margin-top: 6px; }

            /* ----------------------------------------------------------------
               IPs ativos
            ---------------------------------------------------------------- */
            .aapi-ip-list { display: grid; gap: 7px; }

            .aapi-ip-row {
                display: grid;
                grid-template-columns: 1fr 80px 80px;
                gap: 8px;
                padding: 7px 0;
                border-bottom: 1px solid rgba(148, 163, 184, .08);
                font-size: .83rem;
                align-items: center;
            }

            .aapi-ip-row:last-child { border-bottom: 0; }

            .aapi-ip-addr { color: #e2e8f0; font-variant-numeric: tabular-nums; }

            .aapi-ip-count { color: #94a3b8; text-align: right; }

            /* ----------------------------------------------------------------
               Erros recentes
            ---------------------------------------------------------------- */
            .aapi-err-table { width: 100%; border-collapse: collapse; font-size: .82rem; }

            .aapi-err-table thead th {
                color: #64748b;
                font-weight: 800;
                text-transform: uppercase;
                font-size: .69rem;
                letter-spacing: .04em;
                padding: 0 6px 8px;
                text-align: left;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
            }

            .aapi-err-table tbody tr { border-bottom: 1px solid rgba(148, 163, 184, .06); }
            .aapi-err-table tbody tr:last-child { border-bottom: 0; }

            .aapi-err-table tbody td {
                padding: 7px 6px;
                color: #94a3b8;
                vertical-align: middle;
            }

            .aapi-err-table tbody td:first-child { color: #e2e8f0; }

            /* ----------------------------------------------------------------
               Throughput sparkline
            ---------------------------------------------------------------- */
            .aapi-throughput-wrap { height: 72px; width: 100%; overflow: hidden; }

            /* ----------------------------------------------------------------
               Dois graficos lado a lado
            ---------------------------------------------------------------- */
            .aapi-two-charts {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            /* ----------------------------------------------------------------
               Bottom row
            ---------------------------------------------------------------- */
            .aapi-bottom-row {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 12px;
            }

            /* ----------------------------------------------------------------
               Skeleton loader
            ---------------------------------------------------------------- */
            .aapi-loading {
                color: #64748b;
                font-size: .85rem;
                text-align: center;
                padding: 32px 0;
            }

            /* ----------------------------------------------------------------
               Responsivo
            ---------------------------------------------------------------- */
            @media (max-width: 1300px) {
                .aapi-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }

            @media (max-width: 1100px) {
                .aapi-kpi-grid   { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .aapi-chart-row  { grid-template-columns: 1fr; }
                .aapi-two-charts { grid-template-columns: 1fr; }
                .aapi-bottom-row { grid-template-columns: 1fr 1fr; }
            }

            @media (max-width: 700px) {
                .aapi-kpi-grid   { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .aapi-bottom-row { grid-template-columns: 1fr; }
                .aapi-pct-grid   { grid-template-columns: repeat(2, 1fr); }
            }
        </style>

        <section data-admin-route="api-metrics" class="admin-route-panel" hidden>
            <div class="aapi-page" id="aapi-root">

                <!-- Toolbar -->
                <div class="aapi-toolbar">
                    <div>
                        <h2 class="aapi-toolbar-title">Metricas de API</h2>
                        <p class="aapi-toolbar-sub">Monitoramento detalhado de endpoints, latencia, erros, throughput e distribuicao de chamadas.</p>
                    </div>
                    <div class="aapi-filter">
                        <button class="aapi-filter-btn" type="button" id="aapi-filter-btn">
                            <i data-lucide="calendar-range"></i>
                            <span id="aapi-filter-label">1 semana</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="aapi-filter-menu" id="aapi-filter-menu">
                            <button type="button" data-aapi-range="1d">1 dia</button>
                            <button type="button" data-aapi-range="5d">5 dias</button>
                            <button class="active" type="button" data-aapi-range="1w">1 semana</button>
                            <button type="button" data-aapi-range="1m">1 mes</button>
                            <button type="button" data-aapi-range="2m">2 meses</button>
                            <button type="button" data-aapi-range="1y">1 ano</button>
                        </div>
                    </div>
                </div>

                <!-- Realtime -->
                <div class="aapi-realtime" id="aapi-realtime">
                    <i data-lucide="radio"></i>
                    Carregando dados em tempo real...
                </div>

                <!-- KPI Cards (15 metricas) -->
                <div class="aapi-kpi-grid">
                    <!-- 1 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="zap"></i>Total de Chamadas</span>
                        <strong class="aapi-kpi-value" data-aapi="total_calls">-</strong>
                        <span class="aapi-kpi-sub">no periodo</span>
                    </article>
                    <!-- 2 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="plug-zap"></i>Chamadas API</span>
                        <strong class="aapi-kpi-value" data-aapi="api_calls">-</strong>
                        <span class="aapi-kpi-sub">endpoints /api/*</span>
                    </article>
                    <!-- 3 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="check-circle"></i>Taxa de Sucesso</span>
                        <strong class="aapi-kpi-value" data-aapi="success_rate">-</strong>
                        <span class="aapi-kpi-sub">respostas 2xx</span>
                    </article>
                    <!-- 4 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="triangle-alert"></i>Taxa de Erro</span>
                        <strong class="aapi-kpi-value" data-aapi="error_rate_pct">-</strong>
                        <span class="aapi-kpi-sub">4xx + 5xx</span>
                    </article>
                    <!-- 5 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="timer"></i>Latencia Media</span>
                        <strong class="aapi-kpi-value" data-aapi="avg_latency_ms">-</strong>
                        <span class="aapi-kpi-sub">todos os endpoints</span>
                    </article>
                    <!-- 6 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="activity"></i>Lat. Media API</span>
                        <strong class="aapi-kpi-value" data-aapi="avg_api_latency_ms">-</strong>
                        <span class="aapi-kpi-sub">somente /api/*</span>
                    </article>
                    <!-- 7 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="alarm-clock-check"></i>Latencia Max</span>
                        <strong class="aapi-kpi-value" data-aapi="max_latency_ms">-</strong>
                        <span class="aapi-kpi-sub">pior request</span>
                    </article>
                    <!-- 8 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="network"></i>Banda Total</span>
                        <strong class="aapi-kpi-value" data-aapi="total_bytes">-</strong>
                        <span class="aapi-kpi-sub">req + resp</span>
                    </article>
                    <!-- 9 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="cable"></i>Banda API</span>
                        <strong class="aapi-kpi-value" data-aapi="api_bytes">-</strong>
                        <span class="aapi-kpi-sub">somente /api/*</span>
                    </article>
                    <!-- 10 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="server-crash"></i>Erros 5xx</span>
                        <strong class="aapi-kpi-value" data-aapi="server_error_calls">-</strong>
                        <span class="aapi-kpi-sub">erros de servidor</span>
                    </article>
                    <!-- 11 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="shield-x"></i>Erros 4xx</span>
                        <strong class="aapi-kpi-value" data-aapi="client_error_calls">-</strong>
                        <span class="aapi-kpi-sub">erros de cliente</span>
                    </article>
                    <!-- 12 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="lock"></i>Nao Autorizados</span>
                        <strong class="aapi-kpi-value" data-aapi="unauthorized_calls">-</strong>
                        <span class="aapi-kpi-sub">status 401</span>
                    </article>
                    <!-- 13 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="gauge"></i>Rate Limited</span>
                        <strong class="aapi-kpi-value" data-aapi="rate_limited_calls">-</strong>
                        <span class="aapi-kpi-sub">status 429</span>
                    </article>
                    <!-- 14 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="globe"></i>IPs Unicos</span>
                        <strong class="aapi-kpi-value" data-aapi="unique_ips">-</strong>
                        <span class="aapi-kpi-sub">clientes distintos</span>
                    </article>
                    <!-- 15 -->
                    <article class="aapi-kpi-card">
                        <span class="aapi-kpi-label"><i data-lucide="route"></i>Endpoints Unicos</span>
                        <strong class="aapi-kpi-value" data-aapi="unique_endpoints">-</strong>
                        <span class="aapi-kpi-sub">rotas distintas</span>
                    </article>
                </div>

                <!-- Graficos: serie temporal + percentis -->
                <div class="aapi-chart-row">
                    <article class="aapi-section-card">
                        <h3><i data-lucide="bar-chart-3"></i>Chamadas por periodo</h3>
                        <div id="aapi-calls-chart"></div>
                    </article>
                    <article class="aapi-section-card">
                        <h3><i data-lucide="timer"></i>Latencia por periodo (ms)</h3>
                        <div id="aapi-latency-chart"></div>
                    </article>
                </div>

                <!-- Throughput em tempo real + percentis de latencia -->
                <div class="aapi-two-charts">
                    <article class="aapi-section-card">
                        <h3><i data-lucide="activity"></i>Throughput (ultimos 60 min)</h3>
                        <div id="aapi-throughput-chart"></div>
                    </article>
                    <article class="aapi-section-card">
                        <h3><i data-lucide="milestone"></i>Percentis de Latencia</h3>
                        <div class="aapi-pct-grid" id="aapi-percentiles"></div>
                    </article>
                </div>

                <!-- Endpoints + status -->
                <div class="aapi-chart-row">
                    <article class="aapi-section-card">
                        <h3><i data-lucide="list-ordered"></i>Top 15 Endpoints</h3>
                        <div id="aapi-endpoints-table"></div>
                    </article>
                    <article class="aapi-section-card">
                        <h3><i data-lucide="pie-chart"></i>Distribuicao de Status</h3>
                        <div class="aapi-status-list" id="aapi-status-dist"></div>
                    </article>
                </div>

                <!-- Bottom: grupos + IPs + erros -->
                <div class="aapi-bottom-row">
                    <article class="aapi-section-card">
                        <h3><i data-lucide="layers"></i>Grupos de Rota</h3>
                        <div class="aapi-group-list" id="aapi-group-dist"></div>
                    </article>
                    <article class="aapi-section-card">
                        <h3><i data-lucide="globe-2"></i>IPs mais ativos</h3>
                        <div class="aapi-ip-list" id="aapi-top-ips"></div>
                    </article>
                    <article class="aapi-section-card">
                        <h3><i data-lucide="bug"></i>Erros Recentes</h3>
                        <div id="aapi-recent-errors"></div>
                    </article>
                </div>

            </div>
        </section>

        <script>
        (function () {
            const root = document.getElementById('aapi-root');
            if (!root) return;

            const RANGE_LABELS = {
                '1d': '1 dia', '5d': '5 dias', '1w': '1 semana',
                '1m': '1 mes', '2m': '2 meses', '1y': '1 ano'
            };

            let range = '1w';
            let timer  = null;

            // ------------------------------------------------------------------
            // Formatadores
            // ------------------------------------------------------------------
            function num(v) {
                return new Intl.NumberFormat('pt-BR').format(Number(v || 0));
            }

            function bytes(v) {
                v = Number(v || 0);
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
                return `${v.toFixed(i ? 1 : 0)} ${units[i]}`;
            }

            function ms(v) {
                const n = Math.round(Number(v || 0));
                return n >= 1000 ? `${(n / 1000).toFixed(2)} s` : `${n} ms`;
            }

            function pct(v) {
                return `${Number(v || 0).toFixed(2)}%`;
            }

            function esc(v) {
                return String(v ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;',
                    '"': '&quot;', "'": '&#039;'
                }[c]));
            }

            function statusClass(code) {
                code = Number(code);
                if (code < 300) return 's2';
                if (code < 400) return 's3';
                if (code < 500) return 's4';
                return 's5';
            }

            // ------------------------------------------------------------------
            // Grafico de linha SVG — estilo da imagem de referencia
            // ------------------------------------------------------------------
            function lineChart(points, key, color, height = 220) {
                if (!points || !points.length) {
                    return `<p style="color:#64748b;font-size:.82rem;margin:0">Sem dados neste periodo.</p>`;
                }

                const w      = 660;
                const h      = height;
                const padL   = 56;   // largura eixo Y
                const padR   = 16;
                const padT   = 16;
                const padB   = 10;
                const chartW = w - padL - padR;
                const chartH = h - padT - padB;

                const vals  = points.map(p => Number(p[key] || 0));
                const max   = Math.max(1, ...vals);
                const step  = points.length > 1 ? chartW / (points.length - 1) : 0;

                // coordenadas de cada ponto
                const coords = vals.map((v, i) => ({
                    x: padL + i * step,
                    y: padT + chartH - (v / max) * chartH,
                }));

                // polyline da linha principal
                const linePts = coords.map(c => `${c.x.toFixed(1)},${c.y.toFixed(1)}`).join(' ');

                // path do area (fechado na base)
                const baseY   = padT + chartH;
                const areaD   = `M ${coords[0].x.toFixed(1)},${baseY} `
                    + coords.map(c => `L ${c.x.toFixed(1)},${c.y.toFixed(1)}`).join(' ')
                    + ` L ${coords[coords.length - 1].x.toFixed(1)},${baseY} Z`;

                // ID do gradiente unico por key para evitar conflito entre os dois graficos
                const gId = `aapi-g-${key.replace(/[^a-z0-9]/gi, '')}`;

                // labels do eixo Y — 5 marcas horizontais
                const yTicks = [0, 0.25, 0.5, 0.75, 1].map(frac => {
                    const val   = max * frac;
                    const yPos  = (padT + chartH - frac * chartH).toFixed(1);
                    const label = key.includes('bytes') ? bytes(val)
                                : key.includes('ms')    ? ms(val)
                                : num(val);
                    return { yPos, label, frac };
                });

                const gridLines = yTicks.map(({ yPos, label }) =>
                    `<line x1="${padL}" y1="${yPos}" x2="${w - padR}" y2="${yPos}"
                           stroke="rgba(148,163,184,.09)" stroke-dasharray="4 4"/>
                     <text x="${padL - 6}" y="${Number(yPos) + 4}"
                           fill="#475569" font-size="10" text-anchor="end"
                           font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">${label}</text>`
                ).join('');

                // pontos de dados
                const dots = coords.map(c =>
                    `<circle cx="${c.x.toFixed(1)}" cy="${c.y.toFixed(1)}" r="3.5"
                             fill="${color}" stroke="#0f131a" stroke-width="1.5"/>`
                ).join('');

                return `<svg class="aapi-svg" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="${gId}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"   stop-color="${color}" stop-opacity=".35"/>
                            <stop offset="100%" stop-color="${color}" stop-opacity=".01"/>
                        </linearGradient>
                        <clipPath id="${gId}-clip">
                            <rect x="${padL}" y="${padT}" width="${chartW}" height="${chartH}"/>
                        </clipPath>
                    </defs>

                    <!-- grade horizontal -->
                    <g clip-path="url(#${gId}-clip)">${gridLines}</g>

                    <!-- area com gradiente -->
                    <path d="${areaD}" fill="url(#${gId})" clip-path="url(#${gId}-clip)"/>

                    <!-- linha principal -->
                    <polyline points="${linePts}"
                              fill="none"
                              stroke="${color}"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              clip-path="url(#${gId}-clip)"/>

                    <!-- labels do eixo Y (fora do clip) -->
                    ${yTicks.map(({ yPos, label }) =>
                        `<text x="${padL - 6}" y="${Number(yPos) + 4}"
                               fill="#475569" font-size="10" text-anchor="end"
                               font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">${label}</text>`
                    ).join('')}

                    <!-- pontos -->
                    <g clip-path="url(#${gId}-clip)">${dots}</g>
                </svg>`;
            }

            // ------------------------------------------------------------------
            // Throughput sparkline (mini grafico de barras)
            // ------------------------------------------------------------------
            function sparkBars(points) {
                if (!points || !points.length) {
                    return `<p style="color:#64748b;font-size:.82rem;margin:0">Sem dados.</p>`;
                }
                const w = 640, h = 72, pad = 4;
                const max = Math.max(1, ...points.map(p => Number(p.total_calls || 0)));
                const barW = (w - pad * 2) / points.length;
                const bars = points.map((p, i) => {
                    const barH = Math.max(2, (Number(p.total_calls || 0) / max) * (h - pad * 2));
                    const errH = Math.max(0, (Number(p.error_calls || 0) / max) * (h - pad * 2));
                    const x = pad + i * barW + 1;
                    const y = h - pad - barH;
                    const ye = h - pad - errH;
                    return `<rect x="${x}" y="${y}" width="${barW - 2}" height="${barH}" rx="2" fill="rgba(99,102,241,.55)"/>
                            ${errH > 0 ? `<rect x="${x}" y="${ye}" width="${barW - 2}" height="${errH}" rx="2" fill="rgba(239,68,68,.7)"/>` : ''}`;
                }).join('');
                return `<svg viewBox="0 0 ${w} ${h}" style="width:100%;height:72px">${bars}</svg>`;
            }

            // ------------------------------------------------------------------
            // API call
            // ------------------------------------------------------------------
            async function apiFetch(path) {
                const session = await fetch('/api/admin/session');
                if (!session.ok) throw new Error('Sessao expirada.');
                const res  = await fetch('/api/admin/' + path);
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Falha ao carregar metricas de API.');
                return data;
            }

            // ------------------------------------------------------------------
            // Renderizacao
            // ------------------------------------------------------------------
            async function render() {
                const data = await apiFetch('api-metrics/dashboard?range=' + encodeURIComponent(range));
                const s    = data.summary     || {};
                const rt   = data.realtime    || {};
                const pcts = data.percentiles || {};

                // --- KPI cards
                const successRate = s.total_calls > 0
                    ? ((Number(s.success_calls || 0) / Number(s.total_calls)) * 100).toFixed(2) + '%'
                    : '0.00%';

                const kpiMap = {
                    total_calls:        num(s.total_calls),
                    api_calls:          num(s.api_calls),
                    success_rate:       successRate,
                    error_rate_pct:     pct(s.error_rate_pct),
                    avg_latency_ms:     ms(s.avg_latency_ms),
                    avg_api_latency_ms: ms(s.avg_api_latency_ms),
                    max_latency_ms:     ms(s.max_latency_ms),
                    total_bytes:        bytes(s.total_bytes),
                    api_bytes:          bytes(s.api_bytes),
                    server_error_calls: num(s.server_error_calls),
                    client_error_calls: num(s.client_error_calls),
                    unauthorized_calls: num(s.unauthorized_calls),
                    rate_limited_calls: num(s.rate_limited_calls),
                    unique_ips:         num(s.unique_ips),
                    unique_endpoints:   num(s.unique_endpoints),
                };
                Object.entries(kpiMap).forEach(([k, v]) => {
                    const el = root.querySelector(`[data-aapi="${k}"]`);
                    if (el) el.textContent = v;
                });

                // --- Realtime
                document.getElementById('aapi-realtime').innerHTML =
                    `<i data-lucide="radio"></i> Tempo real (5 min): ${num(rt.total_calls)} chamadas &middot; ${num(rt.api_calls)} API &middot; ${num(rt.error_calls)} erros &middot; ${ms(rt.avg_latency_ms)} latencia media &middot; ${bytes(rt.bytes_total)} trafegados`;

                // --- Graficos de serie temporal
                document.getElementById('aapi-calls-chart').innerHTML   = lineChart(data.series, 'total_calls', '#e50914');
                document.getElementById('aapi-latency-chart').innerHTML  = lineChart(data.series, 'avg_latency_ms', '#38bdf8');

                // --- Throughput sparkline
                document.getElementById('aapi-throughput-chart').innerHTML = sparkBars(data.throughput);

                // --- Percentis
                const pOrder = [['p50', 'P50'], ['p90', 'P90'], ['p95', 'P95'], ['p99', 'P99']];
                document.getElementById('aapi-percentiles').innerHTML = pOrder.map(([k, label]) =>
                    `<div class="aapi-pct-card">
                        <span class="aapi-pct-label">${label}</span>
                        <strong class="aapi-pct-value">${ms(pcts[k])}</strong>
                    </div>`
                ).join('');

                // --- Top endpoints
                document.getElementById('aapi-endpoints-table').innerHTML =
                    data.top_endpoints && data.top_endpoints.length
                    ? `<table class="aapi-table">
                        <thead>
                            <tr>
                                <th>Endpoint</th>
                                <th>Metodo</th>
                                <th>Chamadas</th>
                                <th>Erros</th>
                                <th>Lat. Media</th>
                                <th>Banda</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.top_endpoints.map(ep => `<tr>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(ep.path)}">${esc(ep.path)}</td>
                                <td><span class="aapi-method-badge aapi-method-${esc(ep.method)}">${esc(ep.method)}</span></td>
                                <td>${num(ep.total_calls)}</td>
                                <td>${num(ep.error_calls)}</td>
                                <td>${ms(ep.avg_latency_ms)}</td>
                                <td>${bytes(ep.total_bytes)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>`
                    : '<p style="color:#64748b;font-size:.82rem;margin:0">Nenhum endpoint registrado neste periodo.</p>';

                // --- Distribuicao de status
                const totalStatus = (data.status_dist || []).reduce((acc, r) => acc + Number(r.total), 0) || 1;
                document.getElementById('aapi-status-dist').innerHTML =
                    (data.status_dist || []).slice(0, 12).map(r => {
                        const cls = statusClass(r.status_code);
                        const pctVal = (Number(r.total) / totalStatus * 100).toFixed(1);
                        return `<div class="aapi-status-row">
                            <span class="aapi-status-code ${cls}">${esc(r.status_code)}</span>
                            <div class="aapi-bar-track"><div class="aapi-bar-fill ${cls}" style="width:${pctVal}%"></div></div>
                            <span style="color:#94a3b8;font-size:.78rem;text-align:right;font-variant-numeric:tabular-nums">${num(r.total)}</span>
                        </div>`;
                    }).join('') || '<p style="color:#64748b;font-size:.82rem;margin:0">Sem dados.</p>';

                // --- Grupos de rota
                const totalGroup = (data.group_dist || []).reduce((acc, r) => acc + Number(r.total_calls), 0) || 1;
                document.getElementById('aapi-group-dist').innerHTML =
                    (data.group_dist || []).map(r => {
                        const pctVal = (Number(r.total_calls) / totalGroup * 100).toFixed(1);
                        return `<div class="aapi-group-row">
                            <div>
                                <span class="aapi-group-label">${esc(r.route_group)}</span>
                                <div class="aapi-bar-track" style="margin-top:5px"><div class="aapi-bar-fill s2" style="width:${pctVal}%"></div></div>
                            </div>
                            <span style="color:#94a3b8;font-size:.8rem;text-align:right;font-variant-numeric:tabular-nums">${num(r.total_calls)}</span>
                        </div>`;
                    }).join('') || '<p style="color:#64748b;font-size:.82rem;margin:0">Sem dados.</p>';

                // --- IPs mais ativos
                document.getElementById('aapi-top-ips').innerHTML =
                    (data.top_ips || []).map(r =>
                        `<div class="aapi-ip-row">
                            <span class="aapi-ip-addr">${esc(r.ip_address || 'desconhecido')}</span>
                            <span class="aapi-ip-count">${num(r.total_calls)}</span>
                            <span class="aapi-ip-count">${ms(r.avg_latency_ms)}</span>
                        </div>`
                    ).join('') || '<p style="color:#64748b;font-size:.82rem;margin:0">Sem dados.</p>';

                // --- Erros recentes
                document.getElementById('aapi-recent-errors').innerHTML =
                    data.recent_errors && data.recent_errors.length
                    ? `<table class="aapi-err-table">
                        <thead><tr>
                            <th>Endpoint</th><th>Met.</th><th>Status</th><th>Lat.</th><th>IP</th>
                        </tr></thead>
                        <tbody>
                        ${data.recent_errors.map(e => `<tr>
                            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(e.path)}">${esc(e.path)}</td>
                            <td><span class="aapi-method-badge aapi-method-${esc(e.method)}">${esc(e.method)}</span></td>
                            <td><span class="aapi-status-code ${statusClass(e.status_code)}">${esc(e.status_code)}</span></td>
                            <td>${ms(e.duration_ms)}</td>
                            <td>${esc(e.ip_address || '-')}</td>
                        </tr>`).join('')}
                        </tbody>
                    </table>`
                    : '<p style="color:#4ade80;font-size:.82rem;margin:0">Nenhum erro recente.</p>';

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            // ------------------------------------------------------------------
            // Filtro de periodo
            // ------------------------------------------------------------------
            document.addEventListener('click', event => {
                const filterBtn  = event.target.closest('#aapi-filter-btn');
                const menu       = document.getElementById('aapi-filter-menu');
                if (filterBtn) { menu.classList.toggle('active'); return; }

                const rangeBtn = event.target.closest('[data-aapi-range]');
                if (rangeBtn) {
                    range = rangeBtn.dataset.aapiRange || '1w';
                    document.getElementById('aapi-filter-label').textContent = RANGE_LABELS[range] || '1 semana';
                    menu.querySelectorAll('[data-aapi-range]').forEach(b => b.classList.toggle('active', b === rangeBtn));
                    menu.classList.remove('active');
                    render();
                    return;
                }

                if (!event.target.closest('.aapi-filter')) menu.classList.remove('active');
            });

            // ------------------------------------------------------------------
            // API publica para o setAdminRoute
            // ------------------------------------------------------------------
            window.AdminApiMetricsPanel = {
                load() {
                    render();
                    clearInterval(timer);
                    timer = setInterval(render, 20000);
                }
            };
        })();
        </script>
        <?php
    }
}
