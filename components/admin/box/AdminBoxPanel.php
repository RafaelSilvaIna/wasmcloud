<?php
declare(strict_types=1);

final class AdminBoxPanel
{
    public static function render(): void
    {
        ?>
        <style>
            .admin-box-page { display: grid; gap: 14px; }
            .admin-box-head,
            .admin-box-card,
            .admin-box-panel {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0f131a;
            }
            .admin-box-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 14px;
                padding: 14px;
            }
            .admin-box-head h2 { margin: 0; color: #fff; font-size: 1.12rem; }
            .admin-box-head p { margin: 5px 0 0; color: #94a3b8; }
            .admin-box-tabs { display: flex; flex-wrap: wrap; gap: 6px; }
            .admin-box-tab,
            .admin-box-btn {
                min-height: 38px;
                border: 1px solid rgba(148, 163, 184, .20);
                border-radius: 8px;
                background: #111827;
                color: #e2e8f0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 0 12px;
                cursor: pointer;
                font-weight: 760;
                text-decoration: none;
            }
            .admin-box-tab.active,
            .admin-box-btn:hover { border-color: rgba(229, 9, 20, .45); background: rgba(229, 9, 20, .12); color: #fff; }
            .admin-box-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
            .admin-box-card { padding: 16px; }
            .admin-box-card span {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #94a3b8;
                font-size: .76rem;
                font-weight: 820;
                text-transform: uppercase;
            }
            .admin-box-card strong { display: block; margin-top: 10px; color: #fff; font-size: 1.55rem; }
            .admin-box-panel { padding: 16px; }
            .admin-box-pane[hidden] { display: none; }
            .admin-box-form-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 14px; align-items: start; }
            .admin-box-form { display: grid; gap: 12px; }
            .admin-box-field { display: grid; gap: 7px; }
            .admin-box-field label { color: #94a3b8; font-size: .82rem; font-weight: 760; }
            .admin-box-field input,
            .admin-box-field select,
            .admin-box-field textarea {
                width: 100%;
                min-height: 42px;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 8px;
                background: #090c12;
                color: #fff;
                padding: 10px;
                font: inherit;
            }
            .admin-box-field textarea { min-height: 112px; resize: vertical; line-height: 1.5; }
            .admin-box-two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .admin-box-audience { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .admin-box-audience label {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0a0c10;
                color: #e2e8f0;
                min-height: 44px;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0 12px;
                font-weight: 760;
            }
            .admin-box-audience input { accent-color: #e50914; }
            .admin-box-user-results { display: grid; gap: 6px; max-height: 162px; overflow: auto; }
            .admin-box-user-option {
                border: 1px solid rgba(148,163,184,.12);
                border-radius: 8px;
                background: #0a0c10;
                color: #e2e8f0;
                padding: 10px;
                text-align: left;
                cursor: pointer;
            }
            .admin-box-user-option.active { border-color: rgba(229,9,20,.55); background: rgba(229,9,20,.12); }
            .admin-box-preview {
                position: sticky;
                top: 18px;
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0a0c10;
                padding: 14px;
            }
            .admin-box-preview small { color: #94a3b8; font-weight: 800; text-transform: uppercase; }
            .admin-box-preview h3 { color: #fff; margin: 12px 0 8px; font-size: 1rem; }
            .admin-box-preview p { color: #cbd5e1; margin: 0; line-height: 1.5; }
            .admin-box-cta { margin-top: 12px; color: #fff; background: #e50914; border-radius: 8px; padding: 10px 12px; display: inline-flex; font-weight: 800; }
            .admin-box-actions { display: flex; justify-content: flex-end; gap: 8px; }
            .admin-box-feedback { min-height: 20px; color: #94a3b8; font-size: .88rem; }
            .admin-box-feedback.ok { color: #86efac; }
            .admin-box-feedback.err { color: #fca5a5; }
            .admin-box-table-wrap { overflow-x: auto; }
            .admin-box-table { width: 100%; min-width: 900px; border-collapse: collapse; }
            .admin-box-table th,
            .admin-box-table td { padding: 12px; border-bottom: 1px solid rgba(148, 163, 184, .10); text-align: left; color: #e2e8f0; font-size: .86rem; vertical-align: top; }
            .admin-box-table th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; }
            .admin-box-pill { border-radius: 999px; padding: 5px 9px; font-size: .72rem; font-weight: 820; background: rgba(148,163,184,.12); color: #cbd5e1; white-space: nowrap; }
            .admin-box-pill.all { color: #38bdf8; background: rgba(56,189,248,.12); }
            .admin-box-pill.user { color: #fbbf24; background: rgba(251,191,36,.12); }
            .admin-box-templates { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
            .admin-box-template {
                border: 1px solid rgba(148, 163, 184, .16);
                border-radius: 8px;
                background: #0a0c10;
                padding: 14px;
                display: grid;
                gap: 10px;
            }
            .admin-box-template h3 { margin: 0; color: #fff; font-size: 1rem; }
            .admin-box-template p { margin: 0; color: #94a3b8; line-height: 1.5; }
            @media (max-width: 1100px) {
                .admin-box-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .admin-box-form-grid { grid-template-columns: 1fr; }
                .admin-box-preview { position: static; }
                .admin-box-templates { grid-template-columns: 1fr; }
            }
            @media (max-width: 640px) {
                .admin-box-head { display: grid; }
                .admin-box-grid,
                .admin-box-two,
                .admin-box-audience { grid-template-columns: 1fr; }
                .admin-box-actions { display: grid; }
            }
        </style>

        <section data-admin-route="box" class="admin-route-panel" hidden>
            <div class="admin-box-page" id="admin-box-root">
                <div class="admin-box-head">
                    <div>
                        <h2>Box Pipocine</h2>
                        <p>Envie pesquisas de satisfacao, links, avisos e mensagens para todos ou para um usuario especifico.</p>
                    </div>
                    <div class="admin-box-tabs" role="tablist">
                        <button class="admin-box-tab active" type="button" data-box-tab="compose"><i data-lucide="send"></i>Enviar</button>
                        <button class="admin-box-tab" type="button" data-box-tab="history"><i data-lucide="history"></i>Historico</button>
                        <button class="admin-box-tab" type="button" data-box-tab="templates"><i data-lucide="layout-template"></i>Modelos</button>
                    </div>
                </div>

                <div class="admin-box-grid">
                    <article class="admin-box-card"><span><i data-lucide="users"></i>Usuarios ativos</span><strong data-box-stat="active_users">-</strong></article>
                    <article class="admin-box-card"><span><i data-lucide="send"></i>Campanhas</span><strong data-box-stat="campaigns">-</strong></article>
                    <article class="admin-box-card"><span><i data-lucide="mail"></i>Itens enviados</span><strong data-box-stat="admin_items">-</strong></article>
                    <article class="admin-box-card"><span><i data-lucide="mail-warning"></i>Nao lidas</span><strong data-box-stat="unread_admin_items">-</strong></article>
                </div>

                <article class="admin-box-panel admin-box-pane" data-box-pane="compose">
                    <div class="admin-box-form-grid">
                        <form class="admin-box-form" id="admin-box-form">
                            <div class="admin-box-field">
                                <label>Destino</label>
                                <div class="admin-box-audience">
                                    <label><input type="radio" name="audience" value="all"> Todos os usuarios ativos</label>
                                    <label><input type="radio" name="audience" value="user" checked> Usuario especifico</label>
                                </div>
                            </div>

                            <div class="admin-box-field" data-box-target-field>
                                <label for="admin-box-target">E-mail do usuario</label>
                                <input id="admin-box-target" name="target_email" type="email" autocomplete="off" placeholder="usuario@email.com">
                                <div class="admin-box-user-results" id="admin-box-user-results"></div>
                            </div>

                            <div class="admin-box-two">
                                <div class="admin-box-field">
                                    <label for="admin-box-kind">Tipo</label>
                                    <select id="admin-box-kind" name="kind">
                                        <option value="message">Mensagem</option>
                                        <option value="survey">Pesquisa de satisfacao</option>
                                        <option value="link">Link</option>
                                        <option value="notice">Aviso</option>
                                    </select>
                                </div>
                                <div class="admin-box-field">
                                    <label for="admin-box-tone">Tom</label>
                                    <select id="admin-box-tone" name="tone">
                                        <option value="info">Informativo</option>
                                        <option value="success">Positivo</option>
                                        <option value="warning">Atencao</option>
                                        <option value="danger">Urgente</option>
                                    </select>
                                </div>
                            </div>

                            <div class="admin-box-field">
                                <label for="admin-box-title">Titulo</label>
                                <input id="admin-box-title" name="title" maxlength="160" required placeholder="Ex: Queremos ouvir voce">
                            </div>

                            <div class="admin-box-field">
                                <label for="admin-box-body">Mensagem</label>
                                <textarea id="admin-box-body" name="body" maxlength="600" required placeholder="Escreva a mensagem que aparecera na Box do usuario."></textarea>
                            </div>

                            <div class="admin-box-two">
                                <div class="admin-box-field">
                                    <label for="admin-box-url">Link de acao</label>
                                    <input id="admin-box-url" name="action_url" placeholder="/plan ou https://...">
                                </div>
                                <div class="admin-box-field">
                                    <label for="admin-box-label">Texto do botao</label>
                                    <input id="admin-box-label" name="action_label" maxlength="80" placeholder="Responder pesquisa">
                                </div>
                            </div>

                            <div class="admin-box-feedback" id="admin-box-feedback" role="status"></div>
                            <div class="admin-box-actions">
                                <button class="admin-box-btn" type="button" id="admin-box-clear"><i data-lucide="rotate-ccw"></i>Limpar</button>
                                <button class="admin-box-btn" type="submit"><i data-lucide="send"></i>Enviar para Box</button>
                            </div>
                        </form>

                        <aside class="admin-box-preview" aria-label="Previa da mensagem">
                            <small id="admin-box-preview-type">Mensagem</small>
                            <h3 id="admin-box-preview-title">Titulo da mensagem</h3>
                            <p id="admin-box-preview-body">A previa da Box aparece aqui conforme voce escreve.</p>
                            <span class="admin-box-cta" id="admin-box-preview-cta" hidden>Abrir</span>
                        </aside>
                    </div>
                </article>

                <article class="admin-box-panel admin-box-pane" data-box-pane="history" hidden>
                    <div class="admin-box-table-wrap">
                        <table class="admin-box-table">
                            <thead>
                            <tr>
                                <th>Data</th>
                                <th>Destino</th>
                                <th>Tipo</th>
                                <th>Titulo</th>
                                <th>Link</th>
                                <th>Recebidos</th>
                                <th>Admin</th>
                            </tr>
                            </thead>
                            <tbody id="admin-box-history"></tbody>
                        </table>
                    </div>
                </article>

                <article class="admin-box-panel admin-box-pane" data-box-pane="templates" hidden>
                    <div class="admin-box-templates">
                        <section class="admin-box-template">
                            <h3>Pesquisa de satisfacao</h3>
                            <p>Convite curto para coletar opinioes depois de uma melhoria ou lancamento.</p>
                            <button class="admin-box-btn" type="button" data-box-template="survey"><i data-lucide="copy"></i>Usar modelo</button>
                        </section>
                        <section class="admin-box-template">
                            <h3>Aviso operacional</h3>
                            <p>Comunique manutencao, instabilidade ou mudancas importantes no servico.</p>
                            <button class="admin-box-btn" type="button" data-box-template="notice"><i data-lucide="copy"></i>Usar modelo</button>
                        </section>
                        <section class="admin-box-template">
                            <h3>Link personalizado</h3>
                            <p>Envie um destino direto, como plano, suporte, formulario ou novidade.</p>
                            <button class="admin-box-btn" type="button" data-box-template="link"><i data-lucide="copy"></i>Usar modelo</button>
                        </section>
                    </div>
                </article>
            </div>
        </section>

        <script>
            (function () {
                const root = document.getElementById('admin-box-root');
                if (!root) return;

                let searchTimer = null;
                let loaded = false;

                const templates = {
                    survey: {
                        kind: 'survey',
                        tone: 'info',
                        title: 'Pesquisa de satisfacao Pipocine',
                        body: 'Queremos melhorar sua experiencia. Responda esta pesquisa rapida e ajude o Pipocine a evoluir.',
                        action_label: 'Responder pesquisa'
                    },
                    notice: {
                        kind: 'notice',
                        tone: 'warning',
                        title: 'Aviso importante Pipocine',
                        body: 'Temos uma atualizacao importante sobre sua conta. Abra esta mensagem e acompanhe as orientacoes.',
                        action_label: 'Ver detalhes'
                    },
                    link: {
                        kind: 'link',
                        tone: 'success',
                        title: 'Novidade disponivel no Pipocine',
                        body: 'Preparamos um link especial para voce acessar uma novidade do Pipocine.',
                        action_label: 'Abrir link'
                    }
                };

                function esc(value) {
                    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
                }

                function date(value) {
                    if (!value) return '-';
                    const parsed = new Date(String(value).replace(' ', 'T'));
                    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('pt-BR');
                }

                async function api(path, options = {}) {
                    const response = await fetch('/api/admin/' + path, { headers: { 'Content-Type': 'application/json' }, ...options });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) throw new Error(data.error || data.message || 'Falha na acao.');
                    return data;
                }

                function currentFormData() {
                    return Object.fromEntries(new FormData(document.getElementById('admin-box-form')).entries());
                }

                function updateTargetVisibility() {
                    const audience = currentFormData().audience || 'user';
                    root.querySelector('[data-box-target-field]').hidden = audience === 'all';
                }

                function updatePreview() {
                    const data = currentFormData();
                    const kind = document.getElementById('admin-box-kind');
                    document.getElementById('admin-box-preview-type').textContent = kind.options[kind.selectedIndex]?.text || 'Mensagem';
                    document.getElementById('admin-box-preview-title').textContent = data.title || 'Titulo da mensagem';
                    document.getElementById('admin-box-preview-body').textContent = data.body || 'A previa da Box aparece aqui conforme voce escreve.';
                    const cta = document.getElementById('admin-box-preview-cta');
                    cta.textContent = data.action_label || (data.action_url ? 'Abrir' : '');
                    cta.hidden = !data.action_url;
                }

                function renderHistory(campaigns) {
                    document.getElementById('admin-box-history').innerHTML = campaigns.length ? campaigns.map(item => {
                        const audience = item.audience === 'all' ? 'Todos' : (item.target_email || 'Usuario');
                        return `<tr>
                            <td>${esc(date(item.created_at))}</td>
                            <td><span class="admin-box-pill ${esc(item.audience)}">${esc(audience)}</span></td>
                            <td>${esc(item.box_type.replace('admin_', ''))}</td>
                            <td><strong>${esc(item.title)}</strong><br><span style="color:#94a3b8">${esc(item.body)}</span></td>
                            <td>${item.action_url ? `<a href="${esc(item.action_url)}" target="_blank" rel="noopener" style="color:#93c5fd">${esc(item.action_label || item.action_url)}</a>` : '-'}</td>
                            <td>${esc(item.recipients_count)}</td>
                            <td>${esc(item.admin_name)}</td>
                        </tr>`;
                    }).join('') : '<tr><td colspan="7">Nenhum envio administrativo registrado.</td></tr>';
                }

                async function load() {
                    const data = await api('box');
                    const summary = data.summary || {};
                    ['active_users', 'campaigns', 'admin_items', 'unread_admin_items'].forEach(key => {
                        const target = root.querySelector('[data-box-stat="' + key + '"]');
                        if (target) target.textContent = summary[key] || 0;
                    });
                    renderHistory(data.campaigns || []);
                    loaded = true;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                async function searchUsers() {
                    const input = document.getElementById('admin-box-target');
                    const target = document.getElementById('admin-box-user-results');
                    const q = input.value.trim();
                    if (q.length < 2) {
                        target.innerHTML = '';
                        return;
                    }
                    const data = await api('box/search-users?q=' + encodeURIComponent(q));
                    target.innerHTML = (data.users || []).map(user => `
                        <button class="admin-box-user-option" type="button" data-box-email="${esc(user.email)}">
                            <strong>${esc(user.name || 'Sem nome')}</strong><br>
                            <span>${esc(user.email || user.phone || '-')} - ${esc(user.status)}</span>
                        </button>
                    `).join('') || '<p style="color:#94a3b8;margin:0">Nenhum usuario encontrado.</p>';
                }

                function setTab(tab) {
                    root.querySelectorAll('[data-box-tab]').forEach(item => item.classList.toggle('active', item.dataset.boxTab === tab));
                    root.querySelectorAll('[data-box-pane]').forEach(item => item.hidden = item.dataset.boxPane !== tab);
                    if (tab === 'history' && !loaded) load().catch(() => {});
                }

                document.addEventListener('click', event => {
                    const tab = event.target.closest('[data-box-tab]');
                    if (tab) setTab(tab.dataset.boxTab || 'compose');

                    const user = event.target.closest('[data-box-email]');
                    if (user) {
                        document.getElementById('admin-box-target').value = user.dataset.boxEmail || '';
                        document.querySelectorAll('[data-box-email]').forEach(item => item.classList.toggle('active', item === user));
                    }

                    const template = event.target.closest('[data-box-template]');
                    if (template) {
                        const item = templates[template.dataset.boxTemplate];
                        if (!item) return;
                        Object.entries(item).forEach(([key, value]) => {
                            const field = document.querySelector(`[name="${key}"]`);
                            if (field) field.value = value;
                        });
                        setTab('compose');
                        updatePreview();
                    }

                    if (event.target.closest('#admin-box-clear')) {
                        document.getElementById('admin-box-form').reset();
                        document.getElementById('admin-box-user-results').innerHTML = '';
                        updateTargetVisibility();
                        updatePreview();
                    }
                });

                root.addEventListener('input', event => {
                    if (event.target.id === 'admin-box-target') {
                        clearTimeout(searchTimer);
                        searchTimer = setTimeout(() => searchUsers().catch(() => {}), 240);
                    }
                    updatePreview();
                });

                root.addEventListener('change', () => {
                    updateTargetVisibility();
                    updatePreview();
                });

                document.getElementById('admin-box-form').addEventListener('submit', async event => {
                    event.preventDefault();
                    const feedback = document.getElementById('admin-box-feedback');
                    const button = event.currentTarget.querySelector('button[type="submit"]');
                    feedback.className = 'admin-box-feedback';
                    feedback.textContent = 'Enviando...';
                    button.disabled = true;

                    try {
                        const data = await api('box/send', {
                            method: 'POST',
                            body: JSON.stringify(currentFormData())
                        });
                        feedback.className = 'admin-box-feedback ok';
                        feedback.textContent = `${data.message || 'Enviado.'} Destinatarios: ${data.recipients || 0}.`;
                        await load();
                    } catch (error) {
                        feedback.className = 'admin-box-feedback err';
                        feedback.textContent = error.message;
                    } finally {
                        button.disabled = false;
                    }
                });

                updateTargetVisibility();
                updatePreview();
                window.AdminBoxPanel = { load };
            })();
        </script>
        <?php
    }
}
