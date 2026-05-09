<?php
function renderNotContent(array $options = []): void
{
    $image = $options['image'] ?? '/assets/img/not-content.webp';
    $eyebrow = $options['eyebrow'] ?? 'Algo nao pode continuar';
    $title = $options['title'] ?? 'Conteudo indisponivel';
    $message = $options['message'] ?? 'Nao foi possivel concluir este processo.';
    $detail = $options['detail'] ?? '';
    $actionLabel = $options['actionLabel'] ?? null;
    $actionHref = $options['actionHref'] ?? null;
    $className = trim('not-content ' . ($options['className'] ?? ''));
    ?>
    <section class="<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="not-content-title">
        <div class="not-content-media" aria-hidden="true">
            <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="">
        </div>

        <div class="not-content-copy">
            <span class="not-content-eyebrow"><?= htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8') ?></span>
            <h1 id="not-content-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

            <?php if ($detail !== ''): ?>
                <p class="not-content-detail"><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($actionLabel && $actionHref): ?>
                <a class="not-content-action" href="<?= htmlspecialchars($actionHref, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
