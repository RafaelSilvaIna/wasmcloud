(function () {
    const form = document.getElementById('plan-checkout-form');
    const alertBox = document.getElementById('plan-alert');
    const alertTitle = document.getElementById('plan-alert-title');
    const alertText = document.getElementById('plan-alert-text');
    const alertActions = document.getElementById('plan-alert-actions');

    function showAlert(title, text, actionsHtml) {
        alertTitle.textContent = title;
        alertText.textContent = text;
        alertActions.innerHTML = actionsHtml || '<button class="plan-secondary" type="button" data-close-alert>Entendi</button>';
        alertBox.classList.add('open');
    }

    alertBox?.addEventListener('click', (event) => {
        if (event.target === alertBox || event.target.closest('[data-close-alert]')) {
            alertBox.classList.remove('open');
        }
    });

    async function requestJson(url, options) {
        const response = await fetch(url, options);
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || data.error || 'Nao foi possivel concluir a operacao.');
        }
        return data;
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Gerando Pix...';

        try {
            const payload = Object.fromEntries(new FormData(form).entries());
            payload.accepted_terms = form.querySelector('[name="accepted_terms"]').checked;
            const data = await requestJson('/api/v4/subscription/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            window.location.href = '/plan/pix?payment_id=' + encodeURIComponent(data.payment_id);
        } catch (error) {
            showAlert('Nao foi possivel assinar', error.message);
        } finally {
            submit.disabled = false;
            submit.textContent = 'Gerar QR Code Pix';
        }
    });
})();
