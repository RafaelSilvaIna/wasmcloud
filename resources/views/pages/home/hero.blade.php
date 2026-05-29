<section class="hero-section" id="hero" aria-labelledby="hero-title">
    <div class="hero-background" aria-hidden="true"></div>

    <div class="page-shell hero-layout">
        <div class="hero-copy-block" data-hero-copy>
            <p class="eyebrow">Wasm Cloud</p>
            <h1 id="hero-title">Hospedagem com controle.</h1>
            <p class="hero-copy">
                Do banco ao aplicativo, tudo em um fluxo claro.
            </p>

            <div class="hero-actions">
                <a class="primary-action large" href="#fluxo">Ver arquitetura</a>
            </div>
        </div>

        <div class="flow-stage" id="fluxo" aria-label="Fluxo animado da plataforma">
            <svg class="flow-svg" data-flow-svg viewBox="0 0 760 480" role="img" aria-labelledby="flow-title flow-desc">
                <title id="flow-title">Fluxo Wasm Cloud</title>
                <desc id="flow-desc">Animacao mostrando banco de dados, autenticacao, hospedagem e aplicativo conectados.</desc>

                <defs>
                    <filter id="softGlow" x="-40%" y="-40%" width="180%" height="180%">
                        <feGaussianBlur stdDeviation="7" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>

                <rect class="svg-panel" x="20" y="20" width="720" height="440" rx="16" />
                <path class="svg-hairline" d="M130 240H670" />
                <path class="svg-route" id="wasm-flow-route" d="M130 240C190 176 250 176 310 240C370 304 430 304 490 240C550 176 610 176 670 240" />

                <circle class="svg-pulse" data-flow-pulse r="7" />
                <circle class="svg-pulse muted" data-flow-pulse r="5" />
                <circle class="svg-pulse muted" data-flow-pulse r="4" />

                <g class="svg-node" data-flow-node transform="translate(70 164)">
                    <rect width="120" height="152" rx="14" />
                    <g class="node-symbol" transform="translate(38 28)">
                        <rect width="44" height="44" rx="8" />
                        <ellipse cx="22" cy="17" rx="13" ry="6" />
                        <path d="M9 17v14c0 3.3 5.8 6 13 6s13-2.7 13-6V17" />
                    </g>
                    <text x="60" y="110" text-anchor="middle">Banco</text>
                </g>

                <g class="svg-node" data-flow-node transform="translate(250 164)">
                    <rect width="120" height="152" rx="14" />
                    <g class="node-symbol" transform="translate(38 28)">
                        <rect width="44" height="44" rx="8" />
                        <path d="M14 21v-5a8 8 0 0 1 16 0v5" />
                        <rect x="11" y="20" width="22" height="17" rx="4" />
                    </g>
                    <text x="60" y="110" text-anchor="middle">Auth</text>
                </g>

                <g class="svg-node" data-flow-node transform="translate(430 164)">
                    <rect width="120" height="152" rx="14" />
                    <g class="node-symbol" transform="translate(38 28)">
                        <rect width="44" height="44" rx="8" />
                        <rect x="10" y="11" width="24" height="9" rx="2" />
                        <rect x="10" y="25" width="24" height="9" rx="2" />
                        <circle cx="29" cy="15.5" r="1.8" />
                        <circle cx="29" cy="29.5" r="1.8" />
                    </g>
                    <text x="60" y="110" text-anchor="middle">Host</text>
                </g>

                <g class="svg-node" data-flow-node transform="translate(610 164)">
                    <rect width="120" height="152" rx="14" />
                    <g class="node-symbol" transform="translate(38 28)">
                        <rect width="44" height="44" rx="8" />
                        <rect x="13" y="9" width="18" height="27" rx="3" />
                        <path d="M18 31h8" />
                    </g>
                    <text x="60" y="110" text-anchor="middle">App</text>
                </g>
            </svg>
        </div>
    </div>
</section>
