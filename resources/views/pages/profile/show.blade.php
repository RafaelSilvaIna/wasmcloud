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
        @php
            $profilePayload = [
                'name' => $profileUser->name,
                'email' => $profileUser->email,
                'phone' => $profileUser->phone,
                'profile_photo_url' => $profileUser->profile_photo_url,
                'banner_image_url' => $profileUser->banner_image_url,
                'banner_color' => $profileUser->banner_color ?: '#101010',
                'github_url' => $profileUser->github_url,
                'github_repository_url' => $profileUser->github_repository_url,
            ];
        @endphp

        <div data-global-loader-root></div>
        <div data-sonner-root data-status="{{ session('status') }}" data-error="{{ $errors->any() ? $errors->first() : '' }}"></div>
        <x-app.header current-page="Perfil" />

        <div
            data-profile-root
            data-csrf-token="{{ csrf_token() }}"
            data-details-url="{{ route('profile.details.update') }}"
            data-appearance-url="{{ route('profile.appearance.update') }}"
            data-image-url="{{ route('profile.image.upload') }}"
        ></div>
        <script type="application/json" data-profile-payload>{!! json_encode($profilePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    </body>
</html>
