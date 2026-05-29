import './bootstrap';

import { gsap } from 'gsap';
import { MotionPathPlugin } from 'gsap/MotionPathPlugin';

gsap.registerPlugin(MotionPathPlugin);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initHeroFlow() {
    const svg = document.querySelector('[data-flow-svg]');
    const route = document.querySelector('#wasm-flow-route');
    const pulses = gsap.utils.toArray('[data-flow-pulse]');
    const nodes = gsap.utils.toArray('[data-flow-node]');
    const copy = document.querySelector('[data-hero-copy]');

    if (!svg || !route || !pulses.length) {
        return;
    }

    if (prefersReducedMotion) {
        gsap.set(pulses, { opacity: 1, motionPath: { path: route, align: route, end: .5 } });
        return;
    }

    gsap.from([copy, svg], {
        autoAlpha: 0,
        duration: .9,
        ease: 'power2.out',
        stagger: .12,
        y: 18,
    });

    gsap.to(route, {
        attr: { 'stroke-dashoffset': -72 },
        duration: 5.5,
        ease: 'none',
        repeat: -1,
    });

    pulses.forEach((pulse, index) => {
        gsap.to(pulse, {
            duration: 5.8,
            ease: 'none',
            repeat: -1,
            delay: index * 1.65,
            motionPath: {
                path: route,
                align: route,
                alignOrigin: [0.5, 0.5],
            },
        });
    });

    gsap.to(nodes, {
        duration: 1.8,
        ease: 'power1.inOut',
        repeat: -1,
        stagger: {
            each: .5,
            repeat: -1,
            yoyo: true,
        },
        opacity: .78,
        scale: .985,
    });

    svg.addEventListener('pointermove', (event) => {
        const bounds = svg.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - .5;
        const y = (event.clientY - bounds.top) / bounds.height - .5;

        gsap.to(svg, {
            duration: .6,
            ease: 'power2.out',
            rotateX: y * -3,
            rotateY: x * 4,
            transformPerspective: 900,
        });
    });

    svg.addEventListener('pointerleave', () => {
        gsap.to(svg, {
            duration: .7,
            ease: 'power2.out',
            rotateX: 0,
            rotateY: 0,
        });
    });
}

document.addEventListener('DOMContentLoaded', initHeroFlow);
