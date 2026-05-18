<?php
declare(strict_types=1);

final class AdsCampaignPreview
{
    public static function render(array $campaign, string $className = ''): void
    {
        $cdnToken = (string) ($campaign['cdn_token'] ?? '');
        $src = $cdnToken !== '' ? '/cdn/ads=' . rawurlencode($cdnToken) : '';
        $type = (string) ($campaign['creative_type'] ?? 'image');
        $class = trim('ads-creative-preview ' . $className);
        ?>
        <div class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($src === ''): ?>
                <div class="ads-preview-placeholder">Mídia ainda não enviada</div>
            <?php elseif ($type === 'video'): ?>
                <video src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" muted playsinline controls preload="metadata"></video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <?php endif; ?>
        </div>
        <?php
    }
}
