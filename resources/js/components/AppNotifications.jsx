import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster, toast } from 'sonner';

function AppNotifications({ status, error }) {
    useEffect(() => {
        if (status) {
            toast.success(status);
        }

        if (error) {
            toast.error(error);
        }
    }, [status, error]);

    return <Toaster richColors position="top-right" closeButton />;
}

export function mountAppNotifications() {
    const rootElement = document.querySelector('[data-sonner-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <AppNotifications
            status={rootElement.dataset.status || ''}
            error={rootElement.dataset.error || ''}
        />
    );
}
