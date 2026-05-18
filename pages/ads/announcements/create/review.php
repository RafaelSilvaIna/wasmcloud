<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../hooks/ads/AdsAuthHook.php';
require_once __DIR__ . '/../../../../hooks/ads/AdsDesktopOnlyHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../../components/ads/AdsDashboardShell.php';
require_once __DIR__ . '/../../../../components/ads/AdsDesktopOnlyNotice.php';
require_once __DIR__ . '/../../../../components/ads/AdsCampaignPreview.php';
require_once __DIR__ . '/../../../../models/ads/AdsCampaignModel.php';

$account = $activeAdsAccount ?? [];
$mobile = \Hooks\Ads\AdsDesktopOnlyHook::isMobileRequest();
$draftToken = strtolower((string) ($_GET['draft'] ?? ''));
$campaign = (new \Models\Ads\AdsCampaignModel($pdo))->findByDraftToken((int) ($account['id'] ?? 0), $draftToken);
if (!$mobile && (!$campaign || ($campaign['status'] ?? '') !== 'draft' || empty($campaign['creative_url']) || empty($campaign['description']))) {
    header('Location: /ads/anuncios/criar');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisão do anúncio — PipoCine Ads</title>
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
                <span class="ads-eyebrow">Etapa 4 de 4</span>
                <h2>Revisão</h2>
                <div class="ads-step-list">
                    <div class="ads-step-row"><span class="ads-step-dot">1</span><span>Formato</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">2</span><span>Mídia</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">3</span><span>Detalhes</span></div>
                    <div class="ads-step-row active"><span class="ads-step-dot">4</span><span>Revisão</span></div>
                </div>
            </aside>
            <article class="ads-stage-card">
                <span class="ads-eyebrow">Prévia final</span>
                <h2>Veja o anúncio como ele será entregue.</h2>
                <p>A mídia abaixo já está sendo servida pelo CDN interno do PipoCine Ads.</p>
                <div class="ads-review-grid">
                    <?php AdsCampaignPreview::render($campaign); ?>
                    <aside class="ads-summary">
                        <div class="ads-summary-row">
                            <span>Formato</span>
                            <strong><?= ($campaign['creative_type'] ?? '') === 'video' ? 'Vídeo' : 'Imagem / GIF' ?></strong>
                        </div>
                        <div class="ads-summary-row">
                            <span>Duração</span>
                            <strong><?= !empty($campaign['creative_duration_seconds']) ? (int) $campaign['creative_duration_seconds'] . 's' : '—' ?></strong>
                        </div>
                        <div class="ads-summary-row">
                            <span>Comportamento</span>
                            <strong><?= !empty($campaign['can_skip']) ? 'Pode pular' : 'Obrigatório' ?></strong>
                        </div>
                        <div class="ads-summary-row">
                            <span>Destino</span>
                            <strong><?= htmlspecialchars((string) ($campaign['redirect_url'] ?: 'Sem link'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </aside>
                </div>
                <div class="ads-actions">
                    <button class="ads-primary-button" id="open-confirmation" type="button">Avançar</button>
                </div>
            </article>
        </section>
        <div class="ads-modal" id="confirm-modal">
            <div class="ads-modal-card">
                <span class="ads-eyebrow">Revisão administrativa</span>
                <h3>Seu anúncio será analisado antes de ir ao ar.</h3>
                <p>A administração do PipoCine revisará o criativo. Caso sejam encontrados sinais suspeitos, fraude ou violação das regras da plataforma, a conta Ads poderá ser encerrada. Quando não houver crédito promocional disponível, o anúncio aguardará pagamento antes da revisão.</p>
                <div class="ads-message" id="message"></div>
                <div class="ads-actions">
                    <button class="ads-secondary-button" id="close-confirmation" type="button">Voltar</button>
                    <button class="ads-primary-button" id="submit-campaign" type="button">Enviar para revisão</button>
                </div>
            </div>
        </div>
        <script>
            const modal = document.getElementById('confirm-modal');
            const message = document.getElementById('message');
            document.getElementById('open-confirmation').addEventListener('click', () => modal.classList.add('open'));
            document.getElementById('close-confirmation').addEventListener('click', () => modal.classList.remove('open'));
            document.getElementById('submit-campaign').addEventListener('click', async (event) => {
                event.currentTarget.disabled = true;
                message.textContent = '';
                const response = await fetch('/api/ads/campaigns/<?= htmlspecialchars($draftToken, ENT_QUOTES, 'UTF-8') ?>/submit', {method: 'POST'});
                const data = await response.json();
                if (data.success) {
                    location.href = data.redirect;
                    return;
                }
                event.currentTarget.disabled = false;
                message.textContent = data.message || 'Não foi possível enviar o anúncio.';
            });
        </script>
    <?php endif; ?>
<?php AdsDashboardShell::end(); ?>
</body>
</html>
