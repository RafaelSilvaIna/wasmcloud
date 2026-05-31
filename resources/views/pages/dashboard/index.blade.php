<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Dashboard de workspaces Wasm Cloud.">

        <title>Dashboard - Wasm Cloud</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/css/workspace.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        @php
            $workspacePayload = $workspaces->map(fn ($workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'plan_model' => $workspace->plan_model,
                'created_at' => optional($workspace->created_at)->format('d/m/Y'),
            ])->values();
        @endphp

        <div data-global-loader-root></div>
        <div
            data-sonner-root
            data-status="{{ session('status') }}"
            data-error="{{ $errors->any() ? $errors->first() : '' }}"
        ></div>
        <x-app.header current-page="Dashboard" />

        <div
            data-workspaces-dashboard-root
            data-create-url="{{ route('workspaces.create') }}"
            data-docs-workspace-url="{{ route('documentation.article', 'workspaces') }}"
            data-limit="{{ $workspaceLimit }}"
        ></div>
        <script type="application/json" data-workspaces-payload>{!! json_encode($workspacePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    </body>
</html>
