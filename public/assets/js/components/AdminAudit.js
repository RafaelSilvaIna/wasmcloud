window.AdminAuditManager = class {
    static async load() {
        const token = localStorage.getItem('sys_token');
        const tbody = document.getElementById('auditTableBody');
        
        try {
            const response = await fetch('../../api/master/logs/viewer', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await response.json();
            
            if (response.ok && data.status === 'success') {
                tbody.innerHTML = '';
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7">Nenhum registo gravado.</td></tr>';
                    return;
                }

                data.data.forEach(log => {
                    const tr = document.createElement('tr');
                    
                    const statusColor = log.status === 'success' ? '#2e7d32' : '#d32f2f';
                    const statusText = log.status === 'success' ? 'SUCESSO' : 'FALHA';

                    tr.innerHTML = `
                        <td>#${log.id}</td>
                        <td>${log.created_at}</td>
                        <td>${log.actor_id}</td>
                        <td>${log.actor_role.toUpperCase()}</td>
                        <td>${log.ip_address}</td>
                        <td>${log.action}</td>
                        <td style="color: ${statusColor}; font-weight: bold;">${statusText}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" style="color: #d32f2f;">Acesso negado. Token invalido ou expirado.</td></tr>';
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" style="color: #d32f2f;">Erro de comunicacao com o servidor de auditoria (API Offline).</td></tr>';
        }
    }
};

window.AdminAuditManager.load();