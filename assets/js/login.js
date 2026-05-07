document.addEventListener('DOMContentLoaded', () => {
    const loginForm   = document.getElementById('login-form');
    const btnSubmit   = document.getElementById('btn-submit');
    const btnText     = document.getElementById('btn-text');
    const btnLoader   = document.getElementById('btn-loader');
    const errorAlert  = document.getElementById('error-alert');
    const errorText   = document.getElementById('error-text');
    const formBox     = document.querySelector('.form-box');
    const inputs      = document.querySelectorAll('.input-group input');

    // ─── Entrada animada dos campos (stagger) ───────────────────────────────
    document.querySelectorAll('.input-group').forEach((group, i) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(18px)';
        group.style.transition = `opacity 0.5s ease ${0.55 + i * 0.1}s, transform 0.5s cubic-bezier(0.16,1,0.3,1) ${0.55 + i * 0.1}s`;
        requestAnimationFrame(() => {
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        });
    });

    btnSubmit.style.opacity = '0';
    btnSubmit.style.transform = 'translateY(16px)';
    btnSubmit.style.transition = 'opacity 0.5s ease 0.8s, transform 0.5s cubic-bezier(0.16,1,0.3,1) 0.8s, background 0.35s ease, box-shadow 0.35s ease, transform 0.35s cubic-bezier(0.16,1,0.3,1)';
    requestAnimationFrame(() => {
        btnSubmit.style.opacity = '1';
        btnSubmit.style.transform = 'translateY(0)';
    });

    // ─── Efeito Ripple no botão ─────────────────────────────────────────────
    btnSubmit.addEventListener('click', (e) => {
        if (btnSubmit.disabled) return;

        const ripple = document.createElement('span');
        const rect   = btnSubmit.getBoundingClientRect();
        const size   = Math.max(rect.width, rect.height) * 1.5;

        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-anim 0.6s linear;
            background: rgba(255,255,255,0.18);
            width: ${size}px;
            height: ${size}px;
            left: ${e.clientX - rect.left - size / 2}px;
            top: ${e.clientY - rect.top - size / 2}px;
            pointer-events: none;
        `;

        btnSubmit.appendChild(ripple);
        ripple.addEventListener('animationend', () => ripple.remove());
    });

    // ─── Efeito magnético suave no card (forma como o card segue o mouse) ──
    const loginSideForm = document.querySelector('.login-side-form');
    if (loginSideForm && window.matchMedia('(min-width: 1025px)').matches) {
        loginSideForm.addEventListener('mousemove', (e) => {
            const rect = formBox.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = (e.clientX - cx) / rect.width;
            const dy = (e.clientY - cy) / rect.height;
            formBox.style.transform = `perspective(1200px) rotateY(${dx * 3}deg) rotateX(${-dy * 3}deg) translateZ(4px)`;
        });

        loginSideForm.addEventListener('mouseleave', () => {
            formBox.style.transition = 'transform 0.6s cubic-bezier(0.16,1,0.3,1)';
            formBox.style.transform = 'perspective(1200px) rotateY(0deg) rotateX(0deg) translateZ(0)';
            setTimeout(() => { formBox.style.transition = ''; }, 600);
        });
    }

    // ─── Utilitários de erro ────────────────────────────────────────────────
    const showError = (message) => {
        errorText.innerText = message;
        errorAlert.classList.add('show');
        errorAlert.animate([
            { transform: 'translateX(-7px)' },
            { transform: 'translateX(7px)' },
            { transform: 'translateX(-4px)' },
            { transform: 'translateX(4px)' },
            { transform: 'translateX(0)' }
        ], { duration: 380, easing: 'ease-in-out' });

        // Destaca as bordas dos inputs brevemente
        inputs.forEach(input => {
            input.style.borderColor = 'rgba(239,68,68,0.5)';
            setTimeout(() => { input.style.borderColor = ''; }, 1800);
        });
    };

    const hideError = () => {
        errorAlert.classList.remove('show');
    };

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

    // ─── Estado de loading ──────────────────────────────────────────────────
    const setLoading = (active) => {
        if (active) {
            btnSubmit.classList.add('loading');
            btnSubmit.disabled = true;
        } else {
            btnSubmit.classList.remove('loading');
            btnSubmit.disabled = false;
            btnText.innerText = 'Entrar na Plataforma';
            resetButtonStyle();
        }
    };

    // ─── Estado de sucesso ──────────────────────────────────────────────────
    const setSuccess = () => {
        btnSubmit.classList.remove('loading');
        btnSubmit.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        btnSubmit.style.boxShadow = '0 10px 30px rgba(16,185,129,0.45)';
        btnText.innerText = 'Acesso confirmado!';
        btnText.style.visibility = 'visible';
        btnLoader.style.display = 'none';

        // Leve pulso no card sinalizando sucesso
        formBox.animate([
            { boxShadow: '0 25px 50px -12px rgba(0,0,0,0.7), 0 0 0 0 rgba(16,185,129,0)' },
            { boxShadow: '0 25px 50px -12px rgba(0,0,0,0.7), 0 0 0 12px rgba(16,185,129,0.12)' },
            { boxShadow: '0 25px 50px -12px rgba(0,0,0,0.7), 0 0 0 0 rgba(16,185,129,0)' }
        ], { duration: 600, easing: 'ease-out' });
    };

    const resetButtonStyle = () => {
        btnSubmit.style.background = '';
        btnSubmit.style.boxShadow = '';
        btnText.style.visibility = '';
        btnLoader.style.display = '';
    };

    // ─── Submit ─────────────────────────────────────────────────────────────
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();
        setLoading(true);

        const data = Object.fromEntries(new FormData(e.target).entries());

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ...data,
                    device_token: getDeviceToken()
                })
            });

            const result = await response.json();

            if (result.success) {
                if (result.requires_2fa && result.redirect) {
                    localStorage.setItem('pipo_2fa_verify_token', result.verify_token || '');
                    btnText.innerText = 'Verificação necessária';
                    setTimeout(() => { window.location.href = result.redirect; }, 500);
                    return;
                }

                localStorage.removeItem('pipo_2fa_verify_token');
                setSuccess();
                setTimeout(() => { window.location.href = '/home'; }, 900);
            } else {
                showError(result.message || 'Credenciais inválidas.');
                setLoading(false);
            }
        } catch (err) {
            showError('Servidor indisponível no momento.');
            setLoading(false);
        }
    });

    // ─── Limpa erro ao digitar ───────────────────────────────────────────────
    inputs.forEach(input => {
        input.addEventListener('input', hideError);
    });
});

// ─── Keyframe do ripple (injetado via JS para não poluir o CSS) ────────────
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
@keyframes ripple-anim {
    to { transform: scale(3); opacity: 0; }
}`;
document.head.appendChild(rippleStyle);
