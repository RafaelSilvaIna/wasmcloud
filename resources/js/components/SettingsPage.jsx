import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { motion, useReducedMotion } from 'framer-motion';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Globe2,
    Laptop,
    Loader2,
    LogOut,
    MapPin,
    MonitorSmartphone,
    RefreshCw,
    ShieldCheck,
    Smartphone,
    Tablet,
} from 'lucide-react';
import { gsap } from 'gsap';
import { toast } from 'sonner';
import { Modal } from '../UI/Modal.jsx';

const iconByType = {
    desktop: Laptop,
    mobile: Smartphone,
    tablet: Tablet,
};

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
        ...options,
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || 'Nao foi possivel concluir a acao.');
    }

    return payload;
}

function SettingsPage({ endpoints, csrfToken, loginUrl }) {
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [modal, setModal] = useState(null);
    const [lastSync, setLastSync] = useState('');
    const reduceMotion = useReducedMotion();

    const currentSession = useMemo(() => sessions.find((session) => session.is_current), [sessions]);
    const otherSessions = useMemo(() => sessions.filter((session) => !session.is_current), [sessions]);

    const refreshSessions = useCallback(async ({ silent = false } = {}) => {
        if (!silent) {
            setLoading(true);
        }

        try {
            const payload = await requestJson(endpoints.sessions);
            setSessions(payload.sessions || []);
            setLastSync(new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }));
        } catch (error) {
            if (!silent) {
                toast.error(error.message);
            }
        } finally {
            if (!silent) {
                setLoading(false);
            }
        }
    }, [endpoints.sessions]);

    useEffect(() => {
        refreshSessions();
        const interval = window.setInterval(() => refreshSessions({ silent: true }), 6000);

        return () => window.clearInterval(interval);
    }, [refreshSessions]);

    useEffect(() => {
        if (reduceMotion) {
            return;
        }

        gsap.fromTo('[data-settings-card]', {
            autoAlpha: 0,
            y: 14,
        }, {
            autoAlpha: 1,
            y: 0,
            duration: 0.38,
            ease: 'power2.out',
            stagger: 0.07,
        });
    }, [reduceMotion, sessions.length]);

    const runAction = async (action) => {
        setBusy(true);
        window.WasmCloudLoader?.show?.();

        try {
            const payload = await action();

            if (payload.redirect) {
                window.location.assign(payload.redirect || loginUrl);
                return;
            }

            setSessions(payload.sessions || []);
            toast.success(payload.message);
            setModal(null);
        } catch (error) {
            toast.error(error.message);
        } finally {
            setBusy(false);
            window.WasmCloudLoader?.hide?.();
        }
    };

    const disconnectSession = (session) => runAction(() => requestJson(`${endpoints.destroySession}/${session.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    }));

    const disconnectOthers = () => runAction(() => requestJson(endpoints.destroyOthers, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    }));

    return (
        <main className="settings-shell" aria-labelledby="settings-title">
            <motion.section
                className="settings-hero"
                data-settings-card
                initial={false}
            >
                <div>
                    <span>Seguranca da conta</span>
                    <h1 id="settings-title">Configuracoes gerais</h1>
                    <p>Gerencie dispositivos conectados, veja localizacao aproximada por IP e encerre acessos que voce nao reconhece.</p>
                </div>
                <button className="settings-refresh-button" type="button" onClick={() => refreshSessions()}>
                    <RefreshCw size={16} aria-hidden="true" />
                    Atualizar agora
                </button>
            </motion.section>

            <section className="settings-section" data-settings-card>
                <div className="settings-section-head">
                    <div>
                        <span>Sessoes ativas</span>
                        <h2>Dispositivos conectados</h2>
                        <p>Atualizacao automatica a cada poucos segundos. Ultima leitura: {lastSync || 'aguardando'}.</p>
                    </div>
                    <button
                        className="settings-danger-button"
                        type="button"
                        disabled={otherSessions.length === 0 || busy}
                        onClick={() => setModal({ type: 'all' })}
                    >
                        <LogOut size={16} aria-hidden="true" />
                        Sair dos outros dispositivos
                    </button>
                </div>

                {loading ? (
                    <div className="settings-empty-state">
                        <Loader2 size={22} aria-hidden="true" />
                        Carregando dispositivos conectados
                    </div>
                ) : (
                    <div className="settings-device-list">
                        {sessions.map((session) => (
                            <DeviceCard
                                key={session.id}
                                session={session}
                                onDisconnect={() => setModal({ type: 'single', session })}
                            />
                        ))}
                    </div>
                )}
            </section>

            <section className="settings-section settings-section--split" data-settings-card>
                <article>
                    <ShieldCheck size={20} aria-hidden="true" />
                    <h2>Como protegemos essa lista</h2>
                    <p>A lista usa sessoes do Laravel vinculadas ao usuario autenticado. Encerrar um dispositivo remove a sessao no banco, e o aplicativo verifica periodicamente se o acesso atual ainda e valido.</p>
                </article>
                <article>
                    <Globe2 size={20} aria-hidden="true" />
                    <h2>Localizacao aproximada</h2>
                    <p>A regiao exibida vem do IP da sessao. Em redes locais, VPNs ou proxies, a localizacao pode aparecer como rede local ou aproximada.</p>
                </article>
            </section>

            <Modal
                open={Boolean(modal)}
                title={modal?.type === 'all' ? 'Sair dos outros dispositivos?' : 'Desconectar este dispositivo?'}
                description={modal?.type === 'all'
                    ? 'Todas as outras sessoes da sua conta serao encerradas. A sessao atual permanece ativa.'
                    : 'Esse dispositivo perdera acesso na proxima atualizacao ou requisicao autenticada.'}
                onClose={() => setModal(null)}
                footer={(
                    <>
                        <button className="modal-button ghost" type="button" onClick={() => setModal(null)}>Cancelar</button>
                        <button
                            className="modal-button primary"
                            type="button"
                            disabled={busy}
                            onClick={() => (modal?.type === 'all' ? disconnectOthers() : disconnectSession(modal.session))}
                        >
                            Confirmar
                        </button>
                    </>
                )}
            >
                <div className="settings-modal-note">
                    <AlertTriangle size={18} aria-hidden="true" />
                    <p>Revise antes de confirmar. A acao e imediata e protege a conta contra acessos nao reconhecidos.</p>
                </div>
            </Modal>
        </main>
    );
}

function DeviceCard({ session, onDisconnect }) {
    const DeviceIcon = iconByType[session.device_type] || MonitorSmartphone;

    return (
        <article className={session.is_current ? 'settings-device-card is-current' : 'settings-device-card'}>
            <div className="settings-device-icon">
                <DeviceIcon size={22} aria-hidden="true" />
            </div>
            <div className="settings-device-copy">
                <div className="settings-device-title">
                    <h3>{session.device_name}</h3>
                    <span>
                        {session.is_current && <CheckCircle2 size={14} aria-hidden="true" />}
                        {session.status}
                    </span>
                </div>
                <dl>
                    <div>
                        <dt>Navegador</dt>
                        <dd>{session.browser}</dd>
                    </div>
                    <div>
                        <dt>Sistema</dt>
                        <dd>{session.os}</dd>
                    </div>
                    <div>
                        <dt>IP</dt>
                        <dd>{session.ip_address}</dd>
                    </div>
                    <div>
                        <dt><MapPin size={14} aria-hidden="true" /> Regiao</dt>
                        <dd>{session.location}</dd>
                    </div>
                    <div>
                        <dt><Clock3 size={14} aria-hidden="true" /> Atividade</dt>
                        <dd>{session.last_activity_human}</dd>
                    </div>
                </dl>
            </div>
            <button className="settings-device-action" type="button" onClick={onDisconnect}>
                <LogOut size={15} aria-hidden="true" />
                {session.is_current ? 'Sair desta sessao' : 'Desconectar'}
            </button>
        </article>
    );
}

export function mountSettingsPage() {
    const rootElement = document.querySelector('[data-settings-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <SettingsPage
            csrfToken={rootElement.dataset.csrfToken || ''}
            loginUrl={rootElement.dataset.loginUrl || '/login'}
            endpoints={{
                sessions: rootElement.dataset.sessionsUrl,
                destroySession: rootElement.dataset.destroySessionUrl,
                destroyOthers: rootElement.dataset.destroyOthersUrl,
            }}
        />
    );
}
