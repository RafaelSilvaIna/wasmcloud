window.ThemeLoader = class {
    static async boot() {
        try {
            const [themeRes, brandingRes] = await Promise.all([
                fetch('../../api/public/theme'),
                fetch('../../api/branding/info')
            ]);

            const themeData = await themeRes.json();
            const brandingData = await brandingRes.json();

            if (themeData.status === 'success') {
                const root = document.documentElement;
                root.style.setProperty('--primary-color', themeData.theme.primary_color);
                root.style.setProperty('--secondary-color', themeData.theme.secondary_color);
                root.style.setProperty('--background-color', themeData.theme.background_color);
                root.style.setProperty('--text-color', themeData.theme.text_color);
                root.style.setProperty('--accent-color', themeData.theme.accent_color);
            }

            if (brandingData.status === 'success') {
                window.AppBranding = brandingData.data;
                
                document.title = `${window.AppBranding.abbreviation || window.AppBranding.institution_name} - Portal de Acesso`;
                
                let link = document.querySelector("link[rel~='icon']");
                if (!link) {
                    link = document.createElement('link');
                    link.rel = 'icon';
                    document.getElementsByTagName('head')[0].appendChild(link);
                }
                link.href = `../..${window.AppBranding.favicon_url}?t=${Date.now()}`;
            }
            
            return true;
        } catch (e) {
            return false;
        }
    }
};