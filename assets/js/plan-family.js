(function () {
    const modal = document.getElementById('family-invite-modal');
    const openBtn = document.getElementById('open-family-invite');
    const closeBtn = document.getElementById('close-family-invite');
    const form = document.getElementById('family-invite-form');
    const list = document.getElementById('family-member-list');
    const inviteList = document.getElementById('family-invite-list');
    const inviteEmpty = document.getElementById('family-invites-empty');
    const familyTabs = document.querySelectorAll('[data-family-tab]');
    const familyPanels = document.querySelectorAll('[data-family-panel]');
    const removeModal = document.getElementById('family-remove-modal');
    const removeText = document.getElementById('family-remove-text');
    const cancelRemove = document.getElementById('cancel-family-remove');
    const confirmRemove = document.getElementById('confirm-family-remove');
    let pendingRemove = null;

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function setFamilyTab(tabName) {
        familyTabs.forEach((button) => {
            const active = button.dataset.familyTab === tabName;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        familyPanels.forEach((panel) => {
            const active = panel.dataset.familyPanel === tabName;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });
    }

    function updateText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = String(value);
    }

    function pendingInviteCount() {
        return inviteList ? inviteList.querySelectorAll('[data-invite-id]').length : 0;
    }

    function hasPendingInvite(email) {
        if (!inviteList) return false;
        const normalized = String(email || '').trim().toLowerCase();
        return Array.from(inviteList.querySelectorAll('[data-invite-email]')).some((item) => {
            return String(item.dataset.inviteEmail || '').trim().toLowerCase() === normalized;
        });
    }

    function refreshInviteCount() {
        const count = pendingInviteCount();
        updateText('family-invites-count', count);
        updateText('family-pending-count', count);
        if (inviteEmpty) inviteEmpty.hidden = count > 0;
        if (inviteList) inviteList.hidden = count === 0;
    }

    function appendPendingInvite(invite) {
        if (!inviteList || !invite) return;

        const email = String(invite.email || '').trim().toLowerCase();
        if (!email || hasPendingInvite(email)) {
            refreshInviteCount();
            return;
        }

        const item = document.createElement('li');
        item.dataset.inviteId = String(invite.id || '');
        item.dataset.inviteEmail = email;
        const avatar = invite.avatar
            ? `<img src="${escapeHtml(invite.avatar)}" alt="">`
            : '<i data-lucide="mail"></i>';

        item.innerHTML = `
            <span class="me-family-avatar">${avatar}</span>
            <span class="me-family-copy">
                <strong>${escapeHtml(invite.name || 'Usuario Pipocine')}</strong>
                <small>${escapeHtml(email)} · enviado agora</small>
            </span>
            <span class="me-family-pending">Aguardando resposta</span>
        `;

        inviteList.prepend(item);
        refreshInviteCount();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function setModal(open) {
        if (!modal) return;
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) {
            setTimeout(() => document.getElementById('family-email')?.focus(), 60);
        }
    }

    function setRemoveModal(open, item) {
        if (!removeModal) return;
        pendingRemove = open ? item : null;
        const name = item?.querySelector('.me-family-copy strong')?.textContent?.trim() || 'este membro';
        if (removeText) {
            removeText.textContent = `Deseja remover ${name} da sua familia? O acesso aos beneficios familiares sera encerrado imediatamente.`;
        }
        if (confirmRemove) confirmRemove.disabled = false;
        removeModal.classList.toggle('open', open);
        removeModal.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function notifySuccess(message) {
        if (window.PipoNotification?.success) {
            window.PipoNotification.success(message);
            return;
        }

        const note = document.createElement('div');
        note.className = 'plan-family-toast';
        note.textContent = message;
        document.body.appendChild(note);
        setTimeout(() => note.remove(), 3200);
    }

    function notifyError(message) {
        if (window.PipoNotification?.error) {
            window.PipoNotification.error(message);
            return;
        }

        const note = document.createElement('div');
        note.className = 'plan-family-toast error';
        note.textContent = message;
        document.body.appendChild(note);
        setTimeout(() => note.remove(), 3600);
    }

    async function api(path, payload) {
        const response = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || data.error || 'Nao foi possivel concluir a acao.');
        }
        return data;
    }

    openBtn?.addEventListener('click', () => setModal(true));
    closeBtn?.addEventListener('click', () => setModal(false));
    familyTabs.forEach((button) => {
        button.addEventListener('click', () => setFamilyTab(button.dataset.familyTab || 'overview'));
    });
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) setModal(false);
    });
    removeModal?.addEventListener('click', (event) => {
        if (event.target === removeModal) setRemoveModal(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setModal(false);
        if (event.key === 'Escape') setRemoveModal(false);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        const email = String(new FormData(form).get('email') || '').trim().toLowerCase();

        if (hasPendingInvite(email)) {
            notifyError('Ja existe um convite pendente para este usuario. Aguarde a resposta.');
            setFamilyTab('invites');
            return;
        }

        submit.disabled = true;
        const originalHtml = submit.innerHTML;
        submit.textContent = 'Enviando...';

        try {
            const data = await api('/api/v4/family/invite', { email });
            notifySuccess(data.message || 'Convite enviado para a Box do usuario.');
            appendPendingInvite(data.pending_invite);
            form.reset();
            setModal(false);
            setFamilyTab('invites');
        } catch (error) {
            notifyError(error.message);
        } finally {
            submit.disabled = false;
            submit.innerHTML = originalHtml || '<i data-lucide="send"></i>Enviar convite';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });

    list?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-remove-member]');
        if (!button) return;

        setRemoveModal(true, button.closest('li'));
    });

    cancelRemove?.addEventListener('click', () => setRemoveModal(false));

    confirmRemove?.addEventListener('click', async () => {
        if (!pendingRemove) return;
        const button = pendingRemove.querySelector('[data-remove-member]');
        const memberId = Number(button?.dataset.removeMember || 0);
        if (!memberId || !button) return;
        button.disabled = true;
        confirmRemove.disabled = true;
        try {
            const data = await api('/api/v4/family/remove', { member_id: memberId });
            window.PipoNotification?.success(data.message || 'Membro removido.');
            pendingRemove.remove();
            setRemoveModal(false);
            setTimeout(() => window.location.reload(), 600);
        } catch (error) {
            window.PipoNotification?.error(error.message);
            button.disabled = false;
            confirmRemove.disabled = false;
        }
    });
})();
