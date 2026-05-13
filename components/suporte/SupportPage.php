<?php
declare(strict_types=1);

$isAuthenticated = isset($_SESSION['user_id']);
$userDisplayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? null;
$userAvatar      = $_SESSION['profile_pic_url'] ?? null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Suporte — Pipocine</title>
    <meta name="description" content="Central de suporte oficial do Pipocine. Atendimento das 12h as 21h30.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/support.css">
    <style>
        body { margin: 0; background: #07090d; }
    </style>
</head>
<body
    class="sp-page"
    <?php if ($isAuthenticated): ?>
    data-user-id="<?= (int) $_SESSION['user_id'] ?>"
    <?php endif; ?>
>

<!-- ====== NAVBAR ====== -->
<nav class="sp-nav" aria-label="Navegacao principal">
    <a href="/" class="sp-nav-brand" aria-label="Voltar ao Pipocine">
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="sp-nav-logo">
        <span>Pipocine</span>
        <span style="color:#94a3b8;font-weight:500;font-size:.88rem;margin-left:4px">/ Suporte</span>
    </a>

    <div class="sp-nav-badge" aria-label="Horario de atendimento">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        12:00 &ndash; 21:30
    </div>

    <div class="sp-nav-user" aria-label="Usuario atual">
        <div class="sp-user-icon" aria-hidden="true">
            <?php if ($userAvatar): ?>
            <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES) ?>" alt="" width="30" height="30"
                 style="border-radius:50%;object-fit:cover">
            <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <?php endif; ?>
        </div>
        <?php if ($userDisplayName): ?>
        <span style="font-size:.83rem"><?= htmlspecialchars($userDisplayName, ENT_QUOTES) ?></span>
        <?php else: ?>
        <span style="font-size:.83rem;color:#64748b">Visitante</span>
        <?php endif; ?>
    </div>
</nav>

<!-- ====== PAGE HEADER ====== -->
<header style="max-width:780px;margin:32px auto 0;padding:0 24px">
    <h1 style="margin:0;font-size:1.6rem;font-weight:800;color:#fff">
        Central de suporte
    </h1>
    <p style="margin:6px 0 0;color:#94a3b8;font-size:.9rem">
        Estamos aqui para ajudar. Descreva seu problema e responderemos em breve.
    </p>
</header>

<!-- ====== NOTICES ====== -->
<?php require_once __DIR__ . '/SupportNotices.php'; ?>

<!-- ====== CHAT WIDGET ====== -->
<?php require_once __DIR__ . '/SupportChatWidget.php'; ?>

<!-- ====== FOOTER ====== -->
<footer style="max-width:780px;margin:40px auto 0;padding:0 24px 32px;display:flex;align-items:center;gap:16px;border-top:1px solid rgba(148,163,184,.1);padding-top:20px">
    <span style="font-size:.78rem;color:#64748b">
        &copy; <?= date('Y') ?> Pipocine &mdash; O suporte oficial e apenas em <strong style="color:#94a3b8">pipocine.site/suporte</strong>
    </span>
    <a href="/" style="margin-left:auto;font-size:.78rem;color:#94a3b8;text-decoration:none">Voltar ao site</a>
</footer>

<script src="/assets/js/support-client.js" defer></script>
</body>
</html>
