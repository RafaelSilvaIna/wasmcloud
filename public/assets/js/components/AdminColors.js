window.AdminColorsManager = class {
    static async init() {
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/public/theme', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await response.json();

            if (result.status === 'success') {
                const theme = result.theme;
                this.updateFields(theme);
            }
        } catch (e) {
            this.showAlert('Falha ao obter paleta atual do servidor.', 'error');
        }
    }

    static updateFields(theme) {
        Object.keys(theme).forEach(key => {
            const val = theme[key];
            const picker = document.getElementById(`${key}_picker`);
            const input = document.getElementById(key);
            const preview = document.getElementById(`preview_${key.split('_')[0]}`);

            if (picker) picker.value = val;
            if (input) input.value = val;
            if (preview) {
                preview.style.backgroundColor = val;
                // Ajuste inteligente de cor de texto para o preview
                const r = parseInt(val.slice(1,3), 16);
                const g = parseInt(val.slice(3,5), 16);
                const b = parseInt(val.slice(5,7), 16);
                preview.style.color = (r*0.299 + g*0.587 + b*0.114) > 186 ? '#000' : '#fff';
            }
        });
    }

    static async save(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        
        const payload = {
            primary_color: document.getElementById('primary_color').value,
            secondary_color: document.getElementById('secondary_color').value,
            background_color: document.getElementById('background_color').value,
            text_color: document.getElementById('text_color').value,
            accent_color: document.getElementById('accent_color').value
        };

        try {
            const response = await fetch('../../api/master/theme/update', {
                method: 'PUT',
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (response.ok && result.status === 'success') {
                this.showAlert('Paleta cromática atualizada. A auditoria registou a alteração.', 'success');
            } else {
                this.showAlert('Erro ao gravar novas diretrizes de cor.', 'error');
            }
        } catch (e) {
            this.showAlert('Erro de comunicação com o Kernel de personalização.', 'error');
        }
    }

    static showAlert(msg, type) {
        const alert = document.getElementById('colorsAlert');
        alert.style.display = 'block';
        alert.innerText = msg;
        alert.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
        alert.style.color = type === 'success' ? '#155724' : '#721c24';
        setTimeout(() => alert.style.display = 'none', 5000);
    }
};

// Listeners para os pickers
document.querySelectorAll('input[type="color"]').forEach(picker => {
    picker.addEventListener('input', (e) => {
        const targetId = e.target.id.replace('_picker', '');
        const input = document.getElementById(targetId);
        input.value = e.target.value.toUpperCase();
        
        const previewId = `preview_${targetId.split('_')[0]}`;
        const preview = document.getElementById(previewId);
        if (preview) {
            preview.style.backgroundColor = e.target.value;
            const r = parseInt(e.target.value.slice(1,3), 16);
            const g = parseInt(e.target.value.slice(3,5), 16);
            const b = parseInt(e.target.value.slice(5,7), 16);
            preview.style.color = (r*0.299 + g*0.587 + b*0.114) > 186 ? '#000' : '#fff';
        }
    });
});

document.getElementById('themeForm').addEventListener('submit', (e) => window.AdminColorsManager.save(e));
window.AdminColorsManager.init();