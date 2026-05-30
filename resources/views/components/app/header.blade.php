<div
    data-authenticated-header-root
    data-logo-url="{{ asset('images/brand/wasm-cloud-wordmark.png') }}"
    data-user-name="{{ auth()->user()->name }}"
    data-avatar-url="{{ auth()->user()->profile_photo_url }}"
    data-csrf-token="{{ csrf_token() }}"
    data-current-page="{{ $currentPage ?? 'Dashboard' }}"
    data-sidebar-toggle="{{ ($sidebarToggle ?? false) ? 'true' : 'false' }}"
    data-dashboard-url="{{ route('dashboard') }}"
    data-profile-url="{{ route('profile') }}"
    data-documentation-url="{{ route('documentation') }}"
    data-create-project-url="{{ route('projects.create') }}"
    data-api-url="{{ route('api.docs') }}"
    data-system-specs-url="{{ route('system.specs') }}"
    data-logout-url="{{ route('logout') }}"
></div>
