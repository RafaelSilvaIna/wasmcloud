document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('profiles-grid');
    const modalEdit = document.getElementById('pipo-edit-modal');
    const modalAvatars = document.getElementById('pipo-avatar-modal');
    let currentProfiles = [];

    // Funções de Modal
    const openModal = (m) => m.classList.add('open');
    const closeModal = (m) => m.classList.remove('open');

    // Carregar Perfis do Banco de Dados
    const renderSkeletons = () => {
        grid.innerHTML = '';
        for (let i = 0; i < 4; i++) {
            grid.innerHTML += `
                <div class="profile-skeleton">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-text"></div>
                </div>
            `;
        }
    };

    const fetchProfiles = async () => {
        try {
            renderSkeletons();
            const res = await fetch('/api/profiles/list');
            currentProfiles = await res.json();
            renderProfiles();
        } catch (err) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro ao carregar perfis.');
            grid.innerHTML = '<p style="color: var(--profile-text-muted);">Erro ao carregar os perfis.</p>';
        }
    };

    // Renderizar Perfis com a classe 'edit-mode' (Adiciona o ícone de lápis em cima da foto)
    const renderProfiles = () => {
        grid.innerHTML = '';
        currentProfiles.forEach(p => {
            grid.innerHTML += `
                <div class="profile-item edit-mode" data-id="${p.id}" data-name="${p.profile_name}" data-img="${p.profile_image}">
                    <div class="avatar-wrapper">
                        <img src="${p.profile_image}" alt="${p.profile_name}" class="avatar-img">
                    </div>
                    <span class="profile-name">${p.profile_name}</span>
                </div>
            `;
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    // Ao clicar num perfil, abre o modal de edição preenchido
    grid.addEventListener('click', (e) => {
        const item = e.target.closest('.profile-item');
        if (!item) return;

        document.getElementById('edit_profile_id').value = item.dataset.id;
        document.getElementById('edit_pro_name').value = item.dataset.name;
        document.getElementById('current-avatar-img').src = item.dataset.img;
        document.getElementById('selected-avatar-url').value = item.dataset.img;
        
        openModal(modalEdit);
    });

    // Enviar formulário de edição para o Backend
    document.getElementById('edit-profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-edit');
        btn.innerHTML = 'Salvando...'; btn.disabled = true;

        const payload = {
            id: document.getElementById('edit_profile_id').value,
            name: document.getElementById('edit_pro_name').value,
            image: document.getElementById('selected-avatar-url').value
        };

        try {
            const res = await fetch('/api/profiles/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                if (typeof PipoNotification !== 'undefined') PipoNotification.success('Perfil atualizado!');
                closeModal(modalEdit);
                fetchProfiles(); // Recarrega a lista para mostrar o novo nome/foto
            } else {
                if (typeof PipoNotification !== 'undefined') PipoNotification.error(data.message);
            }
        } catch (err) {
            if (typeof PipoNotification !== 'undefined') PipoNotification.error('Erro de conexão.');
        }

        btn.innerHTML = '<i data-lucide="check" width="16" height="16"></i> Salvar'; 
        btn.disabled = false;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    // Lógica do Modal de Avatares (Reaproveitada)
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
                img.src = url;
                img.className = 'avatar-option';
                img.addEventListener('click', () => {
                    document.getElementById('current-avatar-img').src = url;
                    document.getElementById('selected-avatar-url').value = url;
                    closeModal(modalAvatars);
                });
                gridA.appendChild(img);
            });
        } catch (err) {}
    };

    // Fechar modais
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(modalEdit);
            closeModal(modalAvatars);
        });
    });

    // Iniciar
    fetchProfiles();
});