window.GlobalLoader = class {
    static init() {
        if (!document.getElementById('global-loader-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'global-loader-overlay';
            const spinner = document.createElement('div');
            spinner.className = 'modern-spinner';
            overlay.appendChild(spinner);
            document.body.appendChild(overlay);
        }
    }

    static show() {
        this.init();
        const el = document.getElementById('global-loader-overlay');
        if (el) el.classList.remove('loader-hidden');
    }

    static hide() {
        const el = document.getElementById('global-loader-overlay');
        if (el) el.classList.add('loader-hidden');
    }
};