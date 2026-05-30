import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { gsap } from 'gsap';

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

export function ContextMenu({ button, items = [], className = '' }) {
    const [open, setOpen] = useState(false);
    const [position, setPosition] = useState({ left: 0, top: 0, visibility: 'hidden' });
    const referenceRef = useRef(null);
    const menuRef = useRef(null);
    const reduceMotion = useReducedMotion();

    const updatePosition = useCallback(() => {
        if (!referenceRef.current || !menuRef.current) {
            return;
        }

        const viewportPadding = 12;
        const gap = 10;
        const referenceRect = referenceRef.current.getBoundingClientRect();
        const menuRect = menuRef.current.getBoundingClientRect();
        const maxLeft = window.innerWidth - menuRect.width - viewportPadding;
        const bottomTop = referenceRect.bottom + gap;
        const topTop = referenceRect.top - menuRect.height - gap;
        const hasBottomSpace = bottomTop + menuRect.height <= window.innerHeight - viewportPadding;

        setPosition({
            left: clamp(referenceRect.right - menuRect.width, viewportPadding, Math.max(viewportPadding, maxLeft)),
            top: hasBottomSpace ? bottomTop : Math.max(viewportPadding, topTop),
            visibility: 'visible',
        });
    }, []);

    useLayoutEffect(() => {
        if (!open) {
            return undefined;
        }

        const frame = window.requestAnimationFrame(updatePosition);

        return () => window.cancelAnimationFrame(frame);
    }, [open, updatePosition]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const handleOutsidePointer = (event) => {
            if (
                menuRef.current?.contains(event.target)
                || referenceRef.current?.contains(event.target)
            ) {
                return;
            }

            setOpen(false);
        };

        const handleKeydown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
                referenceRef.current?.focus();
            }
        };

        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);
        document.addEventListener('pointerdown', handleOutsidePointer);
        document.addEventListener('keydown', handleKeydown);

        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
            document.removeEventListener('pointerdown', handleOutsidePointer);
            document.removeEventListener('keydown', handleKeydown);
        };
    }, [open, updatePosition]);

    useEffect(() => {
        if (!open || reduceMotion || !menuRef.current) {
            return;
        }

        gsap.fromTo(menuRef.current.querySelectorAll('[data-context-item]'), {
            autoAlpha: 0,
            x: 8,
        }, {
            autoAlpha: 1,
            x: 0,
            duration: 0.22,
            ease: 'power2.out',
            stagger: 0.035,
        });
    }, [open, reduceMotion]);

    const menu = (
        <AnimatePresence>
            {open && (
                <motion.div
                    ref={(node) => {
                        menuRef.current = node;
                    }}
                    className={`context-menu ${className}`}
                    role="menu"
                    style={{
                        left: position.left,
                        top: position.top,
                        visibility: position.visibility,
                    }}
                    initial={reduceMotion ? false : { opacity: 0, y: -4, scale: 0.98 }}
                    animate={reduceMotion ? {} : { opacity: 1, y: 0, scale: 1 }}
                    exit={reduceMotion ? {} : { opacity: 0, y: -4, scale: 0.98 }}
                    transition={{ duration: 0.16, ease: 'easeOut' }}
                >
                    {items.map((item) => {
                        const Icon = item.icon;

                        if (item.type === 'separator') {
                            return <span className="context-menu-separator" key={item.key || item.label} role="separator" />;
                        }

                        if (item.method === 'POST') {
                            return (
                                <form action={item.href} method="POST" data-global-loading key={item.label}>
                                    <input type="hidden" name="_token" value={item.csrfToken} />
                                    <button className="context-menu-item danger" type="submit" data-context-item role="menuitem">
                                        {Icon && <Icon size={17} aria-hidden="true" />}
                                        <span>{item.label}</span>
                                    </button>
                                </form>
                            );
                        }

                        return (
                            <a className="context-menu-item" href={item.href} data-global-loading data-context-item key={item.label} role="menuitem">
                                {Icon && <Icon size={17} aria-hidden="true" />}
                                <span>{item.label}</span>
                            </a>
                        );
                    })}
                </motion.div>
            )}
        </AnimatePresence>
    );

    return (
        <>
            {button({
                ref: (node) => {
                    referenceRef.current = node;
                },
                props: {
                    'aria-haspopup': 'menu',
                    onClick: () => setOpen((current) => !current),
                },
                open,
            })}
            {typeof document !== 'undefined' ? createPortal(menu, document.body) : menu}
        </>
    );
}
