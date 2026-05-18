<?php
declare(strict_types=1);

final class AdsSidebar
{
    public static function render(string $active = 'dashboard', bool $onboardingPending = false): void
    {
        $items = [
            'dashboard' => ['href' => '/ads/dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            'campaigns' => ['href' => '#', 'icon' => 'megaphone', 'label' => 'Campanhas'],
            'analytics' => ['href' => '#', 'icon' => 'chart-no-axes-combined', 'label' => 'Analytics'],
            'billing' => ['href' => '#', 'icon' => 'credit-card', 'label' => 'Faturamento'],
        ];
        ?>
        <aside class="ads-dashboard-sidebar" id="ads-dashboard-sidebar">
            <a class="ads-dashboard-brand" href="/ads/dashboard" aria-label="PipoCine Ads">
                <img src="/assets/img/ads/logo-icone.png" alt="">
            </a>
            <nav class="ads-sidebar-nav" aria-label="Navegação Ads">
                <?php foreach ($items as $key => $item): ?>
                    <a class="ads-sidebar-item <?= $active === $key ? 'active' : '' ?> <?= $onboardingPending && $key !== 'dashboard' ? 'locked' : '' ?>"
                       href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <i data-lucide="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="ads-sidebar-foot">
                <?php if ($onboardingPending): ?>
                    <span class="ads-sidebar-status pending">Configuração pendente</span>
                <?php else: ?>
                    <span class="ads-sidebar-status ready">Conta pronta</span>
                <?php endif; ?>
            </div>
        </aside>
        <div class="ads-sidebar-overlay" id="ads-sidebar-overlay"></div>
        <?php
    }
}
