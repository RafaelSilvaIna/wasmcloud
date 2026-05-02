<header id="main-header" class="header-transparent">
    <div class="header-container">
        <a href="/home" class="logo-link">
            <img src="/assets/img/logo-pipocine.png" alt="PipoCine" class="logo-img">
        </a>
        
        <nav class="desktop-nav">
            <a href="/home" class="nav-link active">Início</a>
            <a href="/catalogo" class="nav-link">Catálogo</a>
            <a href="/minha-lista" class="nav-link">Minha Lista</a>
            <a href="/canais" class="nav-link">Canais</a>
        </nav>

        <div class="header-actions">
            <button class="search-btn" aria-label="Pesquisar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            <div class="user-menu" id="user-menu-container">
                <div class="avatar-skeleton"></div>
            </div>
        </div>
    </div>
</header>

<nav class="mobile-bottom-nav">
    <a href="/home" class="mobile-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Início</span>
    </a>
    <a href="/catalogo" class="mobile-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Catálogo</span>
    </a>
    <a href="/minha-lista" class="mobile-nav-item" id="mobile-nav-lista">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
        </svg>
        <span>Minha Lista</span>
    </a>
    <a href="/canais" class="mobile-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="2"></circle>
            <path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48 0a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"></path>
        </svg>
        <span>Canais</span>
    </a>
</nav>
