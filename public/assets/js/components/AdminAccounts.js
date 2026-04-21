window.AdminAccountsManager = class {
    static async init() {
        await this.loadAccounts();
    }

    static async loadAccounts() {
        const token = localStorage.getItem('sys_token');
        const tbody = document.getElementById('accountsTableBody');
        
        try {
            const response = await fetch('../../api/master/accounts/list', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await response.json();
            
            if (response.ok && data.status === 'success') {
                tbody.innerHTML = '';
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8">Nenhuma identidade encontrada.</td></tr>';
                    return;
                }

                data.data.forEach(acc => {
                    const tr = document.createElement('tr');
                    
                    let statusBadge = '';
                    let actionButtons = '';

                    if (acc.is_archived) {
                        statusBadge = '<span class="badge badge-archived">Arquivado</span>';
                    } else if (!acc.is_active) {
                        statusBadge = '<span class="badge badge-blocked">Bloqueado</span>';
                    } else {
                        statusBadge = '<span class="badge badge-active">Ativo</span>';
                    }

                    if (acc.role === 'master') {
                        actionButtons = `<span style="font-size: 10px; color: #808080;">Imutável</span>`;
                    } else if (acc.is_archived) {
                        actionButtons = `<span style="font-size: 10px; color: #800000;">Trancado (Soft Delete)</span>`;
                    } else {
                        const blockTxt = acc.is_active ? 'Bloquear' : 'Desbloquear';
                        actionButtons = `
                            <button class="win95-button action-btn" onclick="window.AdminAccountsManager.toggleBlock(${acc.id}, '${acc.role}', ${acc.is_active})">${blockTxt}</button>
                            <button class="win95-button action-btn" onclick="window.AdminAccountsManager.openPasswordModal(${acc.id}, '${acc.role}')">Senha</button>
                            <button class="win95-button action-btn" style="color: #800000;" onclick="window.AdminAccountsManager.archive(${acc.id}, '${acc.role}')">Arquivar</button>
                        `;
                    }

                    tr.innerHTML = `
                        <td>#${acc.id}</td>
                        <td>${acc.name}</td>
                        <td>${acc.email}</td>
                        <td><strong>${acc.role.toUpperCase()}</strong></td>
                        <td>${acc.cpf_masked}</td>
                        <td>${acc.last_login_at}</td>
                        <td>${statusBadge}</td>
                        <td style="text-align: center;">${actionButtons}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="8" style="color: red;">Erro critico de comunicacao com o kernel.</td></tr>';
        }
    }

    static async createAccount(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        const form = document.getElementById('createAccountForm');
        const btn = document.getElementById('btnSubmitCreate');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerText = 'A processar...';

        try {
            const response = await fetch('../../api/master/accounts/create', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData 
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                this.closeCreateModal();
                this.showAlert('Identidade provisionada com sucesso.', 'success');
                await this.loadAccounts();
            } else {
                this.showAlert(result.error || 'Falha ao provisionar identidade.', 'error');
            }
        } catch (e) {
            this.showAlert('Falha ao comunicar com o cofre de segurança.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Gerar Identidade';
        }
    }

    static async toggleBlock(id, role, currentStatus) {
        if (!confirm(`Deseja ${currentStatus ? 'BLOQUEAR' : 'DESBLOQUEAR'} o acesso desta identidade?`)) return;
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/master/accounts/block', {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, role, status: !currentStatus })
            });
            if (response.ok) {
                this.showAlert('Estado de acesso modificado e gravado na auditoria.', 'success');
                await this.loadAccounts();
            }
        } catch (e) {}
    }

    static async archive(id, role) {
        if (!confirm('ATENÇÃO: O arquivamento (Soft Delete) é irreversível. Deseja prosseguir?')) return;
        const token = localStorage.getItem('sys_token');
        try {
            const response = await fetch('../../api/master/accounts/archive', {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, role })
            });
            if (response.ok) {
                this.showAlert('Registo arquivado permanentemente.', 'success');
                await this.loadAccounts();
            }
        } catch (e) {}
    }

    static async changePassword(e) {
        e.preventDefault();
        const token = localStorage.getItem('sys_token');
        const payload = {
            id: document.getElementById('pwd_id').value,
            role: document.getElementById('pwd_role').value,
            new_password: document.getElementById('pwd_new').value,
            reason: document.getElementById('pwd_reason').value
        };

        try {
            const response = await fetch('../../api/master/accounts/password', {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok) {
                this.closePasswordModal();
                this.showAlert('Credenciais sobrepostas. Justificação gravada nos logs LGPD.', 'success');
            } else {
                this.showAlert(result.error || 'Falha na sobreposição.', 'error');
            }
        } catch (e) {}
    }

    static openCreateModal() {
        document.getElementById('createAccountForm').reset();
        document.getElementById('createAccountModal').style.display = 'flex';
    }

    static closeCreateModal() {
        document.getElementById('createAccountModal').style.display = 'none';
    }

    static openPasswordModal(id, role) {
        document.getElementById('passwordForm').reset();
        document.getElementById('pwd_id').value = id;
        document.getElementById('pwd_role').value = role;
        document.getElementById('passwordModal').style.display = 'flex';
    }

    static closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
    }

    static showAlert(msg, type) {
        const alert = document.getElementById('accountsAlert');
        alert.style.display = 'block';
        alert.innerText = msg;
        alert.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
        alert.style.color = type === 'success' ? '#155724' : '#721c24';
        alert.style.borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
        setTimeout(() => alert.style.display = 'none', 5000);
    }
};

document.getElementById('createAccountForm').addEventListener('submit', (e) => window.AdminAccountsManager.createAccount(e));
document.getElementById('passwordForm').addEventListener('submit', (e) => window.AdminAccountsManager.changePassword(e));
window.AdminAccountsManager.init();