<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Especificacoes do sistema Wasm Cloud.">

        <title>Especificacoes - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div data-global-loader-root></div>
        <div data-sonner-root data-status="{{ session('status') }}" data-error="{{ $errors->any() ? $errors->first() : '' }}"></div>
        <x-app.header current-page="Especificacoes do sistema" />

        <main class="app-page-shell" aria-labelledby="specs-title">
            <section class="app-page-panel">
                <span>Sistema</span>
                <h1 id="specs-title">Especificacoes do sistema.</h1>
                <p>Area para limites, ambientes, recursos disponiveis, requisitos e parametros operacionais.</p>
            </section>
        </main>
    </body>
</html>
