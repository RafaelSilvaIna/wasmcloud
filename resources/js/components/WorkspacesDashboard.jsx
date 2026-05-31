import React from 'react';
import { createRoot } from 'react-dom/client';
import { ArrowRight, BriefcaseBusiness, FileText, ShieldCheck, Users } from 'lucide-react';
import { motion } from 'framer-motion';

function WorkspacesDashboard({ workspaces, createUrl, docsWorkspaceUrl, limit }) {
    const hasWorkspaces = workspaces.length > 0;

    return (
        <main className="workspace-dashboard" aria-labelledby="workspace-dashboard-title">
            <motion.section
                className="workspace-dashboard-hero"
                initial={{ opacity: 0, y: 14 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.34, ease: 'easeOut' }}
            >
                <div>
                    <span>Workspaces</span>
                    <h1 id="workspace-dashboard-title">
                        {hasWorkspaces ? 'Organize seus ambientes.' : 'Voce ainda nao tem workspaces.'}
                    </h1>
                    <p>
                        Workspaces agrupam projetos, equipe, permissoes e modelo de pagamento em um unico contexto operacional.
                        Sua conta pode ter ate {limit} workspaces.
                    </p>
                </div>

                <div className="workspace-dashboard-actions">
                    <a className="workspace-primary-button" href={createUrl} data-global-loading>
                        Criar workspace
                        <ArrowRight size={16} aria-hidden="true" />
                    </a>
                    <a className="workspace-secondary-button" href={docsWorkspaceUrl} data-global-loading>
                        Ler sobre workspaces
                    </a>
                </div>
            </motion.section>

            {!hasWorkspaces ? (
                <section className="workspace-empty-panel" aria-label="Nenhum workspace encontrado">
                    <div className="workspace-empty-icon">
                        <BriefcaseBusiness size={28} aria-hidden="true" />
                    </div>
                    <div>
                        <span>Comece pela organizacao</span>
                        <h2>Crie seu primeiro workspace</h2>
                        <p>Antes de criar projetos, defina o ambiente onde eles vao viver. O workspace prepara plano, equipe, permissoes e seguranca inicial.</p>
                    </div>
                    <a href={createUrl} data-global-loading>
                        Criar agora
                        <ArrowRight size={16} aria-hidden="true" />
                    </a>
                </section>
            ) : (
                <section className="workspace-list-section" aria-label="Workspaces da conta">
                    <div className="workspace-section-head">
                        <span>{workspaces.length} de {limit}</span>
                        <h2>Seus workspaces</h2>
                    </div>
                    <div className="workspace-card-grid">
                        {workspaces.map((workspace) => (
                            <article className="workspace-card" key={workspace.id}>
                                <div className="workspace-card-icon">
                                    <BriefcaseBusiness size={21} aria-hidden="true" />
                                </div>
                                <div>
                                    <span>{workspace.plan_model}</span>
                                    <h3>{workspace.name}</h3>
                                    <p>{workspace.description || 'Workspace preparado para organizar projetos, equipe e permissoes.'}</p>
                                </div>
                                <footer>
                                    <small>Criado em {workspace.created_at}</small>
                                    <strong>Seguranca preparada</strong>
                                </footer>
                            </article>
                        ))}
                    </div>
                </section>
            )}

            <section className="workspace-principles" aria-label="Principios do workspace">
                <article>
                    <ShieldCheck size={20} aria-hidden="true" />
                    <h2>Seguranca por contexto</h2>
                    <p>Cada workspace nasce isolado por dono, com manifesto inicial para permissoes, auditoria e escopo de pagamento.</p>
                </article>
                <article>
                    <Users size={20} aria-hidden="true" />
                    <h2>Equipe depois</h2>
                    <p>A base ja considera membros e permissoes para que a colaboracao seja ativada com controle fino futuramente.</p>
                </article>
                <article>
                    <FileText size={20} aria-hidden="true" />
                    <h2>Diretrizes claras</h2>
                    <p>A criacao exige confirmacao das regras da plataforma antes de liberar o ambiente.</p>
                </article>
            </section>
        </main>
    );
}

export function mountWorkspacesDashboard() {
    const rootElement = document.querySelector('[data-workspaces-dashboard-root]');

    if (!rootElement) {
        return;
    }

    const workspaces = JSON.parse(document.querySelector('[data-workspaces-payload]')?.textContent || '[]');

    createRoot(rootElement).render(
        <WorkspacesDashboard
            createUrl={rootElement.dataset.createUrl}
            docsWorkspaceUrl={rootElement.dataset.docsWorkspaceUrl}
            limit={rootElement.dataset.limit || 8}
            workspaces={workspaces}
        />
    );
}
