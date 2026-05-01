document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('main-header');
    const userMenuContainer = document.getElementById('user-menu-container');

    // ── Marca item ativo no bottom nav pelo pathname ───────────────
    const path = window.location.pathname;
    document.querySelectorAll('.mobile-nav-item').forEach(item => {
        const href = item.getAttribute('href') || '';
        const isActive =
            href === '/' ? path === '/'
            : href.length > 1 && path.startsWith(href);
        if (isActive) item.classList.add('active');
        else item.classList.remove('active');
    });

    // ── Scroll effect ──────────────────────────────────────────────
    window.addEventListener('scroll', () => {
        window.requestAnimationFrame(() => {
            if (window.scrollY > 50) {
                header.classList.remove('header-transparent');
                header.classList.add('header-solid');
            } else {
                header.classList.remove('header-solid');
                header.classList.add('header-transparent');
            }
        });
    }, { passive: true });

    // ── Dropdown (created dynamically, only when profile exists) ───
    let dropdown = null;
    let overlay = null;

    const createDropdown = (profile) => {
        // Overlay
        overlay = document.createElement('div');
        overlay.className = 'profile-dropdown-overlay';
        overlay.addEventListener('click', closeDropdown);

        // Dropdown
        dropdown = document.createElement('div');
        dropdown.className = 'profile-dropdown';
        dropdown.setAttribute('role', 'menu');
        dropdown.innerHTML = `
            <div class="profile-dropdown-header">
                <img src="${profile.image}" alt="${profile.name}" class="profile-dropdown-avatar">
                <div class="profile-dropdown-info">
                    <span class="profile-dropdown-name">${profile.name}</span>
                    <span class="profile-dropdown-label">Perfil ativo</span>
                </div>
            </div>
            <div class="profile-dropdown-divider"></div>
            <a href="/select-profile" class="profile-dropdown-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Trocar de Perfil
            </a>
            <div class="profile-dropdown-item profile-dropdown-logout" id="pipo-logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sair
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.appendChild(dropdown);

        dropdown.querySelector('#pipo-logout-btn').addEventListener('click', async () => {
            closeDropdown();
            try { await fetch('/api/auth/logout', { method: 'POST' }); } catch (_) {}
            window.location.href = '/login';
        });

        document.addEventListener('click', (e) => {
            if (dropdown && dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        });
    };

    const openDropdown = () => {
        if (!dropdown) return;
        overlay.classList.add('open');
        // Force reflow so transition plays
        dropdown.offsetHeight;
        dropdown.classList.add('open');
        
        const btn = document.getElementById('profile-header-btn');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    };

    const closeDropdown = () => {
        if (!dropdown) return;
        dropdown.classList.remove('open');
        overlay.classList.remove('open');
        
        const btn = document.getElementById('profile-header-btn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    // ── Build profile button ───────────────────────────────────────
    const buildProfileMenu = (profile) => {
        createDropdown(profile);

        userMenuContainer.innerHTML = `
            <button class="profile-header-btn" id="profile-header-btn" title="${profile.name}" aria-haspopup="true" aria-expanded="false">
                <img src="${profile.image}" alt="${profile.name}" class="profile-active-avatar">
                <svg class="profile-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        `;

        document.getElementById('profile-header-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdown.classList.contains('open')) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });
    };

    // ── Fetch current profile ──────────────────────────────────────
    const fetchCurrentProfile = async () => {
        try {
            const res = await fetch('/api/profiles/current', {
                headers: { 'Cache-Control': 'no-cache' }
            });

            if (!res.ok) {
                userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
                return;
            }

            const data = await res.json();

            if (!data.authenticated) {
                userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
                return;
            }

            if (!data.profile) {
                const publicPaths = ['/login', '/select-profile'];
                if (!publicPaths.includes(window.location.pathname)) {
                    window.location.href = '/select-profile';
                    return;
                }
                userMenuContainer.innerHTML = `<div class="avatar-skeleton"></div>`;
                return;
            }

            buildProfileMenu(data.profile);

        } catch (err) {
            userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
        }
    };

    fetchCurrentProfile();
});
