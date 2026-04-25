document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('main-header');
    const userMenuContainer = document.getElementById('user-menu-container');
    
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

    const fetchAuthStatus = async () => {
        try {
            const response = await fetch('/api/auth/status', {
                headers: { 'Cache-Control': 'no-cache' }
            });

            if (response.status === 500) {
                throw new Error('Erro interno no servidor');
            }

            const data = await response.json();

            if (data.isAuthenticated && data.user) {
                const avatar = data.user.avatar || '/assets/img/default-avatar.png';
                userMenuContainer.innerHTML = `
                    <div class="user-profile-wrapper">
                        <img src="${avatar}" alt="${data.user.fullName}" class="user-avatar" title="${data.user.fullName}">
                        <div class="user-badge">${data.user.plan === 'premium' ? '★' : ''}</div>
                    </div>
                `;
            } else {
                userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
            }
        } catch (err) {
            userMenuContainer.innerHTML = `<a href="/login" class="btn-login">Entrar</a>`;
            if (typeof PipoNotification !== 'undefined') {
                PipoNotification.error('Não foi possível sincronizar sua conta.');
            }
        }
    };

    fetchAuthStatus();
});