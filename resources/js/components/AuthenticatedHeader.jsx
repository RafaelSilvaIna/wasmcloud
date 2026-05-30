import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import {
    BookOpen,
    Boxes,
    ChevronDown,
    Code2,
    LogOut,
    PanelLeft,
    PlusCircle,
    Settings2,
    User,
} from 'lucide-react';
import { gsap } from 'gsap';
import { ContextMenu } from './ContextMenu.jsx';

const iconMap = {
    profile: User,
    documentation: BookOpen,
    createProject: PlusCircle,
    api: Code2,
    systemSpecs: Settings2,
    projects: Boxes,
    logout: LogOut,
};

function initialsFromName(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function AuthenticatedHeader({ logoUrl, userName, links, csrfToken, currentPage, sidebarToggle }) {
    const headerRef = useRef(null);
    const [pageTitle, setPageTitle] = useState(currentPage);
    const reduceMotion = useReducedMotion();
    const showCurrentWidget = sidebarToggle;

    const menuItems = useMemo(() => [
        { label: 'Perfil', href: links.profile, icon: iconMap.profile },
        { label: 'Documentacao', href: links.documentation, icon: iconMap.documentation },
        { label: 'Criar Projeto', href: links.createProject, icon: iconMap.createProject },
        { label: 'API', href: links.api, icon: iconMap.api },
        { label: 'Especificacoes do sistema', href: links.systemSpecs, icon: iconMap.systemSpecs },
        { type: 'separator', key: 'account-separator' },
        { label: 'Sair', href: links.logout, icon: iconMap.logout, method: 'POST', csrfToken },
    ], [csrfToken, links]);

    const navItems = useMemo(() => [
        { label: 'Criar Projeto', href: links.createProject, currentNames: ['Criar Projeto'] },
        { label: 'Documentacao', href: links.documentation, currentNames: ['Documentacao'] },
        { label: 'API', href: links.api, currentNames: ['API'] },
    ], [links]);

    useEffect(() => {
        if (reduceMotion || !headerRef.current) {
            return;
        }

        gsap.fromTo(headerRef.current, {
            autoAlpha: 0,
            y: -12,
        }, {
            autoAlpha: 1,
            y: 0,
            duration: 0.48,
            ease: 'power2.out',
        });
    }, [reduceMotion]);

    useEffect(() => {
        const handlePageTitle = (event) => {
            setPageTitle(event.detail || currentPage);
        };

        if (showCurrentWidget) {
            window.addEventListener('wasmcloud:page-title', handlePageTitle);
        }

        return () => window.removeEventListener('wasmcloud:page-title', handlePageTitle);
    }, [currentPage, showCurrentWidget]);

    return (
        <motion.header
            className={showCurrentWidget ? 'app-header app-header--docs' : 'app-header'}
            ref={headerRef}
            aria-label="Cabecalho do aplicativo"
            initial={false}
        >
            <div className="app-header-start">
                {sidebarToggle && (
                    <button
                        className="app-sidebar-toggle"
                        type="button"
                        aria-label="Abrir ou fechar navegacao lateral"
                        onClick={() => window.dispatchEvent(new CustomEvent('wasmcloud:toggle-sidebar'))}
                    >
                        <PanelLeft size={19} aria-hidden="true" />
                    </button>
                )}

                <a className="app-header-brand" href={links.dashboard} data-global-loading aria-label="Wasm Cloud dashboard">
                    <img src={logoUrl} alt="Wasm Cloud" width="142" height="48" />
                </a>
            </div>

            {showCurrentWidget && (
                <div className="app-header-current" aria-live="polite">
                    <span>Pagina atual</span>
                    <strong>{pageTitle}</strong>
                </div>
            )}

            <nav className="app-header-nav" aria-label="Navegacao do aplicativo">
                {navItems.map((item) => {
                    const isActive = item.currentNames.includes(currentPage);

                    return (
                        <a
                            aria-current={isActive ? 'page' : undefined}
                            className={isActive ? 'is-active' : undefined}
                            data-global-loading
                            href={item.href}
                            key={item.label}
                        >
                            {item.label}
                        </a>
                    );
                })}
            </nav>

            <ContextMenu
                items={menuItems}
                button={({ ref, props, open }) => (
                    <button
                        className="app-profile-button"
                        type="button"
                        ref={ref}
                        aria-expanded={open}
                        {...props}
                    >
                        <span className="profile-avatar" aria-hidden="true">
                            {initialsFromName(userName) || <User size={17} />}
                        </span>
                        <span className="profile-copy">
                            <small>Perfil</small>
                            <strong>{userName}</strong>
                        </span>
                        <ChevronDown className={open ? 'is-open' : ''} size={17} aria-hidden="true" />
                    </button>
                )}
            />
        </motion.header>
    );
}

export function mountAuthenticatedHeader() {
    const rootElement = document.querySelector('[data-authenticated-header-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <AuthenticatedHeader
            logoUrl={rootElement.dataset.logoUrl}
            userName={rootElement.dataset.userName || 'Usuario'}
            csrfToken={rootElement.dataset.csrfToken || ''}
            currentPage={rootElement.dataset.currentPage || 'Dashboard'}
            sidebarToggle={rootElement.dataset.sidebarToggle === 'true'}
            links={{
                dashboard: rootElement.dataset.dashboardUrl,
                profile: rootElement.dataset.profileUrl,
                documentation: rootElement.dataset.documentationUrl,
                createProject: rootElement.dataset.createProjectUrl,
                api: rootElement.dataset.apiUrl,
                systemSpecs: rootElement.dataset.systemSpecsUrl,
                logout: rootElement.dataset.logoutUrl,
            }}
        />
    );
}
