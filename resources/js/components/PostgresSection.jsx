import React from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import {
    CheckCircle2,
    Database,
    Gauge,
    GitBranch,
    Layers3,
    ShieldCheck,
} from 'lucide-react';

const benefits = [
    {
        icon: ShieldCheck,
        title: 'Confiabilidade ACID',
        text: 'Transacoes consistentes para operacoes criticas, faturamento, paineis administrativos e dados de clientes.',
    },
    {
        icon: Layers3,
        title: 'SQL com JSONB',
        text: 'Modelagem relacional forte sem abrir mao de campos flexiveis para configuracoes, logs e metadados.',
    },
    {
        icon: Gauge,
        title: 'Performance previsivel',
        text: 'Indices maduros, consultas analisaveis e controle fino para crescer com clareza operacional.',
    },
    {
        icon: GitBranch,
        title: 'Ecossistema extensivel',
        text: 'Recursos avancados como views, triggers, full text search e extensoes para produtos mais robustos.',
    },
];

const comparisons = [
    {
        name: 'PostgreSQL',
        focus: 'Dados criticos, relacionais e hibridos',
        strength: 'Consistencia, JSONB, indices avancados e recursos profissionais.',
        fit: 'Padrao recomendado no Wasm Cloud',
        preferred: true,
    },
    {
        name: 'MySQL',
        focus: 'Aplicacoes web tradicionais',
        strength: 'Simples, popular e eficiente em cargas comuns.',
        fit: 'Bom quando o projeto ja nasceu em MySQL',
        preferred: false,
    },
    {
        name: 'NoSQL',
        focus: 'Documentos, eventos e grande flexibilidade',
        strength: 'Modelo livre e escalas especificas por caso de uso.',
        fit: 'Melhor como apoio para workloads especializados',
        preferred: false,
    },
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
            viewport={{ once: true, amount: 0.28 }}
            variants={fadeUp}
            transition={{ duration: 0.55, ease: 'easeOut', delay }}
        >
            {children}
        </motion.div>
    );
}

function PostgresSection() {
    return (
        <section className="postgres-section" id="postgres" aria-labelledby="postgres-title">
            <div className="page-shell postgres-shell">
                <MotionBlock className="postgres-heading">
                    <div className="section-kicker">
                        <Database size={16} aria-hidden="true" />
                        <span>Especialidade PostgreSQL</span>
                    </div>

                    <h2 id="postgres-title">Wasm Cloud e especializado em PostgreSQL para hospedagens que precisam de dados confiaveis.</h2>

                    <p>
                        PostgreSQL combina a seguranca de um banco relacional maduro com recursos modernos para aplicacoes SaaS,
                        paineis operacionais, e-commerces, APIs e produtos que nao podem perder consistencia.
                    </p>
                </MotionBlock>

                <div className="postgres-benefits" aria-label="Beneficios do PostgreSQL">
                    {benefits.map((benefit, index) => {
                        const Icon = benefit.icon;

                        return (
                            <MotionBlock className="postgres-benefit" delay={index * 0.06} key={benefit.title}>
                                <span className="benefit-icon">
                                    <Icon size={18} aria-hidden="true" />
                                </span>
                                <h3>{benefit.title}</h3>
                                <p>{benefit.text}</p>
                            </MotionBlock>
                        );
                    })}
                </div>

                <MotionBlock className="database-comparison" delay={0.08}>
                    <div className="comparison-head">
                        <span>Comparativo objetivo</span>
                        <strong>Escolha por responsabilidade, nao por moda.</strong>
                    </div>

                    <div className="comparison-table" role="table" aria-label="Comparacao entre PostgreSQL, MySQL e NoSQL">
                        <div className="comparison-row comparison-header" role="row">
                            <span role="columnheader">Banco</span>
                            <span role="columnheader">Melhor uso</span>
                            <span role="columnheader">Forca principal</span>
                            <span role="columnheader">No Wasm Cloud</span>
                        </div>

                        {comparisons.map((item) => (
                            <div className={item.preferred ? 'comparison-row preferred' : 'comparison-row'} role="row" key={item.name}>
                                <span role="cell">
                                    {item.preferred && <CheckCircle2 size={16} aria-hidden="true" />}
                                    {item.name}
                                </span>
                                <span role="cell">{item.focus}</span>
                                <span role="cell">{item.strength}</span>
                                <span role="cell">{item.fit}</span>
                            </div>
                        ))}
                    </div>
                </MotionBlock>
            </div>
        </section>
    );
}

export function mountPostgresSection() {
    const rootElement = document.querySelector('[data-postgres-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(<PostgresSection />);
}
