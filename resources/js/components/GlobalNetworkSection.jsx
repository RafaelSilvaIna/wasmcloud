import React, { useEffect, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import { Activity, Globe2, Network, Server, ShieldCheck, Zap } from 'lucide-react';
import { geoNaturalEarth1, geoPath } from 'd3-geo';
import { feature } from 'topojson-client';
import worldMap from 'world-atlas/countries-110m.json';
import { gsap } from 'gsap';

const mapSize = {
    width: 920,
    height: 440,
};

const locations = [
    { name: 'Sao Paulo', region: 'America do Sul', coordinates: [-46.6333, -23.5505], hub: true },
    { name: 'Virginia', region: 'America do Norte', coordinates: [-77.0369, 38.9072] },
    { name: 'Frankfurt', region: 'Europa', coordinates: [8.6821, 50.1109] },
    { name: 'Singapura', region: 'Asia', coordinates: [103.8198, 1.3521] },
    { name: 'Sydney', region: 'Oceania', coordinates: [151.2093, -33.8688] },
    { name: 'Johannesburg', region: 'Africa', coordinates: [28.0473, -26.2041] },
];

const networkStats = [
    { icon: Globe2, label: 'Cobertura', value: 'Global' },
    { icon: Zap, label: 'Escala', value: 'Sob demanda' },
    { icon: ShieldCheck, label: 'Operacao', value: 'Monitorada' },
];

const fadeUp = {
    hidden: { opacity: 0, y: 18 },
    visible: { opacity: 1, y: 0 },
};

function projectedRoute(projection, start, end) {
    const [x1, y1] = projection(start.coordinates);
    const [x2, y2] = projection(end.coordinates);
    const distance = Math.hypot(x2 - x1, y2 - y1);
    const controlX = (x1 + x2) / 2;
    const controlY = (y1 + y2) / 2 - Math.min(distance * 0.22, 78);

    return `M ${x1.toFixed(2)} ${y1.toFixed(2)} Q ${controlX.toFixed(2)} ${controlY.toFixed(2)} ${x2.toFixed(2)} ${y2.toFixed(2)}`;
}

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

function GlobalNetworkSection() {
    const reduceMotion = useReducedMotion();
    const svgRef = useRef(null);
    const hub = locations.find((location) => location.hub);

    const { countries, projection, path } = useMemo(() => {
        const countryFeatures = feature(worldMap, worldMap.objects.countries).features;
        const mapProjection = geoNaturalEarth1().fitSize([mapSize.width, mapSize.height], {
            type: 'FeatureCollection',
            features: countryFeatures,
        });

        return {
            countries: countryFeatures,
            projection: mapProjection,
            path: geoPath(mapProjection),
        };
    }, []);

    const routes = locations
        .filter((location) => !location.hub)
        .map((location) => ({
            key: `${hub.name}-${location.name}`,
            path: projectedRoute(projection, hub, location),
        }));

    useEffect(() => {
        if (reduceMotion || !svgRef.current) {
            return undefined;
        }

        const routesToAnimate = svgRef.current.querySelectorAll('[data-map-route]');
        const points = svgRef.current.querySelectorAll('[data-map-point]');

        routesToAnimate.forEach((route) => {
            const length = route.getTotalLength();
            gsap.set(route, {
                strokeDasharray: length,
                strokeDashoffset: length,
            });
        });

        const timeline = gsap.timeline({ repeat: -1, repeatDelay: 0.4 });

        timeline
            .to(routesToAnimate, {
                strokeDashoffset: 0,
                duration: 1.35,
                ease: 'power2.out',
                stagger: 0.12,
            })
            .to(routesToAnimate, {
                opacity: 0.44,
                duration: 0.8,
                ease: 'power1.inOut',
                stagger: 0.08,
            }, '<0.35')
            .to(routesToAnimate, {
                opacity: 0.82,
                duration: 0.8,
                ease: 'power1.inOut',
                stagger: 0.08,
            });

        gsap.to(points, {
            scale: 1.22,
            transformOrigin: 'center',
            duration: 1.4,
            ease: 'power1.inOut',
            repeat: -1,
            yoyo: true,
            stagger: 0.18,
        });

        return () => {
            timeline.kill();
            gsap.killTweensOf(points);
        };
    }, [reduceMotion]);

    return (
        <section className="global-network-section" id="global" aria-labelledby="global-title">
            <div className="page-shell global-network-shell">
                <MotionBlock className="global-network-heading">
                    <div className="section-kicker">
                        <Network size={16} aria-hidden="true" />
                        <span>Rede distribuida</span>
                    </div>

                    <h2 id="global-title">Servidores Wasm Cloud em locais estrategicos para manter seu SaaS escalavel.</h2>

                    <p>
                        A infraestrutura da Wasm Cloud foi pensada para conectar regioes, reduzir distancia operacional e permitir
                        crescimento conforme sua aplicacao ganha usuarios em novos mercados.
                    </p>
                </MotionBlock>

                <div className="global-network-grid">
                    <MotionBlock className="network-map-card" delay={0.06}>
                        <svg
                            ref={svgRef}
                            className="network-map"
                            viewBox={`0 0 ${mapSize.width} ${mapSize.height}`}
                            role="img"
                            aria-labelledby="network-map-title network-map-desc"
                        >
                            <title id="network-map-title">Mapa de conexoes globais Wasm Cloud</title>
                            <desc id="network-map-desc">
                                Mapa mundial com rotas conectando pontos estrategicos de operacao da Wasm Cloud.
                            </desc>

                            <rect className="map-ocean" width={mapSize.width} height={mapSize.height} rx="22" />

                            <g className="map-countries" aria-hidden="true">
                                {countries.map((country) => (
                                    <path d={path(country)} key={country.id} />
                                ))}
                            </g>

                            <g className="map-routes" aria-hidden="true">
                                {routes.map((route) => (
                                    <path d={route.path} data-map-route key={route.key} />
                                ))}
                            </g>

                            <g className="map-points">
                                {locations.map((location) => {
                                    const [x, y] = projection(location.coordinates);

                                    return (
                                        <g className={location.hub ? 'map-point hub' : 'map-point'} data-map-point key={location.name}>
                                            <circle cx={x} cy={y} r={location.hub ? 7 : 5} />
                                            <text x={x + 10} y={y - 8}>{location.name}</text>
                                        </g>
                                    );
                                })}
                            </g>
                        </svg>
                    </MotionBlock>

                    <MotionBlock className="network-operations-card" delay={0.12}>
                        <div className="operations-head">
                            <span className="benefit-icon">
                                <Server size={18} aria-hidden="true" />
                            </span>
                            <div>
                                <span>Malha operacional</span>
                                <strong>Pronta para expansao</strong>
                            </div>
                        </div>

                        <div className="network-stat-list" aria-label="Beneficios da rede global">
                            {networkStats.map((stat) => {
                                const Icon = stat.icon;

                                return (
                                    <div key={stat.label}>
                                        <Icon size={17} aria-hidden="true" />
                                        <span>{stat.label}</span>
                                        <strong>{stat.value}</strong>
                                    </div>
                                );
                            })}
                        </div>

                        <ul className="network-checklist">
                            <li>Distribuicao preparada para aproximar usuarios e servicos.</li>
                            <li>Capacidade de crescer regioes conforme a demanda do SaaS.</li>
                            <li>Base visual para acompanhar operacao, latencia e disponibilidade.</li>
                        </ul>

                        <div className="network-signal">
                            <Activity size={16} aria-hidden="true" />
                            <span>Conexoes monitoradas continuamente</span>
                        </div>
                    </MotionBlock>
                </div>
            </div>
        </section>
    );
}

export function mountGlobalNetworkSection() {
    const rootElement = document.querySelector('[data-global-network-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(<GlobalNetworkSection />);
}
