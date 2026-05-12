<?php
declare(strict_types=1);

final class AdminUsageMetricsPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-metrics-page {
                display: grid;
                gap: 14px;
            }

            .admin-metrics-toolbar,
            .admin-metric-card,
            .admin-metric-chart-card,
            .admin-routes-card {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
            }

            .admin-metrics-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px;
            }

            .admin-metrics-toolbar h2 {
                margin: 0;
                color: #fff;
                font-size: 1.12rem;
            }

            .admin-metrics-filter {
                position: relative;
            }

            .admin-metrics-filter-btn {
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
            }

            .admin-metrics-menu {
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

            .admin-metrics-menu.active {
                display: grid;
            }

            .admin-metrics-menu button {
                min-height: 36px;
                border: 0;
                border-radius: 6px;
                background: transparent;
                color: #cbd5e1;
                text-align: left;
                padding: 0 10px;
                cursor: pointer;
                font-weight: 720;
            }

            .admin-metrics-menu button:hover,
            .admin-metrics-menu button.active {
                background: rgba(229, 9, 20, .14);
                color: #fff;
            }

            .admin-metrics-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }

            .admin-metric-card {
                padding: 16px;
            }

            .admin-metric-card span {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #94a3b8;
                font-size: .76rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .admin-metric-card strong {
                display: block;
                margin-top: 10px;
                color: #fff;
                font-size: 1.55rem;
            }

            .admin-metrics-charts {
                display: grid;
                grid-template-columns: 1.3fr .9fr;
                gap: 12px;
            }

            .admin-metric-chart-card,
            .admin-routes-card {
                padding: 16px;
            }

            .admin-metric-chart-card h3,
            .admin-routes-card h3 {
                margin: 0 0 12px;
                color: #fff;
                font-size: 1rem;
            }

            .admin-metric-svg {
                width: 100%;
                height: 260px;
                display: block;
            }

            .admin-route-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 12px;
                padding: 10px 0;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
            }

            .admin-route-row:last-child {
                border-bottom: 0;
            }

            .admin-route-row strong {
                color: #e2e8f0;
                overflow-wrap: anywhere;
                font-size: .88rem;
            }

            .admin-route-row span {
                display: block;
                color: #94a3b8;
                margin-top: 4px;
                font-size: .76rem;
            }

            .admin-realtime-band {
                border: 1px solid rgba(229, 9, 20, .22);
                border-radius: 8px;
                background: rgba(229, 9, 20, .07);
                padding: 14px;
                color: #fecaca;
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 760;
            }

            @media (max-width: 1100px) {
                .admin-metrics-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .admin-metrics-charts {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 620px) {
                .admin-metrics-toolbar {
                    display: grid;
                }

                .admin-metrics-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <section data-admin-route="metrics" class="admin-route-panel" hidden>
            <div class="admin-metrics-page" id="admin-metrics-root">
                <div class="admin-metrics-toolbar">
                    <div>
                        <h2>Metricas de uso</h2>
                        <p style="margin:5px 0 0;color:#94a3b8">Requisicoes, banda estimada, APIs e consumo de rede do Pipocine.</p>
                    </div>
                    <div class="admin-metrics-filter">
                        <button class="admin-metrics-filter-btn" type="button" id="admin-metrics-filter-btn">
                            <i data-lucide="calendar-range"></i>
                            <span id="admin-metrics-filter-label">1 semana</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="admin-metrics-menu" id="admin-metrics-menu">
                            <button type="button" data-metrics-range="1d">1 dia</button>
                            <button type="button" data-metrics-range="5d">5 dias</button>
                            <button class="active" type="button" data-metrics-range="1w">1 semana</button>
                            <button type="button" data-metrics-range="1m">1 mes</button>
                            <button type="button" data-metrics-range="2m">2 meses</button>
                            <button type="button" data-metrics-range="1y">1 ano</button>
                        </div>
                    </div>
                </div>

                <div class="admin-realtime-band" id="admin-metrics-realtime">
                    <i data-lucide="radio"></i>
                    Carregando consumo em tempo real...
                </div>

                <div class="admin-metrics-grid">
                    <article class="admin-metric-card"><span><i data-lucide="mouse-pointer-click"></i>Requisicoes</span><strong data-metric="total_requests">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="plug-zap"></i>Requests API</span><strong data-metric="api_requests">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="network"></i>Banda total</span><strong data-metric="total_bytes">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="timer"></i>Tempo medio</span><strong data-metric="avg_duration_ms">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="triangle-alert"></i>Erros</span><strong data-metric="error_requests">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="globe"></i>IPs unicos</span><strong data-metric="unique_ips">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="activity"></i>Banda APIs</span><strong data-metric="api_bytes">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="gauge"></i>Rede 5 min</span><strong data-metric="realtime_bytes">-</strong></article>
                </div>

                <div class="admin-metrics-charts">
                    <article class="admin-metric-chart-card">
                        <h3>Requisicoes por periodo</h3>
                        <div id="admin-requests-chart"></div>
                    </article>
                    <article class="admin-metric-chart-card">
                        <h3>Consumo de banda</h3>
                        <div id="admin-bandwidth-chart"></div>
                    </article>
                </div>

                <article class="admin-routes-card">
                    <h3>Rotas mais acessadas</h3>
                    <div id="admin-top-routes"></div>
                </article>
            </div>
        </section>

        <script>
            (function () {
                const root = document.getElementById('admin-metrics-root');
                if (!root) return;

                const labels = { '1d': '1 dia', '5d': '5 dias', '1w': '1 semana', '1m': '1 mes', '2m': '2 meses', '1y': '1 ano' };
                let range = '1w';
                let timer = null;

                function esc(value) {
                    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
                }

                function number(value) {
                    return new Intl.NumberFormat('pt-BR').format(Number(value || 0));
                }

                function bytes(value) {
                    value = Number(value || 0);
                    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    let index = 0;
                    while (value >= 1024 && index < units.length - 1) {
                        value /= 1024;
                        index++;
                    }
                    return `${value.toFixed(index ? 1 : 0)} ${units[index]}`;
                }

                function lineChart(points, key, color) {
                    const width = 760, height = 260, pad = 28;
                    const max = Math.max(1, ...points.map(item => Number(item[key] || 0)));
                    const step = points.length > 1 ? (width - pad * 2) / (points.length - 1) : 0;
                    const coords = points.map((item, index) => {
                        const x = pad + index * step;
                        const y = height - pad - (Number(item[key] || 0) / max) * (height - pad * 2);
                        return [x, y];
                    });
                    const poly = coords.map(pair => pair.join(',')).join(' ');
                    const dots = coords.map(([x, y]) => `<circle cx="${x}" cy="${y}" r="3.5"></circle>`).join('');
                    return `<svg class="admin-metric-svg" viewBox="0 0 ${width} ${height}">
                        <line x1="${pad}" y1="${height - pad}" x2="${width - pad}" y2="${height - pad}" stroke="rgba(148,163,184,.22)"></line>
                        <polyline points="${poly}" fill="none" stroke="${color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <g fill="${color}">${dots}</g>
                    </svg>`;
                }

                async function api(path) {
                    const session = await fetch('/api/admin/session');
                    if (!session.ok) throw new Error('Sessao expirada.');

                    const response = await fetch('/api/admin/' + path);
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.error || 'Falha ao carregar metricas.');
                    return data;
                }

                async function loadMetrics() {
                    const data = await api('metrics/usage?range=' + encodeURIComponent(range));
                    const summary = data.summary || {};
                    const realtime = data.realtime || {};

                    const values = {
                        total_requests: number(summary.total_requests),
                        api_requests: number(summary.api_requests),
                        total_bytes: bytes(summary.total_bytes),
                        avg_duration_ms: `${Math.round(Number(summary.avg_duration_ms || 0))} ms`,
                        error_requests: number(summary.error_requests),
                        unique_ips: number(summary.unique_ips),
                        api_bytes: bytes(summary.api_bytes),
                        realtime_bytes: bytes(realtime.bytes_total)
                    };

                    Object.entries(values).forEach(([key, value]) => {
                        const target = root.querySelector(`[data-metric="${key}"]`);
                        if (target) target.textContent = value;
                    });

                    document.getElementById('admin-metrics-realtime').innerHTML =
                        `<i data-lucide="radio"></i> Tempo real: ${number(realtime.requests)} requests nos ultimos 5 min · ${bytes(realtime.bytes_total)} trafegados · APIs ${bytes(realtime.api_bytes)}`;

                    document.getElementById('admin-requests-chart').innerHTML = lineChart(data.series || [], 'total_requests', '#e50914');
                    document.getElementById('admin-bandwidth-chart').innerHTML = lineChart(data.series || [], 'total_bytes', '#38bdf8');
                    document.getElementById('admin-top-routes').innerHTML = (data.top_routes || []).map(route => `
                        <div class="admin-route-row">
                            <div>
                                <strong>${esc(route.path)}</strong>
                                <span>${esc(route.route_group)} · ${bytes(route.total_bytes)} · ${Math.round(Number(route.avg_duration_ms || 0))} ms medio</span>
                            </div>
                            <strong>${number(route.total_requests)}</strong>
                        </div>
                    `).join('') || '<p style="color:#94a3b8;margin:0">Nenhuma rota registrada.</p>';

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                document.addEventListener('click', event => {
                    const btn = event.target.closest('#admin-metrics-filter-btn');
                    const menu = document.getElementById('admin-metrics-menu');
                    if (btn) menu.classList.toggle('active');

                    const option = event.target.closest('[data-metrics-range]');
                    if (option) {
                        range = option.dataset.metricsRange || '1w';
                        document.getElementById('admin-metrics-filter-label').textContent = labels[range] || '1 semana';
                        menu.querySelectorAll('[data-metrics-range]').forEach(item => item.classList.toggle('active', item === option));
                        menu.classList.remove('active');
                        loadMetrics();
                    }

                    if (!event.target.closest('.admin-metrics-filter')) {
                        menu.classList.remove('active');
                    }
                });

                window.AdminMetricsPanel = {
                    load: () => {
                        loadMetrics();
                        clearInterval(timer);
                        timer = setInterval(loadMetrics, 15000);
                    }
                };
            })();
        </script>
        <?php
    }
}
