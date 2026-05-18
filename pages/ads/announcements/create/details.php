<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../hooks/ads/AdsAuthHook.php';
require_once __DIR__ . '/../../../../hooks/ads/AdsDesktopOnlyHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../../components/ads/AdsDashboardShell.php';
require_once __DIR__ . '/../../../../components/ads/AdsDesktopOnlyNotice.php';
require_once __DIR__ . '/../../../../models/ads/AdsCampaignModel.php';

$account = $activeAdsAccount ?? [];
$mobile = \Hooks\Ads\AdsDesktopOnlyHook::isMobileRequest();
$draftToken = strtolower((string) ($_GET['draft'] ?? ''));
$campaign = (new \Models\Ads\AdsCampaignModel($pdo))->findByDraftToken((int) ($account['id'] ?? 0), $draftToken);
if (!$mobile && (!$campaign || ($campaign['status'] ?? '') !== 'draft' || empty($campaign['creative_url']))) {
    header('Location: /ads/anuncios/criar');
    exit;
}
$mandatoryBlocked = ($campaign['creative_type'] ?? '') === 'video'
    && (int) ($campaign['creative_duration_seconds'] ?? 0) > 20;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do anúncio — PipoCine Ads</title>
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
                <span class="ads-eyebrow">Etapa 3 de 4</span>
                <h2>Detalhes</h2>
                <div class="ads-step-list">
                    <div class="ads-step-row"><span class="ads-step-dot">1</span><span>Formato</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">2</span><span>Mídia</span></div>
                    <div class="ads-step-row active"><span class="ads-step-dot">3</span><span>Detalhes</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">4</span><span>Revisão</span></div>
                </div>
            </aside>
            <article class="ads-stage-card">
                <span class="ads-eyebrow">Mensagem e destino</span>
                <h2>Defina como o anúncio se comporta.</h2>
                <p>Informe o texto de apoio, o destino do botão “Saiba mais” e se o criativo poderá ser pulado.</p>
                <form class="ads-form-grid" id="details-form">
                    <div class="ads-field">
                        <label for="description">Descrição do anúncio</label>
                        <textarea id="description" name="description" maxlength="500" required placeholder="Explique o que o usuário verá ao clicar."><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="ads-field">
                        <label for="redirect_url">Link de redirecionamento</label>
                        <input id="redirect_url" name="redirect_url" type="url" placeholder="https://suaempresa.com/oferta" value="<?= htmlspecialchars((string) ($campaign['redirect_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="ads-field">
                        <label>Comportamento</label>
                        <div class="ads-choice-row">
                            <label class="ads-choice">
                                <input type="radio" name="can_skip" value="1" <?= !isset($campaign['can_skip']) || !empty($campaign['can_skip']) ? 'checked' : '' ?>>
                                <span>O usuário pode pular o anúncio.</span>
                            </label>
                            <label class="ads-choice <?= $mandatoryBlocked ? 'disabled' : '' ?>">
                                <input type="radio" name="can_skip" value="0" <?= empty($campaign['can_skip']) ? 'checked' : '' ?> <?= $mandatoryBlocked ? 'disabled' : '' ?>>
                                <span>Obrigatório<?= $mandatoryBlocked ? ' — indisponível acima de 20 segundos.' : '.' ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ads-message" id="message"></div>
                    <div class="ads-actions">
                        <button class="ads-primary-button" type="submit">Avançar</button>
                    </div>
                </form>
            </article>
        </section>
        <script>
            const form = document.getElementById('details-form');
            const message = document.getElementById('message');
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                message.textContent = '';
                const payload = Object.fromEntries(new FormData(form).entries());
                payload.can_skip = payload.can_skip === '1';
                const response = await fetch('/api/ads/campaigns/<?= htmlspecialchars($draftToken, ENT_QUOTES, 'UTF-8') ?>/details', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    location.href = data.redirect;
                    return;
                }
                message.textContent = data.message || 'Não foi possível salvar os detalhes.';
            });
        </script>
    <?php endif; ?>
<?php AdsDashboardShell::end(); ?>
</body>
</html>
