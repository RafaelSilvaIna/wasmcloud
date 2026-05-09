document.addEventListener('DOMContentLoaded', () => {
    const qrImage = document.getElementById('qr-image');
    const loading = document.getElementById('qr-loading');
    const statusBox = document.getElementById('qr-status');
    const refresh = document.getElementById('qr-refresh');
    let token = '';
    let pollTimer = null;

    async function createQr() {
        clearInterval(pollTimer);
        token = '';
        statusBox.textContent = 'Gerando QR Code...';
        loading.style.display = 'block';
        qrImage.removeAttribute('src');
        qrImage.classList.remove('ready');

        try {
            const response = await fetch('/api/v4/qr-login/create', { method: 'POST', headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (!data.success) {
                statusBox.textContent = data.message || 'Nao foi possivel gerar o QR Code.';
                loading.style.display = 'none';
                return;
            }

            token = data.token;
            statusBox.textContent = 'Aguardando aprovacao em outro dispositivo.';
            qrImage.onload = () => {
                loading.style.display = 'none';
                qrImage.classList.add('ready');
            };
            qrImage.onerror = () => {
                loading.style.display = 'none';
                statusBox.textContent = 'Nao foi possivel carregar o QR Code. Gere um novo codigo.';
            };
            qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=12&format=png&data=${encodeURIComponent(data.approve_url)}`;

            pollTimer = setInterval(poll, 2200);
        } catch (error) {
            loading.style.display = 'none';
            statusBox.textContent = 'Conexao instavel. Tente gerar o QR Code novamente.';
        }
    }

    async function poll() {
        if (!token) return;

        try {
            const response = await fetch('/api/v4/qr-login/poll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ token })
            });
            const data = await response.json();

            if (data.status === 'pending') return;

            if (data.status === 'authenticated') {
                clearInterval(pollTimer);
                statusBox.textContent = 'Acesso aprovado. Entrando...';
                window.location.href = data.redirect || '/select-profile';
                return;
            }

            clearInterval(pollTimer);
            statusBox.textContent = data.message || 'QR Code expirado. Gere um novo codigo.';
        } catch (error) {
            statusBox.textContent = 'Conexao instavel. Tentando novamente...';
        }
    }

    refresh.addEventListener('click', createQr);
    createQr();
});
