<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Criar conta gratis na Wasm Cloud para hospedar projetos, deploys e SaaS.">

        <title>Criar conta - Wasm Cloud</title>

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

        <main class="auth-shell" aria-labelledby="register-title">
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
                            <strong>wasm create</strong>
                        </div>
                        <p><span>$</span> criar workspace</p>
                        <p><span>ok</span> email verificado</p>
                        <p><span>ok</span> telefone unico vinculado</p>
                        <p><span>ok</span> plano gratis ativado</p>
                    </div>

                    <div class="auth-metric-row">
                        <div>
                            <span>Plano</span>
                            <strong>Gratis</strong>
                        </div>
                        <div>
                            <span>Projeto</span>
                            <strong>Pronto</strong>
                        </div>
                    </div>
                </div>

                <div class="auth-visual-copy">
                    <h1>Crie sua conta e publique o primeiro projeto.</h1>
                    <p>Comece com email e senha, depois vincule um unico telefone para proteger sua conta.</p>
                </div>
            </section>

            <section class="auth-panel">
                <div class="auth-form-head">
                    <span>Plano gratis generoso</span>
                    <h2 id="register-title">Criar conta Wasm Cloud</h2>
                    <p>Email e telefone sao unicos por conta para manter sua operacao segura.</p>
                </div>

                <form class="auth-form" method="POST" action="{{ route('register.store') }}" data-global-loading>
                    @csrf

                    <label>
                        <span>Nome</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                            autofocus
                            placeholder="Seu nome"
                        >
                    </label>

                    <label>
                        <span>Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            placeholder="voce@empresa.com"
                        >
                    </label>

                    <label>
                        <span>Senha</span>
                        <input
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                            placeholder="Minimo 8 caracteres"
                        >
                    </label>

                    <label>
                        <span>Confirmar senha</span>
                        <input
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                            placeholder="Repita sua senha"
                        >
                    </label>

                    <label>
                        <span>Telefone</span>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                            required
                            placeholder="(11) 99999-9999"
                        >
                    </label>

                    <button class="primary-action large auth-submit" type="submit">Criar conta gratis</button>
                </form>

                <p class="auth-switch">
                    Ja tem uma conta?
                    <a href="{{ route('login') }}" data-global-loading>Entrar</a>
                </p>
            </section>
        </main>
    </body>
</html>
