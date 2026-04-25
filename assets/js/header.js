document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('main-header');
    
    const handleScroll = () => {
        window.requestAnimationFrame(() => {
            if (window.scrollY > 50) {
                header.classList.remove('header-transparent');
                header.classList.add('header-solid');
            } else {
                header.classList.remove('header-solid');
                header.classList.add('header-transparent');
            }
        });
    };

    window.addEventListener('scroll', handleScroll, { passive: true });

    const userMenuContainer = document.getElementById('user-menu-container');

    fetch('/api/auth/status', {
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('401');
            }
            return response.json();
        })
        .then(data => {
            if (data.isAuthenticated && data.user) {
                const avatarUrl = data.user.avatar || '/assets/img/default-avatar.png';
                userMenuContainer.innerHTML = `<img src="${avatarUrl}" alt="${data.user.fullName}" class="user-avatar" title="${data.user.fullName}">`;
            } else {
                userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
            }
        })
        .catch(() => {
            userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
        });
});