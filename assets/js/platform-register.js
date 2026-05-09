document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('platform-register-form');
    const steps = ['email', 'password', 'name'];
    const stepKicker = document.getElementById('step-kicker');
    const title = document.getElementById('auth-title');
    const subtitle = document.getElementById('auth-subtitle');
    const submit = document.getElementById('btn-submit');
    const submitText = document.getElementById('btn-text');
    const errorAlert = document.getElementById('error-alert');
    const errorText = document.getElementById('error-text');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password-confirmation');
    const fullNameInput = document.getElementById('full-name');
    const selectedEmail = document.getElementById('selected-email');
    const progressItems = Array.from(document.querySelectorAll('#auth-progress span'));
    let index = 0;

    const copy = {
        email: ['Crie sua conta', 'Informe seu email para comecar.', 'Continuar'],
        password: ['Crie sua senha', 'Use pelo menos 8 caracteres.', 'Continuar'],
        name: ['Como devemos chamar voce?', 'Informe seu nome completo para concluir.', 'Criar conta']
    };

    const showError = (message) => {
        errorText.textContent = message;
        errorAlert.classList.add('show');
    };

    const hideError = () => errorAlert.classList.remove('show');

    const setLoading = (active) => {
        submit.disabled = active;
        submit.classList.toggle('loading', active);
    };

    const showStep = () => {
        const step = steps[index];
        document.querySelectorAll('.auth-step').forEach(item => {
            item.classList.toggle('active', item.dataset.step === step);
        });
        stepKicker.textContent = `Passo ${index + 1} de 3`;
        title.textContent = copy[step][0];
        subtitle.textContent = copy[step][1];
        submitText.textContent = copy[step][2];
        progressItems.forEach((item, itemIndex) => item.classList.toggle('active', itemIndex <= index));
        selectedEmail.textContent = emailInput.value.trim();
        const focusTarget = step === 'email' ? emailInput : (step === 'password' ? passwordInput : fullNameInput);
        setTimeout(() => focusTarget.focus(), 80);
    };

    document.querySelectorAll('[data-back-register]').forEach(button => {
        button.addEventListener('click', () => {
            hideError();
            index = Math.max(0, index - 1);
            showStep();
        });
    });

    form.querySelectorAll('input').forEach(input => input.addEventListener('input', hideError));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError();

        if (steps[index] === 'email') {
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

                if (!result.success) {
                    showError(result.message || 'Informe um email ou numero de celular valido.');
                    return;
                }

                if (result.exists) {
                    showError(result.type === 'phone' ? 'Este celular ja esta cadastrado. Volte ao login para entrar.' : 'Este email ja esta cadastrado. Volte ao login para entrar.');
                    return;
                }

                index = 1;
                showStep();
            } catch (error) {
                showError('Servidor indisponivel no momento.');
            } finally {
                setLoading(false);
            }
            return;
        }

        if (steps[index] === 'password') {
            if (passwordInput.value.length < 8) {
                showError('A senha precisa ter pelo menos 8 caracteres.');
                return;
            }

            if (passwordInput.value !== passwordConfirmation.value) {
                showError('As senhas nao conferem.');
                return;
            }

            index = 2;
            showStep();
            return;
        }

        if (fullNameInput.value.trim().length < 3) {
            showError('Informe seu nome completo.');
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('/api/v4/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    identifier: emailInput.value.trim(),
                    password: passwordInput.value,
                    password_confirmation: passwordConfirmation.value,
                    full_name: fullNameInput.value.trim()
                })
            });
            const result = await response.json();

            if (result.success) {
                window.location.href = result.redirect || '/select-profile';
                return;
            }

            showError(result.message || 'Nao foi possivel criar sua conta.');
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
