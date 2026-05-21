(function () {
    const modal = document.getElementById('renewal-modal');
    if (!modal) return;

    const openButton = document.getElementById('open-renewal-modal');
    const closeButton = document.getElementById('close-renewal-modal');
    const cancelButton = document.getElementById('cancel-renewal-modal');
    const confirmButton = document.getElementById('confirm-renewal-button');
    const terms = document.getElementById('renewal-terms');
    const errorBox = document.getElementById('renewal-modal-error');

    function setOpen(isOpen) {
        modal.classList.toggle('open', isOpen);
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen) {
            errorBox.textContent = '';
            terms.checked = false;
            setTimeout(() => terms.focus(), 80);
        }
    }

    function close() {
        setOpen(false);
        openButton?.focus();
    }

    async function requestRenewal() {
        errorBox.textContent = '';

        if (!terms.checked) {
            errorBox.textContent = 'Confirme a renovacao para gerar o Pix.';
            terms.focus();
            return;
        }

        confirmButton.disabled = true;
        const originalHtml = confirmButton.innerHTML;
        confirmButton.textContent = 'Gerando Pix...';

        try {
            const response = await fetch('/api/v4/subscription/renew', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan: 'gold', accepted_terms: true })
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || data.error || 'Nao foi possivel gerar a renovacao.');
            }

            window.location.href = '/plan/pix?payment_id=' + encodeURIComponent(data.payment_id);
        } catch (error) {
            errorBox.textContent = error.message;
            confirmButton.disabled = false;
            confirmButton.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    openButton?.addEventListener('click', () => setOpen(true));
    closeButton?.addEventListener('click', close);
    cancelButton?.addEventListener('click', close);
    confirmButton?.addEventListener('click', requestRenewal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            close();
        }
    });
})();
