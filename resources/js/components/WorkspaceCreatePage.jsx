import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { AnimatePresence, motion } from 'framer-motion';
import { ArrowLeft, ArrowRight, CheckCircle2, FileCheck2, Loader2, ShieldCheck, Sparkles } from 'lucide-react';
import { toast } from 'sonner';

const steps = [
    'Identidade',
    'Diretrizes',
    'Confirmacao',
    'Arquitetura',
];

function errorsFromPayload(payload) {
    return Object.values(payload.errors || {}).flat().join(' ');
}

function WorkspaceCreatePage({ storeUrl, csrfToken, dashboardUrl, guidelinesUrl, workspaceDocsUrl }) {
    const [step, setStep] = useState(0);
    const [creating, setCreating] = useState(false);
    const [form, setForm] = useState({
        name: '',
        description: '',
        accepted_guidelines: false,
    });

    const canContinue = useMemo(() => {
        if (step === 0) {
            return form.name.trim().length >= 3;
        }

        if (step === 1) {
            return form.accepted_guidelines;
        }

        return true;
    }, [form, step]);

    const next = () => {
        if (!canContinue) {
            toast.error(step === 0 ? 'Informe um nome com pelo menos 3 caracteres.' : 'Confirme as diretrizes da Wasm Cloud.');
            return;
        }

        setStep((current) => Math.min(current + 1, 2));
    };

    const createWorkspace = async () => {
        setStep(3);
        setCreating(true);

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify(form),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(errorsFromPayload(payload) || payload.message || 'Nao foi possivel criar o workspace.');
            }

            await new Promise((resolve) => window.setTimeout(resolve, 900));
            toast.success(payload.message || 'Workspace criado.');
            window.location.assign(payload.redirect || dashboardUrl);
        } catch (error) {
            toast.error(error.message);
            setCreating(false);
            setStep(2);
        }
    };

    return (
        <main className="workspace-create-shell" aria-labelledby="workspace-create-title">
            <section className="workspace-create-card">
                <header className="workspace-create-head">
                    <div>
                        <span>Workspace</span>
                        <h1 id="workspace-create-title">Criar novo workspace</h1>
                        <p>Configure a base onde seus projetos, equipe, permissoes e modelo de pagamento serao organizados.</p>
                    </div>
                    <a href={dashboardUrl} data-global-loading>
                        <ArrowLeft size={16} aria-hidden="true" />
                        Dashboard
                    </a>
                </header>

                <nav className="workspace-stepper" aria-label="Etapas de criacao">
                    {steps.map((label, index) => (
                        <span className={index <= step ? 'is-active' : ''} key={label}>
                            <strong>{index + 1}</strong>
                            {label}
                        </span>
                    ))}
                </nav>

                <AnimatePresence mode="wait">
                    {step === 0 && (
                        <motion.section className="workspace-step-panel" key="identity" initial={{ opacity: 0, x: 18 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -18 }}>
                            <FileCheck2 size={24} aria-hidden="true" />
                            <h2>Identidade do workspace</h2>
                            <p>Use um nome claro para reconhecer este ambiente no dashboard.</p>
                            <label>
                                <span>Nome do workspace</span>
                                <input value={form.name} maxLength={120} onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} placeholder="Ex: Escola, Produto principal, Cliente ACME" />
                            </label>
                            <label>
                                <span>Descricao</span>
                                <textarea value={form.description} maxLength={600} onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))} placeholder="Descreva o objetivo deste workspace." />
                            </label>
                        </motion.section>
                    )}

                    {step === 1 && (
                        <motion.section className="workspace-step-panel" key="guidelines" initial={{ opacity: 0, x: 18 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -18 }}>
                            <ShieldCheck size={24} aria-hidden="true" />
                            <h2>Diretrizes da Wasm Cloud</h2>
                            <p>Antes de criar o workspace, confirme que voce entende as regras de uso, inclusive restricoes para menores de idade e transacoes.</p>
                            <div className="workspace-guideline-box">
                                <a href={workspaceDocsUrl} target="_blank" rel="noreferrer">Ler sobre workspaces</a>
                                <a href={guidelinesUrl} target="_blank" rel="noreferrer">Ler diretrizes para menores</a>
                            </div>
                            <label className="workspace-check">
                                <input type="checkbox" checked={form.accepted_guidelines} onChange={(event) => setForm((current) => ({ ...current, accepted_guidelines: event.target.checked }))} />
                                <span>Confirmo que sigo todas as diretrizes da Wasm Cloud e que o workspace nao sera usado para atividades proibidas.</span>
                            </label>
                        </motion.section>
                    )}

                    {step === 2 && (
                        <motion.section className="workspace-step-panel" key="confirm" initial={{ opacity: 0, x: 18 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -18 }}>
                            <CheckCircle2 size={24} aria-hidden="true" />
                            <h2>Confirmar criacao</h2>
                            <p>Vamos preparar um workspace Micro, com escopo de seguranca, permissao e pagamento no nivel do workspace.</p>
                            <div className="workspace-review">
                                <div>
                                    <span>Nome</span>
                                    <strong>{form.name}</strong>
                                </div>
                                <div>
                                    <span>Plano inicial</span>
                                    <strong>Micro</strong>
                                </div>
                                <div>
                                    <span>Diretrizes</span>
                                    <strong>Confirmadas</strong>
                                </div>
                            </div>
                        </motion.section>
                    )}

                    {step === 3 && (
                        <motion.section className="workspace-step-panel workspace-step-loading" key="loading" initial={{ opacity: 0, scale: 0.98 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0 }}>
                            <Loader2 size={34} aria-hidden="true" />
                            <h2>Criando arquitetura do workspace</h2>
                            <p>Preparando isolamento do dono, manifesto de seguranca, base de permissoes e escopo de pagamento.</p>
                            <div>
                                <span />
                            </div>
                        </motion.section>
                    )}
                </AnimatePresence>

                {step < 3 && (
                    <footer className="workspace-create-footer">
                        <button type="button" className="workspace-secondary-button" disabled={step === 0} onClick={() => setStep((current) => Math.max(current - 1, 0))}>
                            <ArrowLeft size={16} aria-hidden="true" />
                            Voltar
                        </button>
                        {step < 2 ? (
                            <button type="button" className="workspace-primary-button" onClick={next}>
                                Continuar
                                <ArrowRight size={16} aria-hidden="true" />
                            </button>
                        ) : (
                            <button type="button" className="workspace-primary-button" disabled={creating} onClick={createWorkspace}>
                                <Sparkles size={16} aria-hidden="true" />
                                Criar workspace
                            </button>
                        )}
                    </footer>
                )}
            </section>
        </main>
    );
}

export function mountWorkspaceCreatePage() {
    const rootElement = document.querySelector('[data-workspace-create-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <WorkspaceCreatePage
            csrfToken={rootElement.dataset.csrfToken || ''}
            dashboardUrl={rootElement.dataset.dashboardUrl}
            guidelinesUrl={rootElement.dataset.guidelinesUrl}
            storeUrl={rootElement.dataset.storeUrl}
            workspaceDocsUrl={rootElement.dataset.workspaceDocsUrl}
        />
    );
}
