<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/v4/EvoPayClient.php';
require_once __DIR__ . '/../../models/v4/PlatformUserModel.php';
require_once __DIR__ . '/../../models/v4/SubscriptionModel.php';
require_once __DIR__ . '/../../models/v4/FamilyBoxModel.php';
require_once __DIR__ . '/../../services/v4/SubscriptionService.php';
require_once __DIR__ . '/../../services/v4/FamilyBoxService.php';

use Helpers\V4\EvoPayClient;
use Models\V4\PlatformUserModel;
use Models\V4\SubscriptionModel;
use Models\V4\FamilyBoxModel;
use Services\V4\SubscriptionService;
use Services\V4\FamilyBoxService;

$service = new SubscriptionService(
    new SubscriptionModel($pdo),
    new PlatformUserModel($pdo),
    new EvoPayClient(require __DIR__ . '/../../config/evopay.php')
);
$dashboard = $service->dashboard((int) $_SESSION['user_id']);
$familyService = new FamilyBoxService(new FamilyBoxModel($pdo));
$familyDashboard = $familyService->familyDashboard((int) $_SESSION['user_id']);
$realActive = $dashboard['active'] ?? null;
$familyBenefit = $dashboard['family_benefit'] ?? null;
$active = $realActive ?: ($familyBenefit ?: []);
$isCourtesy = (($active['source'] ?? 'paid') === 'admin_courtesy');
$isFamilyBenefit = !$realActive && !empty($familyBenefit);
$hasPremiumBadge = (bool) $realActive || (bool) $familyBenefit;
$paidPayments = array_values(array_filter($dashboard['payments'] ?? [], static function (array $payment): bool {
    return ($payment['status'] ?? '') === 'paid';
}));
$expiresAt = !empty($active['expires_at']) ? strtotime((string) $active['expires_at']) : null;
$daysLeft = $expiresAt ? max(0, (int) ceil(($expiresAt - time()) / 86400)) : null;
$statusLabel = $isFamilyBenefit ? 'Familia' : ($isCourtesy ? 'Cortesia' : 'Ativo');
$statusClass = $isFamilyBenefit ? 'family' : ($isCourtesy ? 'courtesy' : 'paid');

function brDate(?string $date): string {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function brMoney($value): string {
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Minha Assinatura</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <link rel="stylesheet" href="/assets/css/notification.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="me-shell">
        <a class="me-back" href="/select-profile" aria-label="Voltar aos perfis">
            <i data-lucide="arrow-left"></i>
            <span>Voltar</span>
        </a>

        <header class="me-header">
            <div>
                <p class="me-eyebrow">Minha assinatura</p>
                <h1 class="me-heading"><?= htmlspecialchars($active['plan_name'] ?? 'Plano Gold', ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if ($hasPremiumBadge): ?>
                    <span class="me-plan-icon-badge" title="<?= $isFamilyBenefit ? 'Membro da familia' : 'Plano premium ativo' ?>" aria-label="<?= $isFamilyBenefit ? 'Membro da familia' : 'Plano premium ativo' ?>">
                        <i data-lucide="<?= $isFamilyBenefit ? 'badge-check' : 'sparkles' ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($isCourtesy || $isFamilyBenefit): ?>
                <a class="plan-action gold compact" href="/plan">
                    <i data-lucide="arrow-up-right"></i>Assinar plano real
                </a>
            <?php endif; ?>
        </header>

        <?php if ($isCourtesy || $isFamilyBenefit): ?>
            <div class="me-notice">
                <i data-lucide="<?= $isFamilyBenefit ? 'users-round' : 'sparkles' ?>"></i>
                <?php if ($isFamilyBenefit): ?>
                    <p>
                        <strong>Beneficio familiar ativo.</strong>
                        Voce recebeu beneficios selecionados do Gold por vinculo familiar, mas ainda pode assinar seu proprio Plano Gold.
                    </p>
                <?php else: ?>
                    <p>
                        <strong>Cortesia ativa &mdash; termina em <?= $daysLeft !== null ? $daysLeft . ' dia' . ($daysLeft === 1 ? '' : 's') : 'breve' ?>.</strong>
                        Para manter o acesso, assine o plano real do PipoCine.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="me-grid <?= $isFamilyBenefit ? 'single' : '' ?>">

            <?php if (!$isFamilyBenefit): ?>
            <section class="plan-panel me-status-panel">
                <div class="me-section-label">
                    Status do plano
                    <span class="plan-origin <?= $statusClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <ul class="me-stat-list">
                    <li>
                        <span><?= $isFamilyBenefit ? 'Beneficio ate' : 'Validade' ?></span>
                        <strong><?= brDate($active['expires_at'] ?? null) ?></strong>
                    </li>
                    <li>
                        <span>Dispositivos</span>
                        <strong><?= (int) ($active['device_limit'] ?? 4) ?></strong>
                    </li>
                    <li>
                        <span>Perfis</span>
                        <strong><?= (int) ($active['profile_limit'] ?? 8) ?></strong>
                    </li>
                    <?php if (!$isCourtesy && !$isFamilyBenefit): ?>
                    <li>
                        <span>Valor mensal</span>
                        <strong><?= brMoney($active['amount_paid'] ?? 0) ?></strong>
                    </li>
                    <?php endif; ?>
                    <?php if ($isFamilyBenefit): ?>
                    <li>
                        <span>Titular familiar</span>
                        <strong><?= htmlspecialchars($familyBenefit['owner_name'] ?? 'Titular Pipocine', ENT_QUOTES, 'UTF-8') ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>

                <a class="plan-benefits-link" href="/plan">
                    <i data-lucide="external-link"></i>
                    <span>Ver beneficios do plano</span>
                </a>
            </section>
            <?php endif; ?>

            <section class="plan-panel me-history-panel">
                <div class="me-section-label">Historico de pagamentos</div>

                <?php if (empty($paidPayments)): ?>
                    <p class="me-empty">Nenhum pagamento confirmado ainda.</p>
                <?php else: ?>
                    <ul class="me-payment-list">
                        <?php foreach ($paidPayments as $payment): ?>
                            <li>
                                <span class="me-pay-date"><?= brDate($payment['paid_at'] ?? $payment['created_at'] ?? null) ?></span>
                                <span class="payment-status-paid">Pago</span>
                                <span class="me-pay-amount"><?= brMoney($payment['amount'] ?? 0) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

        </div>

        <?php if (!empty($familyDashboard['enabled'])): ?>
            <section class="plan-panel me-family-panel">
                <div class="me-family-head">
                    <div>
                        <span class="me-panel-eyebrow">Membros da familia</span>
                        <h2>Compartilhe beneficios especificos.</h2>
                        <p>Convide contas Pipocine por e-mail. A pessoa recebe uma solicitacao na Box e so entra na familia depois de aceitar.</p>
                    </div>
                    <button class="plan-action gold compact" type="button" id="open-family-invite">
                        <i data-lucide="user-plus"></i>Adicionar membro
                    </button>
                </div>

                <div class="me-family-meter">
                    <span><?= (int) ($familyDashboard['used'] ?? 0) ?> de <?= (int) ($familyDashboard['limit'] ?? 0) ?> membros usados</span>
                    <strong><?= max(0, (int) ($familyDashboard['limit'] ?? 0) - (int) ($familyDashboard['used'] ?? 0)) ?> disponiveis</strong>
                </div>

                <?php if (empty($familyDashboard['members'])): ?>
                    <p class="me-empty">Nenhum membro familiar adicionado ainda.</p>
                <?php else: ?>
                    <ul class="me-family-list" id="family-member-list">
                        <?php foreach ($familyDashboard['members'] as $member): ?>
                            <li data-member-id="<?= (int) $member['id'] ?>">
                                <span class="me-family-avatar">
                                    <?php if (!empty($member['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($member['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php else: ?>
                                        <i data-lucide="user-round"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="me-family-copy">
                                    <strong><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                </span>
                                <button class="me-family-remove" type="button" data-remove-member="<?= (int) $member['id'] ?>">
                                    Remover
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <div class="plan-alert" id="family-invite-modal" aria-hidden="true">
        <div class="plan-alert-card">
            <h2 class="plan-alert-title">Adicionar membro familiar</h2>
            <p class="plan-alert-text">Digite o e-mail vinculado a conta Pipocine da pessoa. Ela recebera uma solicitacao segura na Box.</p>
            <form class="family-invite-form" id="family-invite-form">
                <label class="plan-field" for="family-email">
                    <span>E-mail da conta</span>
                    <input id="family-email" name="email" type="email" required autocomplete="email" placeholder="pessoa@email.com">
                </label>
                <div class="plan-actions-row">
                    <button class="plan-secondary" type="button" id="close-family-invite">Cancelar</button>
                    <button class="plan-action gold" type="submit"><i data-lucide="send"></i>Enviar convite</button>
                </div>
            </form>
        </div>
    </div>

    <div class="plan-alert" id="family-remove-modal" aria-hidden="true">
        <div class="plan-alert-card plan-alert-card-compact">
            <h2 class="plan-alert-title">Remover membro?</h2>
            <p class="plan-alert-text" id="family-remove-text">Essa pessoa perdera os beneficios familiares imediatamente.</p>
            <div class="plan-actions-row">
                <button class="plan-secondary" type="button" id="cancel-family-remove">Cancelar</button>
                <button class="plan-danger" type="button" id="confirm-family-remove">Remover</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/notification.js"></script>
    <script src="/assets/js/plan-family.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
