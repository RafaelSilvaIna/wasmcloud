<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/v4/FamilyBoxModel.php';

use Models\V4\FamilyBoxModel;

$ownerName = htmlspecialchars($_SESSION['full_name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$familyBenefit = null;
try {
    $familyBenefit = (new FamilyBoxModel($pdo))->activeFamilyBenefitForMember((int) ($_SESSION['user_id'] ?? 0));
} catch (Throwable $e) {
    $familyBenefit = null;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipoCine - Planos</title>
    <link rel="stylesheet" href="/assets/css/plan.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="plan-body">
    <main class="plan-shell">
        <header class="plan-topbar plan-topbar-minimal">
            <span class="plan-pill"><i data-lucide="user-round"></i><?= $ownerName ?></span>
            <?php if ($familyBenefit): ?>
                <span class="plan-pill family plan-pill-icon" title="Membro da familia" aria-label="Membro da familia"><i data-lucide="badge-check"></i></span>
            <?php endif; ?>
        </header>

        <section class="plan-hero">
            <div>
                <h1 class="plan-title">Escolha como voce quer assistir.</h1>
                <p class="plan-subtitle">O Casual mantém o acesso essencial. O Gold libera o ecossistema completo com mobile, downloads offline, mais perfis, familia, segurança e uma experiência sem anúncios.</p>
            </div>
        </section>

        <section class="plan-grid" aria-label="Planos disponiveis">
            <article class="plan-card">
                <div class="plan-card-head">
                    <h2 class="plan-name">Plano Casual</h2>
                    <span class="plan-pill">Gratuito</span>
                </div>
                <div class="plan-price">
                    <small>R$</small>
                    <strong>0</strong>
                    <span>BRL / mes</span>
                </div>
                <p class="plan-desc">Para continuar assistindo com o básico do Pipocine.</p>
                <span class="plan-action disabled">Plano atual</span>
                <ul class="plan-benefits">
                    <li><i data-lucide="monitor"></i><span>1 dispositivo</span></li>
                    <li><i data-lucide="users"></i><span>2 perfis</span></li>
                    <li><i data-lucide="library"></i><span>Acesso completo ao catálogo</span></li>
                    <li><i data-lucide="download"></i><span>1 download por dia</span></li>
                    <li><i data-lucide="badge-alert"></i><span>Com anúncios</span></li>
                    <li><i data-lucide="screen-share"></i><span>Qualidade até 1080p</span></li>
                </ul>
            </article>

            <article class="plan-card gold">
                <div class="plan-card-head">
                    <h2 class="plan-name">Plano Gold</h2>
                    <span class="plan-pill"><i data-lucide="sparkles"></i>Mais completo</span>
                </div>
                <div class="plan-price">
                    <small>R$</small>
                    <strong>20,99</strong>
                    <span>BRL / mes</span>
                </div>
                <p class="plan-desc">Desbloqueie o Pipocine completo para voce e sua familia.</p>
                <a class="plan-action gold" href="/plan/checkout"><i data-lucide="credit-card"></i>Assinar Plano Gold</a>
                <ul class="plan-benefits" id="beneficios-gold">
                    <li><i data-lucide="smartphone"></i><span>Acesso ao aplicativo Mobile Pipocine</span></li>
                    <li><i data-lucide="download-cloud"></i><span>Download offline de filmes e séries</span></li>
                    <li><i data-lucide="monitor-smartphone"></i><span>4 dispositivos</span></li>
                    <li><i data-lucide="users-round"></i><span>8 perfis</span></li>
                    <li>
                        <i data-lucide="user-plus"></i>
                        <span>Até 3 membros na família com benefícios específicos <a class="plan-inline-link" href="/docs/beneficios-familiar">Saiba mais</a></span>
                    </li>
                    <li><i data-lucide="infinity"></i><span>Download ilimitado dentro do aplicativo</span></li>
                    <li><i data-lucide="screen-share"></i><span>Filmes e séries com qualidade 2K</span></li>
                    <li><i data-lucide="palette"></i><span>Personalização de perfil</span></li>
                    <li><i data-lucide="shield-check"></i><span>Mais camadas de segurança para perfil</span></li>
                    <li><i data-lucide="headphones"></i><span>Suporte prioritário</span></li>
                    <li><i data-lucide="badge-x"></i><span>Sem anúncios</span></li>
                </ul>
                <p class="plan-note">Ao assinar, voce confirma estar ciente das politicas de privacidade e das politicas de assinatura do Pipocine.</p>
            </article>
        </section>
    </main>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
