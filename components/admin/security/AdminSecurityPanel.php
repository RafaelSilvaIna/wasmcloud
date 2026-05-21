<?php
declare(strict_types=1);

/**
 * AdminSecurityPanel — Painel de gerenciamento da Global Security Layer.
 *
 * Renderiza o painel SPA da camada Anti-DDoS/Anti-Bot.
 * Toda comunicação é feita via /api/admin/security/* (SecurityAdminController).
 */
final class AdminSecurityPanel
{
    public static function render(): void
    {
        ?>
        <section data-admin-route="security" class="admin-route-panel" hidden>
        <style>
            /* ── Layout ─────────────────────────────────────────────── */
            .sec-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 20px;
            }
            .sec-header h2 {
                margin: 0;
                font-size: 1.18rem;
                color: #fff;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .sec-header h2 svg { width:20px; height:20px; color: #e50914; }

            /* ── Tabs ───────────────────────────────────────────────── */
            .sec-tabs {
                display: flex;
                gap: 4px;
                flex-wrap: wrap;
                margin-bottom: 20px;
                border-bottom: 1px solid rgba(148,163,184,.14);
                padding-bottom: 4px;
            }
            .sec-tab {
                min-height: 36px;
                padding: 0 14px;
                border: 1px solid transparent;
                border-radius: 8px 8px 0 0;
                background: transparent;
                color: #94a3b8;
                font-size: .86rem;
                font-weight: 650;
                cursor: pointer;
                transition: background .15s, color .15s;
            }
            .sec-tab:hover { color: #fff; background: rgba(229,9,20,.08); }
            .sec-tab.active {
                color: #fff;
                background: rgba(229,9,20,.14);
                border-color: rgba(229,9,20,.3);
                border-bottom-color: transparent;
            }

            /* ── Panels ─────────────────────────────────────────────── */
            .sec-pane { display: none; }
            .sec-pane.active { display: block; }

            /* ── KPI grid ───────────────────────────────────────────── */
            .sec-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 12px;
                margin-bottom: 20px;
            }
            .sec-kpi {
                border: 1px solid rgba(148,163,184,.14);
                border-radius: 10px;
                background: #0f131a;
                padding: 16px;
            }
            .sec-kpi label {
                display: block;
                color: #94a3b8;
                font-size: .75rem;
                font-weight: 750;
                text-transform: uppercase;
                letter-spacing: .04em;
                margin-bottom: 8px;
            }
            .sec-kpi strong {
                display: block;
                font-size: 1.7rem;
                font-weight: 800;
                color: #fff;
            }
            .sec-kpi.danger strong { color: #f87171; }
            .sec-kpi.warn  strong { color: #fbbf24; }
            .sec-kpi.ok    strong { color: #34d399; }

            /* ── Tables ─────────────────────────────────────────────── */
            .sec-table-wrap {
                overflow-x: auto;
                border: 1px solid rgba(148,163,184,.14);
                border-radius: 10px;
            }
            .sec-table {
                width: 100%;
                border-collapse: collapse;
                font-size: .84rem;
            }
            .sec-table th {
                background: #0f131a;
                color: #94a3b8;
                font-weight: 750;
                text-align: left;
                padding: 10px 14px;
                white-space: nowrap;
                border-bottom: 1px solid rgba(148,163,184,.14);
            }
            .sec-table td {
                padding: 9px 14px;
                color: #e2e8f0;
                border-bottom: 1px solid rgba(148,163,184,.08);
                vertical-align: top;
            }
            .sec-table tr:last-child td { border-bottom: none; }
            .sec-table tr:hover td { background: rgba(255,255,255,.025); }

            /* ── Badges ─────────────────────────────────────────────── */
            .sec-badge {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 20px;
                font-size: .75rem;
                font-weight: 700;
            }
            .sec-badge.critical { background: rgba(239,68,68,.18); color: #f87171; }
            .sec-badge.high     { background: rgba(249,115,22,.18); color: #fb923c; }
            .sec-badge.medium   { background: rgba(234,179,8,.18);  color: #fbbf24; }
            .sec-badge.low      { background: rgba(148,163,184,.12); color: #94a3b8; }
            .sec-badge.active   { background: rgba(239,68,68,.18); color: #f87171; }
            .sec-badge.shadow   { background: rgba(139,92,246,.18); color: #a78bfa; }
            .sec-badge.soft     { background: rgba(234,179,8,.18);  color: #fbbf24; }
            .sec-badge.hard     { background: rgba(239,68,68,.22); color: #fca5a5; }
            .sec-badge.bot      { background: rgba(249,115,22,.14); color: #fb923c; }

            /* ── Score bar ─────────────────────────────────────────── */
            .sec-score-bar {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: .82rem;
            }
            .sec-score-track {
                flex: 1;
                height: 6px;
                border-radius: 99px;
                background: rgba(148,163,184,.15);
                overflow: hidden;
            }
            .sec-score-fill {
                height: 100%;
                border-radius: 99px;
                background: #e50914;
                transition: width .3s;
            }

            /* ── Actions ────────────────────────────────────────────── */
            .sec-btn {
                min-height: 34px;
                padding: 0 14px;
                border: 1px solid rgba(148,163,184,.2);
                border-radius: 7px;
                background: #141923;
                color: #e2e8f0;
                font-size: .83rem;
                font-weight: 650;
                cursor: pointer;
                transition: background .15s, border-color .15s;
                white-space: nowrap;
            }
            .sec-btn:hover { background: #1e2535; border-color: rgba(148,163,184,.35); }
            .sec-btn.danger { border-color: rgba(239,68,68,.35); color: #f87171; }
            .sec-btn.danger:hover { background: rgba(239,68,68,.12); }
            .sec-btn.ok     { border-color: rgba(52,211,153,.35); color: #34d399; }
            .sec-btn.ok:hover { background: rgba(52,211,153,.1); }

            /* ── Form inline ─────────────────────────────────────────── */
            .sec-inline-form {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: flex-end;
                padding: 16px;
                background: #0f131a;
                border: 1px solid rgba(148,163,184,.14);
                border-radius: 10px;
                margin-bottom: 16px;
            }
            .sec-inline-form .sec-field { display: grid; gap: 5px; }
            .sec-inline-form label { color: #94a3b8; font-size: .78rem; font-weight: 700; }
            .sec-inline-form input,
            .sec-inline-form select {
                min-height: 36px;
                padding: 0 10px;
                border: 1px solid rgba(148,163,184,.18);
                border-radius: 7px;
                background: #090c12;
                color: #e2e8f0;
                font-size: .85rem;
            }

            /* ── Paginação ───────────────────────────────────────────── */
            .sec-pagination {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 14px;
                font-size: .84rem;
                color: #94a3b8;
            }
            .sec-pagination button {
                min-height: 30px;
                padding: 0 12px;
                border: 1px solid rgba(148,163,184,.2);
                border-radius: 6px;
                background: #141923;
                color: #e2e8f0;
                cursor: pointer;
                font-size: .82rem;
            }
            .sec-pagination button:disabled { opacity: .4; cursor: default; }

            /* ── Empty / loading ─────────────────────────────────────── */
            .sec-empty {
                text-align: center;
                color: #94a3b8;
                padding: 40px 20px;
                font-size: .9rem;
            }
            .sec-loading {
                color: #94a3b8;
                font-size: .86rem;
                padding: 20px 0;
            }

            /* ── Toast ───────────────────────────────────────────────── */
            #sec-toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 8px;
                pointer-events: none;
            }
            .sec-toast-item {
                padding: 10px 18px;
                border-radius: 8px;
                font-size: .86rem;
                font-weight: 650;
                color: #fff;
                pointer-events: none;
                box-shadow: 0 8px 30px rgba(0,0,0,.4);
                opacity: 0;
                transform: translateY(8px);
                transition: opacity .2s, transform .2s;
            }
            .sec-toast-item.show { opacity: 1; transform: none; }
            .sec-toast-item.success { background: #065f46; }
            .sec-toast-item.error   { background: #7f1d1d; }
        </style>

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="sec-header">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                Seguranca — Global Security Layer
            </h2>
            <button class="sec-btn" id="sec-refresh-btn" onclick="window.AdminSecurityPanel.refresh()">
                Atualizar
            </button>
        </div>

        <!-- ── Tabs ────────────────────────────────────────────────────── -->
        <nav class="sec-tabs" id="sec-tabs">
            <button class="sec-tab active" data-sec-tab="dashboard">Dashboard</button>
            <button class="sec-tab" data-sec-tab="threats">Eventos</button>
            <button class="sec-tab" data-sec-tab="bans">Banimentos</button>
            <button class="sec-tab" data-sec-tab="quarantine">Quarentena</button>
            <button class="sec-tab" data-sec-tab="whitelist">Whitelist</button>
            <button class="sec-tab" data-sec-tab="reputation">Reputacao IP</button>
            <button class="sec-tab" data-sec-tab="patterns">Padroes Distr.</button>
            <button class="sec-tab" data-sec-tab="routes">Perfis de Rota</button>
        </nav>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Dashboard
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane active" id="sec-pane-dashboard">
            <div class="sec-kpi-grid" id="sec-kpi-grid">
                <div class="sec-loading">Carregando metricas...</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:4px" id="sec-dash-bottom">
                <!-- Top threats + event breakdown populados via JS -->
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Threat Events
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-threats">
            <!-- Filtros -->
            <div class="sec-inline-form" style="margin-bottom:16px">
                <div class="sec-field">
                    <label>Severidade</label>
                    <select id="sec-threat-severity">
                        <option value="">Todas</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="sec-field">
                    <label>Tipo de evento</label>
                    <input id="sec-threat-type" type="text" placeholder="rate_limit_exceeded...">
                </div>
                <button class="sec-btn" onclick="window.AdminSecurityPanel.loadThreats(0)">Filtrar</button>
            </div>
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Evento</th>
                            <th>Severidade</th>
                            <th>Acao</th>
                            <th>Score</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody id="sec-threats-body">
                        <tr><td colspan="6" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="sec-pagination" id="sec-threats-pagination"></div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Banimentos
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-bans">
            <!-- Criar ban -->
            <div class="sec-inline-form">
                <div class="sec-field">
                    <label>Endereco IP</label>
                    <input id="sec-ban-ip" type="text" placeholder="192.168.0.1">
                </div>
                <div class="sec-field">
                    <label>Tipo</label>
                    <select id="sec-ban-type">
                        <option value="soft">Soft (15 min)</option>
                        <option value="hard">Hard (24 h)</option>
                        <option value="shadow">Shadow (7 dias)</option>
                    </select>
                </div>
                <div class="sec-field">
                    <label>Motivo</label>
                    <input id="sec-ban-reason" type="text" placeholder="Ban manual via painel" style="width:220px">
                </div>
                <button class="sec-btn danger" onclick="window.AdminSecurityPanel.createBan()">Banir IP</button>
            </div>
            <!-- Tabela -->
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Tipo</th>
                            <th>Motivo</th>
                            <th>Score</th>
                            <th>Banido em</th>
                            <th>Expira em</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody id="sec-bans-body">
                        <tr><td colspan="7" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Quarentena
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-quarantine">
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Nivel</th>
                            <th>Delay (ms)</th>
                            <th>Motivo</th>
                            <th>Entrou em</th>
                            <th>Expira em</th>
                        </tr>
                    </thead>
                    <tbody id="sec-quarantine-body">
                        <tr><td colspan="6" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Whitelist
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-whitelist">
            <div class="sec-inline-form">
                <div class="sec-field">
                    <label>Tipo</label>
                    <select id="sec-wl-type">
                        <option value="ip">IP</option>
                        <option value="ip_network">Rede CIDR</option>
                        <option value="user_agent_prefix">User-Agent (prefixo)</option>
                        <option value="asn">ASN</option>
                        <option value="cdn_range">CDN Range</option>
                    </select>
                </div>
                <div class="sec-field">
                    <label>Valor</label>
                    <input id="sec-wl-value" type="text" placeholder="1.2.3.4 ou 10.0.0.0/8">
                </div>
                <div class="sec-field">
                    <label>Descricao</label>
                    <input id="sec-wl-desc" type="text" placeholder="Cloudflare, Monitoring..." style="width:200px">
                </div>
                <button class="sec-btn ok" onclick="window.AdminSecurityPanel.addWhitelist()">Adicionar</button>
            </div>
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Descricao</th>
                            <th>Adicionado por</th>
                            <th>Data</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody id="sec-whitelist-body">
                        <tr><td colspan="6" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Reputacao IP
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-reputation">
            <div class="sec-inline-form">
                <div class="sec-field">
                    <label>Endereco IP</label>
                    <input id="sec-rep-ip" type="text" placeholder="1.2.3.4">
                </div>
                <button class="sec-btn" onclick="window.AdminSecurityPanel.loadReputation()">Consultar</button>
            </div>
            <div id="sec-rep-result" style="color:#94a3b8;font-size:.88rem">
                Digite um IP para consultar.
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Padroes Distribuidos
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-patterns">
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rota</th>
                            <th>IPs envolvidos</th>
                            <th>Requisicoes</th>
                            <th>Status</th>
                            <th>Detectado em</th>
                        </tr>
                    </thead>
                    <tbody id="sec-patterns-body">
                        <tr><td colspan="6" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             PANE: Perfis de Rota
        ════════════════════════════════════════════════════════════════ -->
        <div class="sec-pane" id="sec-pane-routes">
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Limit clean</th>
                            <th>Limit suspeito</th>
                            <th>Limit hostil</th>
                            <th>Burst RPS</th>
                            <th>Critico</th>
                            <th>Challenge</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody id="sec-routes-body">
                        <tr><td colspan="8" class="sec-empty">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Toast container -->
        <div id="sec-toast"></div>

        </section>

        <script>
        (function () {
            'use strict';

            /* ── helpers ────────────────────────────────────────────── */
            const API = '/api/admin/security';

            async function apiFetch(path, opts = {}) {
                const token = localStorage.getItem('pipocine_admin_token') || '';
                const res = await fetch(API + path, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token,
                        ...(opts.headers || {}),
                    },
                    ...opts,
                });
                return res.json();
            }

            function toast(msg, type = 'success') {
                const el = document.createElement('div');
                el.className = 'sec-toast-item ' + type;
                el.textContent = msg;
                document.getElementById('sec-toast').appendChild(el);
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => el.classList.add('show'));
                });
                setTimeout(() => {
                    el.classList.remove('show');
                    setTimeout(() => el.remove(), 300);
                }, 3500);
            }

            function fmt(dateStr) {
                if (!dateStr) return '—';
                return new Date(dateStr).toLocaleString('pt-BR', {
                    day: '2-digit', month: '2-digit', year: '2-digit',
                    hour: '2-digit', minute: '2-digit',
                });
            }

            function badge(text, cls) {
                return `<span class="sec-badge ${cls}">${text}</span>`;
            }

            function scoreBar(score) {
                const pct = Math.min(100, (score / 1000) * 100).toFixed(1);
                const color = score >= 500 ? '#f87171' : score >= 250 ? '#fbbf24' : '#34d399';
                return `<div class="sec-score-bar">
                    <div class="sec-score-track">
                        <div class="sec-score-fill" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <span>${score}</span>
                </div>`;
            }

            /* ── Tab switching ──────────────────────────────────────── */
            const tabLoaded = {};

            document.getElementById('sec-tabs').addEventListener('click', function (e) {
                const btn = e.target.closest('.sec-tab');
                if (!btn) return;
                const tab = btn.dataset.secTab;

                document.querySelectorAll('.sec-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.sec-pane').forEach(p => p.classList.remove('active'));

                btn.classList.add('active');
                document.getElementById('sec-pane-' + tab)?.classList.add('active');

                if (!tabLoaded[tab]) {
                    tabLoaded[tab] = true;
                    loadTab(tab);
                }
            });

            function loadTab(tab) {
                switch (tab) {
                    case 'dashboard':  loadDashboard(); break;
                    case 'threats':    loadThreats(0);  break;
                    case 'bans':       loadBans();      break;
                    case 'quarantine': loadQuarantine(); break;
                    case 'whitelist':  loadWhitelist(); break;
                    case 'patterns':   loadPatterns();  break;
                    case 'routes':     loadRoutes();    break;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               DASHBOARD
            ═══════════════════════════════════════════════════════════ */
            async function loadDashboard() {
                const kpiGrid = document.getElementById('sec-kpi-grid');
                kpiGrid.innerHTML = '<div class="sec-loading">Carregando...</div>';

                try {
                    const data = await apiFetch('/dashboard');
                    if (!data.success) throw new Error(data.error || 'Erro');

                    const m = data.metrics;

                    const kpis = [
                        { label: 'Eventos 24h',     value: m.threat_events_24h,   cls: m.threat_events_24h > 100 ? 'danger' : '' },
                        { label: 'Eventos 1h',      value: m.threat_events_1h,    cls: m.threat_events_1h > 20 ? 'warn' : '' },
                        { label: 'Bans ativos',     value: m.active_bans,         cls: m.active_bans > 0 ? 'danger' : 'ok' },
                        { label: 'Quarentena',      value: m.active_quarantine,   cls: m.active_quarantine > 0 ? 'warn' : '' },
                        { label: 'Criticos 24h',    value: m.critical_events_24h, cls: m.critical_events_24h > 0 ? 'danger' : 'ok' },
                        { label: 'IPs alto risco',  value: m.high_risk_ips,       cls: m.high_risk_ips > 0 ? 'warn' : '' },
                        { label: 'Bots detectados', value: m.bots_detected,       cls: m.bots_detected > 0 ? 'warn' : '' },
                        { label: 'Padr. distribuidos', value: m.active_patterns,  cls: m.active_patterns > 0 ? 'danger' : '' },
                        { label: 'Rate limits 1h',  value: m.rate_limits_1h,      cls: '' },
                    ];

                    kpiGrid.innerHTML = kpis.map(k => `
                        <div class="sec-kpi ${k.cls}">
                            <label>${k.label}</label>
                            <strong>${k.value ?? 0}</strong>
                        </div>
                    `).join('');

                    // Bottom grid
                    const bottom = document.getElementById('sec-dash-bottom');

                    // Top threats table
                    const topRows = (data.top_threats || []).map(r => `
                        <tr>
                            <td><code style="font-size:.82rem">${r.ip_address}</code></td>
                            <td>${scoreBar(r.threat_score)}</td>
                            <td>${r.is_bot_detected ? badge('bot','bot') : '—'}</td>
                            <td>${fmt(r.last_request_at)}</td>
                        </tr>
                    `).join('') || `<tr><td colspan="4" class="sec-empty">Nenhum</td></tr>`;

                    // Event breakdown
                    const evtRows = (data.event_breakdown || []).map(r => `
                        <tr>
                            <td><code style="font-size:.82rem">${r.event_type}</code></td>
                            <td style="font-weight:700;color:#f87171">${r.total}</td>
                        </tr>
                    `).join('') || `<tr><td colspan="2" class="sec-empty">Nenhum</td></tr>`;

                    bottom.innerHTML = `
                        <div>
                            <p style="margin:0 0 10px;font-size:.86rem;font-weight:750;color:#94a3b8">TOP 5 IPS AMEACADORES</p>
                            <div class="sec-table-wrap">
                                <table class="sec-table">
                                    <thead><tr><th>IP</th><th>Score</th><th>Bot</th><th>Ultimo req.</th></tr></thead>
                                    <tbody>${topRows}</tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p style="margin:0 0 10px;font-size:.86rem;font-weight:750;color:#94a3b8">TIPOS DE EVENTO (24H)</p>
                            <div class="sec-table-wrap">
                                <table class="sec-table">
                                    <thead><tr><th>Tipo</th><th>Total</th></tr></thead>
                                    <tbody>${evtRows}</tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } catch (e) {
                    kpiGrid.innerHTML = `<div class="sec-empty">Erro ao carregar: ${e.message}</div>`;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               THREATS
            ═══════════════════════════════════════════════════════════ */
            let threatPage = 0;
            let threatTotalPages = 0;

            async function loadThreats(page = 0) {
                threatPage = page;
                const severity = document.getElementById('sec-threat-severity')?.value || '';
                const type     = document.getElementById('sec-threat-type')?.value     || '';

                const qs = new URLSearchParams({ page, per: 50 });
                if (severity) qs.set('severity', severity);
                if (type)     qs.set('type', type);

                const tbody = document.getElementById('sec-threats-body');
                tbody.innerHTML = '<tr><td colspan="6" class="sec-loading">Carregando...</td></tr>';

                try {
                    const data = await apiFetch('/threats?' + qs);
                    threatTotalPages = data.pages || 0;

                    if (!data.events?.length) {
                        tbody.innerHTML = '<tr><td colspan="6" class="sec-empty">Nenhum evento encontrado.</td></tr>';
                    } else {
                        tbody.innerHTML = data.events.map(e => `
                            <tr>
                                <td><code style="font-size:.82rem">${e.ip_address}</code></td>
                                <td style="max-width:200px;word-break:break-all">${e.event_type}</td>
                                <td>${badge(e.severity, e.severity)}</td>
                                <td>${e.action_taken || '—'}</td>
                                <td>${e.threat_score_at_event ?? '—'}</td>
                                <td>${fmt(e.created_at)}</td>
                            </tr>
                        `).join('');
                    }

                    renderPagination('sec-threats-pagination', threatPage, threatTotalPages,
                        p => loadThreats(p), data.total);
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="6" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               BANS
            ═══════════════════════════════════════════════════════════ */
            async function loadBans() {
                const tbody = document.getElementById('sec-bans-body');
                tbody.innerHTML = '<tr><td colspan="7" class="sec-loading">Carregando...</td></tr>';

                try {
                    const data = await apiFetch('/bans');
                    if (!data.bans?.length) {
                        tbody.innerHTML = '<tr><td colspan="7" class="sec-empty">Nenhum banimento ativo.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.bans.map(b => `
                        <tr>
                            <td><code style="font-size:.82rem">${b.ip_address}</code></td>
                            <td>${badge(b.ban_type, b.ban_type)}</td>
                            <td style="max-width:200px">${b.reason || '—'}</td>
                            <td>${b.threat_score_at_ban ?? '—'}</td>
                            <td>${fmt(b.banned_at)}</td>
                            <td>${b.expires_at ? fmt(b.expires_at) : 'Permanente'}</td>
                            <td>
                                <button class="sec-btn ok" onclick="window.AdminSecurityPanel.liftBan(${b.id})">
                                    Remover
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="7" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            async function createBan() {
                const ip     = document.getElementById('sec-ban-ip').value.trim();
                const type   = document.getElementById('sec-ban-type').value;
                const reason = document.getElementById('sec-ban-reason').value.trim() || 'Ban manual via painel';

                if (!ip) { toast('Informe o IP.', 'error'); return; }

                const durationMap = { soft: 900, hard: 86400, shadow: 604800 };

                try {
                    const data = await apiFetch('/bans/create', {
                        method: 'POST',
                        body: JSON.stringify({ ip_address: ip, ban_type: type, reason, duration_seconds: durationMap[type] }),
                    });
                    if (!data.success) throw new Error(data.error);
                    toast(data.message || 'IP banido.');
                    document.getElementById('sec-ban-ip').value = '';
                    tabLoaded.bans = false;
                    loadBans();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            async function liftBan(id) {
                if (!confirm('Remover este banimento?')) return;
                try {
                    const data = await apiFetch('/bans/' + id + '/lift', {
                        method: 'POST',
                        body: JSON.stringify({ lifted_by: 'admin-panel' }),
                    });
                    if (!data.success) throw new Error(data.error);
                    toast('Ban removido.');
                    loadBans();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            /* ═══════════════════════════════════════════════════════════
               QUARANTINE
            ═══════════════════════════════════════════════════════════ */
            async function loadQuarantine() {
                const tbody = document.getElementById('sec-quarantine-body');
                tbody.innerHTML = '<tr><td colspan="6" class="sec-loading">Carregando...</td></tr>';
                try {
                    const data = await apiFetch('/quarantine');
                    if (!data.quarantine?.length) {
                        tbody.innerHTML = '<tr><td colspan="6" class="sec-empty">Nenhum IP em quarentena.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.quarantine.map(q => `
                        <tr>
                            <td><code style="font-size:.82rem">${q.ip_address}</code></td>
                            <td>${q.quarantine_level ?? '—'}</td>
                            <td>${q.delay_ms ?? '—'}</td>
                            <td style="max-width:200px">${q.reason || '—'}</td>
                            <td>${fmt(q.entered_at)}</td>
                            <td>${q.expires_at ? fmt(q.expires_at) : '—'}</td>
                        </tr>
                    `).join('');
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="6" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               WHITELIST
            ═══════════════════════════════════════════════════════════ */
            async function loadWhitelist() {
                const tbody = document.getElementById('sec-whitelist-body');
                tbody.innerHTML = '<tr><td colspan="6" class="sec-loading">Carregando...</td></tr>';
                try {
                    const data = await apiFetch('/whitelist');
                    if (!data.whitelist?.length) {
                        tbody.innerHTML = '<tr><td colspan="6" class="sec-empty">Whitelist vazia.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.whitelist.map(w => `
                        <tr>
                            <td>${badge(w.entry_type, 'low')}</td>
                            <td><code style="font-size:.82rem">${w.entry_value}</code></td>
                            <td>${w.description || '—'}</td>
                            <td>${w.added_by || '—'}</td>
                            <td>${fmt(w.created_at)}</td>
                            <td>
                                <button class="sec-btn danger" onclick="window.AdminSecurityPanel.removeWhitelist(${w.id})">
                                    Remover
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="6" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            async function addWhitelist() {
                const type  = document.getElementById('sec-wl-type').value;
                const value = document.getElementById('sec-wl-value').value.trim();
                const desc  = document.getElementById('sec-wl-desc').value.trim();
                if (!value) { toast('Informe o valor.', 'error'); return; }
                try {
                    const data = await apiFetch('/whitelist/add', {
                        method: 'POST',
                        body: JSON.stringify({ entry_type: type, entry_value: value, description: desc }),
                    });
                    if (!data.success) throw new Error(data.error);
                    toast('Adicionado a whitelist.');
                    document.getElementById('sec-wl-value').value = '';
                    document.getElementById('sec-wl-desc').value  = '';
                    tabLoaded.whitelist = false;
                    loadWhitelist();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            async function removeWhitelist(id) {
                if (!confirm('Remover esta entrada da whitelist?')) return;
                try {
                    const data = await apiFetch('/whitelist/' + id + '/remove', { method: 'POST', body: '{}' });
                    if (!data.success) throw new Error(data.error);
                    toast('Entrada removida.');
                    loadWhitelist();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            /* ═══════════════════════════════════════════════════════════
               REPUTATION
            ═══════════════════════════════════════════════════════════ */
            async function loadReputation() {
                const ip = document.getElementById('sec-rep-ip').value.trim();
                if (!ip) { toast('Informe um IP.', 'error'); return; }

                const el = document.getElementById('sec-rep-result');
                el.innerHTML = '<div class="sec-loading">Consultando...</div>';

                try {
                    const data = await apiFetch('/reputation/' + encodeURIComponent(ip));
                    if (!data.success) throw new Error(data.error);

                    const r = data.reputation;
                    const b = data.active_ban;
                    const evts = data.events || [];

                    el.innerHTML = `
                        <div style="display:grid;gap:14px">
                            ${r ? `
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px">
                                <div class="sec-kpi ${r.threat_score >= 500 ? 'danger' : r.threat_score >= 250 ? 'warn' : 'ok'}">
                                    <label>Score</label><strong>${r.threat_score}</strong>
                                </div>
                                <div class="sec-kpi">
                                    <label>Nivel mitigacao</label><strong>${r.mitigation_level}</strong>
                                </div>
                                <div class="sec-kpi">
                                    <label>Bot detectado</label><strong>${r.is_bot_detected ? 'Sim' : 'Nao'}</strong>
                                </div>
                                <div class="sec-kpi">
                                    <label>Req. 1 min</label><strong>${r.req_count_1min ?? 0}</strong>
                                </div>
                                <div class="sec-kpi">
                                    <label>Req. 1 hora</label><strong>${r.req_count_1hour ?? 0}</strong>
                                </div>
                                <div class="sec-kpi">
                                    <label>Rotas sensiveis</label><strong>${r.sensitive_route_hits ?? 0}</strong>
                                </div>
                            </div>` : '<p style="color:#94a3b8">Nenhum registro de reputacao para este IP.</p>'}
                            ${b ? `<div style="padding:12px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#f87171;font-size:.86rem">
                                <strong>Ban ativo:</strong> ${b.ban_type} — ${b.reason} — Expira: ${b.expires_at ? fmt(b.expires_at) : 'Permanente'}
                            </div>` : ''}
                            ${evts.length ? `
                            <div>
                                <p style="margin:0 0 8px;font-size:.82rem;font-weight:750;color:#94a3b8">ULTIMOS 10 EVENTOS</p>
                                <div class="sec-table-wrap">
                                    <table class="sec-table">
                                        <thead><tr><th>Evento</th><th>Severidade</th><th>Acao</th><th>Score</th><th>Data</th></tr></thead>
                                        <tbody>${evts.map(e => `
                                            <tr>
                                                <td><code style="font-size:.81rem">${e.event_type}</code></td>
                                                <td>${badge(e.severity, e.severity)}</td>
                                                <td>${e.action_taken || '—'}</td>
                                                <td>${e.threat_score_at_event ?? '—'}</td>
                                                <td>${fmt(e.created_at)}</td>
                                            </tr>
                                        `).join('')}</tbody>
                                    </table>
                                </div>
                            </div>` : ''}
                        </div>
                    `;
                } catch (e) {
                    el.innerHTML = `<div class="sec-empty">Erro: ${e.message}</div>`;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               DISTRIBUTED PATTERNS
            ═══════════════════════════════════════════════════════════ */
            async function loadPatterns() {
                const tbody = document.getElementById('sec-patterns-body');
                tbody.innerHTML = '<tr><td colspan="6" class="sec-loading">Carregando...</td></tr>';
                try {
                    const data = await apiFetch('/patterns');
                    if (!data.patterns?.length) {
                        tbody.innerHTML = '<tr><td colspan="6" class="sec-empty">Nenhum padrao distribuido ativo.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.patterns.map(p => `
                        <tr>
                            <td>${p.id}</td>
                            <td>${p.target_route_group || '—'}</td>
                            <td>${p.ip_count ?? '—'}</td>
                            <td>${p.total_requests ?? '—'}</td>
                            <td>${badge(p.status, p.status === 'active' ? 'danger' : 'low')}</td>
                            <td>${fmt(p.first_detected_at)}</td>
                        </tr>
                    `).join('');
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="6" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            /* ═══════════════════════════════════════════════════════════
               ROUTE PROFILES
            ═══════════════════════════════════════════════════════════ */
            async function loadRoutes() {
                const tbody = document.getElementById('sec-routes-body');
                tbody.innerHTML = '<tr><td colspan="8" class="sec-loading">Carregando...</td></tr>';
                try {
                    const data = await apiFetch('/route-profiles');
                    if (!data.profiles?.length) {
                        tbody.innerHTML = '<tr><td colspan="8" class="sec-empty">Nenhum perfil encontrado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.profiles.map(p => `
                        <tr>
                            <td><strong>${p.route_group}</strong></td>
                            <td>${p.rate_limit_clean}</td>
                            <td>${p.rate_limit_suspicious}</td>
                            <td>${p.rate_limit_hostile}</td>
                            <td>${p.burst_threshold_rps}</td>
                            <td>${p.is_critical ? badge('sim','danger') : '—'}</td>
                            <td>${p.requires_challenge ? badge('sim','warn') : '—'}</td>
                            <td>
                                <button class="sec-btn" onclick="window.AdminSecurityPanel.editRoute('${p.route_group}', ${p.rate_limit_clean}, ${p.rate_limit_suspicious}, ${p.rate_limit_hostile})">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="8" class="sec-empty">Erro: ${e.message}</td></tr>`;
                }
            }

            async function editRoute(group, clean, suspicious, hostile) {
                const nc = prompt(`[${group}] Rate limit CLEAN (atual: ${clean}):`, clean);
                if (nc === null) return;
                const ns = prompt(`[${group}] Rate limit SUSPEITO (atual: ${suspicious}):`, suspicious);
                if (ns === null) return;
                const nh = prompt(`[${group}] Rate limit HOSTIL (atual: ${hostile}):`, hostile);
                if (nh === null) return;

                try {
                    const data = await apiFetch('/route-profiles/update', {
                        method: 'POST',
                        body: JSON.stringify({
                            route_group: group,
                            rate_limit_clean: parseInt(nc) || clean,
                            rate_limit_suspicious: parseInt(ns) || suspicious,
                            rate_limit_hostile: parseInt(nh) || hostile,
                        }),
                    });
                    if (!data.success) throw new Error(data.error);
                    toast(data.message || 'Perfil atualizado.');
                    tabLoaded.routes = false;
                    loadRoutes();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            /* ═══════════════════════════════════════════════════════════
               PAGINATION
            ═══════════════════════════════════════════════════════════ */
            function renderPagination(elId, page, totalPages, onGo, total) {
                const el = document.getElementById(elId);
                if (!el) return;
                if (totalPages <= 1) { el.innerHTML = `<span>Total: ${total}</span>`; return; }
                el.innerHTML = `
                    <button ${page === 0 ? 'disabled' : ''} onclick="(${onGo})(${page - 1})">&#8249;</button>
                    <span>Pag. ${page + 1} / ${totalPages} &nbsp;(${total} registros)</span>
                    <button ${page >= totalPages - 1 ? 'disabled' : ''} onclick="(${onGo})(${page + 1})">&#8250;</button>
                `;
            }

            /* ═══════════════════════════════════════════════════════════
               PUBLIC API
            ═══════════════════════════════════════════════════════════ */
            window.AdminSecurityPanel = {
                refresh() {
                    Object.keys(tabLoaded).forEach(k => delete tabLoaded[k]);
                    const activeTab = document.querySelector('.sec-tab.active')?.dataset?.secTab || 'dashboard';
                    loadTab(activeTab);
                    toast('Dados atualizados.');
                },
                loadThreats,
                createBan,
                liftBan,
                addWhitelist,
                removeWhitelist,
                loadReputation,
                editRoute,
            };

            /* ── Auto-load quando a rota security for ativada ──────── */
            // Observa quando o painel fica visivel (setAdminRoute remove hidden)
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (m.attributeName === 'hidden' && !m.target.hidden) {
                        if (!tabLoaded.dashboard) {
                            tabLoaded.dashboard = true;
                            loadDashboard();
                        }
                    }
                });
            });
            const pane = document.querySelector('[data-admin-route="security"]');
            if (pane) observer.observe(pane, { attributes: true });

        })();
        </script>
        <?php
    }
}
