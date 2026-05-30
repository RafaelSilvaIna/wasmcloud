import React, { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { X } from 'lucide-react';
import { gsap } from 'gsap';

export function Modal({
    open,
    title,
    description,
    children,
    footer,
    onClose,
    size = 'md',
}) {
    const modalRef = useRef(null);
    const reduceMotion = useReducedMotion();

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const handleKeydown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeydown);

        return () => {
            document.body.style.overflow = '';
            document.removeEventListener('keydown', handleKeydown);
        };
    }, [onClose, open]);

    useEffect(() => {
        if (!open || reduceMotion || !modalRef.current) {
            return;
        }

        gsap.fromTo(modalRef.current.querySelectorAll('[data-modal-stagger]'), {
            autoAlpha: 0,
            y: 8,
        }, {
            autoAlpha: 1,
            y: 0,
            duration: 0.24,
            ease: 'power2.out',
            stagger: 0.045,
        });
    }, [open, reduceMotion]);

    const modal = (
        <AnimatePresence>
            {open && (
                <motion.div
                    className="modal-backdrop"
                    role="presentation"
                    initial={reduceMotion ? false : { opacity: 0 }}
                    animate={reduceMotion ? {} : { opacity: 1 }}
                    exit={reduceMotion ? {} : { opacity: 0 }}
                    transition={{ duration: 0.16 }}
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            onClose();
                        }
                    }}
                >
                    <motion.section
                        aria-modal="true"
                        className={`modal-card modal-card--${size}`}
                        initial={reduceMotion ? false : { opacity: 0, y: 18, scale: 0.98 }}
                        animate={reduceMotion ? {} : { opacity: 1, y: 0, scale: 1 }}
                        exit={reduceMotion ? {} : { opacity: 0, y: 12, scale: 0.98 }}
                        ref={modalRef}
                        role="dialog"
                        transition={{ duration: 0.2, ease: 'easeOut' }}
                    >
                        <header className="modal-head" data-modal-stagger>
                            <div>
                                <h2>{title}</h2>
                                {description && <p>{description}</p>}
                            </div>
                            <button type="button" onClick={onClose} aria-label="Fechar modal">
                                <X size={18} aria-hidden="true" />
                            </button>
                        </header>

                        <div className="modal-body" data-modal-stagger>
                            {children}
                        </div>

                        {footer && (
                            <footer className="modal-footer" data-modal-stagger>
                                {footer}
                            </footer>
                        )}
                    </motion.section>
                </motion.div>
            )}
        </AnimatePresence>
    );

    return typeof document !== 'undefined' ? createPortal(modal, document.body) : modal;
}
