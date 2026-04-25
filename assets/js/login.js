document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const btnSubmit = document.getElementById('btn-submit');
    const btnText = document.getElementById('btn-text');
    const btnLoader = document.getElementById('btn-loader');
    const errorAlert = document.getElementById('error-alert');
    const errorText = document.getElementById('error-text');

    const showError = (message) => {
        errorText.innerText = message;
        errorAlert.classList.add('show');
        
        errorAlert.animate([
            { transform: 'translateX(-8px)' },
            { transform: 'translateX(8px)' },
            { transform: 'translateX(-4px)' },
            { transform: 'translateX(4px)' },
            { transform: 'translateX(0)' }
        ], { duration: 400, easing: 'ease-in-out' });
    };

    const hideError = () => {
        errorAlert.classList.remove('show');
    };

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        btnSubmit.classList.add('loading');
        btnSubmit.disabled = true;

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                btnSubmit.style.backgroundColor = 'var(--status-success)';
                btnSubmit.style.boxShadow = '0 10px 25px rgba(16, 185, 129, 0.4)';
                btnText.innerText = 'Redirecionando...';
                btnText.style.visibility = 'visible';
                btnLoader.style.display = 'none';
                
                setTimeout(() => {
                    window.location.href = '/home';
                }, 800);
            } else {
                showError(result.message || 'Credenciais inválidas.');
                btnSubmit.classList.remove('loading');
                btnSubmit.disabled = false;
                btnText.innerText = 'Entrar na Plataforma';
            }
        } catch (err) {
            showError('Servidor indisponível no momento.');
            btnSubmit.classList.remove('loading');
            btnSubmit.disabled = false;
            btnText.innerText = 'Entrar na Plataforma';
        }
    });

    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        input.addEventListener('input', hideError);
    });
});