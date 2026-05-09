document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('verify-form');
    const tokenInput = document.getElementById('verify-token');
    const codeInputs = Array.from(document.querySelectorAll('.code-input'));
    const rememberDevice = document.getElementById('remember-device');
    const alertBox = document.getElementById('verify-alert');
    const submit = document.getElementById('verify-submit');
    const submitText = document.getElementById('verify-submit-text');

    const urlToken = tokenInput.value.trim();
    const storedToken = localStorage.getItem('pipo_2fa_verify_token') || '';
    const verifyToken = urlToken || storedToken;
    const loginProvider = localStorage.getItem('pipo_login_provider') || 'cineveo';

    if (urlToken) {
        localStorage.setItem('pipo_2fa_verify_token', urlToken);
    }

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

    const setLoading = (active) => {
        submit.disabled = active;
        submit.classList.toggle('loading', active);
    };

    const showAlert = (message, success = false) => {
        alertBox.textContent = message;
        alertBox.classList.toggle('success', success);
        alertBox.classList.add('show');
    };

    const hideAlert = () => {
        alertBox.classList.remove('show', 'success');
    };

    const getCode = () => codeInputs.map(input => input.value).join('');

    const focusFirstEmpty = () => {
        const firstEmpty = codeInputs.find(input => !input.value);
        (firstEmpty || codeInputs[codeInputs.length - 1]).focus();
    };

    const clearCode = () => {
        codeInputs.forEach(input => { input.value = ''; });
        focusFirstEmpty();
    };

    if (!verifyToken) {
        showAlert('Sua verificacao expirou. Volte ao login para entrar novamente.');
        submit.disabled = true;
    } else {
        setTimeout(focusFirstEmpty, 120);
    }

    codeInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            hideAlert();
            input.value = input.value.replace(/\D/g, '').slice(0, 1);

            if (input.value && codeInputs[index + 1]) {
                codeInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && codeInputs[index - 1]) {
                codeInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 6);

            pasted.split('').forEach((char, pasteIndex) => {
                if (codeInputs[pasteIndex]) {
                    codeInputs[pasteIndex].value = char;
                }
            });

            focusFirstEmpty();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();

        const code = getCode();
        if (!verifyToken || code.length !== 6) {
            showAlert('Digite os 6 digitos do autenticador.');
            clearCode();
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(loginProvider === 'platform' ? '/api/v4/auth/verify-2fa' : '/api/auth/verify-2fa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    verify_token: verifyToken,
                    code,
                    remember_device: rememberDevice?.checked || false,
                    device_token: getDeviceToken()
                })
            });

            const result = await response.json();

            if (result.success) {
                localStorage.removeItem('pipo_2fa_verify_token');
                localStorage.removeItem('pipo_login_provider');
                showAlert('Acesso confirmado. Redirecionando...', true);
                submitText.textContent = 'Acesso confirmado';
                setTimeout(() => {
                    window.location.href = loginProvider === 'platform' ? '/select-profile' : '/home';
                }, 650);
                return;
            }

            showAlert(result.message || 'Codigo invalido. Tente novamente.');
            clearCode();
        } catch (error) {
            showAlert('Servidor indisponivel no momento.');
        } finally {
            setLoading(false);
        }
    });
});
