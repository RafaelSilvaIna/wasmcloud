<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Configuracoes gerais da conta Wasm Cloud.">

        <title>Configuracoes gerais - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/css/settings.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div data-global-loader-root></div>
        <div data-sonner-root data-status="{{ session('status') }}" data-error="{{ $errors->any() ? $errors->first() : '' }}"></div>
        <x-app.header current-page="Configuracoes gerais" />

        <div
            data-settings-root
            data-csrf-token="{{ csrf_token() }}"
            data-sessions-url="{{ route('settings.sessions.index') }}"
            data-destroy-session-url="{{ url('/configuracoes/sessoes') }}"
            data-destroy-others-url="{{ route('settings.sessions.destroy-others') }}"
            data-login-url="{{ route('login') }}"
        ></div>
    </body>
</html>
