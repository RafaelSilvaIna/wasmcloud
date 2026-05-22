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
            color-scheme: light;
            --bg: #f6f7f9;
            --surface: #ffffff;
            --surface-soft: #fafafa;
            --line: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --quiet: #9ca3af;
            --ok: #2563eb;
            --degraded: #b7791f;
            --warn: #be5b45;
            --danger: #9f1239;
            --maintenance: #6d28d9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        .status-shell {
            width: min(1040px, calc(100% - 40px));
            margin: 0 auto;
            padding: 44px 0 72px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 54px;
        }
        .brand {
            display: flex;
            align-items: center;
            font-weight: 760;
            font-size: .98rem;
            letter-spacing: 0;
        }
        .updated {
            color: var(--muted);
            font-size: .86rem;
        }
        .hero {
            margin-bottom: 34px;
        }
        .system-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-radius: 0;
            padding: 0 0 34px;
            border-bottom: 1px solid var(--line);
            background: transparent;
        }
        .system-badge h1 {
            margin: 0;
            color: var(--text);
            font-size: 4.1rem;
            line-height: .98;
            font-weight: 780;
            letter-spacing: 0;
        }
        .system-badge span {
            display: block;
            width: min(620px, 100%);
            margin-top: 16px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
        }
        .pulse {
            width: 14px;
            height: 14px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 7px color-mix(in srgb, currentColor 12%, transparent);
        }
        .tone-ok { color: var(--ok); }
        .tone-degraded { color: var(--degraded); }
        .tone-warn { color: var(--warn); }
        .tone-danger { color: var(--danger); }
        .tone-maintenance { color: var(--maintenance); }
        .status-section { margin-top: 18px; }
        .section {
            border: 1px solid var(--line);
            border-radius: 0;
            background: var(--surface);
            overflow: hidden;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
        }
        .section-head h2 {
            margin: 0;
            font-size: .95rem;
            font-weight: 740;
            letter-spacing: 0;
        }
        .section-head small,
        .muted { color: var(--muted); }
        .active-list,
        .history-list,
        .component-list { display: grid; gap: 0; padding: 0; }
        .incident {
            border: 0;
            border-bottom: 1px solid var(--line);
            border-radius: 0;
            background: transparent;
            padding: 20px;
        }
        .incident:last-child { border-bottom: 0; }
        .incident h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 740;
        }
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
            min-height: 24px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 0 9px;
            color: #374151;
            background: var(--surface-soft);
            font-size: .74rem;
            font-weight: 680;
        }
        .timeline {
            display: grid;
            gap: 14px;
            margin-top: 18px;
            padding-left: 16px;
            border-left: 1px solid var(--line);
        }
        .timeline-item { position: relative; }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -20px;
            top: 4px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }
        .timeline-item strong { display:block; font-size:.88rem; }
        .timeline-item time { color: var(--muted); font-size:.78rem; }
        .component {
            display: grid;
            gap: 14px;
            border: 0;
            border-bottom: 1px solid var(--line);
            border-radius: 0;
            background: transparent;
            padding: 20px;
        }
        .component:last-child { border-bottom: 0; }
        .component-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .component-title strong {
            font-size: 1rem;
            font-weight: 740;
        }
        .component-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ok);
            font-size: .86rem;
            font-weight: 740;
        }
        .component-status::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }
        .component-metrics {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            color: var(--quiet);
            font-size: .76rem;
        }
        .component-metrics span strong {
            color: var(--text);
            font-weight: 680;
        }
        .subcomponents {
            display: grid;
            gap: 0;
            margin-top: 2px;
            padding-left: 20px;
            border-left: 1px solid var(--line);
        }
        .health-card { overflow: hidden; }
        .graph-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 28px 32px 8px;
        }
        .graph-head h2 {
            margin: 0;
            color: var(--text);
            font-size: 1.28rem;
            font-weight: 520;
            letter-spacing: 0;
        }
        .graph-head small {
            color: var(--muted);
            font-size: 1.28rem;
            font-weight: 500;
        }
        .chart-wrap { padding: 0 32px 20px; }
        .chart {
            display: block;
            width: 100%;
            height: 168px;
            border: 0;
            border-radius: 0;
            background: transparent;
        }
        .history-date {
            color: var(--text);
            font-weight: 760;
            margin: 20px 20px 8px;
        }
        .history-filters {
            display: none;
        }
        details.history-item {
            border: 0;
            border-top: 1px solid var(--line);
            border-radius: 0;
            padding: 16px 20px;
            background: transparent;
        }
        details.history-item summary { cursor: pointer; }
        .empty {
            padding: 44px 20px;
            color: var(--muted);
            text-align: center;
        }
        @media (max-width: 900px) {
            .graph-head {
                padding: 20px 20px 4px;
            }
            .graph-head h2,
            .graph-head small {
                font-size: 1rem;
            }
            .chart-wrap {
                padding: 0 20px 18px;
            }
            .chart {
                height: auto;
                aspect-ratio: 860 / 168;
                min-height: 118px;
            }
            .system-badge { align-items: flex-start; }
            .system-badge h1 { font-size: 2.35rem; }
            .topbar { align-items: flex-start; flex-direction: column; margin-bottom: 38px; }
        }
    </style>
</head>
<body>
    <main class="status-shell">
        <header class="topbar">
            <div class="brand">PipoCine Status</div>
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

        <section class="section status-section" id="active-section" hidden>
            <div class="section-head">
                <h2>Incidente ativo</h2>
                <small id="active-count"></small>
            </div>
            <div class="active-list" id="active-incidents"></div>
        </section>

        <section class="section status-section">
            <div class="section-head">
                <h2>Componentes</h2>
                <small>Ultimos 30 dias</small>
            </div>
            <div class="component-list" id="component-list"></div>
        </section>

        <section class="section status-section">
            <div class="section-head">
                <h2>Past Incidents</h2>
                <small>Historico publico</small>
            </div>
            <form class="history-filters" id="history-filters">
                <input type="hidden" name="period" value="90">
            </form>
            <div class="history-list" id="history-list"></div>
        </section>

        <section class="section status-section health-card">
            <div class="graph-head">
                <h2>Saude operacional</h2>
                <small id="health-score"></small>
            </div>
            <div class="chart-wrap">
                <svg class="chart" id="health-chart" role="img" aria-label="System Health Score"></svg>
            </div>
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
            const section = document.getElementById('active-section');
            const root = document.getElementById('active-incidents');
            const incidents = data.active_incidents || [];
            document.getElementById('active-count').textContent = incidents.length ? `${incidents.length} em andamento` : '';
            if (!incidents.length) {
                section.hidden = true;
                root.innerHTML = '';
                return;
            }
            section.hidden = false;
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
            const children = (component.children || []).map(componentHtml).join('');
            const status = (component.current_status || 'operational').replaceAll('_', ' ');
            const bars = component.bars || [];
            const impacted = bars.filter(bar => bar.status && bar.status !== 'operational').length;
            const uptime = bars.length
                ? (bars.reduce((total, bar) => total + Number(bar.uptime_pct || 100), 0) / bars.length).toFixed(2)
                : '100.00';
            return `
                <div class="component">
                    <div class="component-title">
                        <strong>${esc(component.name)}</strong>
                        <span class="component-status">${esc(status)}</span>
                    </div>
                    <div class="component-metrics">
                        <span><strong>${uptime}%</strong> uptime em 30 dias</span>
                        <span><strong>${impacted}</strong> dias com impacto</span>
                    </div>
                    ${children ? `<div class="subcomponents">${children}</div>` : ''}
                </div>
            `;
        }

        function renderHealth() {
            const health = data.health || {};
            const series = health.series || [];
            document.getElementById('health-score').textContent = `${Number(health.current_score || 0).toFixed(1)}%`;
            const svg = document.getElementById('health-chart');
            const width = 860;
            const height = 168;
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            const pad = { left: 12, right: 54, top: 12, bottom: 28 };
            const plotW = width - pad.left - pad.right;
            const plotH = height - pad.top - pad.bottom;
            const points = series.map((row, index) => {
                const x = series.length <= 1 ? pad.left : pad.left + index * (plotW / (series.length - 1));
                const y = pad.top + (100 - Number(row.score || 0)) / 100 * plotH;
                return { x, y, row };
            });
            const pointString = points.map(p => `${p.x},${p.y}`).join(' ');
            const bars = points.map(p => {
                const err = Math.min(100, Number(p.row.error_rate_pct || 0));
                if (err <= 0) return '';
                const h = Math.max(2, err / 100 * 30);
                return `<rect x="${p.x - 2}" y="${height - pad.bottom - h}" width="4" height="${h}" rx="2" fill="#d8dde6"><title>${esc(p.row.date)} · erro ${err.toFixed(2)}%</title></rect>`;
            }).join('');
            const grid = [0, 50, 75, 100].map(v => {
                const y = pad.top + (100 - v) / 100 * plotH;
                return `<line x1="${pad.left}" y1="${y}" x2="${width - pad.right}" y2="${y}" stroke="#e9edf3"/><text x="${width - pad.right + 8}" y="${y + 4}" fill="#64748b" font-size="13">${v}</text>`;
            }).join('');
            const labels = points.filter((_, index) => index === 0 || index === points.length - 1 || index === Math.floor(points.length / 2)).map(p => {
                const label = new Date(p.row.date + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
                return `<text x="${p.x}" y="${height - 10}" text-anchor="middle" fill="#64748b" font-size="13">${esc(label)}</text>`;
            }).join('');
            svg.innerHTML = `
                ${grid}
                ${bars}
                <polyline points="${pointString}" fill="none" stroke="#5267ff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                ${labels}
            `;
        }

        function renderHistory() {
            const root = document.getElementById('history-list');
            const history = data.history || {};
            const filters = Object.fromEntries(new FormData(document.getElementById('history-filters')).entries());
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - Number(filters.period || 90));
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
        setInterval(tickDurations, 30000);
        setInterval(refresh, 60000);
    })();
    </script>
</body>
</html>
