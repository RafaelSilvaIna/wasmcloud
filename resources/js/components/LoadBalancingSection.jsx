import React, { useEffect, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import { Activity, Boxes, Gauge, GitBranch, Route, ServerCog, ShieldCheck, Workflow, Zap } from 'lucide-react';
import { forceCenter, forceCollide, forceLink, forceManyBody, forceSimulation, forceX, forceY } from 'd3-force';
import { gsap } from 'gsap';

const diagramSize = {
    width: 760,
    height: 420,
};

const nodes = [
    { id: 'users', label: 'Usuarios', type: 'source', x: 90, y: 210 },
    { id: 'edge', label: 'Edge', type: 'control', x: 230, y: 150 },
    { id: 'router', label: 'Roteador', type: 'control', x: 360, y: 210 },
    { id: 'web', label: 'Web', type: 'service', x: 520, y: 110 },
    { id: 'api', label: 'API', type: 'service', x: 560, y: 220 },
    { id: 'jobs', label: 'Jobs', type: 'service', x: 500, y: 315 },
    { id: 'health', label: 'Health', type: 'control', x: 670, y: 210 },
];

const links = [
    { source: 'users', target: 'edge', label: 'trafego' },
    { source: 'edge', target: 'router', label: 'balanceamento' },
    { source: 'router', target: 'web', label: 'replica' },
    { source: 'router', target: 'api', label: 'replica' },
    { source: 'router', target: 'jobs', label: 'fila' },
    { source: 'web', target: 'health', label: 'status' },
    { source: 'api', target: 'health', label: 'status' },
    { source: 'jobs', target: 'health', label: 'status' },
];

const benefits = [
    {
        icon: Boxes,
        title: 'Carga distribuida',
        text: 'O trafego pode ser dividido entre replicas, rotas e tarefas para evitar gargalos em um unico ponto.',
    },
    {
        icon: ShieldCheck,
        title: 'Maior estabilidade',
        text: 'Health checks e isolamento ajudam a manter a aplicacao respondendo mesmo quando uma parte exige mais recursos.',
    },
    {
        icon: Zap,
        title: 'Crescimento sem travas',
        text: 'Quando a demanda aumenta, a arquitetura prepara o caminho para escalar servicos sem redesenhar tudo.',
    },
];

const steps = [
    'Entrada global recebe o trafego do SaaS.',
    'Roteamento distribui requisicoes por servico.',
    'Replicas absorvem picos e reduzem pressao.',
    'Monitoramento identifica gargalos antes do usuario sentir.',
];

const fadeUp = {
    hidden: { opacity: 0, y: 18 },
    visible: { opacity: 1, y: 0 },
};

const iconMap = {
    users: Activity,
    edge: Route,
    router: Workflow,
    web: ServerCog,
    api: GitBranch,
    jobs: Gauge,
    health: ShieldCheck,
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
            viewport={{ once: true, amount: 0.22 }}
            variants={fadeUp}
            transition={{ duration: 0.55, ease: 'easeOut', delay }}
        >
            {children}
        </motion.div>
    );
}

function curvedLink(source, target) {
    const middleX = (source.x + target.x) / 2;
    const middleY = (source.y + target.y) / 2;
    const dx = target.x - source.x;
    const dy = target.y - source.y;
    const distance = Math.max(Math.hypot(dx, dy), 1);
    const curve = Math.min(distance * 0.16, 34);
    const controlX = middleX - (dy / distance) * curve;
    const controlY = middleY + (dx / distance) * curve;

    return `M ${source.x.toFixed(2)} ${source.y.toFixed(2)} Q ${controlX.toFixed(2)} ${controlY.toFixed(2)} ${target.x.toFixed(2)} ${target.y.toFixed(2)}`;
}

function useNetworkLayout() {
    return useMemo(() => {
        const layoutNodes = nodes.map((node) => ({ ...node, fx: node.x, fy: node.y }));
        const layoutLinks = links.map((link) => ({ ...link }));

        const simulation = forceSimulation(layoutNodes)
            .force('link', forceLink(layoutLinks).id((node) => node.id).distance(118).strength(0.55))
            .force('charge', forceManyBody().strength(-260))
            .force('collide', forceCollide(46))
            .force('center', forceCenter(diagramSize.width / 2, diagramSize.height / 2))
            .force('x', forceX((node) => node.x).strength(0.2))
            .force('y', forceY((node) => node.y).strength(0.2))
            .stop();

        for (let index = 0; index < 80; index += 1) {
            simulation.tick();
        }

        const nodeMap = new Map(layoutNodes.map((node) => [node.id, node]));

        return {
            nodes: layoutNodes,
            links: layoutLinks.map((link) => ({
                ...link,
                sourceNode: nodeMap.get(link.source.id || link.source),
                targetNode: nodeMap.get(link.target.id || link.target),
            })),
        };
    }, []);
}

function LoadBalancingSection() {
    const reduceMotion = useReducedMotion();
    const svgRef = useRef(null);
    const layout = useNetworkLayout();

    useEffect(() => {
        if (reduceMotion || !svgRef.current) {
            return undefined;
        }

        const paths = svgRef.current.querySelectorAll('[data-scale-link]');
        const nodesToPulse = svgRef.current.querySelectorAll('[data-scale-node]');

        paths.forEach((path) => {
            const length = path.getTotalLength();
            gsap.set(path, {
                strokeDasharray: `${length * 0.18} ${length * 0.82}`,
                strokeDashoffset: length,
            });
        });

        const timeline = gsap.timeline({ repeat: -1 });

        timeline.to(paths, {
            strokeDashoffset: 0,
            duration: 2.4,
            ease: 'none',
            stagger: 0.1,
        });

        gsap.to(nodesToPulse, {
            scale: 1.08,
            transformOrigin: 'center',
            duration: 1.25,
            ease: 'power1.inOut',
            repeat: -1,
            yoyo: true,
            stagger: 0.14,
        });

        return () => {
            timeline.kill();
            gsap.killTweensOf(nodesToPulse);
        };
    }, [reduceMotion]);

    return (
        <section className="load-balance-section" id="escala" aria-labelledby="scale-title">
            <div className="page-shell load-balance-shell">
                <MotionBlock className="load-balance-heading">
                    <div className="section-kicker">
                        <Workflow size={16} aria-hidden="true" />
                        <span>Escala sem atrito</span>
                    </div>

                    <h2 id="scale-title">Seu SaaS cresce sem voce se preocupar em estourar limites.</h2>

                    <p>
                        A Wasm Cloud distribui carga entre camadas da aplicacao, reduz gargalos e mantem a operacao estavel
                        quando o trafego aumenta ou novas rotinas entram em producao.
                    </p>
                </MotionBlock>

                <div className="load-balance-grid">
                    <MotionBlock className="load-diagram-card" delay={0.06}>
                        <svg
                            ref={svgRef}
                            className="load-diagram"
                            viewBox={`0 0 ${diagramSize.width} ${diagramSize.height}`}
                            role="img"
                            aria-labelledby="load-diagram-title load-diagram-desc"
                        >
                            <title id="load-diagram-title">Esquema de distribuicao de carga Wasm Cloud</title>
                            <desc id="load-diagram-desc">
                                Diagrama animado com usuarios, edge, roteador, replicas de servico e monitoramento de saude.
                            </desc>

                            <defs>
                                <filter id="scaleGlow" x="-40%" y="-40%" width="180%" height="180%">
                                    <feGaussianBlur stdDeviation="5" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>

                            <rect className="load-diagram-bg" width={diagramSize.width} height={diagramSize.height} rx="20" />

                            <g className="load-links" aria-hidden="true">
                                {layout.links.map((link) => (
                                    <path
                                        d={curvedLink(link.sourceNode, link.targetNode)}
                                        data-scale-link
                                        key={`${link.sourceNode.id}-${link.targetNode.id}`}
                                    />
                                ))}
                            </g>

                            <g className="load-nodes">
                                {layout.nodes.map((node) => {
                                    const Icon = iconMap[node.id];

                                    return (
                                        <g className={`load-node ${node.type}`} data-scale-node key={node.id}>
                                            <rect x={node.x - 52} y={node.y - 34} width="104" height="68" rx="12" />
                                            <foreignObject x={node.x - 12} y={node.y - 22} width="24" height="24">
                                                <Icon size={22} aria-hidden="true" />
                                            </foreignObject>
                                            <text x={node.x} y={node.y + 21} textAnchor="middle">{node.label}</text>
                                        </g>
                                    );
                                })}
                            </g>
                        </svg>
                    </MotionBlock>

                    <MotionBlock className="load-explainer-card" delay={0.12}>
                        <div className="load-explainer-head">
                            <span className="benefit-icon">
                                <Gauge size={18} aria-hidden="true" />
                            </span>
                            <div>
                                <span>Sem gargalo unico</span>
                                <strong>Rede preparada para picos</strong>
                            </div>
                        </div>

                        <ol className="load-steps">
                            {steps.map((step, index) => (
                                <li key={step}>
                                    <span>{String(index + 1).padStart(2, '0')}</span>
                                    {step}
                                </li>
                            ))}
                        </ol>
                    </MotionBlock>
                </div>

                <div className="load-benefits" aria-label="Beneficios da distribuicao de carga">
                    {benefits.map((benefit, index) => {
                        const Icon = benefit.icon;

                        return (
                            <MotionBlock className="load-benefit" delay={index * 0.06} key={benefit.title}>
                                <span className="benefit-icon">
                                    <Icon size={18} aria-hidden="true" />
                                </span>
                                <h3>{benefit.title}</h3>
                                <p>{benefit.text}</p>
                            </MotionBlock>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export function mountLoadBalancingSection() {
    const rootElement = document.querySelector('[data-load-balancing-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(<LoadBalancingSection />);
}
