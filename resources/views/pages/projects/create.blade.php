<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Criar projeto na Wasm Cloud.">

        <title>Criar Projeto - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div data-global-loader-root></div>
        <div data-sonner-root data-status="{{ session('status') }}" data-error="{{ $errors->any() ? $errors->first() : '' }}"></div>
        <x-app.header current-page="Criar Projeto" />

        <main class="app-page-shell" aria-labelledby="create-project-title">
            <section class="app-page-panel">
                <span>Projetos</span>
                <h1 id="create-project-title">Criacao de projeto.</h1>
                <p>Base inicial para o fluxo de criacao, documentacao e configuracao guiada de novos projetos.</p>

                <div class="app-action-row">
                    <button class="primary-action large" type="button">Novo projeto</button>
                    <a class="secondary-action large" href="{{ route('documentation') }}" data-global-loading>Ler documentacao</a>
                </div>
            </section>
        </main>
    </body>
</html>
