window.AdminSettingsManager = class {
    static async load() {
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/master/config/get', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await response.json();
            
            if (response.ok && (data.status === 'success' || data.data.status === 'pending_setup' || data.data.status === 'active')) {
                const configs = data.data.configs || data.data;
                
                const mapCheckbox = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.checked = (val === true || val === 'true');
                };

                mapCheckbox('allow_auth_routing', configs.allow_auth_routing);
                mapCheckbox('allow_coordinators_login', configs.allow_coordinators_login);
                mapCheckbox('allow_students_login', configs.allow_students_login);
                mapCheckbox('allow_coordinator_panel_access', configs.allow_coordinator_panel_access);
                mapCheckbox('allow_student_panel_access', configs.allow_student_panel_access);
                mapCheckbox('allow_profile_photos_on_signup', configs.allow_profile_photos_on_signup);
            }
        } catch (e) {
            this.showMessage('Falha ao sincronizar estado dos interruptores de circuito com a base de dados.', 'error');
        }
    }

    static async save(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        const btn = e.target.querySelector('button[type="submit"]');
        
        btn.disabled = true;
        btn.innerText = 'A INJETAR POLÍTICAS...';
        
        const payload = {
            allow_auth_routing: document.getElementById('allow_auth_routing').checked,
            allow_coordinators_login: document.getElementById('allow_coordinators_login').checked,
            allow_students_login: document.getElementById('allow_students_login').checked,
            allow_coordinator_panel_access: document.getElementById('allow_coordinator_panel_access').checked,
            allow_student_panel_access: document.getElementById('allow_student_panel_access').checked,
            allow_profile_photos_on_signup: document.getElementById('allow_profile_photos_on_signup').checked
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
                this.showMessage('As diretrizes de acesso foram forçadas no núcleo do sistema e gravadas na auditoria LGPD.', 'success');
            } else {
                this.showMessage(data.error || 'Acesso negado: falha ao alterar permissões globais.', 'error');
            }
        } catch (e) {
            this.showMessage('Colapso na comunicação encriptada com o servidor.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = 'APLICAR DIRETRIZES DE SEGURANÇA';
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