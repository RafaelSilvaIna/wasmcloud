import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, AnimatePresence } from 'framer-motion';

function GlobalPageLoader() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        window.WasmCloudLoader = {
            show: () => setVisible(true),
            hide: () => setVisible(false),
        };

        const showLoader = (event) => {
            const target = event.target.closest('a[data-global-loading], button[data-global-loading]');

            if (!target) {
                return;
            }

            setVisible(true);
        };

        window.addEventListener('pageshow', () => setVisible(false));
        const showFormLoader = (event) => {
            if (event.target.matches('form[data-global-loading]')) {
                setVisible(true);
            }
        };

        document.addEventListener('click', showLoader);
        document.addEventListener('submit', showFormLoader);

        return () => {
            document.removeEventListener('click', showLoader);
            document.removeEventListener('submit', showFormLoader);
        };
    }, []);

    return (
        <AnimatePresence>
            {visible && (
                <motion.div
                    className="global-page-loader"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.18 }}
                    role="status"
                    aria-live="polite"
                    aria-label="Carregando"
                >
                    <span aria-hidden="true"></span>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

export function mountGlobalPageLoader() {
    const rootElement = document.querySelector('[data-global-loader-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(<GlobalPageLoader />);
}
