import './bootstrap';

import { gsap } from 'gsap';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function mountInteractiveSections() {
    if (document.querySelector('[data-global-loader-root]')) {
        import('./components/GlobalPageLoader.jsx').then(({ mountGlobalPageLoader }) => {
            mountGlobalPageLoader();
        });
    }

    if (document.querySelector('[data-sonner-root]')) {
        import('./components/AppNotifications.jsx').then(({ mountAppNotifications }) => {
            mountAppNotifications();
        });
    }

    if (document.querySelector('[data-authenticated-header-root]')) {
        import('./components/AuthenticatedHeader.jsx').then(({ mountAuthenticatedHeader }) => {
            mountAuthenticatedHeader();
        });
    }

    if (document.querySelector('[data-docs-root]')) {
        import('./components/DocsPage.jsx').then(({ mountDocsPage }) => {
            mountDocsPage();
        });
    }

    if (document.querySelector('[data-profile-root]')) {
        import('./components/ProfilePage.jsx').then(({ mountProfilePage }) => {
            mountProfilePage();
        });
    }

    if (document.querySelector('[data-settings-root]')) {
        import('./components/SettingsPage.jsx').then(({ mountSettingsPage }) => {
            mountSettingsPage();
        });
    }

    if (document.querySelector('[data-workspaces-dashboard-root]')) {
        import('./components/WorkspacesDashboard.jsx').then(({ mountWorkspacesDashboard }) => {
            mountWorkspacesDashboard();
        });
    }

    if (document.querySelector('[data-workspace-create-root]')) {
        import('./components/WorkspaceCreatePage.jsx').then(({ mountWorkspaceCreatePage }) => {
            mountWorkspaceCreatePage();
        });
    }

    if (document.querySelector('[data-postgres-root]')) {
        import('./components/PostgresSection.jsx').then(({ mountPostgresSection }) => {
            mountPostgresSection();
        });
    }

    if (document.querySelector('[data-global-network-root]')) {
        import('./components/GlobalNetworkSection.jsx').then(({ mountGlobalNetworkSection }) => {
            mountGlobalNetworkSection();
        });
    }

    if (document.querySelector('[data-load-balancing-root]')) {
        import('./components/LoadBalancingSection.jsx').then(({ mountLoadBalancingSection }) => {
            mountLoadBalancingSection();
        });
    }

    if (document.querySelector('[data-project-cta-root]')) {
        import('./components/ProjectCtaSection.jsx').then(({ mountProjectCtaSection }) => {
            mountProjectCtaSection();
        });
    }
}

function initSmoothScroll() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#"]');

        if (!link) {
            return;
        }

        const hash = link.getAttribute('href');

        if (!hash || hash === '#') {
            return;
        }

        const target = document.querySelector(hash);

        if (!target) {
            return;
        }

        event.preventDefault();

        if (prefersReducedMotion) {
            target.scrollIntoView({ block: 'start' });
        } else {
            const scrollState = { y: window.scrollY };
            const targetY = Math.max(target.getBoundingClientRect().top + window.scrollY - 92, 0);

            gsap.to(scrollState, {
                duration: .95,
                ease: 'power3.out',
                y: targetY,
                onUpdate() {
                    window.scrollTo(0, scrollState.y);
                },
            });
        }

        window.history.pushState(null, '', hash);
    });
}

function initHeroConsole() {
    const copy = document.querySelector('[data-hero-copy]');
    const mockup = document.querySelector('[data-console-mockup]');

    if (!mockup) {
        return;
    }

    if (prefersReducedMotion) {
        gsap.set('[data-progress-bar]', { width: '84%' });
        return;
    }

    gsap.from([copy, mockup], {
        autoAlpha: 0,
        duration: .9,
        ease: 'power2.out',
        stagger: .14,
        y: 18,
    });

    gsap.from('[data-terminal-line]', {
        autoAlpha: 0,
        duration: .42,
        ease: 'power2.out',
        stagger: .18,
        y: 8,
        delay: .45,
    });

    gsap.to('[data-terminal-cursor]', {
        opacity: .45,
        duration: .8,
        ease: 'power1.inOut',
        repeat: -1,
        yoyo: true,
    });

    gsap.fromTo('[data-progress-bar]', { width: '28%' }, {
        width: '84%',
        duration: 1.6,
        ease: 'power3.out',
        delay: .65,
    });

    gsap.to('[data-deploy-step] span', {
        scale: 1.35,
        opacity: .72,
        duration: 1.2,
        ease: 'power1.inOut',
        repeat: -1,
        stagger: {
            each: .28,
            repeat: -1,
            yoyo: true,
        },
    });

    mockup.addEventListener('pointermove', (event) => {
        const bounds = mockup.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - .5;
        const y = (event.clientY - bounds.top) / bounds.height - .5;

        gsap.to(mockup, {
            duration: .6,
            ease: 'power2.out',
            rotateX: y * -2,
            rotateY: x * 2.5,
            transformPerspective: 1200,
        });
    });

    mockup.addEventListener('pointerleave', () => {
        gsap.to(mockup, {
            duration: .7,
            ease: 'power2.out',
            rotateX: 0,
            rotateY: 0,
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    mountInteractiveSections();
    initSmoothScroll();
    initHeroConsole();
});
