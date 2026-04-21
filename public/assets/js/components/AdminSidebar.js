window.AdminSidebar = class {
    static render() {
        return `
            <div class="win95-window" style="height: 100%;">
                <div class="win95-titlebar">
                    <span>Navegação do Sistema</span>
                </div>
                <div class="win95-content" style="background: #c0c0c0;">
                    <nav>
                        ${this.menuItem('Visão Geral', 'dashboard')}
                        ${this.menuItem('Identidade Visual', 'branding')}
                        ${this.menuItem('Paleta de Cores', 'colors')}
                        ${this.menuItem('Gestão de Contas', 'accounts')}
                        ${this.menuItem('Configurações Globais', 'settings')}
                        ${this.menuItem('Auditoria e LGPD', 'audit')}
                    </nav>
                    <div style="margin-top: 20px; border-top: 2px solid #808080; padding-top: 10px;">
                        <button class="win95-button" style="width: 100%; color: #800000;" onclick="logout()">ENCERRAR SESSÃO</button>
                    </div>
                </div>
            </div>
        `;
    }

    static menuItem(label, action) {
        return `<a href="#" class="win95-button" onclick="AppRouter.load('${action}')">${label}</a>`;
    }
};

function logout() {
    localStorage.removeItem('sys_token');
    window.location.replace('../../login.html');
}