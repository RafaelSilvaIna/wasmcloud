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
$familyMembers = $familyDashboard['members'] ?? [];
$familyPendingInvites = $familyDashboard['pending_invites'] ?? [];
$familyLimit = (int) ($familyDashboard['limit'] ?? 0);
$familyUsed = (int) ($familyDashboard['used'] ?? 0);
$familyAvailable = max(0, (int) ($familyDashboard['available'] ?? ($familyLimit - $familyUsed)));
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
$renewalWindowDays = 14;
$canRenew = (bool) $realActive
    && !$isCourtesy
    && !$isFamilyBenefit
    && (($active['plan_code'] ?? '') === 'gold')
    && $daysLeft !== null
    && $daysLeft <= $renewalWindowDays;
$renewalAmount = (float) ($active['plan_price'] ?? $active['amount_paid'] ?? 20.99);
$renewalDurationDays = max(1, (int) ($active['plan_duration_days'] ?? 30));
$renewalExpiresLabel = brDate($active['expires_at'] ?? null);
$renewalNewExpiresLabel = $expiresAt ? date('d/m/Y', strtotime('+' . $renewalDurationDays . ' days', max(time(), $expiresAt))) : '-';
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

        <?php if ($canRenew): ?>
            <section class="me-renewal-panel" aria-labelledby="renewal-title">
                <div class="me-renewal-copy">
                    <span class="me-panel-eyebrow">Renovacao disponivel</span>
                    <h2 id="renewal-title">Seu plano termina em <?= (int) $daysLeft ?> dia<?= $daysLeft === 1 ? '' : 's' ?>.</h2>
                    <p>Renove agora por Pix e mantenha seus beneficios sem interrupcao. A nova validade sera somada ao fim do ciclo atual.</p>
                </div>
                <div class="me-renewal-summary" aria-label="Resumo da renovacao">
                    <span><?= brMoney($renewalAmount) ?></span>
                    <strong><?= htmlspecialchars($renewalNewExpiresLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <button class="plan-action gold compact" type="button" id="open-renewal-modal">
                    <i data-lucide="refresh-cw"></i>Renovar com Pix
                </button>
            </section>
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
                        <span class="me-panel-eyebrow">Familia Gold</span>
                        <h2>Compartilhe beneficios especificos.</h2>
                        <p>Convide contas Pipocine por e-mail. A pessoa recebe uma solicitacao na Box e so entra na familia depois de aceitar.</p>
                    </div>
                    <button class="plan-action gold compact" type="button" id="open-family-invite">
                        <i data-lucide="user-plus"></i>Adicionar membro
                    </button>
                </div>

                <div class="me-family-tabs" role="tablist" aria-label="Secoes da familia">
                    <button class="me-family-tab active" type="button" role="tab" aria-selected="true" data-family-tab="overview">Resumo</button>
                    <button class="me-family-tab" type="button" role="tab" aria-selected="false" data-family-tab="members">Membros <span id="family-members-count"><?= $familyUsed ?></span></button>
                    <button class="me-family-tab" type="button" role="tab" aria-selected="false" data-family-tab="invites">Convites <span id="family-invites-count"><?= count($familyPendingInvites) ?></span></button>
                </div>

                <div class="me-family-tab-panel active" data-family-panel="overview" role="tabpanel">
                    <div class="me-family-overview">
                        <div class="me-family-stat">
                            <span>Membros ativos</span>
                            <strong id="family-used-count"><?= $familyUsed ?></strong>
                        </div>
                        <div class="me-family-stat">
                            <span>Convites pendentes</span>
                            <strong id="family-pending-count"><?= count($familyPendingInvites) ?></strong>
                        </div>
                        <div class="me-family-stat">
                            <span>Vagas disponiveis</span>
                            <strong id="family-available-count"><?= $familyAvailable ?></strong>
                        </div>
                    </div>

                    <div class="me-family-meter">
                        <span><span id="family-meter-used"><?= $familyUsed ?></span> de <?= $familyLimit ?> membros usados</span>
                        <strong><span id="family-meter-available"><?= $familyAvailable ?></span> disponiveis</strong>
                    </div>
                </div>

                <div class="me-family-tab-panel" data-family-panel="members" role="tabpanel" hidden>
                    <?php if (empty($familyMembers)): ?>
                        <p class="me-empty" id="family-members-empty">Nenhum membro familiar adicionado ainda.</p>
                    <?php else: ?>
                        <p class="me-empty" id="family-members-empty" hidden>Nenhum membro familiar adicionado ainda.</p>
                    <?php endif; ?>
                    <ul class="me-family-list" id="family-member-list" <?= empty($familyMembers) ? 'hidden' : '' ?>>
                        <?php foreach ($familyMembers as $member): ?>
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
                </div>

                <div class="me-family-tab-panel" data-family-panel="invites" role="tabpanel" hidden>
                    <?php if (empty($familyPendingInvites)): ?>
                        <p class="me-empty" id="family-invites-empty">Nenhum convite pendente no momento.</p>
                    <?php else: ?>
                        <p class="me-empty" id="family-invites-empty" hidden>Nenhum convite pendente no momento.</p>
                    <?php endif; ?>
                    <ul class="me-family-list me-family-invite-list" id="family-invite-list" <?= empty($familyPendingInvites) ? 'hidden' : '' ?>>
                        <?php foreach ($familyPendingInvites as $invite): ?>
                            <li data-invite-id="<?= (int) $invite['id'] ?>" data-invite-email="<?= htmlspecialchars(strtolower((string) $invite['email']), ENT_QUOTES, 'UTF-8') ?>">
                                <span class="me-family-avatar">
                                    <?php if (!empty($invite['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($invite['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php else: ?>
                                        <i data-lucide="mail"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="me-family-copy">
                                    <strong><?= htmlspecialchars($invite['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($invite['email'], ENT_QUOTES, 'UTF-8') ?> · enviado em <?= brDate($invite['created_at'] ?? null) ?></small>
                                </span>
                                <span class="me-family-pending">Aguardando resposta</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($canRenew): ?>
    <div class="plan-alert renewal-modal" id="renewal-modal" aria-hidden="true">
        <div class="plan-alert-card renewal-modal-card" role="dialog" aria-modal="true" aria-labelledby="renewal-modal-title">
            <button class="modal-close-button" type="button" id="close-renewal-modal" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
            <span class="modal-icon"><i data-lucide="qr-code"></i></span>
            <h2 class="plan-alert-title" id="renewal-modal-title">Renovar Plano Gold</h2>
            <p class="plan-alert-text">Vamos gerar um Pix de renovacao para o proximo ciclo. O QR Code expira em 1 hora e a confirmacao acontece automaticamente.</p>

            <div class="renewal-details">
                <div>
                    <span>Vence em</span>
                    <strong><?= htmlspecialchars($renewalExpiresLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span>Nova validade</span>
                    <strong><?= htmlspecialchars($renewalNewExpiresLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span>Valor</span>
                    <strong><?= brMoney($renewalAmount) ?></strong>
                </div>
            </div>

            <label class="plan-check renewal-check">
                <input type="checkbox" id="renewal-terms">
                <span>Confirmo a renovacao manual por Pix para manter meu Plano Gold ativo.</span>
            </label>

            <div class="plan-actions-row">
                <button class="plan-secondary" type="button" id="cancel-renewal-modal">Agora nao</button>
                <button class="plan-action gold" type="button" id="confirm-renewal-button">
                    <i data-lucide="qr-code"></i>Gerar Pix
                </button>
            </div>
            <p class="renewal-modal-error" id="renewal-modal-error" aria-live="polite"></p>
        </div>
    </div>
    <?php endif; ?>

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
    <script src="/assets/js/plan-renewal.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
