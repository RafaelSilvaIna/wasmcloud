import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import { Link2, ShieldCheck, Share2 } from 'lucide-react';
import { gsap } from 'gsap';
import { toast } from 'sonner';
import { DocsSidebar, rememberDocsArticle } from '../UI/DocsSidebar.jsx';
import { withArticleLinks } from '../docs/articles/index.js';

function DocsPage({ initialArticleId, docsBaseUrl }) {
    const [sidebarOpen, setSidebarOpen] = useState(() => window.matchMedia('(min-width: 921px)').matches);
    const reduceMotion = useReducedMotion();
    const articles = useMemo(() => withArticleLinks(docsBaseUrl), [docsBaseUrl]);

    const activeArticle = useMemo(
        () => articles.find((article) => article.id === initialArticleId) || articles[0],
        [articles, initialArticleId]
    );

    const shareArticle = async () => {
        const shareUrl = activeArticle.href.startsWith('http')
            ? activeArticle.href
            : `${window.location.origin}${activeArticle.href}`;

        if (navigator.share) {
            await navigator.share({
                title: `${activeArticle.title} - Wasm Cloud`,
                text: activeArticle.excerpt,
                url: shareUrl,
            });

            return;
        }

        await navigator.clipboard.writeText(shareUrl);
        toast.success('Link da documentacao copiado.');
    };

    useEffect(() => {
        rememberDocsArticle(activeArticle.id);
        window.dispatchEvent(new CustomEvent('wasmcloud:page-title', { detail: activeArticle.title }));

        if (!reduceMotion) {
            gsap.fromTo('[data-docs-article]', { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.34, ease: 'power2.out' });
        }
    }, [activeArticle, reduceMotion]);

    useEffect(() => {
        const toggle = () => setSidebarOpen((current) => !current);

        window.addEventListener('wasmcloud:toggle-sidebar', toggle);

        return () => window.removeEventListener('wasmcloud:toggle-sidebar', toggle);
    }, []);

    const Icon = activeArticle.icon;

    return (
        <div className={sidebarOpen ? 'docs-layout sidebar-open' : 'docs-layout'}>
            <DocsSidebar
                articles={articles}
                activeArticleId={activeArticle.id}
                open={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
            />

            <motion.main className="docs-content" data-docs-article key={activeArticle.id}>
                <article className="docs-article">
                    <header className="docs-article-head">
                        <div className="docs-article-topline">
                            <div className="docs-article-icon">
                                <Icon size={22} aria-hidden="true" />
                            </div>
                            <button className="docs-share-button" type="button" onClick={shareArticle}>
                                <Share2 size={16} aria-hidden="true" />
                                Compartilhar
                            </button>
                        </div>
                        <span>{activeArticle.category}</span>
                        <h1>{activeArticle.title}</h1>
                        <p>{activeArticle.excerpt}</p>
                    </header>

                    <div className="docs-legal-note">
                        <ShieldCheck size={18} aria-hidden="true" />
                        <p>Conteudo informativo. Termos finais, politicas comerciais e documentos legais prevalecem em caso de divergencia.</p>
                    </div>

                    {activeArticle.content.map((section) => (
                        <section className="docs-section" key={section.heading}>
                            <h2>{section.heading}</h2>
                            {section.body.map((paragraph) => (
                                <p key={paragraph}>{paragraph}</p>
                            ))}
                        </section>
                    ))}

                    <footer className="docs-sources">
                        <h2>Referencias externas</h2>
                        <a className="docs-copy-link" href={activeArticle.href}>
                            <Link2 size={15} aria-hidden="true" />
                            Link permanente deste artigo
                        </a>
                        <ul>
                            <li><a href="https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm" target="_blank" rel="noreferrer">LGPD - Lei 13.709/2018</a></li>
                            <li><a href="https://www.pcisecuritystandards.org/" target="_blank" rel="noreferrer">PCI Security Standards Council</a></li>
                            <li><a href="https://docs.stripe.com/security" target="_blank" rel="noreferrer">Stripe Security e PCI</a></li>
                            <li><a href="https://docs.abacatepay.com/" target="_blank" rel="noreferrer">Documentacao AbacatePay</a></li>
                        </ul>
                    </footer>
                </article>
            </motion.main>
        </div>
    );
}

export function mountDocsPage() {
    const rootElement = document.querySelector('[data-docs-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <DocsPage
            docsBaseUrl={rootElement.dataset.docsBaseUrl || '/documentacao'}
            initialArticleId={rootElement.dataset.currentArticle || 'assinaturas'}
        />
    );
}
