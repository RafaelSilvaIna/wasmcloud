<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../hooks/ads/AdsAuthHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../components/ads/AdsDashboardShell.php';

$account = $activeAdsAccount ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title>Configurar conta — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <style>
        .onboarding-layout {
            display: grid;
            grid-template-columns: 330px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }
        .onboarding-card {
            border: 1px solid var(--ads-line);
            border-radius: 28px;
            background: var(--ads-surface);
            padding: 24px;
        }
        .aside-card {
            position: sticky;
            top: 132px;
        }
        .aside-card h2 {
            margin: 0 0 10px;
            letter-spacing: -.03em;
        }
        .aside-card p {
            color: var(--ads-muted);
            line-height: 1.7;
            margin: 0;
        }
        .progress-list {
            display: grid;
            gap: 14px;
            margin-top: 24px;
        }
        .progress-row {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--ads-muted);
        }
        .progress-dot {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(10,122,255,.14);
            color: #b7d6ff;
            font-weight: 800;
        }
        .form-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 22px;
        }
        .form-head h2 {
            margin: 0 0 8px;
            letter-spacing: -.03em;
        }
        .form-head p {
            margin: 0;
            color: var(--ads-muted);
        }
        .logo-stage {
            display: grid;
            grid-template-columns: 112px 1fr;
            gap: 16px;
            align-items: center;
            border: 1px solid var(--ads-line);
            border-radius: 22px;
            padding: 16px;
            background: rgba(255,255,255,.025);
            margin-bottom: 18px;
        }
        .logo-preview {
            width: 96px;
            height: 96px;
            border-radius: 24px;
            object-fit: cover;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--ads-line);
        }
        .upload-label {
            display: inline-flex;
            width: fit-content;
            min-height: 42px;
            align-items: center;
            border: 1px solid var(--ads-line);
            border-radius: 14px;
            padding: 0 14px;
            cursor: pointer;
            font-weight: 750;
            background: rgba(255,255,255,.04);
        }
        .upload-label input {
            display: none;
        }
        .logo-copy {
            display: grid;
            gap: 9px;
        }
        .logo-copy span {
            color: var(--ads-muted);
            font-size: .88rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .field {
            display: grid;
            gap: 8px;
        }
        .field.full {
            grid-column: 1 / -1;
        }
        label {
            color: var(--ads-muted);
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--ads-line);
            border-radius: 16px;
            color: #fff;
            background: rgba(255,255,255,.035);
            font: inherit;
            outline: none;
        }
        input, select {
            min-height: 50px;
            padding: 0 15px;
        }
        textarea {
            min-height: 110px;
            resize: vertical;
            padding: 14px 15px;
        }
        input:focus, select:focus, textarea:focus {
            border-color: rgba(138,181,255,.58);
            box-shadow: 0 0 0 4px rgba(10,122,255,.12);
        }
        .form-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-top: 20px;
        }
        .message {
            min-height: 20px;
            color: #ff9c9c;
            font-size: .9rem;
        }
        .submit {
            min-height: 48px;
            border: 0;
            border-radius: 16px;
            padding: 0 18px;
            color: #fff;
            font-weight: 800;
            background: linear-gradient(135deg, var(--ads-blue), var(--ads-violet));
            cursor: pointer;
        }
        @media (max-width: 960px) {
            .onboarding-layout { grid-template-columns: 1fr; }
            .aside-card { position: static; }
        }
        @media (max-width: 680px) {
            .form-grid, .logo-stage { grid-template-columns: 1fr; }
            .form-foot { flex-direction: column; align-items: stretch; }
            .submit { width: 100%; }
        }
    </style>
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'Configurar conta', 'dashboard', true); ?>
    <section class="onboarding-layout">
        <aside class="onboarding-card aside-card">
            <h2>Finalize sua conta comercial</h2>
            <p>Esta etapa aparece uma única vez. Ela organiza sua marca antes da primeira campanha e evita painéis incompletos.</p>
            <div class="progress-list">
                <div class="progress-row"><span class="progress-dot">1</span><span>Identidade visual</span></div>
                <div class="progress-row"><span class="progress-dot">2</span><span>Contato operacional</span></div>
                <div class="progress-row"><span class="progress-dot">3</span><span>Perfil do negócio</span></div>
            </div>
        </aside>

        <article class="onboarding-card">
            <div class="form-head">
                <div>
                    <h2>Dados da marca</h2>
                    <p>Essas informações ajudam o Ads a identificar sua conta com precisão.</p>
                </div>
            </div>

            <form id="onboarding-form" novalidate>
                <input type="hidden" name="logo_url" id="logo_url">
                <div class="logo-stage">
                    <img id="logo-preview" class="logo-preview" src="/assets/img/ads/logo-icone.png" alt="">
                    <div class="logo-copy">
                        <strong>Logo da marca</strong>
                        <span>PNG, JPG ou WEBP. Recomendado: formato quadrado.</span>
                        <label class="upload-label">
                            Enviar logo
                            <input id="logo-file" type="file" accept="image/png,image/jpeg,image/webp">
                        </label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="contact_name">Responsável</label>
                        <input id="contact_name" name="contact_name" required placeholder="Nome completo">
                    </div>
                    <div class="field">
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" required inputmode="tel" placeholder="(11) 99999-9999">
                    </div>
                    <div class="field">
                        <label for="website_url">Site</label>
                        <input id="website_url" name="website_url" type="url" placeholder="https://suaempresa.com">
                    </div>
                    <div class="field">
                        <label for="industry">Segmento</label>
                        <select id="industry" name="industry" required>
                            <option value="">Selecione</option>
                            <option value="retail">Varejo</option>
                            <option value="entertainment">Entretenimento</option>
                            <option value="technology">Tecnologia</option>
                            <option value="education">Educação</option>
                            <option value="finance">Finanças</option>
                            <option value="food">Alimentação</option>
                            <option value="health">Saúde</option>
                            <option value="services">Serviços</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="company_size">Porte</label>
                        <select id="company_size" name="company_size" required>
                            <option value="">Selecione</option>
                            <option value="solo">Autônomo / creator</option>
                            <option value="small">Pequena empresa</option>
                            <option value="medium">Média empresa</option>
                            <option value="large">Grande empresa</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="business_description">Descrição do negócio</label>
                        <textarea id="business_description" name="business_description" maxlength="280" placeholder="Conte em poucas linhas o que sua marca oferece."></textarea>
                    </div>
                </div>
                <div class="form-foot">
                    <div class="message" id="message"></div>
                    <button class="submit" type="submit">Concluir configuração</button>
                </div>
            </form>
        </article>
    </section>
<?php AdsDashboardShell::end(); ?>
<script>
    const IMGBB_KEY = '538999ea6353b2b12c58af1f65f3cd8c';
    const logoFile = document.getElementById('logo-file');
    const logoUrl = document.getElementById('logo_url');
    const logoPreview = document.getElementById('logo-preview');
    const message = document.getElementById('message');
    const form = document.getElementById('onboarding-form');

    logoFile?.addEventListener('change', async () => {
        const file = logoFile.files[0];
        if (!file) return;
        if (!['image/png','image/jpeg','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            message.textContent = 'Use PNG, JPG ou WEBP com até 5 MB.';
            return;
        }
        message.textContent = 'Enviando logo...';
        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_KEY}`, { method:'POST', body:fd });
            const data = await res.json();
            if (!data.success) throw new Error();
            logoUrl.value = data.data.display_url;
            logoPreview.src = data.data.display_url;
            message.textContent = '';
        } catch (_) {
            message.textContent = 'Não foi possível enviar a logo.';
        }
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.textContent = '';
        const payload = Object.fromEntries(new FormData(form).entries());
        const res = await fetch('/api/ads/onboarding', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            location.href = data.redirect;
            return;
        }
        message.textContent = data.message || 'Não foi possível concluir a configuração.';
    });
</script>
</body>
</html>
