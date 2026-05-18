<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hooks/ads/AdsAuthHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../components/ads/AdsDashboardShell.php';

$account = $activeAdsAccount ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title>Dashboard — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .dashboard-card {
            border: 1px solid var(--ads-line);
            border-radius: 24px;
            padding: 22px;
            background: var(--ads-surface);
        }
        .metric small {
            display: block;
            color: var(--ads-muted);
            margin-bottom: 10px;
        }
        .metric strong {
            font-size: 2rem;
            letter-spacing: -.04em;
        }
        .welcome-card {
            grid-column: span 3;
            display: flex;
            justify-content: space-between;
            gap: 22px;
            align-items: center;
            background:
                linear-gradient(135deg, rgba(10,122,255,.17), rgba(124,92,255,.12)),
                var(--ads-surface);
        }
        .welcome-card h2 {
            margin: 0 0 8px;
            letter-spacing: -.03em;
        }
        .welcome-card p {
            margin: 0;
            color: var(--ads-muted);
            line-height: 1.65;
        }
        .primary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            border-radius: 16px;
            padding: 0 18px;
            color: #fff;
            text-decoration: none;
            font-weight: 780;
            background: linear-gradient(135deg, var(--ads-blue), var(--ads-violet));
            white-space: nowrap;
        }
        .panel-wide {
            grid-column: span 2;
        }
        .empty-state {
            min-height: 220px;
            display: grid;
            align-content: center;
            gap: 10px;
        }
        .empty-state h3 {
            margin: 0;
        }
        .empty-state p {
            margin: 0;
            color: var(--ads-muted);
            line-height: 1.65;
        }
        .checklist {
            display: grid;
            gap: 14px;
        }
        .checklist-row {
            display: flex;
            gap: 12px;
            align-items: center;
            color: var(--ads-muted);
        }
        .checklist-mark {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: #b3ffe4;
            background: rgba(52,211,153,.13);
        }
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .welcome-card, .panel-wide { grid-column: auto; }
            .welcome-card { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'Dashboard', 'dashboard', false); ?>
    <section class="dashboard-grid">
        <article class="dashboard-card welcome-card">
            <div>
                <h2>Seu espaço comercial está pronto.</h2>
                <p>Crie campanhas, acompanhe o desempenho e transforme atenção em resultado com métricas claras.</p>
            </div>
            <a class="primary-action" href="/ads/anuncios">Novo anúncio</a>
        </article>

        <article class="dashboard-card metric">
            <small>Campanhas ativas</small>
            <strong>0</strong>
        </article>
        <article class="dashboard-card metric">
            <small>Impressões</small>
            <strong>0</strong>
        </article>
        <article class="dashboard-card metric">
            <small>Cliques</small>
            <strong>0</strong>
        </article>

        <article class="dashboard-card panel-wide empty-state">
            <h3>Nenhuma campanha publicada ainda</h3>
            <p>Quando você publicar sua primeira campanha, o desempenho aparecerá aqui com leitura diária.</p>
        </article>

        <article class="dashboard-card checklist">
            <h3 style="margin:0;">Conta preparada</h3>
            <div class="checklist-row"><span class="checklist-mark">✓</span><span>Perfil comercial concluído</span></div>
            <div class="checklist-row"><span class="checklist-mark">✓</span><span>Logo e dados da marca salvos</span></div>
            <div class="checklist-row"><span class="checklist-mark">✓</span><span>Pronta para criar campanhas</span></div>
        </article>
    </section>
<?php AdsDashboardShell::end(); ?>
</body>
</html>
