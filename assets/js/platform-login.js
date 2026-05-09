document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('platform-login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const selectedEmail = document.getElementById('selected-email');
    const stepKicker = document.getElementById('step-kicker');
    const title = document.getElementById('auth-title');
    const subtitle = document.getElementById('auth-subtitle');
    const submit = document.getElementById('btn-submit');
    const submitText = document.getElementById('btn-text');
    const errorAlert = document.getElementById('error-alert');
    const errorText = document.getElementById('error-text');
    const backToEmail = document.getElementById('back-to-email');
    const methodsModal = document.getElementById('methods-modal');
    const consentModal = document.getElementById('cineveo-consent-modal');
    const cookieModal = document.getElementById('cookie-modal');
    const statusBox = document.getElementById('cineveo-status');
    const progressItems = Array.from(document.querySelectorAll('#auth-progress span'));

    let step = 'email';

    const showStep = (nextStep) => {
        step = nextStep;
        document.querySelectorAll('.auth-step').forEach(item => {
            item.classList.toggle('active', item.dataset.step === step);
        });
        stepKicker.textContent = step === 'email' ? 'Passo 1 de 2' : 'Passo 2 de 2';
        title.textContent = step === 'email' ? 'Informe seus dados para entrar' : 'Digite sua senha';
        subtitle.textContent = step === 'email'
            ? 'Ou crie uma conta.'
            : 'Entrando com a conta Pipocine.';
        submitText.textContent = step === 'email' ? 'Continuar' : 'Entrar';
        progressItems.forEach((item, index) => item.classList.toggle('active', index <= (step === 'email' ? 0 : 1)));
        selectedEmail.textContent = emailInput.value.trim();
        passwordInput.required = step === 'password';
        setTimeout(() => (step === 'email' ? emailInput : passwordInput).focus(), 80);
    };

    const setLoading = (active) => {
        submit.disabled = active;
        submit.classList.toggle('loading', active);
    };

    const showError = (message) => {
        errorText.textContent = message;
        errorAlert.classList.add('show');
    };

    const hideError = () => errorAlert.classList.remove('show');

    const getDeviceToken = () => {
        const key = 'pipo_2fa_device_token';
        let token = localStorage.getItem(key);

        if (!token) {
            const bytes = new Uint8Array(32);
            crypto.getRandomValues(bytes);
            token = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
            localStorage.setItem(key, token);
        }

        return token;
    };

    const openModal = (modal) => modal.classList.add('open');
    const closeModals = () => document.querySelectorAll('.auth-modal').forEach(modal => modal.classList.remove('open'));

    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeModals));
    document.getElementById('open-methods')?.addEventListener('click', () => openModal(methodsModal));
    document.getElementById('choose-cineveo').addEventListener('click', () => {
        closeModals();
        openModal(consentModal);
    });
    document.getElementById('continue-cineveo').addEventListener('click', () => {
        closeModals();
        openModal(cookieModal);
    });

    document.getElementById('check-cineveo-session').addEventListener('click', async () => {
        statusBox.classList.remove('show');
        try {
            const response = await fetch('/api/auth/status', { headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));

            if (response.ok && result.isAuthenticated) {
                window.location.href = '/select-profile';
                return;
            }
        } catch (error) {}

        statusBox.textContent = 'Nao encontramos uma sessao Cineveo ativa neste navegador. Faca login no site do Cineveo, permita cookies de terceiros e tente novamente.';
        statusBox.classList.add('show');
    });

    backToEmail.addEventListener('click', () => {
        hideError();
        showStep('email');
    });

    emailInput.addEventListener('input', hideError);
    passwordInput.addEventListener('input', hideError);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError();

        if (step === 'email') {
            const email = emailInput.value.trim();
            if (!email) {
                showError('Informe seu e-mail.');
                return;
            }

            setLoading(true);
            try {
                const response = await fetch('/api/v4/auth/email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ identifier: email })
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    showError(result.message || 'Nao foi possivel validar este e-mail.');
                    return;
                }

                if (!result.exists) {
                    window.location.href = '/login/plataforma/register?identifier=' + encodeURIComponent(email);
                    return;
                }

                showStep('password');
            } catch (error) {
                showError('Servidor indisponivel no momento.');
            } finally {
                setLoading(false);
            }
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('/api/v4/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    identifier: emailInput.value.trim(),
                    password: passwordInput.value,
                    device_token: getDeviceToken()
                })
            });
            const result = await response.json();

            if (result.success) {
                if (result.requires_2fa && result.redirect) {
                    localStorage.setItem('pipo_login_provider', 'platform');
                    localStorage.setItem('pipo_2fa_verify_token', result.verify_token || '');
                    window.location.href = result.redirect;
                    return;
                }

                localStorage.removeItem('pipo_login_provider');
                window.location.href = result.redirect || '/select-profile';
                return;
            }

            showError(result.message || 'E-mail ou senha incorretos.');
        } catch (error) {
            showError('Servidor indisponivel no momento.');
        } finally {
            setLoading(false);
        }
    });

    const queryEmail = new URLSearchParams(window.location.search).get('identifier') || new URLSearchParams(window.location.search).get('email');
    if (queryEmail) {
        emailInput.value = queryEmail;
    }
});
