window.AdminBrandingManager = class {
    static async init() {
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/branding/info', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await response.json();

            if (result.status === 'success') {
                const d = result.data;
                document.getElementById('institution_name').value = d.institution_name || '';
                document.getElementById('abbreviation').value = d.abbreviation || '';
                document.getElementById('slogan').value = d.slogan || '';
                
                document.getElementById('logo_preview').src = '../../api/img/logo?t=' + Date.now();
                document.getElementById('favicon_preview').src = '../../api/img/favicon?t=' + Date.now();
            }
        } catch (e) {
            this.showAlert('Falha ao carregar dados de branding.', 'error');
        }
    }

    static async save(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        const formData = new FormData(e.target);

        try {
            const response = await fetch('../../api/master/branding/update', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                this.showAlert('Branding e Auditoria atualizados com sucesso.', 'success');
                this.init(); 
            } else {
                this.showAlert(result.message || 'Erro ao processar ficheiros.', 'error');
            }
        } catch (e) {
            this.showAlert('Erro de conexão com o servidor de armazenamento.', 'error');
        }
    }

    static showAlert(msg, type) {
        const alert = document.getElementById('brandingAlert');
        alert.style.display = 'block';
        alert.innerText = msg;
        alert.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
        alert.style.color = type === 'success' ? '#155724' : '#721c24';
        setTimeout(() => alert.style.display = 'none', 5000);
    }
};

document.getElementById('brandingForm').addEventListener('submit', (e) => window.AdminBrandingManager.save(e));
window.AdminBrandingManager.init();