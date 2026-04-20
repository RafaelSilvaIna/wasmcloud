class SecurityBootstrapper {
    static init() {
        const token = localStorage.getItem('sys_token');
        if (!token) {
            this.abort();
        }

        const payload = this.decodeJWT(token);
        if (!payload || payload.exp < (Date.now() / 1000)) {
            this.abort();
        }

        window.SysUser = payload;
    }

    static decodeJWT(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (e) {
            return null;
        }
    }

    static abort() {
        localStorage.removeItem('sys_token');
        window.location.replace('login.html');
        throw new Error('Acesso bloqueado pelo Bootstrapper.');
    }

    static enforceRole(requiredRole) {
        if (!window.SysUser || window.SysUser.role !== requiredRole) {
            this.abort();
        }
    }
}

SecurityBootstrapper.init();