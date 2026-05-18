<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../hooks/ads/AdsAuthHook.php';
require_once __DIR__ . '/../../../../hooks/ads/AdsDesktopOnlyHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../../components/ads/AdsDashboardShell.php';
require_once __DIR__ . '/../../../../components/ads/AdsDesktopOnlyNotice.php';

$account = $activeAdsAccount ?? [];
$mobile = \Hooks\Ads\AdsDesktopOnlyHook::isMobileRequest();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar anúncio — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <link rel="stylesheet" href="/assets/css/ads-campaigns.css">
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'Criar anúncio', 'campaign_create', false); ?>
    <?php if ($mobile): ?>
        <?php AdsDesktopOnlyNotice::render(); ?>
    <?php else: ?>
        <section class="ads-stage-layout">
            <aside class="ads-stage-rail">
                <span class="ads-eyebrow">Etapa 1 de 4</span>
                <h2>Formato</h2>
                <div class="ads-step-list">
                    <div class="ads-step-row active"><span class="ads-step-dot">1</span><span>Formato</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">2</span><span>Mídia</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">3</span><span>Detalhes</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">4</span><span>Revisão</span></div>
                </div>
            </aside>
            <article class="ads-stage-card">
                <span class="ads-eyebrow">Novo anúncio</span>
                <h2>Escolha o formato do criativo.</h2>
                <p>Imagens e GIFs seguem para processamento visual. Vídeos usam o pipeline de upload e transmissão do PipoCine Ads.</p>
                <div class="ads-type-grid">
                    <button class="ads-type-card" data-type="image" type="button">
                        <strong>Foto ou GIF</strong>
                        <span class="ads-muted">Ideal para peças estáticas, banners e criativos leves.</span>
                    </button>
                    <button class="ads-type-card" data-type="video" type="button">
                        <strong>Vídeo</strong>
                        <span class="ads-muted">Use quando a mensagem precisa de movimento e narrativa curta.</span>
                    </button>
                </div>
                <div class="ads-message" id="message"></div>
            </article>
        </section>
        <script>
            const message = document.getElementById('message');
            document.querySelectorAll('[data-type]').forEach((button) => {
                button.addEventListener('click', async () => {
                    message.textContent = '';
                    button.disabled = true;
                    const response = await fetch('/api/ads/campaigns/draft', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({creative_type: button.dataset.type})
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.href = data.redirect;
                        return;
                    }
                    button.disabled = false;
                    message.textContent = data.message || 'Não foi possível iniciar o anúncio.';
                });
            });
        </script>
    <?php endif; ?>
<?php AdsDashboardShell::end(); ?>
</body>
</html>
