/**
 * PipoCine - Theme Manager Profissional
 * Otimizado para performance, segurança (prevenção contra bloqueios de localStorage)
 * e integração com preferências do Sistema Operativo.
 */

const PipoTheme = (function() {
    'use strict';

    const STORAGE_KEY = 'pipocine_theme_pref';
    const LIGHT_CLASS = 'light-mode';

    // 1. Segurança: Wrapper para LocalStorage à prova de falhas
    const storage = {
        get: function() {
            try {
                return localStorage.getItem(STORAGE_KEY);
            } catch (e) {
                console.warn('PipoCine: Acesso ao armazenamento local bloqueado pelas definições de privacidade.');
                return null;
            }
        },
        set: function(value) {
            try {
                localStorage.setItem(STORAGE_KEY, value);
            } catch (e) {
                // Falha silenciosa para não quebrar a experiência do utilizador
            }
        }
    };

    // 2. Integração: Deteta a preferência de cor do Sistema Operativo do utilizador
    const getSystemPreference = function() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            return 'light';
        }
        return 'dark'; // O padrão do PipoCine continua a ser o Dark Mode
    };

    // 3. Performance: Aplica o tema sincronizado com a taxa de atualização do ecrã
    const applyTheme = function(theme) {
        window.requestAnimationFrame(function() {
            if (theme === 'light') {
                document.body.classList.add(LIGHT_CLASS);
            } else {
                document.body.classList.remove(LIGHT_CLASS);
            }
            
            // Dispara um evento global caso outros scripts precisem de saber que o tema mudou
            document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
        });
    };

    // 4. Inicialização Segura e Imediata
    const init = function() {
        const savedTheme = storage.get();
        // Se o utilizador já escolheu um tema, usa-o. Se não, lê o Sistema Operativo.
        const currentTheme = savedTheme || getSystemPreference();
        
        applyTheme(currentTheme);

        // Ouve ativamente se o utilizador mudar a cor do telemóvel/PC enquanto usa o site
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function(e) {
                if (!storage.get()) { // Só muda sozinho se o utilizador não tiver forçado um tema manualmente no site
                    applyTheme(e.matches ? 'light' : 'dark');
                }
            });
        }
    };

    // 5. Função Pública para o Botão
    const toggle = function() {
        const isLight = document.body.classList.contains(LIGHT_CLASS);
        const newTheme = isLight ? 'dark' : 'light';
        
        storage.set(newTheme);
        applyTheme(newTheme);
    };

    // Executa a inicialização imediatamente para evitar "Piscadas" em branco (FOUC)
    init();

    // Expõe apenas as funções necessárias para o exterior (Segurança e Encapsulamento)
    return {
        toggleTheme: toggle
    };

})();

document.addEventListener('DOMContentLoaded', function() {
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        // O parâmetro 'passive: true' melhora a performance de rolagem em dispositivos móveis
        themeBtn.addEventListener('click', PipoTheme.toggleTheme, { passive: true });
    }
});