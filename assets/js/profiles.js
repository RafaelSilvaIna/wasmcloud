document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('profiles-grid');
    const modalForm = document.getElementById('pipo-profile-modal');
    const modalPin = document.getElementById('pipo-pin-modal');
    const modalAvatars = document.getElementById('pipo-avatar-modal');
    const actionMenu = document.getElementById('pipo-profile-action-menu');
    const pressOverlay = document.getElementById('pipo-press-overlay');

    let currentProfiles = [];
    let selectedProfileForPin = null;
    let pressTimer;
    let actionProfileData = null;

    const isMobile = () => window.innerWidth <= 768;

    const openModal = (m) => m.classList.add('open');
    const closeModal = (m) => {
        m.classList.remove('open');
        if (m === modalForm) {
            document.getElementById('profile-form').reset();
            document.getElementById('pin-input-box').classList.remove('show');
            document.getElementById('username-status').innerText = '';
            document.getElementById('username').removeAttribute('readonly');
            document.getElementById('current-avatar-img').src = 'https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo';
        }
        if (m === modalPin) {
            document.getElementById('access-pin-input').value = '';
        }
    };

    const fetchProfiles = async () => {
        try {
            const res = await fetch('/api/profiles/list');
            const data = await res.json();
            currentProfiles = data;
            renderProfiles();
        } catch (err) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro ao carregar perfis.');
        }
    };

    const renderProfiles = () => {
        grid.innerHTML = '';
        currentProfiles.forEach(p => {
            const isLock = p.has_pin ? 'lock' : '';
            const isKids = p.is_kids ? 'kids' : '';
            grid.innerHTML += `
                <div class="profile-item ${isLock} ${isKids}" data-id="${p.id}" data-name="${p.profile_name}" data-img="${p.profile_image}" data-pin="${p.has_pin}">
                    <div class="avatar-wrapper">
                        <img src="${p.profile_image}" alt="${p.profile_name}" class="avatar-img">
                    </div>
                    <span class="profile-name">${p.profile_name}</span>
                </div>
            `;
        });

        grid.innerHTML += `
            <div class="add-profile-btn" id="trigger-add-profile">
                <div class="add-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </div>
                <span class="profile-name" style="color:var(--text-muted)">Adicionar Perfil</span>
            </div>
        `;
    };

    grid.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.add-profile-btn');
        if (addBtn) {
            document.getElementById('modal-title').innerText = 'Criar Novo Perfil';
            openModal(modalForm);
            return;
        }

        const item = e.target.closest('.profile-item');
        if (item && !isMobile()) {
            handleProfileSelection(item);
        }
    });

    grid.addEventListener('touchstart', (e) => {
        const item = e.target.closest('.profile-item:not(.add-profile-btn)');
        if (!item || !isMobile()) return;

        pressTimer = setTimeout(() => {
            actionProfileData = {
                id: item.dataset.id,
                name: item.dataset.name,
                image: item.dataset.img,
                hasPin: item.dataset.pin === '1'
            };
            openActionMenu();
        }, 600);
    });

    grid.addEventListener('touchend', (e) => {
        clearTimeout(pressTimer);
        const item = e.target.closest('.profile-item:not(.add-profile-btn)');
        if (item && isMobile() && !actionMenu.classList.contains('open')) {
            handleProfileSelection(item);
        }
    });
    grid.addEventListener('touchmove', () => clearTimeout(pressTimer));

    const handleProfileSelection = (item) => {
        const id = item.dataset.id;
        const name = item.dataset.name;
        const img = item.dataset.img;
        const hasPin = item.dataset.pin === '1';

        if (hasPin) {
            selectedProfileForPin = { id, name, img };
            openModal(modalPin);
            setTimeout(() => document.getElementById('access-pin-input').focus(), 100);
        } else {
            executeSelectProfile(id, name, img, null);
        }
    };

    const executeSelectProfile = async (id, name, img, pin) => {
        try {
            const res = await fetch('/api/profiles/select', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, pin })
            });
            const data = await res.json();

            if (data.success) {
                localStorage.setItem('pipo_current_profile', JSON.stringify({ id, name, img }));
                if (typeof PipoNotification !== 'undefined') {
                    PipoNotification.success(`Bem-vindo de volta, ${name}!`, 3000);
                }
                setTimeout(() => window.location.href = '/home', 1500);
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message || 'Erro ao acessar.');
                if (pin) document.getElementById('access-pin-input').value = '';
            }
        } catch (err) {}
    };

    document.getElementById('btn-confirm-pin').addEventListener('click', () => {
        const pin = document.getElementById('access-pin-input').value;
        if (pin.length === 4) {
            executeSelectProfile(selectedProfileForPin.id, selectedProfileForPin.name, selectedProfileForPin.img, pin);
        }
    });

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-profile');
        btn.innerText = 'Salvando...';
        btn.disabled = true;

        const formData = new FormData(e.target);
        const payload = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('/api/profiles/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.success('Perfil criado com sucesso!');
                closeModal(modalForm);
                fetchProfiles();
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message);
            }
        } catch (err) {}
        btn.innerText = 'Salvar Perfil';
        btn.disabled = false;
    });

    const usernameInput = document.getElementById('username');
    const usernameStatus = document.getElementById('username-status');
    let debounceTimer;

    usernameInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        if (usernameInput.hasAttribute('readonly')) return;

        usernameStatus.innerText = '...';
        usernameStatus.className = 'username-status loading';

        debounceTimer = setTimeout(async () => {
            const username = usernameInput.value.trim();
            if (username.length < 3) {
                usernameStatus.innerText = 'muito curto';
                usernameStatus.className = 'username-status taken';
                return;
            }
            try {
                const res = await fetch(`/api/profiles/check-username?username=${username}`);
                const data = await res.json();
                if (data.available) {
                    usernameStatus.innerText = 'disponível';
                    usernameStatus.className = 'username-status available';
                } else {
                    usernameStatus.innerText = 'em uso';
                    usernameStatus.className = 'username-status taken';
                }
            } catch (err) { usernameStatus.innerText = ''; }
        }, 500);
    });

    document.getElementById('pin-toggle').addEventListener('change', function() {
        document.getElementById('pin-input-box').classList.toggle('show', this.checked);
        if (this.checked) document.getElementById('pin_input').setAttribute('required', 'true');
        else document.getElementById('pin_input').removeAttribute('required');
    });

    document.querySelectorAll('.modal-close, .pipo-modal-cancel').forEach(b => {
        b.addEventListener('click', () => { closeModal(modalForm); closeModal(modalAvatars); closeModal(modalPin); });
    });

    const openActionMenu = () => {
        document.getElementById('menu-avatar-img').src = actionProfileData.image;
        document.getElementById('menu-profile-name').innerText = actionProfileData.name;
        document.getElementById('menu-username').innerText = 'Opções do perfil';
        actionMenu.classList.add('open');
        pressOverlay.classList.add('open');
    };

    const closeActionMenu = () => {
        actionMenu.classList.remove('open');
        pressOverlay.classList.remove('open');
    };

    pressOverlay.addEventListener('click', closeActionMenu);

    document.getElementById('trigger-edit-profile').addEventListener('click', () => {
        closeActionMenu();
        if (typeof PipoNotification !== 'undefined') PipoNotification.info('Edição avançada em breve nas configurações.');
    });

    document.getElementById('trigger-delete-profile').addEventListener('click', () => {
        closeActionMenu();
        if (typeof PipoNotification !== 'undefined') PipoNotification.warning('Para excluir, acesse as configurações da conta principal.');
    });

    document.getElementById('avatar-picker-trigger').addEventListener('click', () => {
        openModal(modalAvatars);
        loadAvatars('adventurer');
    });

    document.getElementById('avatar-categories').addEventListener('click', (e) => {
        const btn = e.target.closest('.category-btn');
        if (!btn) return;
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadAvatars(btn.dataset.category);
    });

    const loadAvatars = async (category) => {
        const gridA = document.getElementById('avatar-grid');
        gridA.innerHTML = '<div class="loader-pipo" style="margin:20px auto; grid-column: 1/-1;"></div>';
        try {
            const res = await fetch(`/api/profiles/avatars?category=${category}`);
            const data = await res.json();
            gridA.innerHTML = '';
            data.avatars.forEach(url => {
                const img = document.createElement('img');
                img.src = url; img.className = 'avatar-option';
                img.addEventListener('click', () => {
                    document.getElementById('current-avatar-img').src = url;
                    document.getElementById('selected-avatar-url').value = url;
                    closeModal(modalAvatars);
                });
                gridA.appendChild(img);
            });
        } catch (err) {}
    };

    fetchProfiles();
});