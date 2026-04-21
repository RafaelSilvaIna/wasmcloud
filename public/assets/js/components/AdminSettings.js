window.AdminSettingsManager = class {
    static async load() {
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/master/config/get', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                const configs = data.data.configs;
                document.getElementById('allow_profile_photos_on_signup').checked = configs.allow_profile_photos_on_signup;
                document.getElementById('allow_coordinators_login').checked = configs.allow_coordinators_login;
                document.getElementById('allow_students_login').checked = configs.allow_students_login;
            }
        } catch (e) {
            this.showMessage('Erro ao carregar configuracoes de seguranca da base de dados.', 'error');
        }
    }

    static async save(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        
        const payload = {
            allow_profile_photos_on_signup: document.getElementById('allow_profile_photos_on_signup').checked,
            allow_coordinators_login: document.getElementById('allow_coordinators_login').checked,
            allow_students_login: document.getElementById('allow_students_login').checked
        };

        try {
            const response = await fetch('../../api/master/config/update', {
                method: 'PUT',
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (response.ok && data.status === 'success') {
                this.showMessage('Politicas atualizadas e gravadas na auditoria LGPD com sucesso.', 'success');
            } else {
                this.showMessage(data.message || 'Falha ao atualizar politicas.', 'error');
            }
        } catch (e) {
            this.showMessage('Falha de comunicacao segura (Firewall / Token).', 'error');
        }
    }

    static showMessage(msg, type) {
        const alertBox = document.getElementById('settingsAlert');
        alertBox.style.display = 'block';
        alertBox.innerText = msg;
        if (type === 'success') {
            alertBox.style.backgroundColor = '#d4edda';
            alertBox.style.color = '#155724';
            alertBox.style.borderColor = '#c3e6cb';
        } else {
            alertBox.style.backgroundColor = '#f8d7da';
            alertBox.style.color = '#721c24';
            alertBox.style.borderColor = '#f5c6cb';
        }
        setTimeout(() => { alertBox.style.display = 'none'; }, 5000);
    }
};

document.getElementById('globalSettingsForm').addEventListener('submit', (e) => window.AdminSettingsManager.save(e));
window.AdminSettingsManager.load();