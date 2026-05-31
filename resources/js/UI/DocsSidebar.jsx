import React, { useEffect, useMemo, useState } from 'react';
import { Sidebar, Menu, MenuItem, SubMenu } from 'react-pro-sidebar';
import { BookOpen, Clock3, FileText, Search, X } from 'lucide-react';

const storageKey = 'wasmcloud.docs.recent';

function readRecentArticles() {
    try {
        return JSON.parse(window.localStorage.getItem(storageKey) || '[]');
    } catch {
        return [];
    }
}

export function rememberDocsArticle(articleId) {
    const recent = readRecentArticles().filter((id) => id !== articleId);
    const next = [articleId, ...recent].slice(0, 4);

    window.localStorage.setItem(storageKey, JSON.stringify(next));
    window.dispatchEvent(new CustomEvent('wasmcloud:docs-recent-updated'));
}

export function DocsSidebar({ articles, activeArticleId, open, onClose }) {
    const [query, setQuery] = useState('');
    const [recentIds, setRecentIds] = useState([]);

    useEffect(() => {
        const syncRecent = () => setRecentIds(readRecentArticles());

        syncRecent();
        window.addEventListener('wasmcloud:docs-recent-updated', syncRecent);

        return () => window.removeEventListener('wasmcloud:docs-recent-updated', syncRecent);
    }, []);

    const filteredArticles = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        if (!normalizedQuery) {
            return articles;
        }

        return articles.filter((article) => (
            article.title.toLowerCase().includes(normalizedQuery)
            || article.category.toLowerCase().includes(normalizedQuery)
            || article.excerpt.toLowerCase().includes(normalizedQuery)
        ));
    }, [articles, query]);

    const categories = useMemo(() => (
        filteredArticles.reduce((accumulator, article) => {
            accumulator[article.category] ??= [];
            accumulator[article.category].push(article);

            return accumulator;
        }, {})
    ), [filteredArticles]);

    const recentArticles = recentIds
        .map((id) => articles.find((article) => article.id === id))
        .filter(Boolean);

    return (
        <aside className={open ? 'docs-sidebar-shell is-open' : 'docs-sidebar-shell'} aria-label="Navegacao da documentacao">
            <Sidebar className="docs-sidebar" width="320px" backgroundColor="transparent">
                <div className="docs-sidebar-head">
                    <div>
                        <span>Documentacao</span>
                        <strong>Wasm Cloud</strong>
                    </div>
                    <button type="button" onClick={onClose} aria-label="Fechar navegacao">
                        <X size={18} aria-hidden="true" />
                    </button>
                </div>

                <label className="docs-search">
                    <Search size={17} aria-hidden="true" />
                    <input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Buscar paginas"
                        type="search"
                    />
                </label>

                {recentArticles.length > 0 && (
                    <div className="docs-recent" data-docs-section>
                        <div className="docs-sidebar-label">
                            <Clock3 size={15} aria-hidden="true" />
                            Ultimas paginas
                        </div>

                        <Menu>
                            {recentArticles.map((article) => (
                                <MenuItem
                                    active={activeArticleId === article.id}
                                    className="is-recent"
                                    component={<a href={article.href} data-global-loading />}
                                    icon={<FileText size={16} />}
                                    key={article.id}
                                >
                                    {article.title}
                                </MenuItem>
                            ))}
                        </Menu>
                    </div>
                )}

                <div data-docs-section>
                    <div className="docs-sidebar-label">
                        <BookOpen size={15} aria-hidden="true" />
                        Categorias
                    </div>
                    <Menu>
                        {Object.entries(categories).map(([category, categoryArticles]) => (
                            <SubMenu defaultOpen icon={<BookOpen size={16} />} label={category} key={category}>
                                {categoryArticles.map((article) => (
                                    <MenuItem
                                        active={activeArticleId === article.id}
                                        component={<a href={article.href} data-global-loading />}
                                        icon={<FileText size={16} />}
                                        key={article.id}
                                    >
                                        {article.title}
                                    </MenuItem>
                                ))}
                            </SubMenu>
                        ))}
                    </Menu>
                </div>
            </Sidebar>
        </aside>
    );
}
