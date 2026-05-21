<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../models/admin/AdminStatusModel.php';
require_once __DIR__ . '/../services/admin/AdminStatusService.php';

use Models\Admin\AdminStatusModel;
use Services\Admin\AdminStatusService;

$overview = [
    'success' => false,
    'overall' => ['label' => 'Status indisponivel', 'tone' => 'danger'],
    'active_incidents' => [],
    'components' => [],
    'health' => ['current_score' => 0, 'series' => []],
    'history' => [],
];

if ($pdo) {
    try {
        $overview = (new AdminStatusService(new AdminStatusModel($pdo)))->publicOverview(30);
    } catch (Throwable $e) {
        error_log('[StatusPage] ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Status do Sistema | PipoCine</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #05070b;
            --surface: #0c1118;
            --surface-2: #111824;
            --line: rgba(148, 163, 184, .16);
            --text: #f8fafc;
            --muted: #91a0b8;
            --ok: #14b8a6;
            --blue: #38bdf8;
            --degraded: #f59e0b;
            --warn: #fb7185;
            --danger: #ef4444;
            --maintenance: #8b5cf6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color: inherit; }
        .status-shell {
            width: min(1180px, calc(100% - 36px));
            margin: 0 auto;
            padding: 34px 0 54px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 850;
            font-size: 1.08rem;
        }
        .brand-mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #e50914;
            color: #fff;
            font-weight: 900;
        }
        .updated { color: var(--muted); font-size: .9rem; }
        .hero {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            padding: clamp(20px, 4vw, 34px);
            margin-bottom: 18px;
        }
        .system-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-radius: 8px;
            padding: clamp(18px, 4vw, 28px);
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(20, 184, 166, .13);
        }
        .system-badge h1 {
            margin: 0;
            font-size: clamp(1.65rem, 5vw, 3.1rem);
            line-height: 1.05;
            letter-spacing: 0;
        }
        .system-badge span {
            display: block;
            margin-top: 8px;
            color: rgba(255,255,255,.72);
            font-size: .95rem;
        }
        .pulse {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 8px color-mix(in srgb, currentColor 22%, transparent);
        }
        .tone-ok { color: var(--ok); background: rgba(20, 184, 166, .12); }
        .tone-degraded { color: var(--degraded); background: rgba(245, 158, 11, .12); }
        .tone-warn { color: var(--warn); background: rgba(251, 113, 133, .12); }
        .tone-danger { color: var(--danger); background: rgba(239, 68, 68, .12); }
        .tone-maintenance { color: var(--maintenance); background: rgba(139, 92, 246, .12); }
        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
            gap: 18px;
            align-items: start;
        }
        .section {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            overflow: hidden;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
        }
        .section-head h2 {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0;
        }
        .section-head small,
        .muted { color: var(--muted); }
        .active-list,
        .history-list,
        .component-list { display: grid; gap: 12px; padding: 16px; }
        .incident {
            border: 1px solid rgba(148,163,184,.14);
            border-radius: 8px;
            background: #090e15;
            padding: 16px;
        }
        .incident h3 { margin: 0; font-size: 1.02rem; }
        .incident p { margin: 10px 0 0; color: var(--muted); line-height: 1.55; }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border: 1px solid rgba(148,163,184,.16);
            border-radius: 999px;
            padding: 0 10px;
            color: #dbeafe;
            background: rgba(15, 23, 42, .7);
            font-size: .78rem;
            font-weight: 760;
        }
        .timeline {
            display: grid;
            gap: 12px;
            margin-top: 16px;
            padding-left: 14px;
            border-left: 1px solid rgba(148,163,184,.18);
        }
        .timeline-item { position: relative; }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -19px;
            top: 4px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--blue);
        }
        .timeline-item strong { display:block; font-size:.9rem; }
        .timeline-item time { color: var(--muted); font-size:.78rem; }
        .component {
            border: 1px solid rgba(148,163,184,.13);
            border-radius: 8px;
            background: #090e15;
            padding: 14px;
        }
        .component summary {
            cursor: pointer;
            list-style: none;
        }
        .component summary::-webkit-details-marker { display: none; }
        .component-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .bars {
            display: grid;
            grid-template-columns: repeat(30, minmax(4px, 1fr));
            gap: 3px;
        }
        .bar {
            height: 28px;
            border-radius: 3px;
            background: var(--ok);
        }
        .bar.degraded_performance,
        .bar.api_degradation { background: var(--degraded); }
        .bar.partial_outage,
        .bar.network_incident,
        .bar.third_party_provider_issue { background: var(--warn); }
        .bar.major_outage,
        .bar.database_incident,
        .bar.security_incident { background: var(--danger); }
        .bar.maintenance { background: var(--maintenance); }
        .subcomponents {
            display: grid;
            gap: 10px;
            margin-top: 12px;
            padding-left: 12px;
            border-left: 1px solid rgba(148,163,184,.14);
        }
        .chart-wrap { padding: 16px; }
        .chart {
            width: 100%;
            height: 260px;
            border: 1px solid rgba(148,163,184,.12);
            border-radius: 8px;
            background: #070b11;
        }
        .chart-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .stat {
            border: 1px solid rgba(148,163,184,.12);
            border-radius: 8px;
            padding: 12px;
            background: #090e15;
        }
        .stat strong { display:block; font-size: 1.25rem; }
        .stat span { color: var(--muted); font-size:.78rem; }
        .history-date {
            color: #fff;
            font-weight: 850;
            margin: 8px 0 2px;
        }
        .history-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 14px 16px 0;
        }
        .history-filters select {
            min-height: 36px;
            border: 1px solid rgba(148,163,184,.16);
            border-radius: 8px;
            background: #090e15;
            color: #e2e8f0;
            padding: 0 10px;
        }
        details.history-item {
            border: 1px solid rgba(148,163,184,.13);
            border-radius: 8px;
            padding: 14px;
            background: #090e15;
        }
        details.history-item summary { cursor: pointer; }
        .empty {
            padding: 18px;
            color: var(--muted);
            text-align: center;
        }
        @media (max-width: 900px) {
            .grid,
            .chart-stats { grid-template-columns: 1fr; }
            .system-badge { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <main class="status-shell">
        <header class="topbar">
            <div class="brand"><span class="brand-mark">PC</span><span>PipoCine Status</span></div>
            <div class="updated" id="updated-at">Atualizando...</div>
        </header>

        <section class="hero">
            <div class="system-badge" id="overall-badge">
                <div>
                    <h1>Status do Sistema</h1>
                    <span>Carregando estado operacional.</span>
                </div>
                <i class="pulse" aria-hidden="true"></i>
            </div>
        </section>

        <section class="grid">
            <div class="section">
                <div class="section-head">
                    <h2>Incidente ativo</h2>
                    <small id="active-count"></small>
                </div>
                <div class="active-list" id="active-incidents"></div>
            </div>

            <aside class="section">
                <div class="section-head">
                    <h2>Saude operacional</h2>
                    <small id="health-score"></small>
                </div>
                <div class="chart-wrap">
                    <svg class="chart" id="health-chart" role="img" aria-label="System Health Score"></svg>
                    <div class="chart-stats" id="health-stats"></div>
                </div>
            </aside>
        </section>

        <section class="section" style="margin-top:18px">
            <div class="section-head">
                <h2>Componentes</h2>
                <small>Ultimos 30 dias</small>
            </div>
            <div class="component-list" id="component-list"></div>
        </section>

        <section class="section" style="margin-top:18px">
            <div class="section-head">
                <h2>Past Incidents</h2>
                <small>Historico publico</small>
            </div>
            <form class="history-filters" id="history-filters">
                <select name="component"><option value="">Componente</option></select>
                <select name="impact">
                    <option value="">Severidade</option>
                    <option value="degraded_performance">Degraded Performance</option>
                    <option value="partial_outage">Partial Outage</option>
                    <option value="major_outage">Major Outage</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="security_incident">Security Incident</option>
                    <option value="api_degradation">API Degradation</option>
                    <option value="database_incident">Database Incident</option>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="resolved">Resolved</option>
                    <option value="archived">Archived</option>
                </select>
                <select name="type">
                    <option value="">Tipo</option>
                    <option value="incident">Incident</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="security">Security</option>
                    <option value="api">API</option>
                    <option value="database">Database</option>
                    <option value="third_party">Third-party</option>
                </select>
                <select name="period">
                    <option value="21">21 dias</option>
                    <option value="7">7 dias</option>
                    <option value="30">30 dias</option>
                    <option value="60">60 dias</option>
                    <option value="90">90 dias</option>
                </select>
            </form>
            <div class="history-list" id="history-list"></div>
        </section>
    </main>

    <script id="status-data" type="application/json"><?= json_encode($overview, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    <script>
    (function () {
        let data = JSON.parse(document.getElementById('status-data').textContent || '{}');
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[ch]));
        const fmtDate = (value) => {
            if (!value) return '';
            return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(String(value).replace(' ', 'T')));
        };
        const fmtDay = (value) => new Intl.DateTimeFormat('pt-BR', { dateStyle: 'long' }).format(new Date(value + 'T12:00:00'));
        const fmtDuration = (seconds, resolved) => {
            const minutes = Math.max(0, Math.floor(Number(seconds || 0) / 60));
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            const label = h > 0 ? `${h}h ${m}min` : `${m}min`;
            return resolved ? `Resolvido apos ${label}` : `${label} em andamento`;
        };
        const bytes = (value) => {
            const n = Number(value || 0);
            if (n >= 1073741824) return (n / 1073741824).toFixed(1) + ' GB';
            if (n >= 1048576) return (n / 1048576).toFixed(1) + ' MB';
            if (n >= 1024) return (n / 1024).toFixed(1) + ' KB';
            return n + ' B';
        };

        function renderOverall() {
            const overall = data.overall || {};
            const badge = document.getElementById('overall-badge');
            badge.className = 'system-badge tone-' + esc(overall.tone || 'ok');
            badge.innerHTML = `
                <div>
                    <h1>${esc(overall.label || 'All Systems Operational')}</h1>
                    <span>${(data.active_incidents || []).length ? 'Ha incidentes ativos publicados.' : 'Todos os componentes publicos estao operacionais.'}</span>
                </div>
                <i class="pulse" aria-hidden="true"></i>
            `;
            document.getElementById('updated-at').textContent = 'Atualizado em ' + fmtDate(data.generated_at || new Date().toISOString());
        }

        function renderActive() {
            const root = document.getElementById('active-incidents');
            const incidents = data.active_incidents || [];
            document.getElementById('active-count').textContent = incidents.length ? `${incidents.length} em andamento` : '';
            if (!incidents.length) {
                root.innerHTML = '<div class="empty">No incidents reported</div>';
                return;
            }
            root.innerHTML = incidents.map(incident => incidentHtml(incident, true)).join('');
        }

        function incidentHtml(incident, showTimeline) {
            const components = (incident.component_names || []).map(name => `<span class="chip">${esc(name)}</span>`).join('');
            const updates = (incident.updates || []).map(update => `
                <div class="timeline-item">
                    <strong>${esc(update.update_type)} · ${esc(update.status)}</strong>
                    <time>${fmtDate(update.created_at)}</time>
                    <p>${esc(update.public_message || '')}</p>
                </div>
            `).join('');
            return `
                <article class="incident" id="incident-${Number(incident.id)}">
                    <h3>${esc(incident.title)}</h3>
                    <p>${esc(incident.public_description || '')}</p>
                    <div class="meta">
                        <span class="chip">${esc(incident.category || incident.impact_label)}</span>
                        <span class="chip">${esc(incident.status)}</span>
                        <span class="chip" data-duration-start="${esc(incident.started_at)}" data-duration-end="${esc(incident.resolved_at || '')}">${esc(incident.duration_label || '')}</span>
                        ${components}
                    </div>
                    ${showTimeline ? `<div class="timeline">${updates || '<div class="muted">Sem atualizacoes publicas.</div>'}</div>` : ''}
                </article>
            `;
        }

        function renderComponents() {
            const root = document.getElementById('component-list');
            const components = data.components || [];
            if (!components.length) {
                root.innerHTML = '<div class="empty">Nenhum componente publico configurado.</div>';
                return;
            }
            root.innerHTML = components.map(componentHtml).join('');
        }

        function componentHtml(component) {
            const bars = (component.bars || []).map(bar => {
                const title = `${bar.date} · ${bar.label} · uptime ${bar.uptime_pct}% · impacto ${bar.duration_minutes}min`;
                return `<span class="bar ${esc(bar.status)}" title="${esc(title)}"></span>`;
            }).join('');
            const children = (component.children || []).map(componentHtml).join('');
            return `
                <details class="component" ${children ? 'open' : ''}>
                    <summary>
                        <div class="component-title">
                            <strong>${esc(component.name)}</strong>
                            <span class="chip">${esc((component.current_status || 'operational').replaceAll('_', ' '))}</span>
                        </div>
                        <div class="bars">${bars}</div>
                    </summary>
                    ${children ? `<div class="subcomponents">${children}</div>` : ''}
                </details>
            `;
        }

        function renderHealth() {
            const health = data.health || {};
            const series = health.series || [];
            document.getElementById('health-score').textContent = `${Number(health.current_score || 0).toFixed(1)}%`;
            const svg = document.getElementById('health-chart');
            const width = 760;
            const height = 260;
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            const points = series.map((row, index) => {
                const x = series.length <= 1 ? 24 : 24 + index * ((width - 48) / (series.length - 1));
                const y = height - 28 - (Number(row.score || 0) / 100) * (height - 56);
                return `${x},${y}`;
            }).join(' ');
            const grid = [25, 50, 75, 100].map(v => {
                const y = height - 28 - (v / 100) * (height - 56);
                return `<line x1="24" y1="${y}" x2="${width - 24}" y2="${y}" stroke="rgba(148,163,184,.12)"/><text x="26" y="${y - 5}" fill="#64748b" font-size="11">${v}%</text>`;
            }).join('');
            svg.innerHTML = `
                ${grid}
                <polyline points="${points}" fill="none" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            `;
            const last = series[series.length - 1] || {};
            document.getElementById('health-stats').innerHTML = `
                <div class="stat"><strong>${Number(last.avg_latency_ms || 0).toFixed(0)} ms</strong><span>Latencia media</span></div>
                <div class="stat"><strong>${Number(last.error_rate_pct || 0).toFixed(2)}%</strong><span>Taxa de erro</span></div>
                <div class="stat"><strong>${bytes(last.total_bytes || 0)}</strong><span>Banda total</span></div>
            `;
        }

        function renderHistory() {
            const root = document.getElementById('history-list');
            const history = data.history || {};
            const filters = Object.fromEntries(new FormData(document.getElementById('history-filters')).entries());
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - Number(filters.period || 21));
            const days = Object.keys(history).filter(day => new Date(day + 'T23:59:59') >= cutoff);
            if (!days.length) {
                root.innerHTML = '<div class="empty">No incidents reported</div>';
                return;
            }
            const html = days.map(day => {
                const incidents = (history[day] || []).filter(incident => {
                    const names = (incident.component_names || []).join('|');
                    return (!filters.component || names.includes(filters.component))
                        && (!filters.impact || incident.impact === filters.impact)
                        && (!filters.status || incident.status === filters.status)
                        && (!filters.type || incident.incident_type === filters.type);
                });
                if (!incidents.length) return '';
                return `
                <div class="history-date">${fmtDay(day)}</div>
                ${incidents.map(incident => `
                    <details class="history-item">
                        <summary>
                            <strong>${esc(incident.title)}</strong>
                            <div class="meta">
                                <span class="chip">${esc(incident.category)}</span>
                                <span class="chip">${esc(incident.status)}</span>
                                <span class="chip">${esc(incident.duration_label)}</span>
                            </div>
                        </summary>
                        ${incidentHtml(incident, true)}
                    </details>
                `).join('')}
            `}).join('');
            root.innerHTML = html || '<div class="empty">No incidents reported</div>';
        }

        function renderHistoryFilters() {
            const select = document.querySelector('#history-filters select[name="component"]');
            const current = select.value;
            const names = new Set();
            Object.values(data.history || {}).flat().forEach(incident => {
                (incident.component_names || []).forEach(name => names.add(name));
            });
            select.innerHTML = '<option value="">Componente</option>' + Array.from(names).sort().map(name => `<option value="${esc(name)}">${esc(name)}</option>`).join('');
            select.value = current;
        }

        function tickDurations() {
            document.querySelectorAll('[data-duration-start]').forEach(node => {
                const end = node.dataset.durationEnd;
                const start = new Date(node.dataset.durationStart.replace(' ', 'T')).getTime();
                const final = end ? new Date(end.replace(' ', 'T')).getTime() : Date.now();
                node.textContent = fmtDuration(Math.floor((final - start) / 1000), Boolean(end));
            });
        }

        function renderAll() {
            renderOverall();
            renderActive();
            renderComponents();
            renderHealth();
            renderHistoryFilters();
            renderHistory();
            tickDurations();
        }

        async function refresh() {
            try {
                const response = await fetch('/api/status/overview?days=30', { headers: { 'Accept': 'application/json' } });
                const fresh = await response.json();
                if (fresh && fresh.success) {
                    data = fresh;
                    renderAll();
                }
            } catch (error) {}
        }

        renderAll();
        document.getElementById('history-filters').addEventListener('change', renderHistory);
        setInterval(tickDurations, 30000);
        setInterval(refresh, 60000);
    })();
    </script>
</body>
</html>
