(function () {
    const modal = document.getElementById('family-invite-modal');
    const openBtn = document.getElementById('open-family-invite');
    const closeBtn = document.getElementById('close-family-invite');
    const form = document.getElementById('family-invite-form');
    const list = document.getElementById('family-member-list');

    function setModal(open) {
        if (!modal) return;
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) {
            setTimeout(() => document.getElementById('family-email')?.focus(), 60);
        }
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

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setModal(false);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        const email = String(new FormData(form).get('email') || '').trim();
        submit.disabled = true;

        try {
            const data = await api('/api/v4/family/invite', { email });
            window.PipoNotification?.success(data.message || 'Solicitacao enviada.');
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

        const memberId = Number(button.dataset.removeMember || 0);
        button.disabled = true;

        try {
            const data = await api('/api/v4/family/remove', { member_id: memberId });
            window.PipoNotification?.success(data.message || 'Membro removido.');
            button.closest('li')?.remove();
            setTimeout(() => window.location.reload(), 600);
        } catch (error) {
            window.PipoNotification?.error(error.message);
            button.disabled = false;
        }
    });
})();
