(function () {
    const root = document.getElementById('payment-activation');
    if (!root) return;

    async function activate() {
        const token = root.dataset.token || '';
        const title = root.querySelector('[data-title]');
        const text = root.querySelector('[data-text]');

        try {
            const response = await fetch('/api/v4/subscription/activate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Nao foi possivel ativar a assinatura.');
            }

            title.textContent = 'Assinatura ativada';
            text.textContent = 'Seu Plano Gold esta pronto. Redirecionando para sua assinatura...';
            setTimeout(() => window.location.href = data.redirect || '/plan/me', 1500);
        } catch (error) {
            title.textContent = 'Ativacao nao concluida';
            text.textContent = error.message;
            root.querySelector('[data-actions]').style.display = 'flex';
        }
    }

    activate();
})();
