window.SecurityBootstrapper = class {
    static async init() {
        const token = localStorage.getItem('sys_token');
        if (!token) return this.abort();

        const payload = this.decodeJWT(token);
        if (!payload || payload.exp < (Date.now() / 1000)) return this.abort();

        window.SysUser = payload;

        const path = window.location.pathname;
        if (!path.includes('/auth')) {
            await this.verifyServerIntegrity(token);
        }
    }

    static async verifyServerIntegrity(token) {
        try {
            const response = await fetch('/api/auth/verify', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            if (!response.ok) {
                this.abort();
            }
        } catch (e) {
            this.abort();
        }
    }

    static decodeJWT(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            return JSON.parse(window.atob(base64));
        } catch (e) { return null; }
    }

    static abort() {
        localStorage.removeItem('sys_token');
        window.location.replace('/auth');
        throw new Error('Acesso revogado ou interceptado.');
    }

    static enforceRole(requiredRole) {
        if (!window.SysUser || window.SysUser.role !== requiredRole) this.abort();
    }
};

window.SecurityBootstrapper.init();