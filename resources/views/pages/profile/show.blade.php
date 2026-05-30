<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Perfil da conta Wasm Cloud.">

        <title>Perfil - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div data-global-loader-root></div>
        <div data-sonner-root data-status="{{ session('status') }}" data-error="{{ $errors->any() ? $errors->first() : '' }}"></div>
        <x-app.header current-page="Perfil" />

        <main class="app-page-shell" aria-labelledby="profile-title">
            <section class="app-page-panel">
                <span>Conta</span>
                <h1 id="profile-title">Perfil do usuario</h1>
                <p>Base preparada para dados da conta e futura foto de perfil.</p>

                <dl class="app-info-grid">
                    <div>
                        <dt>Nome</dt>
                        <dd>{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ auth()->user()->email }}</dd>
                    </div>
                    <div>
                        <dt>Telefone</dt>
                        <dd>{{ auth()->user()->phone }}</dd>
                    </div>
                </dl>
            </section>
        </main>
    </body>
</html>
