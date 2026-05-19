(function () {
    const modal = document.getElementById('family-invite-modal');
    const openBtn = document.getElementById('open-family-invite');
    const closeBtn = document.getElementById('close-family-invite');
    const form = document.getElementById('family-invite-form');
    const list = document.getElementById('family-member-list');
    const removeModal = document.getElementById('family-remove-modal');
    const removeText = document.getElementById('family-remove-text');
    const cancelRemove = document.getElementById('cancel-family-remove');
    const confirmRemove = document.getElementById('confirm-family-remove');
    let pendingRemove = null;

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
        const email = String(new FormData(form).get('email') || '').trim();
        submit.disabled = true;

        try {
            const data = await api('/api/v4/family/invite', { email });
            notifySuccess(data.message || 'Convite enviado para a Box do usuario.');
            form.reset();
            setModal(false);
        } catch (error) {
            window.PipoNotification?.error(error.message);
        } finally {
            submit.disabled = false;
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
