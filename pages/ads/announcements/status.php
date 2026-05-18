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
    <title>Monitoramento — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <link rel="stylesheet" href="/assets/css/ads-campaigns.css">
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'Monitoramento', 'campaign_status', false); ?>
    <section class="ads-campaign-shell">
        <?php if (isset($_GET['submitted'])): ?>
            <section class="ads-panel" style="padding:18px; border-color:rgba(52,211,153,.28); background:rgba(52,211,153,.08);">
                Anúncio enviado com sucesso. A jornada dele agora será atualizada automaticamente aqui.
            </section>
        <?php endif; ?>
        <header class="ads-page-intro">
            <div>
                <span class="ads-eyebrow">Status dos anúncios</span>
                <h2>Do envio até a exibição, sem pontos cegos.</h2>
                <p>Esta tela sincroniza automaticamente o que acontece na revisão administrativa, mostra o estágio atual de cada anúncio e mantém um histórico público das decisões.</p>
            </div>
            <div class="ads-page-actions">
                <span class="ads-sync-pill">Sincroniza??o ativa</span>
                <a class="ads-primary-link" href="/ads/anuncios/criar">Novo an?ncio</a>
            </div>
        </header>

        <section class="ads-stat-grid">
            <article class="ads-stat-card"><span>Fila</span><strong id="ads-count-pending_review">0</strong></article>
            <article class="ads-stat-card"><span>Em análise</span><strong id="ads-count-in_review">0</strong></article>
            <article class="ads-stat-card"><span>Aprovados</span><strong id="ads-count-approved">0</strong></article>
            <article class="ads-stat-card"><span>Em exibição</span><strong id="ads-count-active">0</strong></article>
        </section>

        <section class="ads-status-layout">
            <div class="ads-status-list" id="ads-status-list">
                <div class="ads-empty"><div><h3>Carregando</h3><p>Sincronizando anúncios...</p></div></div>
            </div>
            <article class="ads-status-detail" id="ads-status-detail">
                <div class="ads-empty"><div><h3>Nenhum anúncio selecionado</h3><p>Escolha um criativo para ver a jornada completa.</p></div></div>
            </article>
        </section>
    </section>
<?php AdsDashboardShell::end(); ?>
<script src="/assets/js/ads-status-board.js"></script>
</body>
</html>
