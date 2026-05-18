<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../hooks/ads/AdsAuthHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../components/ads/AdsDashboardShell.php';
require_once __DIR__ . '/../../../components/ads/AdsCampaignPreview.php';
require_once __DIR__ . '/../../../helpers/ads/AdsStatusPresenter.php';
require_once __DIR__ . '/../../../helpers/ads/AdsDraftPresenter.php';
require_once __DIR__ . '/../../../models/ads/AdsCampaignModel.php';

$account = $activeAdsAccount ?? [];
$campaignModel = new \Models\Ads\AdsCampaignModel($pdo);
$campaigns = $campaignModel->listByAccount((int) ($account['id'] ?? 0));
$demoAvailable = empty($account['first_ad_demo_claimed_at']);
$draftCount = count(array_filter($campaigns, static fn (array $campaign): bool => ($campaign['status'] ?? '') === 'draft'));
$publishedCount = count(array_filter($campaigns, static fn (array $campaign): bool => ($campaign['status'] ?? '') === 'active'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>An?ncios ? PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <link rel="stylesheet" href="/assets/css/ads-campaigns.css">
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'An?ncios', 'campaigns', false); ?>
    <section class="ads-campaign-shell">
        <header class="ads-page-intro">
            <div>
                <span class="ads-eyebrow">Biblioteca de criativos</span>
                <h2>Seus an?ncios, em um lugar s?.</h2>
                <p>Crie, revise e acompanhe cada criativo antes de ele ganhar invent?rio dentro do PipoCine. Cada novo an?ncio custa R$ 10; sua primeira campanha pode usar a demonstra??o gratuita.</p>
                <div class="ads-inline-metrics">
                    <span><?= $draftCount ?> rascunho<?= $draftCount === 1 ? '' : 's' ?></span>
                    <span><?= $publishedCount ?> em exibi??o</span>
                    <span><?= $demoAvailable ? 'Demonstra??o dispon?vel' : 'Conta pronta para campanhas pagas' ?></span>
                </div>
            </div>
            <a class="ads-primary-link" href="/ads/anuncios/criar">Criar an?ncio</a>
        </header>

        <div class="ads-toolbar">
            <span class="ads-muted">
                <?= count($campaigns) ?> an?ncio<?= count($campaigns) === 1 ? '' : 's' ?> cadastrado<?= count($campaigns) === 1 ? '' : 's' ?>
            </span>
            <div class="ads-toolbar-actions">
                <div class="ads-view-toggle" aria-label="Alternar visualiza??o">
                    <button class="active" data-view="cards" type="button">Cards</button>
                    <button data-view="table" type="button">Tabela</button>
                </div>
                <a class="ads-secondary-link" href="/ads/anuncios/status">Abrir monitoramento</a>
            </div>
        </div>

        <?php if (!$campaigns): ?>
            <section class="ads-empty">
                <div>
                    <h3>Nenhum an?ncio criado ainda</h3>
                    <p>Seu primeiro criativo gratuito pode nascer agora.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="ads-card-grid" id="cards-view">
                <?php foreach ($campaigns as $campaign): ?>
                    <?php $isDraft = ($campaign['status'] ?? '') === 'draft'; ?>
                    <article class="ads-campaign-card <?= $isDraft ? 'draft' : '' ?>">
                        <?php AdsCampaignPreview::render($campaign); ?>
                        <div class="ads-campaign-meta">
                            <div class="ads-card-topline">
                                <span class="ads-badge <?= \Helpers\Ads\AdsStatusPresenter::tone((string) $campaign['status']) ?>">
                                    <?= htmlspecialchars(\Helpers\Ads\AdsStatusPresenter::label((string) $campaign['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($isDraft): ?>
                                    <span class="ads-stage-chip"><?= htmlspecialchars(\Helpers\Ads\AdsDraftPresenter::stageLabel($campaign), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <h3><?= htmlspecialchars((string) $campaign['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="ads-muted">
                                <?= ($campaign['creative_type'] ?? '') === 'video' ? 'V?deo' : 'Imagem / GIF' ?>
                                <?php if (!empty($campaign['creative_duration_seconds'])): ?>
                                    ? <?= (int) $campaign['creative_duration_seconds'] ?>s
                                <?php endif; ?>
                            </span>
                            <?php if ($isDraft): ?>
                                <div class="ads-draft-progress"><span style="width:<?= \Helpers\Ads\AdsDraftPresenter::progress($campaign) ?>%"></span></div>
                                <div class="ads-card-actions">
                                    <a class="ads-card-link primary" href="<?= htmlspecialchars(\Helpers\Ads\AdsDraftPresenter::continueUrl($campaign), ENT_QUOTES, 'UTF-8') ?>">Continuar</a>
                                    <a class="ads-card-link" href="<?= htmlspecialchars(\Helpers\Ads\AdsDraftPresenter::editUrl($campaign), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                    <button class="ads-card-link danger" type="button" data-delete-draft="<?= htmlspecialchars((string) $campaign['draft_token'], ENT_QUOTES, 'UTF-8') ?>">Excluir</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
            <section class="ads-table-wrap" id="table-view" hidden>
                <table class="ads-table">
                    <thead>
                    <tr>
                        <th>Pr?via</th>
                        <th>An?ncio</th>
                        <th>Formato</th>
                        <th>Status</th>
                        <th>A??es</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php $isDraft = ($campaign['status'] ?? '') === 'draft'; ?>
                        <tr>
                            <td class="ads-thumb"><?php AdsCampaignPreview::render($campaign); ?></td>
                            <td><?= htmlspecialchars((string) $campaign['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ($campaign['creative_type'] ?? '') === 'video' ? 'V?deo' : 'Imagem / GIF' ?></td>
                            <td>
                                <span class="ads-badge <?= \Helpers\Ads\AdsStatusPresenter::tone((string) $campaign['status']) ?>">
                                    <?= htmlspecialchars(\Helpers\Ads\AdsStatusPresenter::label((string) $campaign['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isDraft): ?>
                                    <div class="ads-table-actions">
                                        <a href="<?= htmlspecialchars(\Helpers\Ads\AdsDraftPresenter::editUrl($campaign), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                        <button type="button" data-delete-draft="<?= htmlspecialchars((string) $campaign['draft_token'], ENT_QUOTES, 'UTF-8') ?>">Excluir</button>
                                    </div>
                                <?php else: ?>
                                    <span class="ads-muted">?</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </section>
<?php AdsDashboardShell::end(); ?>

<div class="ads-modal" id="delete-draft-modal">
    <div class="ads-modal-card">
        <span class="ads-eyebrow">Excluir rascunho</span>
        <h3>Tem certeza?</h3>
        <p>O rascunho ser? removido e essa a??o n?o poder? ser desfeita.</p>
        <div class="ads-message" id="delete-draft-message"></div>
        <div class="ads-actions">
            <button class="ads-secondary-button" id="cancel-delete-draft" type="button">Cancelar</button>
            <button class="ads-primary-button" id="confirm-delete-draft" type="button">Excluir rascunho</button>
        </div>
    </div>
</div>

<?php if ($demoAvailable): ?>
    <div class="ads-modal" id="intro-modal">
        <div class="ads-modal-card">
            <span class="ads-eyebrow">Demonstra??o inicial</span>
            <h3>Cada an?ncio custa R$ 10.</h3>
            <p>Como esta ? sua primeira campanha no PipoCine Ads, preparamos uma demonstra??o gratuita: seu primeiro an?ncio poder? ser enviado sem cobran?a. A promo??o s? ? reivindicada quando voc? realmente envia o criativo para revis?o.</p>
            <div class="ads-actions">
                <button class="ads-primary-button" id="intro-ok" type="button">Ok, entendi</button>
            </div>
        </div>
    </div>
    <script>
        const introKey = 'ads-intro-ack:<?= (int) ($account['id'] ?? 0) ?>';
        const introModal = document.getElementById('intro-modal');
        if (!localStorage.getItem(introKey)) introModal.classList.add('open');
        document.getElementById('intro-ok')?.addEventListener('click', () => {
            localStorage.setItem(introKey, '1');
            introModal.classList.remove('open');
        });
    </script>
<?php endif; ?>
<script>
    const viewKey = 'ads-campaign-view';
    const cardsView = document.getElementById('cards-view');
    const tableView = document.getElementById('table-view');
    const viewButtons = document.querySelectorAll('[data-view]');
    const setView = (view) => {
        if (!cardsView || !tableView) return;
        cardsView.hidden = view !== 'cards';
        tableView.hidden = view !== 'table';
        viewButtons.forEach((button) => button.classList.toggle('active', button.dataset.view === view));
        localStorage.setItem(viewKey, view);
    };
    if (cardsView && tableView) {
        setView(localStorage.getItem(viewKey) || 'cards');
        viewButtons.forEach((button) => button.addEventListener('click', () => setView(button.dataset.view)));
    }

    const deleteModal = document.getElementById('delete-draft-modal');
    const deleteMessage = document.getElementById('delete-draft-message');
    const confirmDeleteButton = document.getElementById('confirm-delete-draft');
    let pendingDeleteToken = '';

    document.querySelectorAll('[data-delete-draft]').forEach((button) => {
        button.addEventListener('click', () => {
            pendingDeleteToken = button.dataset.deleteDraft || '';
            deleteMessage.textContent = '';
            deleteModal.classList.add('open');
        });
    });

    document.getElementById('cancel-delete-draft')?.addEventListener('click', () => {
        pendingDeleteToken = '';
        deleteModal.classList.remove('open');
    });

    confirmDeleteButton?.addEventListener('click', async () => {
        if (!pendingDeleteToken) return;
        confirmDeleteButton.disabled = true;
        deleteMessage.textContent = '';
        const response = await fetch(`/api/ads/campaigns/${pendingDeleteToken}/draft`, {method: 'DELETE'});
        const data = await response.json();
        if (data.success) {
            location.reload();
            return;
        }
        confirmDeleteButton.disabled = false;
        deleteMessage.textContent = data.message || 'N?o foi poss?vel excluir o rascunho.';
    });
</script>
</body>
</html>
