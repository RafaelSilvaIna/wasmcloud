(function () {
    const root = document.getElementById('pix-payment-page');
    if (!root) return;

    const paymentId = Number(root.dataset.paymentId || 0);
    const pixCode = document.getElementById('pix-code');
    const copyBtn = document.getElementById('copy-pix-btn');
    const cancelBtn = document.getElementById('cancel-payment-btn');
    const statusText = document.getElementById('pix-status-text');
    const alertBox = document.getElementById('plan-alert');
    const alertTitle = document.getElementById('plan-alert-title');
    const alertText = document.getElementById('plan-alert-text');
    const alertActions = document.getElementById('plan-alert-actions');
    let pollTimer = null;

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

    copyBtn?.addEventListener('click', async () => {
        await navigator.clipboard.writeText(pixCode.value);
        copyBtn.textContent = 'Codigo copiado';
        setTimeout(() => copyBtn.textContent = 'Copiar codigo Pix', 1800);
    });

    cancelBtn?.addEventListener('click', () => {
        showAlert(
            'Cancelar pagamento?',
            'Esta acao invalida o QR Code atual. Se o Pix ja foi pago, aguarde a confirmacao ou entre em contato com o suporte antes de cancelar.',
            '<button class="plan-secondary" type="button" data-close-alert>Continuar aguardando</button><a class="plan-secondary" href="/settings?section=support">Falar com suporte</a><button class="plan-danger" type="button" id="confirm-cancel-payment">Cancelar pagamento</button>'
        );

        document.getElementById('confirm-cancel-payment')?.addEventListener('click', async () => {
            try {
                await requestJson('/api/v4/subscription/cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_id: paymentId })
                });
                clearInterval(pollTimer);
                window.location.href = '/plan';
            } catch (error) {
                showAlert('Cancelamento nao concluido', error.message, '<a class="plan-secondary" href="/settings?section=support">Falar com suporte</a><button class="plan-secondary" type="button" data-close-alert>Entendi</button>');
            }
        }, { once: true });
    });

    function startPolling() {
        pollTimer = setInterval(async () => {
            try {
                const data = await requestJson('/api/v4/subscription/payment-status?payment_id=' + encodeURIComponent(paymentId), {
                    method: 'GET'
                });

                if (statusText) {
                    statusText.textContent = data.paid ? 'Pagamento confirmado. Ativando assinatura...' : 'Aguardando pagamento Pix';
                }

                if (data.paid && data.activation_url) {
                    clearInterval(pollTimer);
                    window.location.href = data.activation_url;
                }
            } catch (error) {
                console.warn(error.message);
            }
        }, 5000);
    }

    startPolling();
})();
