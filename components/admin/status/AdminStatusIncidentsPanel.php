<?php
declare(strict_types=1);

final class AdminStatusIncidentsPanel
{
    public static function render(): void
    {
        ?>
        <section data-admin-route="status-incidents" class="admin-route-panel" hidden>
            <style>
                .asi-shell { display: grid; gap: 16px; }
                .asi-head {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 14px;
                    flex-wrap: wrap;
                }
                .asi-head h2 { margin: 0; color: #fff; font-size: 1.2rem; }
                .asi-head p { margin: 6px 0 0; color: #94a3b8; line-height: 1.5; }
                .asi-actions { display: flex; gap: 10px; flex-wrap: wrap; }
                .asi-btn {
                    min-height: 38px;
                    border: 1px solid rgba(148,163,184,.18);
                    border-radius: 8px;
                    background: #10151f;
                    color: #e2e8f0;
                    padding: 0 13px;
                    font-weight: 750;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                .asi-btn svg { width: 16px; height: 16px; }
                .asi-btn.primary { background: #e50914; border-color: rgba(229,9,20,.55); color: #fff; }
                .asi-btn.ghost { background: transparent; }
                .asi-btn.warn { color: #fde68a; border-color: rgba(245,158,11,.35); }
                .asi-btn.danger { color: #fecaca; border-color: rgba(248,113,113,.45); }
                .asi-btn:disabled { opacity:.55; cursor: wait; }
                .asi-layout {
                    display: grid;
                    grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
                    gap: 16px;
                    align-items: start;
                }
                .asi-systems-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) minmax(340px, .55fr);
                    gap: 16px;
                    align-items: start;
                }
                .asi-panel {
                    border: 1px solid rgba(148,163,184,.16);
                    border-radius: 10px;
                    background: #0f131a;
                    overflow: hidden;
                }
                .asi-panel-head {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 12px;
                    padding: 14px;
                    border-bottom: 1px solid rgba(148,163,184,.12);
                }
                .asi-panel-head strong { color: #fff; }
                .asi-filters {
                    display: grid;
                    grid-template-columns: repeat(8, minmax(120px, 1fr));
                    gap: 10px;
                    padding: 14px;
                    border-bottom: 1px solid rgba(148,163,184,.12);
                }
                .asi-input,
                .asi-select,
                .asi-textarea {
                    width: 100%;
                    border: 1px solid rgba(148,163,184,.18);
                    border-radius: 8px;
                    background: #080b11;
                    color: #fff;
                    font: inherit;
                }
                .asi-input,
                .asi-select { min-height: 40px; padding: 0 11px; }
                .asi-textarea { min-height: 90px; padding: 10px 11px; resize: vertical; line-height: 1.45; }
                .asi-table-wrap { overflow:auto; }
                .asi-table { width: 100%; border-collapse: collapse; min-width: 980px; }
                .asi-table th,
                .asi-table td {
                    padding: 12px 14px;
                    border-bottom: 1px solid rgba(148,163,184,.1);
                    text-align: left;
                    vertical-align: top;
                    font-size: .86rem;
                }
                .asi-table th {
                    color: #94a3b8;
                    font-size: .72rem;
                    text-transform: uppercase;
                    letter-spacing: .08em;
                    background: #0b0f16;
                }
                .asi-title { color:#fff; font-weight:800; }
                .asi-sub { display:block; margin-top:5px; color:#64748b; font-size:.78rem; }
                .asi-badge {
                    display:inline-flex;
                    align-items:center;
                    min-height:24px;
                    padding:0 9px;
                    border-radius:999px;
                    border:1px solid rgba(148,163,184,.18);
                    color:#cbd5e1;
                    background:rgba(15,23,42,.66);
                    font-size:.72rem;
                    font-weight:800;
                    white-space: nowrap;
                }
                .asi-badge.ok { color:#bbf7d0; border-color:rgba(34,197,94,.28); background:rgba(20,83,45,.22); }
                .asi-badge.warn { color:#fde68a; border-color:rgba(245,158,11,.3); background:rgba(113,63,18,.22); }
                .asi-badge.danger { color:#fecaca; border-color:rgba(248,113,113,.34); background:rgba(127,29,29,.26); }
                .asi-badge.maintenance { color:#ddd6fe; border-color:rgba(139,92,246,.38); background:rgba(76,29,149,.24); }
                .asi-mini { display:flex; flex-wrap:wrap; gap:7px; }
                .asi-detail { padding: 14px; display:grid; gap:14px; }
                .asi-detail h3 { margin:0; color:#fff; }
                .asi-detail p { margin:0; color:#94a3b8; line-height:1.55; }
                .asi-kpis { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; }
                .asi-kpi { border:1px solid rgba(148,163,184,.12); border-radius:8px; padding:11px; background:#090e15; }
                .asi-kpi span { display:block; color:#64748b; font-size:.74rem; }
                .asi-kpi strong { display:block; margin-top:5px; color:#fff; }
                .asi-timeline { display:grid; gap:10px; padding-left:14px; border-left:1px solid rgba(148,163,184,.16); }
                .asi-event { position:relative; border:1px solid rgba(148,163,184,.11); border-radius:8px; padding:10px; background:#090e15; }
                .asi-event:before { content:""; position:absolute; left:-19px; top:13px; width:9px; height:9px; border-radius:50%; background:#38bdf8; }
                .asi-event strong { color:#fff; }
                .asi-event small { display:block; margin-top:4px; color:#64748b; }
                .asi-empty { padding:24px; color:#94a3b8; text-align:center; }
                .asi-systems-list { display:grid; gap:0; }
                .asi-system-row {
                    display:grid;
                    grid-template-columns: minmax(0, 1fr) auto;
                    gap:12px;
                    align-items:center;
                    padding:14px;
                    border-bottom:1px solid rgba(148,163,184,.1);
                }
                .asi-system-row:last-child { border-bottom:0; }
                .asi-system-row.child { margin-left:26px; border-left:1px solid rgba(148,163,184,.14); }
                .asi-system-name { color:#fff; font-weight:800; }
                .asi-system-meta { display:block; margin-top:5px; color:#64748b; font-size:.78rem; }
                .asi-system-dot {
                    display:inline-flex;
                    width:9px;
                    height:9px;
                    margin-right:8px;
                    border-radius:50%;
                    background:#38bdf8;
                }
                .asi-system-dot.private { background:#64748b; }
                .asi-component-form {
                    display:grid;
                    gap:12px;
                    padding:14px;
                }
                .asi-modal[hidden] { display:none; }
                .asi-modal {
                    position:fixed;
                    inset:0;
                    z-index:70;
                    display:grid;
                    place-items:center;
                    padding:20px;
                    background:rgba(3,6,12,.78);
                }
                .asi-dialog {
                    width:min(760px,100%);
                    max-height:min(92vh,860px);
                    overflow:auto;
                    border:1px solid rgba(148,163,184,.2);
                    border-radius:8px;
                    background:#0d1117;
                    padding:22px;
                    box-shadow:0 24px 70px rgba(0,0,0,.46);
                }
                .asi-dialog h3 { margin:0 0 14px; color:#fff; }
                .asi-form { display:grid; gap:12px; }
                .asi-form-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px; }
                .asi-form label { display:grid; gap:7px; color:#94a3b8; font-size:.82rem; font-weight:760; }
                .asi-checks { display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:8px; }
                .asi-check {
                    display:flex !important;
                    align-items:center;
                    gap:8px !important;
                    border:1px solid rgba(148,163,184,.14);
                    border-radius:8px;
                    padding:9px;
                    background:#090e15;
                }
                .asi-dialog-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:10px; margin-top:4px; }
                @media (max-width: 1180px) {
                    .asi-layout,
                    .asi-systems-grid,
                    .asi-filters,
                    .asi-form-grid,
                    .asi-checks { grid-template-columns:1fr; }
                }
            </style>

            <div class="asi-shell">
                <div class="asi-head">
                    <div>
                        <h2>Gerenciamento de Incidentes</h2>
                        <p>CRUD completo, timeline publica, logs internos e reflexo automatico na pagina publica de status.</p>
                    </div>
                    <div class="asi-actions">
                        <a class="asi-btn ghost" href="/status" target="_blank"><i data-lucide="external-link"></i>Status publico</a>
                        <button class="asi-btn primary" type="button" id="asi-new"><i data-lucide="plus"></i>Novo incidente</button>
                        <button class="asi-btn ghost" type="button" id="asi-refresh"><i data-lucide="refresh-cw"></i>Atualizar</button>
                    </div>
                </div>

                <div class="asi-layout">
                    <article class="asi-panel">
                        <div class="asi-panel-head">
                            <strong>Incidentes</strong>
                            <span class="asi-badge" id="asi-count">0</span>
                        </div>
                        <form class="asi-filters" id="asi-filters">
                            <select class="asi-select" name="status">
                                <option value="all">Status</option>
                                <option value="investigating">Investigating</option>
                                <option value="identified">Identified</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="resolved">Resolved</option>
                                <option value="archived">Archived</option>
                            </select>
                            <select class="asi-select" name="impact">
                                <option value="all">Impacto</option>
                                <option value="degraded_performance">Degraded Performance</option>
                                <option value="partial_outage">Partial Outage</option>
                                <option value="major_outage">Major Outage</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="security_incident">Security Incident</option>
                                <option value="api_degradation">API Degradation</option>
                                <option value="database_incident">Database Incident</option>
                                <option value="third_party_provider_issue">Third-Party Provider Issue</option>
                            </select>
                            <select class="asi-select" name="incident_type" id="asi-filter-type">
                                <option value="all">Tipo</option>
                                <option value="incident">Incident</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="security">Security</option>
                                <option value="api">API</option>
                                <option value="database">Database</option>
                                <option value="third_party">Third-party</option>
                            </select>
                            <select class="asi-select" name="component_id" id="asi-filter-component"><option value="">Sistema</option></select>
                            <input class="asi-input" type="date" name="from">
                            <input class="asi-input" type="date" name="to">
                            <input class="asi-input" type="number" min="1" name="owner_admin_id" placeholder="Responsavel ID">
                            <button class="asi-btn ghost" type="submit"><i data-lucide="filter"></i>Filtrar</button>
                        </form>
                        <div class="asi-table-wrap">
                            <table class="asi-table">
                                <thead>
                                    <tr>
                                        <th>Incidente</th>
                                        <th>Status</th>
                                        <th>Impacto</th>
                                        <th>Sistemas</th>
                                        <th>Duracao</th>
                                        <th>Acoes</th>
                                    </tr>
                                </thead>
                                <tbody id="asi-body"><tr><td colspan="6" class="asi-empty">Carregando...</td></tr></tbody>
                            </table>
                        </div>
                    </article>

                    <aside class="asi-panel">
                        <div class="asi-panel-head">
                            <strong>Detalhes operacionais</strong>
                            <button class="asi-btn ghost" type="button" id="asi-publish" disabled><i data-lucide="send"></i>Publicar update</button>
                        </div>
                        <div class="asi-detail" id="asi-detail">
                            <div class="asi-empty">Selecione um incidente.</div>
                        </div>
                    </aside>
                </div>

                <section class="asi-systems-grid">
                    <article class="asi-panel">
                        <div class="asi-panel-head">
                            <strong>Sistemas e subsistemas</strong>
                            <button class="asi-btn ghost" type="button" id="asi-new-system"><i data-lucide="plus"></i>Novo sistema</button>
                        </div>
                        <div class="asi-systems-list" id="asi-systems-list">
                            <div class="asi-empty">Carregando sistemas...</div>
                        </div>
                    </article>

                    <aside class="asi-panel">
                        <div class="asi-panel-head">
                            <strong>Editar sistema</strong>
                            <span class="asi-badge">Componentes publicos</span>
                        </div>
                        <form class="asi-component-form" id="asi-component-form">
                            <input type="hidden" name="id">
                            <label>Nome
                                <input class="asi-input" name="name" placeholder="Edge Network" required maxlength="140">
                            </label>
                            <label>Chave
                                <input class="asi-input" name="component_key" placeholder="edge-network" maxlength="90">
                            </label>
                            <label>Sistema pai
                                <select class="asi-select" name="parent_id" id="asi-component-parent"><option value="">Nenhum</option></select>
                            </label>
                            <label>Descricao
                                <textarea class="asi-textarea" name="description" placeholder="Descricao curta para administracao e status publico."></textarea>
                            </label>
                            <div class="asi-form-grid">
                                <label>Ordem
                                    <input class="asi-input" type="number" name="sort_order" value="0">
                                </label>
                                <label>Publico
                                    <select class="asi-select" name="is_public">
                                        <option value="1">Sim</option>
                                        <option value="0">Nao</option>
                                    </select>
                                </label>
                                <label>Critico
                                    <select class="asi-select" name="is_critical">
                                        <option value="1">Sim</option>
                                        <option value="0">Nao</option>
                                    </select>
                                </label>
                            </div>
                            <div class="asi-dialog-actions">
                                <button class="asi-btn ghost" type="button" id="asi-component-clear">Limpar</button>
                                <button class="asi-btn primary" type="submit"><i data-lucide="save"></i>Salvar sistema</button>
                            </div>
                        </form>
                    </aside>
                </section>
            </div>

            <div class="asi-modal" id="asi-incident-modal" hidden>
                <form class="asi-dialog asi-form" id="asi-incident-form">
                    <h3 id="asi-modal-title">Novo incidente</h3>
                    <input type="hidden" name="id">
                    <label>
                        Titulo
                        <input class="asi-input" name="title" required maxlength="180">
                    </label>
                    <div class="asi-form-grid">
                        <label>Tipo
                            <select class="asi-select" name="incident_type">
                                <option value="incident">Incident</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="security">Security</option>
                                <option value="network">Network</option>
                                <option value="api">API</option>
                                <option value="database">Database</option>
                                <option value="third_party">Third-party</option>
                            </select>
                        </label>
                        <label>Status
                            <select class="asi-select" name="status">
                                <option value="investigating">Investigating</option>
                                <option value="identified">Identified</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="resolved">Resolved</option>
                                <option value="archived">Archived</option>
                            </select>
                        </label>
                        <label>Impacto
                            <select class="asi-select" name="impact" id="asi-impact">
                                <option value="degraded_performance">Degraded Performance</option>
                                <option value="partial_outage">Partial Outage</option>
                                <option value="major_outage">Major Outage</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="security_incident">Security Incident</option>
                                <option value="network_incident">Network Incident</option>
                                <option value="api_degradation">API Degradation</option>
                                <option value="database_incident">Database Incident</option>
                                <option value="third_party_provider_issue">Third-Party Provider Issue</option>
                            </select>
                        </label>
                        <label>Categoria
                            <input class="asi-input" name="category" value="Degraded Performance">
                        </label>
                        <label>Visibilidade
                            <select class="asi-select" name="visibility">
                                <option value="public">Publica</option>
                                <option value="private">Privada</option>
                            </select>
                        </label>
                        <label>Responsavel admin ID
                            <input class="asi-input" name="owner_admin_id" type="number" min="1">
                        </label>
                        <label>Inicio
                            <input class="asi-input" name="started_at" type="datetime-local">
                        </label>
                        <label>Resolucao
                            <input class="asi-input" name="resolved_at" type="datetime-local">
                        </label>
                        <label>Inicio manutencao
                            <input class="asi-input" name="scheduled_start_at" type="datetime-local">
                        </label>
                        <label>Fim manutencao
                            <input class="asi-input" name="scheduled_end_at" type="datetime-local">
                        </label>
                    </div>
                    <label>
                        Sistemas afetados
                        <input class="asi-input" name="systems_affected" placeholder="API, Web App, Database">
                    </label>
                    <label>
                        Componentes impactados
                        <div class="asi-checks" id="asi-component-checks"></div>
                    </label>
                    <label>
                        Mensagem publica
                        <textarea class="asi-textarea" name="public_description"></textarea>
                    </label>
                    <label>
                        Descricao tecnica interna
                        <textarea class="asi-textarea" name="internal_description"></textarea>
                    </label>
                    <div class="asi-dialog-actions">
                        <button class="asi-btn ghost" type="button" data-asi-close>Cancelar</button>
                        <button class="asi-btn primary" type="submit"><i data-lucide="save"></i>Salvar</button>
                    </div>
                </form>
            </div>

            <div class="asi-modal" id="asi-update-modal" hidden>
                <form class="asi-dialog asi-form" id="asi-update-form">
                    <h3>Publicar atualizacao</h3>
                    <div class="asi-form-grid">
                        <label>Tipo
                            <select class="asi-select" name="update_type">
                                <option>Investigating</option>
                                <option>Identified</option>
                                <option>Monitoring</option>
                                <option>Resolved</option>
                                <option>Update</option>
                                <option>Status Changed</option>
                                <option>Component Affected</option>
                                <option>Component Restored</option>
                                <option>Maintenance Started</option>
                                <option>Maintenance Completed</option>
                            </select>
                        </label>
                        <label>Status
                            <select class="asi-select" name="status">
                                <option value="investigating">Investigating</option>
                                <option value="identified">Identified</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="resolved">Resolved</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </label>
                        <label>Impacto
                            <select class="asi-select" name="impact">
                                <option value="degraded_performance">Degraded Performance</option>
                                <option value="partial_outage">Partial Outage</option>
                                <option value="major_outage">Major Outage</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="api_degradation">API Degradation</option>
                                <option value="database_incident">Database Incident</option>
                                <option value="security_incident">Security Incident</option>
                            </select>
                        </label>
                        <label>Publico
                            <select class="asi-select" name="is_public">
                                <option value="1">Sim</option>
                                <option value="0">Nao</option>
                            </select>
                        </label>
                    </div>
                    <label>Mensagem publica
                        <textarea class="asi-textarea" name="public_message" required></textarea>
                    </label>
                    <label>Nota interna
                        <textarea class="asi-textarea" name="internal_note"></textarea>
                    </label>
                    <div class="asi-dialog-actions">
                        <button class="asi-btn ghost" type="button" data-asi-close>Cancelar</button>
                        <button class="asi-btn primary" type="submit"><i data-lucide="send"></i>Publicar</button>
                    </div>
                </form>
            </div>

            <script>
            (function () {
                const API = '/api/admin/status';
                const state = { incidents: [], components: [], selected: null, loaded: false };
                const impactLabels = {
                    degraded_performance: 'Degraded Performance',
                    partial_outage: 'Partial Outage',
                    major_outage: 'Major Outage',
                    maintenance: 'Under Maintenance',
                    security_incident: 'Security Incident',
                    network_incident: 'Network Incident',
                    api_degradation: 'API Degradation',
                    database_incident: 'Database Incident',
                    third_party_provider_issue: 'Third-Party Provider Issue',
                    resolved: 'Resolved'
                };
                const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[ch]));
                const fmt = (value) => value ? new Intl.DateTimeFormat('pt-BR', { dateStyle:'short', timeStyle:'short' }).format(new Date(String(value).replace(' ', 'T'))) : '-';
                const tone = (impact) => impact === 'major_outage' || impact === 'database_incident' || impact === 'security_incident'
                    ? 'danger' : impact === 'maintenance' ? 'maintenance' : impact === 'partial_outage' ? 'warn' : 'ok';
                const localDate = (value) => value ? String(value).replace(' ', 'T').slice(0, 16) : '';

                async function req(path, options = {}) {
                    const response = await fetch(API + path, {
                        headers: { 'Content-Type': 'application/json' },
                        ...options
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) throw new Error(data.error || 'Falha na operacao.');
                    return data;
                }

                async function load() {
                    const filters = new URLSearchParams(new FormData(document.getElementById('asi-filters')));
                    const [components, incidents] = await Promise.all([
                        req('/components'),
                        req('/incidents?' + filters.toString())
                    ]);
                    state.components = components.components || [];
                    state.incidents = incidents.incidents || [];
                    renderFilters();
                    renderComponentParentOptions();
                    renderComponentChecks();
                    renderSystems();
                    renderTable();
                    if (state.selected) {
                        state.selected = state.incidents.find(i => Number(i.id) === Number(state.selected.id)) || state.selected;
                        renderDetail(state.selected);
                    }
                }

                function renderFilters() {
                    const select = document.getElementById('asi-filter-component');
                    const current = select.value;
                    select.innerHTML = '<option value="">Sistema</option>' + state.components.map(c => `<option value="${Number(c.id)}">${esc(c.name)}</option>`).join('');
                    select.value = current;
                }

                function renderComponentParentOptions() {
                    const select = document.getElementById('asi-component-parent');
                    const current = select.value;
                    select.innerHTML = '<option value="">Nenhum</option>' + state.components.map(c => `<option value="${Number(c.id)}">${esc(c.parent_name ? c.parent_name + ' / ' + c.name : c.name)}</option>`).join('');
                    select.value = current;
                }

                function renderComponentChecks(selected = []) {
                    const selectedIds = selected.map(Number);
                    document.getElementById('asi-component-checks').innerHTML = state.components.map(c => `
                        <label class="asi-check">
                            <input type="checkbox" name="component_ids" value="${Number(c.id)}" ${selectedIds.includes(Number(c.id)) ? 'checked' : ''}>
                            <span>${esc(c.parent_name ? c.parent_name + ' / ' + c.name : c.name)}</span>
                        </label>
                    `).join('');
                }

                function renderSystems() {
                    const root = document.getElementById('asi-systems-list');
                    const byParent = new Map();
                    state.components.forEach(component => {
                        const parent = Number(component.parent_id || 0);
                        if (!byParent.has(parent)) byParent.set(parent, []);
                        byParent.get(parent).push(component);
                    });
                    const rows = [];
                    const draw = (component, child = false) => {
                        rows.push(`
                            <div class="asi-system-row ${child ? 'child' : ''}">
                                <div>
                                    <span class="asi-system-name"><i class="asi-system-dot ${Number(component.is_public) ? '' : 'private'}"></i>${esc(component.name)}</span>
                                    <span class="asi-system-meta">${esc(component.component_key)} · ${Number(component.is_public) ? 'publico' : 'privado'} · ${Number(component.is_critical) ? 'critico' : 'normal'}</span>
                                </div>
                                <div class="asi-mini">
                                    <button class="asi-btn ghost" type="button" data-system-action="edit" data-id="${Number(component.id)}"><i data-lucide="pencil"></i></button>
                                    <button class="asi-btn danger" type="button" data-system-action="delete" data-id="${Number(component.id)}"><i data-lucide="trash-2"></i></button>
                                </div>
                            </div>
                        `);
                        (byParent.get(Number(component.id)) || []).forEach(item => draw(item, true));
                    };
                    (byParent.get(0) || []).forEach(item => draw(item, false));
                    root.innerHTML = rows.join('') || '<div class="asi-empty">Nenhum sistema cadastrado.</div>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                function renderTable() {
                    const body = document.getElementById('asi-body');
                    document.getElementById('asi-count').textContent = state.incidents.length;
                    if (!state.incidents.length) {
                        body.innerHTML = '<tr><td colspan="6" class="asi-empty">Nenhum incidente encontrado.</td></tr>';
                        return;
                    }
                    body.innerHTML = state.incidents.map((incident, index) => `
                        <tr>
                            <td>
                                <button class="asi-btn ghost" type="button" data-asi-action="select" data-index="${index}"><span class="asi-title">${esc(incident.title)}</span></button>
                                <span class="asi-sub">${esc(incident.incident_type)} · ${fmt(incident.started_at)}</span>
                            </td>
                            <td><span class="asi-badge">${esc(incident.status)}</span></td>
                            <td><span class="asi-badge ${tone(incident.impact)}">${esc(incident.impact_label || incident.category)}</span></td>
                            <td>${(incident.component_names || []).map(name => `<span class="asi-badge">${esc(name)}</span>`).join(' ') || '-'}</td>
                            <td>${esc(incident.duration_label || '-')}</td>
                            <td>
                                <div class="asi-mini">
                                    <button class="asi-btn ghost" type="button" data-asi-action="edit" data-index="${index}"><i data-lucide="pencil"></i></button>
                                    <button class="asi-btn warn" type="button" data-asi-action="monitoring" data-index="${index}">Monitorar</button>
                                    <button class="asi-btn ghost" type="button" data-asi-action="maintenance" data-index="${index}">Manutencao</button>
                                    <button class="asi-btn primary" type="button" data-asi-action="resolve" data-index="${index}">Resolver</button>
                                    <button class="asi-btn danger" type="button" data-asi-action="delete" data-index="${index}"><i data-lucide="trash-2"></i></button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                function renderDetail(incident) {
                    state.selected = incident;
                    document.getElementById('asi-publish').disabled = false;
                    const updates = (incident.updates || []).map(update => `
                        <div class="asi-event">
                            <strong>${esc(update.update_type)} · ${esc(update.status)}</strong>
                            <small>${fmt(update.created_at)} · ${esc(update.impact)}</small>
                            <p>${esc(update.public_message || '')}</p>
                        </div>
                    `).join('');
                    const logs = (incident.logs || []).slice(0, 6).map(log => `
                        <div class="asi-event">
                            <strong>${esc(log.action)}</strong>
                            <small>${fmt(log.created_at)} · ${esc(log.admin_name || 'admin')}</small>
                            <p>${esc(log.message || '')}</p>
                        </div>
                    `).join('');
                    document.getElementById('asi-detail').innerHTML = `
                        <div>
                            <h3>${esc(incident.title)}</h3>
                            <p>${esc(incident.internal_description || incident.public_description || '')}</p>
                        </div>
                        <div class="asi-kpis">
                            <div class="asi-kpi"><span>Status</span><strong>${esc(incident.status)}</strong></div>
                            <div class="asi-kpi"><span>Impacto</span><strong>${esc(incident.impact_label || incident.category)}</strong></div>
                            <div class="asi-kpi"><span>Inicio</span><strong>${fmt(incident.started_at)}</strong></div>
                            <div class="asi-kpi"><span>Duracao</span><strong>${esc(incident.duration_label || '-')}</strong></div>
                            <div class="asi-kpi"><span>Responsavel</span><strong>${esc(incident.owner_name || incident.owner_admin_id || '-')}</strong></div>
                            <div class="asi-kpi"><span>Visibilidade</span><strong>${esc(incident.visibility || '-')}</strong></div>
                        </div>
                        <div class="asi-mini">${(incident.component_names || []).map(name => `<span class="asi-badge">${esc(name)}</span>`).join('')}</div>
                        <strong>Timeline publica</strong>
                        <div class="asi-timeline">${updates || '<div class="asi-empty">Sem updates.</div>'}</div>
                        <strong>Logs internos</strong>
                        <div class="asi-timeline">${logs || '<div class="asi-empty">Sem logs.</div>'}</div>
                    `;
                }

                function openIncidentModal(incident = null) {
                    const form = document.getElementById('asi-incident-form');
                    form.reset();
                    form.id.value = incident ? incident.id : '';
                    document.getElementById('asi-modal-title').textContent = incident ? 'Editar incidente' : 'Novo incidente';
                    if (incident) {
                        for (const key of ['title','incident_type','status','impact','category','visibility','owner_admin_id','systems_affected','public_description','internal_description']) {
                            if (form.elements[key]) form.elements[key].value = incident[key] || '';
                        }
                        for (const key of ['started_at','resolved_at','scheduled_start_at','scheduled_end_at']) {
                            if (form.elements[key]) form.elements[key].value = localDate(incident[key]);
                        }
                        renderComponentChecks((incident.components || []).map(c => c.id));
                    } else {
                        form.started_at.value = new Date().toISOString().slice(0, 16);
                        form.category.value = 'Degraded Performance';
                        renderComponentChecks([]);
                    }
                    document.getElementById('asi-incident-modal').hidden = false;
                }

                function closeModals() {
                    document.querySelectorAll('.asi-modal').forEach(modal => modal.hidden = true);
                }

                function resetComponentForm() {
                    const form = document.getElementById('asi-component-form');
                    form.reset();
                    form.id.value = '';
                    form.is_public.value = '1';
                    form.is_critical.value = '1';
                    form.sort_order.value = '0';
                }

                function fillComponentForm(id) {
                    const component = state.components.find(item => Number(item.id) === Number(id));
                    if (!component) return;
                    const form = document.getElementById('asi-component-form');
                    form.id.value = component.id || '';
                    form.name.value = component.name || '';
                    form.component_key.value = component.component_key || '';
                    form.parent_id.value = component.parent_id || '';
                    form.description.value = component.description || '';
                    form.sort_order.value = component.sort_order || 0;
                    form.is_public.value = Number(component.is_public) ? '1' : '0';
                    form.is_critical.value = Number(component.is_critical) ? '1' : '0';
                }

                function openUpdateModal() {
                    if (!state.selected) return;
                    const form = document.getElementById('asi-update-form');
                    form.reset();
                    form.status.value = state.selected.status || 'investigating';
                    form.impact.value = state.selected.impact || 'degraded_performance';
                    document.getElementById('asi-update-modal').hidden = false;
                }

                function payloadFromForm(form) {
                    const data = Object.fromEntries(new FormData(form).entries());
                    data.component_ids = Array.from(form.querySelectorAll('input[name="component_ids"]:checked')).map(i => Number(i.value));
                    return data;
                }

                async function quick(index, action) {
                    const incident = state.incidents[index];
                    if (!incident) return;
                    if (action === 'monitoring') {
                        await req(`/incidents/${incident.id}/status`, { method:'POST', body: JSON.stringify({ status:'monitoring', impact: incident.impact, category: incident.category }) });
                    }
                    if (action === 'resolve') {
                        await req(`/incidents/${incident.id}/resolve`, { method:'POST', body: '{}' });
                    }
                    if (action === 'maintenance') {
                        await req(`/incidents/${incident.id}/maintenance`, { method:'POST', body: '{}' });
                    }
                    if (action === 'delete') {
                        if (!confirm('Excluir este incidente permanentemente?')) return;
                        await req(`/incidents/${incident.id}/delete`, { method:'POST', body: '{}' });
                        state.selected = null;
                        document.getElementById('asi-publish').disabled = true;
                    }
                    await load();
                }

                function bind() {
                    document.getElementById('asi-new').addEventListener('click', () => openIncidentModal());
                    document.getElementById('asi-new-system').addEventListener('click', resetComponentForm);
                    document.getElementById('asi-refresh').addEventListener('click', load);
                    document.getElementById('asi-publish').addEventListener('click', openUpdateModal);
                    document.getElementById('asi-filters').addEventListener('submit', event => { event.preventDefault(); load(); });
                    document.getElementById('asi-systems-list').addEventListener('click', async event => {
                        const button = event.target.closest('[data-system-action]');
                        if (!button) return;
                        const id = Number(button.dataset.id);
                        if (button.dataset.systemAction === 'edit') {
                            fillComponentForm(id);
                            return;
                        }
                        if (!confirm('Excluir este sistema/subsistema?')) return;
                        button.disabled = true;
                        try {
                            await req(`/components/${id}/delete`, { method:'POST', body: '{}' });
                            await load();
                            resetComponentForm();
                        } catch (error) {
                            alert(error.message);
                        } finally {
                            button.disabled = false;
                        }
                    });
                    document.getElementById('asi-body').addEventListener('click', async event => {
                        const button = event.target.closest('[data-asi-action]');
                        if (!button) return;
                        const index = Number(button.dataset.index);
                        const incident = state.incidents[index];
                        if (button.dataset.asiAction === 'select') renderDetail(incident);
                        else if (button.dataset.asiAction === 'edit') openIncidentModal(incident);
                        else {
                            button.disabled = true;
                            try { await quick(index, button.dataset.asiAction); } catch (error) { alert(error.message); }
                            finally { button.disabled = false; }
                        }
                    });
                    document.querySelectorAll('[data-asi-close]').forEach(btn => btn.addEventListener('click', closeModals));
                    document.querySelectorAll('.asi-modal').forEach(modal => modal.addEventListener('click', event => {
                        if (event.target === modal) closeModals();
                    }));
                    document.getElementById('asi-impact').addEventListener('change', event => {
                        const form = document.getElementById('asi-incident-form');
                        form.category.value = impactLabels[event.target.value] || form.category.value;
                    });
                    document.getElementById('asi-incident-form').addEventListener('submit', async function (event) {
                        event.preventDefault();
                        const data = payloadFromForm(this);
                        const id = data.id;
                        delete data.id;
                        const button = this.querySelector('button[type="submit"]');
                        button.disabled = true;
                        try {
                            await req(id ? `/incidents/${id}/update` : '/incidents', { method:'POST', body: JSON.stringify(data) });
                            closeModals();
                            await load();
                        } catch (error) {
                            alert(error.message);
                        } finally {
                            button.disabled = false;
                        }
                    });
                    document.getElementById('asi-component-clear').addEventListener('click', resetComponentForm);
                    document.getElementById('asi-component-form').addEventListener('submit', async function (event) {
                        event.preventDefault();
                        const data = Object.fromEntries(new FormData(this).entries());
                        data.is_public = data.is_public === '1';
                        data.is_critical = data.is_critical === '1';
                        data.parent_id = data.parent_id || null;
                        const button = this.querySelector('button[type="submit"]');
                        button.disabled = true;
                        try {
                            await req('/components', { method:'POST', body: JSON.stringify(data) });
                            resetComponentForm();
                            await load();
                        } catch (error) {
                            alert(error.message);
                        } finally {
                            button.disabled = false;
                        }
                    });
                    document.getElementById('asi-update-form').addEventListener('submit', async function (event) {
                        event.preventDefault();
                        if (!state.selected) return;
                        const data = Object.fromEntries(new FormData(this).entries());
                        data.is_public = data.is_public === '1';
                        const button = this.querySelector('button[type="submit"]');
                        button.disabled = true;
                        try {
                            await req(`/incidents/${state.selected.id}/timeline`, { method:'POST', body: JSON.stringify(data) });
                            closeModals();
                            await load();
                        } catch (error) {
                            alert(error.message);
                        } finally {
                            button.disabled = false;
                        }
                    });
                }

                window.AdminStatusIncidentsPanel = {
                    async init() {
                        if (!state.loaded) {
                            state.loaded = true;
                            bind();
                        }
                        await load();
                    }
                };
            })();
            </script>
        </section>
        <?php
    }
}
