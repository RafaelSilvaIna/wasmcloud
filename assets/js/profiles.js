document.addEventListener('DOMContentLoaded', () => {
    // ── Elementos ─────────────────────────────────────────────────────────
    const grid             = document.getElementById('profiles-grid');
    const modalForm        = document.getElementById('pipo-profile-modal');
    const modalPin         = document.getElementById('pipo-pin-modal');
    const modalAvatars     = document.getElementById('pipo-avatar-modal');
    const modalAccountType = document.getElementById('pipo-account-type-modal');
    const actionMenu       = document.getElementById('pipo-profile-action-menu');
    const pressOverlay     = document.getElementById('pipo-press-overlay');

    let currentProfiles        = [];
    let selectedProfileForPin  = null;
    let pressTimer;
    let actionProfileData      = null;

    const isMobile = () => window.innerWidth <= 768;

    // ── Helpers de modal ──────────────────────────────────────────────────
    const openModal  = (m) => { if (m) m.classList.add('open'); };
    const closeModal = (m) => {
        if (!m) return;
        m.classList.remove('open');

        if (m === modalForm) {
            const form = document.getElementById('profile-form');
            if (form) form.reset();
            const pinBox = document.getElementById('pin-input-box');
            if (pinBox) pinBox.classList.remove('show');
            const uStatus = document.getElementById('username-status');
            if (uStatus) uStatus.innerText = '';
            const uInput = document.getElementById('username');
            if (uInput) uInput.removeAttribute('readonly');
            const avatarImg = document.getElementById('current-avatar-img');
            if (avatarImg) avatarImg.src = 'https://api.dicebear.com/7.x/adventurer/svg?seed=Pipo';
            const pinInput = document.getElementById('pin_input');
            if (pinInput) pinInput.value = '';
            updateDots('pin-dots', 0);
            selectAccountType('standard');
        }

        if (m === modalPin) {
            const accessPin = document.getElementById('access-pin-input');
            if (accessPin) accessPin.value = '';
            updateDots('access-pin-dots', 0);
        }
    };

    // ── Carregar e renderizar perfis ──────────────────────────────────────
    const fetchProfiles = async () => {
        try {
            const res  = await fetch('/api/profiles/list');
            const data = await res.json();
            currentProfiles = data;
            renderProfiles();
        } catch (err) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro ao carregar perfis.');
        }
    };

    const renderProfiles = () => {
        if (!grid) return;
        grid.innerHTML = '';
        currentProfiles.forEach(p => {
            const isLock = p.has_pin ? 'lock' : '';
            const isKids = p.is_kids ? 'kids' : '';

            // ── NOVA BADGE KIDS MODERNA ──────────────────────────────────────────────
            // Implementação do novo design profissional, moderno e responsivo.
            const kidsBadge = p.is_kids
                ? `<span class="pipo-badge-kids" aria-label="Perfil infantil"
                        style="display: inline-flex; align-items: center; gap: 6px; 
                               background: linear-gradient(135deg, #2c3e50 0%, #1a2533 100%); 
                               color: #ffffff; padding: 6px 12px; border-radius: 50px; 
                               font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                               letter-spacing: 1px; border: 1px solid rgba(255,255,255,0.05); 
                               box-shadow: inset 0 1px 3px rgba(255,255,255,0.1), 0 2px 5px rgba(0,0,0,0.2); 
                               max-width: 90%;">
                       <span class="pipo-badge-icon" style="color: #3498db; display: flex;">
                           <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 12s2-2 2-2 1 0 1 1.5V13"></path><path d="M12 12s-2-2-2-2-1 0-1 1.5V13"></path><path d="M12 12v3.5c0 .7-.3 1.2-1 1.5h-.1c-.7-.3-1-.8-1-1.5V12.5a1.5 1.5 0 0 1 1.5-1.5H12"></path></svg>
                       </span>
                       <span class="pipo-badge-text" style="line-height: 1;">Kids</span>
                   </span>`
                : '';
            // ─────────────────────────────────────────────────────────────────────────

            grid.innerHTML += `
                <div class="profile-item ${isLock} ${isKids}" data-id="${p.id}" data-name="${p.profile_name}" data-img="${p.profile_image}" data-pin="${p.has_pin}">
                    <div class="avatar-wrapper">
                        <img src="${p.profile_image}" alt="${p.profile_name}" class="avatar-img">
                    </div>
                    <span class="profile-name">${p.profile_name}</span>
                    ${kidsBadge}
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

    // ── Cliques na grid ───────────────────────────────────────────────────
    if (grid) {
        grid.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.add-profile-btn');
            if (addBtn) {
                const title = document.getElementById('modal-title');
                if (title) title.innerText = 'Criar Novo Perfil';
                openModal(modalForm);
                return;
            }

            const item = e.target.closest('.profile-item');
            if (item && !isMobile()) handleProfileSelection(item);
        });

        grid.addEventListener('touchstart', (e) => {
            const item = e.target.closest('.profile-item:not(.add-profile-btn)');
            if (!item || !isMobile()) return;
            pressTimer = setTimeout(() => {
                actionProfileData = {
                    id:     item.dataset.id,
                    name:   item.dataset.name,
                    image:  item.dataset.img,
                    hasPin: item.dataset.pin === '1' || item.dataset.pin === 'true'
                };
                openActionMenu();
            }, 600);
        });

        grid.addEventListener('touchend', (e) => {
            clearTimeout(pressTimer);
            const item = e.target.closest('.profile-item:not(.add-profile-btn)');
            const isMenuOpen = actionMenu ? actionMenu.classList.contains('open') : false;
            
            if (item && isMobile() && !isMenuOpen) {
                handleProfileSelection(item);
            }
        });

        grid.addEventListener('touchmove', () => clearTimeout(pressTimer));
    }

    // ── Seleção de perfil ─────────────────────────────────────────────────
    const handleProfileSelection = (item) => {
        const id     = item.dataset.id;
        const name   = item.dataset.name;
        const img    = item.dataset.img;
        const hasPin = item.dataset.pin === '1' || item.dataset.pin === 'true';

        if (hasPin) {
            selectedProfileForPin = { id, name, img };
            openModal(modalPin);
        } else {
            executeSelectProfile(id, name, img, null);
        }
    };

    const executeSelectProfile = async (id, name, img, pin) => {
        try {
            const res  = await fetch('/api/profiles/select', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id, pin })
            });
            const data = await res.json();

            if (data.success) {
                localStorage.setItem('pipo_current_profile', JSON.stringify({ id, name, img }));
                if (typeof PipoNotification !== 'undefined') PipoNotification.success(`Bem-vindo de volta, ${name}!`, 3000);
                setTimeout(() => window.location.href = '/home', 1000);
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message || 'Erro ao acessar.');
                const accessPin = document.getElementById('access-pin-input');
                if (pin && accessPin) {
                    accessPin.value = '';
                    updateDots('access-pin-dots', 0);
                    const dotsWrap = document.getElementById('access-pin-dots');
                    if(dotsWrap) {
                        dotsWrap.classList.add('shake');
                        setTimeout(() => dotsWrap.classList.remove('shake'), 500);
                    }
                }
            }
        } catch (err) {}
    };

    // ── Teclado PIN ───────────────────────────────────────────────────────
    const updateDots = (containerId, length) => {
        const dots = document.querySelectorAll(`#${containerId} .pin-dot`);
        dots.forEach((dot, i) => {
            if (i < length) dot.classList.add('filled');
            else dot.classList.remove('filled');
        });
    };

    const setupNumpad = (numpadSelector, inputId, dotsId, autoSubmit = false) => {
        const numpad = document.querySelector(numpadSelector);
        if (!numpad) return;

        numpad.addEventListener('click', (e) => {
            const key = e.target.closest('.pin-key');
            if (!key || key.classList.contains('pin-key--empty')) return;

            const input = document.getElementById(inputId);
            if (!input) return;

            let val = input.value || '';

            if (key.classList.contains('pin-key--del')) {
                val = val.slice(0, -1);
            } else {
                const digit = key.dataset.digit;
                if (digit !== undefined && val.length < 4) val += digit;
            }

            input.value = val;
            updateDots(dotsId, val.length);

            if (autoSubmit && val.length === 4 && selectedProfileForPin) {
                executeSelectProfile(
                    selectedProfileForPin.id,
                    selectedProfileForPin.name,
                    selectedProfileForPin.img,
                    val
                );
            }
        });
    };

    setupNumpad('#pin-numpad', 'pin_input', 'pin-dots', false);
    setupNumpad('#pipo-pin-modal .pin-numpad', 'access-pin-input', 'access-pin-dots', true);

    // ── Formulário de criação/edição ──────────────────────────────────────
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-save-profile');
            if (btn) { btn.innerText = 'Salvando...'; btn.disabled = true; }

            // CORREÇÃO ANTERIOR MANTIDA: Lemos diretamente o estado do Switch "kids-toggle".
            const kidsToggle = document.getElementById('kids-toggle');
            const profileType = (kidsToggle && kidsToggle.checked) ? 'kids' : (document.getElementById('pro_type')?.value || 'standard');

            const payload = {
                name:     (document.getElementById('pro_name')?.value     || '').trim(),
                username: (document.getElementById('username')?.value     || '').trim(),
                image:     document.getElementById('selected-avatar-url')?.value || '',
                type:      profileType, 
                pin:       document.getElementById('pin_input')?.value            || ''
            };

            const isPinEnabled = document.getElementById('pin-toggle')?.checked;
            if (!isPinEnabled) {
                payload.pin = '';
            } else if (payload.pin.length < 4) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.warning('O PIN deve conter 4 dígitos.');
                if (btn) { btn.innerText = 'Salvar Perfil'; btn.disabled = false; }
                return;
            }

            try {
                const res  = await fetch('/api/profiles/create', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(payload)
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

            if (btn) { btn.innerText = 'Salvar Perfil'; btn.disabled = false; }
        });
    }

    // ── Validação de username ─────────────────────────────────────────────
    const usernameInput  = document.getElementById('username');
    const usernameStatus = document.getElementById('username-status');
    let debounceTimer;

    if (usernameInput) {
        usernameInput.addEventListener('input', (e) => {
            const clean = e.target.value.replace(/[^a-zA-Z0-9_]/g, '');
            if (clean !== e.target.value) e.target.value = clean;

            clearTimeout(debounceTimer);
            if (usernameInput.hasAttribute('readonly')) return;
            if (!usernameStatus) return;

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
                    const res  = await fetch(`/api/profiles/check-username?username=${username}`);
                    const data = await res.json();
                    if (data.available) {
                        usernameStatus.innerText = 'disponível';
                        usernameStatus.className = 'username-status available';
                    } else {
                        usernameStatus.innerText = 'em uso';
                        usernameStatus.className = 'username-status taken';
                    }
                } catch (err) { if (usernameStatus) usernameStatus.innerText = ''; }
            }, 500);
        });
    }

    // ── Toggle PIN ────────────────────────────────────────────────────────
    const pinToggle = document.getElementById('pin-toggle');
    if (pinToggle) {
        pinToggle.addEventListener('change', function () {
            const pinBox  = document.getElementById('pin-input-box');
            if (pinBox)  pinBox.classList.toggle('show', this.checked);
        });
    }

    // ── Botões fechar modal ───────────────────────────────────────────────
    document.querySelectorAll('.modal-close, .pipo-modal-cancel, #btn-cancel-pin').forEach(b => {
        b.addEventListener('click', (e) => {
            const parentModal = e.currentTarget.closest('.profile-modal, .avatar-modal, #pipo-avatar-modal');
            if (parentModal) {
                closeModal(parentModal);
            } else {
                closeModal(modalForm);
                closeModal(modalAvatars);
                closeModal(modalPin);
                closeModal(modalAccountType);
            }
        });
    });

    // ── Tipo de conta ─────────────────────────────────────────────────────
    const selectAccountType = (type) => {
        const proTypeInput = document.getElementById('pro_type');
        if (proTypeInput) proTypeInput.value = type;
        document.querySelectorAll('.account-type-btn').forEach(b => b.classList.remove('active'));
        const btn = document.querySelector(`.account-type-btn[data-type="${type}"]`);
        if (btn) btn.classList.add('active');
    };

    document.querySelectorAll('.account-type-btn').forEach(btn => {
        btn.addEventListener('click', (e) => selectAccountType(e.currentTarget.dataset.type));
    });

    const btnTypeInfo = document.getElementById('btn-type-info');
    if (btnTypeInfo) {
        btnTypeInfo.addEventListener('click', () => openModal(modalAccountType));
    }

    document.querySelectorAll('.atc-select-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            selectAccountType(e.currentTarget.dataset.selectType);
            closeModal(modalAccountType);
        });
    });

    // ── Menu de ação (mobile long-press) ──────────────────────────────────
    const openActionMenu = () => {
        if (!actionProfileData) return;
        const menuAvatar = document.getElementById('menu-avatar-img');
        const menuName   = document.getElementById('menu-profile-name');
        const menuUser   = document.getElementById('menu-username');
        if (menuAvatar) menuAvatar.src = actionProfileData.image;
        if (menuName)   menuName.innerText = actionProfileData.name;
        if (menuUser)   menuUser.innerText = 'Opções do perfil';
        if (actionMenu) actionMenu.classList.add('open');
        if (pressOverlay) pressOverlay.classList.add('open');
    };

    const closeActionMenu = () => {
        if (actionMenu)   actionMenu.classList.remove('open');
        if (pressOverlay) pressOverlay.classList.remove('open');
    };

    if (pressOverlay) pressOverlay.addEventListener('click', closeActionMenu);

    const btnEdit = document.getElementById('trigger-edit-profile');
    if (btnEdit) {
        btnEdit.addEventListener('click', () => {
            closeActionMenu();
            window.location.href = '/manage-profiles';
        });
    }

    const btnDelete = document.getElementById('trigger-delete-profile');
    if (btnDelete) {
        btnDelete.addEventListener('click', () => {
            closeActionMenu();
            if (typeof PipoNotification !== 'undefined') PipoNotification.warning('Para excluir, acesse as configurações da conta principal.');
        });
    }

    // ── Modal de avatares ─────────────────────────────────────────────────
    const avatarPickerTrigger = document.getElementById('avatar-picker-trigger');
    if (avatarPickerTrigger) {
        avatarPickerTrigger.addEventListener('click', () => {
            openModal(modalAvatars);
            loadAvatars('adventurer');
        });
    }

    const avatarCategories = document.getElementById('avatar-categories');
    if (avatarCategories) {
        avatarCategories.addEventListener('click', (e) => {
            const btn = e.target.closest('.category-btn');
            if (!btn) return;
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadAvatars(btn.dataset.category);
        });
    }

    const loadAvatars = async (category) => {
        const gridA = document.getElementById('avatar-grid');
        if (!gridA) return;
        gridA.innerHTML = '<div class="loader-pipo" style="margin:20px auto; grid-column: 1/-1;"></div>';
        try {
            const res  = await fetch(`/api/profiles/avatars?category=${category}`);
            const data = await res.json();
            gridA.innerHTML = '';
            data.avatars.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'avatar-option';
                img.addEventListener('click', () => {
                    const currAvatar = document.getElementById('current-avatar-img');
                    const hiddenUrl  = document.getElementById('selected-avatar-url');
                    if (currAvatar) currAvatar.src = url;
                    if (hiddenUrl)  hiddenUrl.value = url;
                    closeModal(modalAvatars);
                });
                gridA.appendChild(img);
            });
        } catch (err) {}
    };

    // ── Iniciar ───────────────────────────────────────────────────────────
    fetchProfiles();
});