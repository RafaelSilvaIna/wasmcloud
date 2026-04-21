window.AuthLoginComponent = class {
    static render() {
        const name = window.AppBranding?.institution_name || 'School System';
        const slogan = window.AppBranding?.slogan || 'Excelência em Educação';
        const logoUrl = window.AppBranding?.logo_url ? `../..${window.AppBranding.logo_url}?t=${Date.now()}` : '../../assets/img/not-found.png';

        return `
            <div class="auth-container">
                <div class="auth-banner">
                    <div class="auth-institution-name">${name}</div>
                    <div class="auth-slogan">${slogan}</div>
                </div>
                <div class="auth-form-wrapper">
                    <div class="auth-box">
                        <div class="auth-logo-container">
                            <img src="${logoUrl}" alt="Logótipo da Instituição">
                        </div>
                        <div id="loginAlert" class="status-alert status-error"></div>
                        <form id="secureLoginForm">
                            <div class="form-group">
                                <label for="email">E-mail Institucional</label>
                                <input type="email" id="email" autocomplete="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Credencial de Segurança</label>
                                <input type="password" id="password" autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="auth-btn" id="submitBtn">Aceder ao Sistema</button>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    static attachEvents() {
        document.getElementById('secureLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const alertBox = document.getElementById('loginAlert');
            const btn = document.getElementById('submitBtn');
            
            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerText = 'A validar...';

            try {
                const response = await fetch('../../api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    localStorage.setItem('sys_token', data.token);
                    if (data.role === 'master') {
                        window.location.replace('../admin/index.html');
                    } else if (data.role === 'coordinator') {
                        window.location.replace('../coordinator/index.html');
                    } else {
                        window.location.replace('../student/index.html');
                    }
                } else {
                    this.showError(data.error || 'Credenciais inválidas.');
                }
            } catch (error) {
                this.showError('Ocorreu uma falha de comunicação com o servidor.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Aceder ao Sistema';
            }
        });
    }

    static showError(msg) {
        const alert = document.getElementById('loginAlert');
        alert.innerText = msg;
        alert.style.display = 'block';
    }
};