<?php
declare(strict_types=1);

final class AdminPlayerLogsPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .player-logs-page { display: grid; gap: 14px; }
            .player-log-list {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
                padding: 14px;
            }
            .player-log-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 14px;
                padding: 14px 0;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
            }
            .player-log-row:last-child { border-bottom: 0; }
            .player-log-title {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #fff;
                font-weight: 800;
                overflow-wrap: anywhere;
            }
            .player-log-meta {
                margin-top: 6px;
                color: #94a3b8;
                font-size: .78rem;
                overflow-wrap: anywhere;
            }
            .player-log-tech {
                margin-top: 8px;
                color: #cbd5e1;
                font-size: .82rem;
                line-height: 1.5;
                overflow-wrap: anywhere;
            }
            .player-log-badges {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                margin-top: 10px;
            }
            .player-log-badge {
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 999px;
                color: #cbd5e1;
                background: rgba(148, 163, 184, .08);
                padding: 4px 8px;
                font-size: .72rem;
                font-weight: 800;
            }
            .player-log-badge.error,
            .player-log-badge.fatal {
                border-color: rgba(229, 9, 20, .32);
                color: #fecaca;
                background: rgba(229, 9, 20, .12);
            }
            .player-log-actions {
                display: flex;
                align-items: flex-start;
                gap: 6px;
            }
            .player-log-action {
                min-height: 34px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #111827;
                color: #e2e8f0;
                cursor: pointer;
                padding: 0 10px;
                font-weight: 780;
            }
            .player-log-action:hover {
                color: #fff;
                border-color: rgba(229, 9, 20, .36);
            }
            .player-stage-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px;
                color: #cbd5e1;
                padding: 9px 0;
                border-bottom: 1px solid rgba(148, 163, 184, .10);
            }
            .player-stage-row:last-child { border-bottom: 0; }
            @media (max-width: 720px) {
                .player-log-row { grid-template-columns: 1fr; }
                .player-log-actions { flex-wrap: wrap; }
            }
        </style>

        <section data-admin-route="player-logs" class="admin-route-panel" hidden>
            <div class="player-logs-page" id="admin-player-logs-root">
                <div class="admin-metrics-toolbar">
                    <div>
                        <h2>Player logs</h2>
                        <p style="margin:5px 0 0;color:#94a3b8">Falhas reportadas automaticamente pelo player de video.</p>
                    </div>
                    <div class="admin-metrics-filter">
                        <button class="admin-metrics-filter-btn" type="button" id="player-logs-filter-btn">
                            <i data-lucide="calendar-range"></i>
                            <span id="player-logs-filter-label">1 semana</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="admin-metrics-menu" id="player-logs-menu">
                            <button type="button" data-player-logs-range="1d">1 dia</button>
                            <button type="button" data-player-logs-range="5d">5 dias</button>
                            <button class="active" type="button" data-player-logs-range="1w">1 semana</button>
                            <button type="button" data-player-logs-range="1m">1 mes</button>
                            <button type="button" data-player-logs-range="2m">2 meses</button>
                            <button type="button" data-player-logs-range="1y">1 ano</button>
                        </div>
                    </div>
                </div>

                <div class="admin-metrics-grid">
                    <article class="admin-metric-card"><span><i data-lucide="monitor-play"></i>Total</span><strong data-player-log-metric="total_logs">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="triangle-alert"></i>Erros</span><strong data-player-log-metric="errors">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="inbox"></i>Abertos</span><strong data-player-log-metric="open_logs">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="users"></i>Usuarios afetados</span><strong data-player-log-metric="affected_users">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="smartphone"></i>Navegador interno</span><strong data-player-log-metric="embedded_browser">-</strong></article>
                    <article class="admin-metric-card"><span><i data-lucide="shield-alert"></i>VPN suspeita</span><strong data-player-log-metric="vpn_suspected">-</strong></article>
                </div>

                <div class="admin-metrics-charts">
                    <article class="admin-routes-card">
                        <h3>Falhas por etapa</h3>
                        <div id="player-log-stages"></div>
                    </article>
                    <article class="admin-routes-card">
                        <h3>Leitura operacional</h3>
                        <p style="color:#94a3b8;line-height:1.7;margin:0">Priorize erros em <strong style="color:#fff">api_response</strong>, <strong style="color:#fff">hls_error</strong> e <strong style="color:#fff">video_element_error</strong>. Muitos registros em <strong style="color:#fff">embedded_browser</strong> normalmente indicam abertura via apps sociais.</p>
                    </article>
                </div>

                <article class="player-log-list">
                    <h3 style="margin:0 0 10px;color:#fff;font-size:1rem">Registros recentes</h3>
                    <div id="player-log-list"></div>
                </article>
            </div>
        </section>

        <script>
            (function () {
                const root = document.getElementById('admin-player-logs-root');
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

                async function api(path, options = {}) {
                    const session = await fetch('/api/admin/session');
                    if (!session.ok) throw new Error('Sessao expirada.');
                    const response = await fetch('/api/admin/' + path, options);
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.error || 'Falha ao carregar logs.');
                    return data;
                }

                function renderStages(stages) {
                    const target = document.getElementById('player-log-stages');
                    target.innerHTML = (stages || []).map(stage => `
                        <div class="player-stage-row">
                            <span>${esc(stage.stage || 'unknown')}</span>
                            <strong>${number(stage.total)}</strong>
                        </div>
                    `).join('') || '<p style="color:#94a3b8;margin:0">Nenhuma falha registrada.</p>';
                }

                function renderLogs(logs) {
                    const target = document.getElementById('player-log-list');
                    target.innerHTML = (logs || []).map(log => {
                        const title = log.content_title || 'Conteudo nao informado';
                        const content = `${esc(title)}${log.season_number ? ` · T${esc(log.season_number)}E${esc(log.episode_number)}` : ''}${log.content_id ? ` · ${esc(log.content_type)} #${esc(log.content_id)}` : ''}`;
                        return `
                            <div class="player-log-row">
                                <div>
                                    <div class="player-log-title">
                                        <i data-lucide="circle-alert"></i>
                                        ${esc(log.error_title || 'Erro no player')}
                                    </div>
                                    <div class="player-log-meta">${content} · ${esc(log.stage)} · ${esc(log.audio || '-')} · ${esc(log.created_at)}</div>
                                    <div class="player-log-tech">${esc(log.technical_message || log.error_message || 'Sem detalhe tecnico.')}</div>
                                    <div class="player-log-badges">
                                        <span class="player-log-badge ${esc(log.severity)}">${esc(log.severity)}</span>
                                        <span class="player-log-badge">${esc(log.status)}</span>
                                        ${Number(log.is_embedded_browser) ? '<span class="player-log-badge">navegador interno</span>' : ''}
                                        ${Number(log.is_vpn_suspected) ? '<span class="player-log-badge">vpn suspeita</span>' : ''}
                                        <span class="player-log-badge">${esc(log.browser_name || 'browser ?')}</span>
                                    </div>
                                </div>
                                <div class="player-log-actions">
                                    <button class="player-log-action" type="button" data-log-status="reviewing" data-log-id="${esc(log.id)}">Revisar</button>
                                    <button class="player-log-action" type="button" data-log-status="resolved" data-log-id="${esc(log.id)}">Resolver</button>
                                </div>
                            </div>
                        `;
                    }).join('') || '<p style="color:#94a3b8;margin:0">Nenhum log no periodo.</p>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function load() {
                    const data = await api('player-logs/dashboard?range=' + encodeURIComponent(range));
                    const summary = data.summary || {};
                    ['total_logs', 'errors', 'open_logs', 'affected_users', 'embedded_browser', 'vpn_suspected'].forEach(key => {
                        const target = root.querySelector(`[data-player-log-metric="${key}"]`);
                        if (target) target.textContent = number(summary[key]);
                    });
                    renderStages(data.stages || []);
                    renderLogs(data.logs || []);
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                document.addEventListener('click', async event => {
                    const btn = event.target.closest('#player-logs-filter-btn');
                    const menu = document.getElementById('player-logs-menu');
                    if (btn) menu.classList.toggle('active');

                    const option = event.target.closest('[data-player-logs-range]');
                    if (option) {
                        range = option.dataset.playerLogsRange || '1w';
                        document.getElementById('player-logs-filter-label').textContent = labels[range] || '1 semana';
                        menu.querySelectorAll('[data-player-logs-range]').forEach(item => item.classList.toggle('active', item === option));
                        menu.classList.remove('active');
                        load();
                    }

                    const statusBtn = event.target.closest('[data-log-status]');
                    if (statusBtn) {
                        await api('player-logs/status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: statusBtn.dataset.logId, status: statusBtn.dataset.logStatus })
                        });
                        load();
                    }

                    if (!event.target.closest('.admin-metrics-filter')) {
                        menu.classList.remove('active');
                    }
                });

                window.AdminPlayerLogsPanel = {
                    load: () => {
                        load();
                        clearInterval(timer);
                        timer = setInterval(load, 15000);
                    }
                };
            })();
        </script>
        <?php
    }
}
