<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Entrar no painel Wasm Cloud para gerenciar projetos, deploys e infraestrutura.">

        <title>Entrar - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="auth-body">
        <div data-global-loader-root></div>
        <div
            data-sonner-root
            data-status="{{ session('status') }}"
            data-error="{{ $errors->any() ? $errors->first() : '' }}"
        ></div>

        <main class="auth-shell" aria-labelledby="login-title">
            <section class="auth-visual" aria-label="Wasm Cloud">
                <a class="auth-brand" href="{{ route('home') }}" aria-label="Wasm Cloud">
                    <img src="{{ asset('images/brand/wasm-cloud-wordmark.png') }}" alt="Wasm Cloud" width="142" height="48">
                </a>

                <div class="auth-illustration" aria-hidden="true">
                    <div class="auth-terminal-card">
                        <div class="auth-terminal-top">
                            <span></span>
                            <span></span>
                            <span></span>
                            <strong>wasm auth</strong>
                        </div>
                        <p><span>$</span> acessar painel</p>
                        <p><span>ok</span> sessao protegida</p>
                        <p><span>ok</span> projetos sincronizados</p>
                    </div>

                    <div class="auth-metric-row">
                        <div>
                            <span>Status</span>
                            <strong>Seguro</strong>
                        </div>
                        <div>
                            <span>Deploys</span>
                            <strong>Online</strong>
                        </div>
                    </div>
                </div>

                <div class="auth-visual-copy">
                    <h1>Controle seus projetos com uma sessao segura.</h1>
                    <p>Entre para acompanhar deploys, terminais, logs e ambientes da sua hospedagem.</p>
                </div>
            </section>

            <section class="auth-panel">
                <div class="auth-form-head">
                    <span>Bem-vindo de volta</span>
                    <h2 id="login-title">Entrar na Wasm Cloud</h2>
                    <p>Acesse sua conta usando email e senha.</p>
                </div>

                <form class="auth-form" method="POST" action="{{ route('login.store') }}" data-global-loading>
                    @csrf

                    <label>
                        <span>Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                            placeholder="voce@empresa.com"
                        >
                    </label>

                    <label>
                        <span>Senha</span>
                        <input
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            placeholder="Sua senha"
                        >
                    </label>

                    <label class="auth-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Manter conectado neste dispositivo</span>
                    </label>

                    <button class="primary-action large auth-submit" type="submit">Entrar</button>
                </form>

                <p class="auth-switch">
                    Ainda nao tem conta?
                    <a href="{{ route('register') }}" data-global-loading>Criar conta gratis</a>
                </p>
            </section>
        </main>
    </body>
</html>
