<?php
declare(strict_types=1);

final class AdsHeader
{
    public static function render(string $rightHtml = ''): void
    {
        ?>
        <header class="ads-topbar">
            <a class="ads-brand" href="/ads" aria-label="PipoCine Ads">
                <img src="/assets/img/ads/logo-icone.png" alt="">
            </a>
            <div class="ads-topbar-actions"><?= $rightHtml ?></div>
        </header>
        <?php
    }
}
