document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const toggle = document.getElementById('ads-mobile-toggle');
    const overlay = document.getElementById('ads-sidebar-overlay');

    const setOpen = (open) => {
        document.body.classList.toggle('ads-sidebar-open', open);
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle?.addEventListener('click', () => {
        setOpen(!document.body.classList.contains('ads-sidebar-open'));
    });
    overlay?.addEventListener('click', () => setOpen(false));
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
});
