<?php
declare(strict_types=1);

final class AdsDesktopOnlyNotice
{
    public static function render(): void
    {
        ?>
        <section class="ads-desktop-notice">
            <span class="ads-eyebrow">Criação de anúncios</span>
            <h2>Use um computador para montar seu anúncio.</h2>
            <p>O editor trabalha com upload, revisão visual e etapas laterais pensadas para tela ampla. Em dispositivos móveis, você ainda pode acompanhar o status dos anúncios pelo painel.</p>
            <a href="/ads/anuncios/status">Ver monitoramento</a>
        </section>
        <?php
    }
}
