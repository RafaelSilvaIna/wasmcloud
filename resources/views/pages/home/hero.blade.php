<section class="hero-section" id="hero" aria-labelledby="hero-title">
    <div class="hero-background" aria-hidden="true"></div>

    <div class="page-shell hero-shell">
        <div class="hero-copy-block" data-hero-copy>
            <div class="hero-kicker">
                <span>Wasm Cloud</span>
                <span>Console profissional para deploys, terminais e operacao</span>
            </div>

            <h1 id="hero-title">Controle sua hospedagem pelo terminal e pelo painel.</h1>

            <p class="hero-copy">
                Execute deploys, acompanhe logs, gerencie ambientes e publique aplicacoes com uma experiencia clara para operacao diaria.
            </p>

            <div class="hero-actions">
                <a class="primary-action large" href="#fluxo">Comecar agora</a>
                <a class="secondary-action large" href="#fluxo">Ver console</a>
            </div>

            <dl class="hero-proof" aria-label="Destaques da plataforma">
                <div>
                    <dt>Terminal</dt>
                    <dd>Comandos assistidos</dd>
                </div>
                <div>
                    <dt>Deploy</dt>
                    <dd>Pipeline monitorado</dd>
                </div>
                <div>
                    <dt>Operacao</dt>
                    <dd>Logs em tempo real</dd>
                </div>
            </dl>
        </div>

        <div class="flow-stage" id="fluxo" aria-label="Mockup de gerenciamento por terminal e deploy">
            <div class="product-frame" data-console-mockup>
                <div class="product-toolbar" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                    <strong>console.wasmcloud.local</strong>
                </div>

                <div class="product-canvas">
                    <aside class="console-sidebar" aria-label="Resumo operacional">
                        <div class="deploy-status">
                            <span class="status-dot"></span>
                            <span>Producao</span>
                            <strong>Online</strong>
                        </div>

                        <div class="console-metric-grid">
                            <div>
                                <span>Deploy</span>
                                <strong>2m 14s</strong>
                            </div>
                            <div>
                                <span>Build</span>
                                <strong>Passou</strong>
                            </div>
                            <div>
                                <span>Logs</span>
                                <strong>24/min</strong>
                            </div>
                            <div>
                                <span>Release</span>
                                <strong>v1.8.3</strong>
                            </div>
                        </div>
                    </aside>

                    <div class="terminal-workspace">
                        <div class="workspace-tabs" aria-label="Areas do console">
                            <button type="button" aria-pressed="true">Terminal</button>
                            <button type="button">Deploy</button>
                            <button type="button">Logs</button>
                            <button type="button">Ambientes</button>
                        </div>

                        <div class="terminal-layout">
                            <section class="terminal-window" aria-label="Terminal de deploy">
                                <div class="terminal-header">
                                    <span>wasm-cloud / producao</span>
                                    <strong>executando</strong>
                                </div>

                                <div class="terminal-stream" data-terminal-stream>
                                    <p data-terminal-line><span>$</span> wasm deploy --env producao</p>
                                    <p data-terminal-line><span>01</span> preparando artefatos</p>
                                    <p data-terminal-line><span>02</span> instalando dependencias</p>
                                    <p data-terminal-line><span>03</span> compilando assets</p>
                                    <p data-terminal-line><span>04</span> publicando release v1.8.3</p>
                                    <p data-terminal-line><span>ok</span> deploy concluido sem erros</p>
                                    <p class="terminal-cursor" data-terminal-cursor><span>$</span> aguardando proximo comando</p>
                                </div>
                            </section>

                            <aside class="deploy-monitor" aria-label="Monitor de deploy">
                                <div class="monitor-card">
                                    <span>Progresso</span>
                                    <strong>84%</strong>
                                    <div class="progress-track">
                                        <i data-progress-bar></i>
                                    </div>
                                </div>

                                <ol class="deploy-steps">
                                    <li data-deploy-step>
                                        <span></span>
                                        Build validado
                                    </li>
                                    <li data-deploy-step>
                                        <span></span>
                                        Variaveis verificadas
                                    </li>
                                    <li data-deploy-step>
                                        <span></span>
                                        Release publicado
                                    </li>
                                    <li data-deploy-step>
                                        <span></span>
                                        Health check ativo
                                    </li>
                                </ol>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
