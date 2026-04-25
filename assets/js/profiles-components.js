document.addEventListener('DOMContentLoaded', () => {
    const profilesRoot = document.getElementById('pipo-profiles-root');
    if (!profilesRoot) return;

    const modalForm = document.getElementById('pipo-profile-modal');
    const modalAvatars = document.getElementById('pipo-avatar-modal');
    const actionMenu = document.getElementById('pipo-profile-action-menu');
    const pressOverlay = document.getElementById('pipo-press-overlay');

    const longPressDuration = 600;
    let pressTimer;
    let currentProfileData = null;

    const openModal = (modal) => modal.classList.add('open');
    const closeModal = (modal) => {
        modal.classList.remove('open');
        document.getElementById('profile-form').reset();
        document.getElementById('pin-input-box').classList.remove('show');
        document.getElementById('username-status').innerText = '';
        document.getElementById('username').removeAttribute('readonly');
    };

    const isMobile = () => window.innerWidth <= 768;

    profilesRoot.addEventListener('click', (e) => {
        const target = e.target.closest('.profile-item');
        if (!target) return;

        if (target.classList.contains('add-profile-btn')) {
            document.getElementById('modal-title').innerText = 'Criar Novo Perfil';
            document.getElementById('username').removeAttribute('readonly');
            openModal(modalForm);
        } else if (!isMobile()) {
            const profileId = target.dataset.profileId;
            selectProfile(profileId);
        }
    });

    profilesRoot.addEventListener('touchstart', (e) => {
        const target = e.target.closest('.profile-item:not(.add-profile-btn)');
        if (!target || !isMobile()) return;

        pressTimer = setTimeout(() => {
            currentProfileData = {
                id: target.dataset.profileId,
                name: target.querySelector('.profile-name').innerText,
                image: target.querySelector('.avatar-img').src,
                lock: target.classList.contains('lock')
            };
            openActionMenu();
        }, longPressDuration);
    });

    profilesRoot.addEventListener('touchend', () => clearTimeout(pressTimer));
    profilesRoot.addEventListener('touchmove', () => clearTimeout(pressTimer));

    const openActionMenu = () => {
        document.getElementById('menu-avatar-img').src = currentProfileData.image;
        document.getElementById('menu-profile-name').innerText = currentProfileData.name;
        document.getElementById('menu-username').innerText = '@' + currentProfileData.name.toLowerCase();

        actionMenu.classList.add('open');
        pressOverlay.classList.add('open');
    };

    const closeActionMenu = () => {
        actionMenu.classList.remove('open');
        pressOverlay.classList.remove('open');
    };

    pressOverlay.addEventListener('click', closeActionMenu);

    document.querySelectorAll('.modal-close, .pipo-modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(modalForm);
            closeModal(modalAvatars);
        });
    });

    document.getElementById('pin-toggle').addEventListener('change', function() {
        document.getElementById('pin-input-box').classList.toggle('show', this.checked);
    });

    const usernameInput = document.getElementById('username');
    const usernameStatus = document.getElementById('username-status');
    let debounceTimer;

    usernameInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        if (usernameInput.hasAttribute('readonly')) return;

        usernameStatus.innerText = 'verificando...';
        usernameStatus.className = 'username-status loading';

        debounceTimer = setTimeout(async () => {
            const username = usernameInput.value.trim();
            if (username.length < 3) {
                usernameStatus.innerText = 'muito curto';
                usernameStatus.className = 'username-status taken';
                return;
            }

            try {
                const response = await fetch(`/api/profiles/check-username?username=${username}`);
                const result = await response.json();

                if (result.available) {
                    usernameStatus.innerText = 'disponível';
                    usernameStatus.className = 'username-status available';
                } else {
                    usernameStatus.innerText = 'já em uso';
                    usernameStatus.className = 'username-status taken';
                }
            } catch (err) {
                usernameStatus.innerText = '';
            }
        }, 500);
    });

    document.getElementById('trigger-edit-profile').addEventListener('click', () => {
        closeActionMenu();
        document.getElementById('modal-title').innerText = 'Editar Perfil';
        document.getElementById('pro_name').value = currentProfileData.name;
        document.getElementById('username').value = currentProfileData.name.toLowerCase();
        document.getElementById('username').setAttribute('readonly', true);
        document.getElementById('current-avatar-img').src = currentProfileData.image;
        usernameStatus.innerText = '';
        
        if (currentProfileData.lock) {
            document.getElementById('pin-toggle').checked = true;
            document.getElementById('pin-toggle').disabled = true;
        }

        openModal(modalForm);
    });

    document.getElementById('trigger-delete-profile').addEventListener('click', () => {
        closeActionMenu();
        if (typeof PipoNotification !== 'undefined') {
            PipoNotification.warning(`Para excluir '${currentProfileData.name}', entre no Modo de Edição avançado nas configurações.`);
        }
    });

    const triggerAvatarPicker = document.getElementById('avatar-picker-trigger');
    triggerAvatarPicker.addEventListener('click', () => {
        openModal(modalAvatars);
        loadAvatars('adventurer');
    });

    document.querySelector('.avatar-categories').addEventListener('click', (e) => {
        const btn = e.target.closest('.category-btn');
        if (!btn) return;
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadAvatars(btn.dataset.category);
    });

    const loadAvatars = async (category) => {
        const grid = document.getElementById('avatar-grid');
        grid.innerHTML = '<div class="loader-pipo"></div>';

        try {
            const response = await fetch(`/api/profiles/avatars?category=${category}`);
            const result = await response.json();
            grid.innerHTML = '';

            result.avatars.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'avatar-option';
                img.addEventListener('click', () => {
                    document.getElementById('current-avatar-img').src = url;
                    document.getElementById('selected-avatar-url').value = url;
                    closeModal(modalAvatars);
                });
                grid.appendChild(img);
            });
        } catch (err) {
            grid.innerHTML = '<p class="text-error">Erro ao carregar avatares.</p>';
        }
    };

    const selectProfile = async (id) => {
        try {
            const response = await fetch('/api/profiles/select', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await response.json();
            if (result.success) {
                window.location.href = '/home';
            } else if (result.requires_pin) {
                const pin = prompt('Digite o PIN de 4 dígitos do perfil:');
                if (pin) selectProfileWithPin(id, pin);
            }
        } catch (err) {}
    };

    const selectProfileWithPin = async (id, pin) => {
        try {
            const response = await fetch('/api/profiles/select', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, pin })
            });
            const result = await response.json();
            if (result.success) {
                window.location.href = '/home';
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error('PIN incorreto.');
            }
        } catch (err) {}
    };
});