<?php
declare(strict_types=1);

final class AdsDashboardHeader
{
    public static function render(array $account, string $pageTitle): void
    {
        $brand = htmlspecialchars($account['brand_name'] ?? 'Conta Ads', ENT_QUOTES, 'UTF-8');
        $logo = htmlspecialchars($account['logo_url'] ?? '/assets/img/ads/logo-icone.png', ENT_QUOTES, 'UTF-8');
        ?>
        <header class="ads-dashboard-header">
            <button class="ads-mobile-toggle" id="ads-mobile-toggle" type="button" aria-label="Abrir menu" aria-controls="ads-dashboard-sidebar" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div>
                <span class="ads-page-kicker">PipoCine Ads</span>
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <div class="ads-account-chip">
                <img src="<?= $logo ?>" alt="">
                <div>
                    <strong><?= $brand ?></strong>
                    <span>Conta comercial</span>
                </div>
            </div>
        </header>
        <?php
    }
}
