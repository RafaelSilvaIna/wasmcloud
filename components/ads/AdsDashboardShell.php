<?php
declare(strict_types=1);

require_once __DIR__ . '/AdsSidebar.php';
require_once __DIR__ . '/AdsDashboardHeader.php';

final class AdsDashboardShell
{
    public static function headAssets(): void
    {
        ?>
        <link rel="stylesheet" href="/assets/css/ads-dashboard.css">
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
        <?php
    }

    public static function start(array $account, string $pageTitle, string $active = 'dashboard', bool $onboardingPending = false): void
    {
        ?>
        <div class="ads-app-shell">
            <?php AdsSidebar::render($active, $onboardingPending); ?>
            <div class="ads-app-main">
                <?php AdsDashboardHeader::render($account, $pageTitle); ?>
                <main class="ads-app-content">
        <?php
    }

    public static function end(): void
    {
        ?>
                </main>
            </div>
        </div>
        <script src="/assets/js/ads-dashboard.js"></script>
        <?php
    }
}
