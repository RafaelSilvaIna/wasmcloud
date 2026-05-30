<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Dashboard Wasm Cloud.">

        <title>Dashboard - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div data-global-loader-root></div>
        <div
            data-sonner-root
            data-status="{{ session('status') }}"
            data-error="{{ $errors->any() ? $errors->first() : '' }}"
        ></div>

        <main class="dashboard-shell" aria-labelledby="dashboard-title">
            <section class="dashboard-panel">
                <div>
                    <span>Wasm Cloud</span>
                    <h1 id="dashboard-title">Seu workspace esta pronto.</h1>
                    <p>Bem-vindo, {{ auth()->user()->name }}. A proxima etapa e criar seu primeiro projeto.</p>
                </div>

                <form method="POST" action="{{ route('logout') }}" data-global-loading>
                    @csrf
                    <button class="secondary-action" type="submit">Sair</button>
                </form>
            </section>
        </main>
    </body>
</html>
