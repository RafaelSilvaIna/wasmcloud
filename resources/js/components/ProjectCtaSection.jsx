import React from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import { ArrowRight, CheckCircle2, Code2, CreditCard, Rocket, ServerCog, Sparkles } from 'lucide-react';

const includedItems = [
    'Projeto inicial sem complexidade',
    'Deploy, terminal e logs no mesmo painel',
    'Base pronta para crescer quando precisar',
    'Plano gratis generoso para validar sua ideia',
];

const projectStats = [
    { label: 'Ambiente', value: 'Producao' },
    { label: 'Plano', value: 'Gratis' },
    { label: 'Deploy', value: 'Pronto' },
];

const fadeUp = {
    hidden: { opacity: 0, y: 18 },
    visible: { opacity: 1, y: 0 },
};

function MotionBlock({ children, delay = 0, className = '' }) {
    const reduceMotion = useReducedMotion();

    if (reduceMotion) {
        return <div className={className}>{children}</div>;
    }

    return (
        <motion.div
            className={className}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.24 }}
            variants={fadeUp}
            transition={{ duration: 0.55, ease: 'easeOut', delay }}
        >
            {children}
        </motion.div>
    );
}

function ProjectCtaSection({ authenticated = false, dashboardUrl = '/dashboard', registerUrl = '/cadastro' }) {
    const primaryUrl = authenticated ? dashboardUrl : registerUrl;
    const primaryLabel = authenticated ? 'Acessar projetos' : 'Criar projeto';

    return (
        <section className="project-cta-section" id="criar-projeto" aria-labelledby="project-cta-title">
            <div className="page-shell project-cta-shell">
                <MotionBlock className="project-cta-copy">
                    <div className="section-kicker">
                        <Rocket size={16} aria-hidden="true" />
                        <span>Comece agora</span>
                    </div>

                    <h2 id="project-cta-title">Crie seu primeiro projeto na Wasm Cloud sem compromisso.</h2>

                    <p>
                        O plano gratis foi pensado para ser generoso: voce consegue publicar, testar e evoluir sua aplicacao
                        com uma base profissional antes de precisar escalar recursos.
                    </p>

                    <div className="project-cta-actions">
                        <a className="primary-action large" href={primaryUrl} data-global-loading>
                            {primaryLabel}
                            <ArrowRight size={17} aria-hidden="true" />
                        </a>
                        <a className="secondary-action large" href="#postgres">Ver recursos inclusos</a>
                    </div>
                </MotionBlock>

                <MotionBlock className="project-plan-card" delay={0.08}>
                    <div className="plan-card-head">
                        <span className="benefit-icon">
                            <Sparkles size={18} aria-hidden="true" />
                        </span>
                        <div>
                            <span>Plano gratis</span>
                            <strong>Generoso para sair do zero</strong>
                        </div>
                    </div>

                    <div className="project-stat-grid">
                        {projectStats.map((stat) => (
                            <div key={stat.label}>
                                <span>{stat.label}</span>
                                <strong>{stat.value}</strong>
                            </div>
                        ))}
                    </div>

                    <ul className="project-included-list">
                        {includedItems.map((item) => (
                            <li key={item}>
                                <CheckCircle2 size={16} aria-hidden="true" />
                                {item}
                            </li>
                        ))}
                    </ul>

                    <div className="project-mini-console" aria-label="Resumo de criacao de projeto">
                        <p><Code2 size={15} aria-hidden="true" /> wasm create projeto-saas</p>
                        <p><ServerCog size={15} aria-hidden="true" /> ambiente preparado</p>
                        <p><CreditCard size={15} aria-hidden="true" /> sem cartao para comecar</p>
                    </div>
                </MotionBlock>
            </div>
        </section>
    );
}

export function mountProjectCtaSection() {
    const rootElement = document.querySelector('[data-project-cta-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <ProjectCtaSection
            authenticated={rootElement.dataset.authenticated === 'true'}
            dashboardUrl={rootElement.dataset.dashboardUrl || '/dashboard'}
            registerUrl={rootElement.dataset.registerUrl || '/cadastro'}
        />
    );
}
