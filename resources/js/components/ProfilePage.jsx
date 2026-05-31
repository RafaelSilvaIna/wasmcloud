import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { motion } from 'framer-motion';
import {
    Camera,
    Check,
    GitBranch,
    Image,
    Link2,
    Mail,
    Paintbrush,
    Pencil,
    Phone,
    UploadCloud,
    User,
} from 'lucide-react';
import { toast } from 'sonner';
import { Modal } from '../UI/Modal.jsx';

const maxImageBytes = 4 * 1024 * 1024;

function initialsFromName(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function fieldErrorsFromResponse(payload) {
    return Object.values(payload.errors || {}).flat().join(' ');
}

async function requestJson(url, method, csrfToken, body) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(body),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(fieldErrorsFromResponse(payload) || payload.message || 'Nao foi possivel salvar as alteracoes.');
    }

    return payload;
}

function uploadImage({ url, csrfToken, imageType, file, onProgress }) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        const xhr = new XMLHttpRequest();

        formData.append('image_type', imageType);
        formData.append('image', file);

        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        });

        xhr.addEventListener('load', () => {
            const payload = JSON.parse(xhr.responseText || '{}');

            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(payload);
                return;
            }

            reject(new Error(fieldErrorsFromResponse(payload) || payload.message || 'Upload nao concluido.'));
        });

        xhr.addEventListener('error', () => reject(new Error('Falha de rede durante o upload.')));
        xhr.open('POST', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.send(formData);
    });
}

function ProfilePage({ initialProfile, endpoints, csrfToken }) {
    const [profile, setProfile] = useState(initialProfile);
    const [modal, setModal] = useState(null);
    const [saving, setSaving] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [detailsForm, setDetailsForm] = useState({
        name: initialProfile.name || '',
        github_url: initialProfile.github_url || '',
        github_repository_url: initialProfile.github_repository_url || '',
    });
    const [bannerColor, setBannerColor] = useState(initialProfile.banner_color || '#101010');

    const initials = useMemo(() => initialsFromName(profile.name), [profile.name]);

    const syncProfile = (nextProfile) => {
        setProfile(nextProfile);
        setDetailsForm({
            name: nextProfile.name || '',
            github_url: nextProfile.github_url || '',
            github_repository_url: nextProfile.github_repository_url || '',
        });
        setBannerColor(nextProfile.banner_color || '#101010');
        window.dispatchEvent(new CustomEvent('wasmcloud:profile-updated', { detail: nextProfile }));
    };

    const saveDetails = async () => {
        setSaving(true);

        try {
            const payload = await requestJson(endpoints.details, 'PATCH', csrfToken, detailsForm);
            syncProfile(payload.profile);
            setModal(null);
            toast.success(payload.message);
        } catch (error) {
            toast.error(error.message);
        } finally {
            setSaving(false);
        }
    };

    const saveBannerColor = async () => {
        setSaving(true);

        try {
            const payload = await requestJson(endpoints.appearance, 'PATCH', csrfToken, { banner_color: bannerColor });
            syncProfile(payload.profile);
            toast.success(payload.message);
        } catch (error) {
            toast.error(error.message);
        } finally {
            setSaving(false);
        }
    };

    const handleImageUpload = async (imageType, file) => {
        if (!file) {
            return;
        }

        if (file.size > maxImageBytes) {
            toast.error('Imagem acima do limite de 4 MB.');
            return;
        }

        setSaving(true);
        setUploadProgress(0);

        try {
            const payload = await uploadImage({
                url: endpoints.image,
                csrfToken,
                imageType,
                file,
                onProgress: setUploadProgress,
            });

            syncProfile(payload.profile);
            setUploadProgress(100);
            toast.success(payload.message);
        } catch (error) {
            toast.error(error.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <main className="profile-page-shell" aria-labelledby="profile-title">
            <motion.section
                className="profile-hero-card"
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.34, ease: 'easeOut' }}
            >
                <div
                    className="profile-banner"
                    style={{
                        backgroundColor: profile.banner_color || '#101010',
                        backgroundImage: profile.banner_image_url ? `url(${profile.banner_image_url})` : 'none',
                    }}
                >
                    <button type="button" onClick={() => setModal('banner')}>
                        <Paintbrush size={16} aria-hidden="true" />
                        Editar banner
                    </button>
                </div>

                <div className="profile-identity-row">
                    <button className="profile-avatar-editor" type="button" onClick={() => setModal('avatar')} aria-label="Editar foto de perfil">
                        {profile.profile_photo_url ? (
                            <img src={profile.profile_photo_url} alt="" />
                        ) : (
                            <span>{initials || <User size={32} aria-hidden="true" />}</span>
                        )}
                        <small><Camera size={14} aria-hidden="true" /></small>
                    </button>

                    <div className="profile-title-block">
                        <h1 id="profile-title">{profile.name}</h1>
                        <p>Gerencie sua identidade, links tecnicos e a aparencia usada nas paginas autenticadas.</p>
                    </div>

                    <button className="profile-secondary-button" type="button" onClick={() => setModal('identity')}>
                        <Pencil size={16} aria-hidden="true" />
                        Editar perfil
                    </button>
                </div>
            </motion.section>

            <section className="profile-grid" aria-label="Dados do perfil">
                <article className="profile-info-card">
                    <div className="profile-card-head">
                        <div>
                            <span>Conta</span>
                            <h2>Dados principais</h2>
                        </div>
                        <Check size={18} aria-hidden="true" />
                    </div>
                    <dl className="profile-detail-list">
                        <div>
                            <dt><Mail size={16} aria-hidden="true" /> Email</dt>
                            <dd>{profile.email}</dd>
                        </div>
                        <div>
                            <dt><Phone size={16} aria-hidden="true" /> Telefone</dt>
                            <dd>{profile.phone || 'Nao informado'}</dd>
                        </div>
                    </dl>
                </article>

                <article className="profile-info-card">
                    <div className="profile-card-head">
                        <div>
                            <span>GitHub</span>
                            <h2>Codigo e repositorio</h2>
                        </div>
                        <GitBranch size={18} aria-hidden="true" />
                    </div>
                    <dl className="profile-detail-list">
                        <div>
                            <dt><GitBranch size={16} aria-hidden="true" /> Perfil</dt>
                            <dd>{profile.github_url ? <a href={profile.github_url} target="_blank" rel="noreferrer">{profile.github_url}</a> : 'Nenhum perfil vinculado'}</dd>
                        </div>
                        <div>
                            <dt><Link2 size={16} aria-hidden="true" /> Repositorio</dt>
                            <dd>{profile.github_repository_url ? <a href={profile.github_repository_url} target="_blank" rel="noreferrer">{profile.github_repository_url}</a> : 'Nenhum repositorio vinculado'}</dd>
                        </div>
                    </dl>
                    <button className="profile-inline-action" type="button" onClick={() => setModal('github')}>
                        <Pencil size={15} aria-hidden="true" />
                        Configurar GitHub
                    </button>
                </article>
            </section>

            <Modal
                open={modal === 'identity'}
                title="Editar nome do perfil"
                description="Use seu nome real ou o nome publico que identifica a conta dentro da Wasm Cloud."
                onClose={() => setModal(null)}
                footer={(
                    <>
                        <button className="modal-button ghost" type="button" onClick={() => setModal(null)}>Cancelar</button>
                        <button className="modal-button primary" type="button" disabled={saving} onClick={saveDetails}>Salvar</button>
                    </>
                )}
            >
                <label className="profile-form-field">
                    <span>Nome completo</span>
                    <input
                        value={detailsForm.name}
                        maxLength={120}
                        minLength={3}
                        onChange={(event) => setDetailsForm((current) => ({ ...current, name: event.target.value }))}
                        placeholder="Seu nome"
                    />
                </label>
            </Modal>

            <Modal
                open={modal === 'github'}
                title="Configurar GitHub"
                description="Cole uma URL oficial do GitHub. Repositorios HTTPS e SSH sao aceitos para compatibilidade com fluxos de deploy."
                onClose={() => setModal(null)}
                footer={(
                    <>
                        <button className="modal-button ghost" type="button" onClick={() => setModal(null)}>Cancelar</button>
                        <button className="modal-button primary" type="button" disabled={saving} onClick={saveDetails}>Salvar links</button>
                    </>
                )}
            >
                <div className="profile-form-stack">
                    <label className="profile-form-field">
                        <span>Perfil do GitHub</span>
                        <input
                            value={detailsForm.github_url}
                            onChange={(event) => setDetailsForm((current) => ({ ...current, github_url: event.target.value }))}
                            placeholder="https://github.com/usuario"
                        />
                    </label>
                    <label className="profile-form-field">
                        <span>Repositorio principal</span>
                        <input
                            value={detailsForm.github_repository_url}
                            onChange={(event) => setDetailsForm((current) => ({ ...current, github_repository_url: event.target.value }))}
                            placeholder="https://github.com/usuario/repositorio"
                        />
                    </label>
                    <p className="profile-helper-text">Use links do dominio github.com. Para repositorios, tambem aceitamos o formato SSH git@github.com:usuario/repositorio.git.</p>
                </div>
            </Modal>

            <Modal
                open={modal === 'banner'}
                title="Editar banner"
                description="Escolha uma cor solida ou envie uma imagem leve para personalizar o topo do perfil."
                onClose={() => setModal(null)}
                footer={(
                    <>
                        <button className="modal-button ghost" type="button" onClick={() => setModal(null)}>Fechar</button>
                        <button className="modal-button primary" type="button" disabled={saving} onClick={saveBannerColor}>Salvar cor</button>
                    </>
                )}
            >
                <div className="profile-form-stack">
                    <label className="profile-color-field">
                        <span>Cor solida</span>
                        <input
                            type="color"
                            value={bannerColor}
                            onChange={(event) => setBannerColor(event.target.value)}
                        />
                        <strong>{bannerColor}</strong>
                    </label>
                    <label className="profile-upload-box">
                        <Image size={22} aria-hidden="true" />
                        <strong>Enviar imagem do banner</strong>
                        <span>PNG, JPG, WEBP ou GIF ate 4 MB.</span>
                        <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" onChange={(event) => handleImageUpload('banner', event.target.files?.[0])} />
                    </label>
                    {saving && <ProgressBar value={uploadProgress} />}
                </div>
            </Modal>

            <Modal
                open={modal === 'avatar'}
                title="Editar foto de perfil"
                description="A foto aparece no header e nas areas autenticadas do painel."
                onClose={() => setModal(null)}
                footer={<button className="modal-button ghost" type="button" onClick={() => setModal(null)}>Fechar</button>}
            >
                <label className="profile-upload-box">
                    <UploadCloud size={22} aria-hidden="true" />
                    <strong>Enviar foto de perfil</strong>
                    <span>Use uma imagem quadrada em PNG, JPG, WEBP ou GIF ate 4 MB.</span>
                    <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" onChange={(event) => handleImageUpload('avatar', event.target.files?.[0])} />
                </label>
                {saving && <ProgressBar value={uploadProgress} />}
            </Modal>
        </main>
    );
}

function ProgressBar({ value }) {
    return (
        <div className="profile-upload-progress" role="progressbar" aria-valuenow={value} aria-valuemin="0" aria-valuemax="100">
            <span style={{ width: `${value}%` }} />
            <strong>{value}%</strong>
        </div>
    );
}

export function mountProfilePage() {
    const rootElement = document.querySelector('[data-profile-root]');

    if (!rootElement) {
        return;
    }

    createRoot(rootElement).render(
        <ProfilePage
            csrfToken={rootElement.dataset.csrfToken || ''}
            endpoints={{
                details: rootElement.dataset.detailsUrl,
                appearance: rootElement.dataset.appearanceUrl,
                image: rootElement.dataset.imageUrl,
            }}
            initialProfile={JSON.parse(document.querySelector('[data-profile-payload]')?.textContent || '{}')}
        />
    );
}
