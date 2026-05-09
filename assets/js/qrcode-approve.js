document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('approve-btn');
    const statusBox = document.getElementById('approve-status');

    button.addEventListener('click', async () => {
        button.disabled = true;
        statusBox.textContent = 'Aprovando acesso...';

        try {
            const response = await fetch('/api/v4/qr-login/approve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ token: button.dataset.token || '' })
            });
            const data = await response.json();

            statusBox.textContent = data.message || (data.success ? 'Acesso aprovado.' : 'Nao foi possivel aprovar.');
            if (!data.success) button.disabled = false;
        } catch (error) {
            statusBox.textContent = 'Erro de conexao. Tente novamente.';
            button.disabled = false;
        }
    });
});
