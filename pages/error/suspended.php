<?php
declare(strict_types=1);

$restriction = $_SESSION['account_restriction'] ?? [];
$reason = $restriction['reason'] ?? 'Sua conta esta suspensa temporariamente.';
$until = $restriction['until'] ?? null;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Conta suspensa - PipoCine</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <style>
        :root { --bg: #07090d; --surface: #0f131a; --line: rgba(148,163,184,.18); --text: #e2e8f0; --muted: #94a3b8; --red: #e50914; --amber: #f59e0b; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 22px; background: radial-gradient(circle at top, rgba(229,9,20,.18), transparent 34%), var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        .error-card { width: min(620px, 100%); border: 1px solid var(--line); border-radius: 8px; background: rgba(15,19,26,.94); padding: 30px; box-shadow: 0 26px 90px rgba(0,0,0,.48); }
        .error-icon { width: 58px; height: 58px; border-radius: 8px; display: grid; place-items: center; background: rgba(245,158,11,.14); color: var(--amber); margin-bottom: 18px; }
        .error-icon svg { width: 30px; height: 30px; }
        h1 { margin: 0 0 10px; color: #fff; font-size: clamp(1.7rem, 5vw, 2.45rem); letter-spacing: 0; }
        p { margin: 0; color: var(--muted); line-height: 1.6; }
        .error-box { margin-top: 18px; border: 1px solid var(--line); border-radius: 8px; padding: 14px; background: #0a0c10; }
        .error-box span { display: block; color: var(--muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; }
        .error-box strong { color: #fff; overflow-wrap: anywhere; }
        .error-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .error-btn { min-height: 42px; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; padding: 0 14px; text-decoration: none; color: #fff; background: var(--red); font-weight: 800; }
        .error-btn.secondary { background: transparent; border: 1px solid var(--line); }
    </style>
</head>
<body>
<main class="error-card">
    <div class="error-icon"><i data-lucide="timer-off"></i></div>
    <h1>Conta suspensa temporariamente</h1>
    <p>O acesso a PipoCine esta pausado ate o fim da suspensao aplicada pela administracao.</p>
    <div class="error-box">
        <span>Motivo</span>
        <strong><?= htmlspecialchars((string) $reason, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="error-box">
        <span>Duracao</span>
        <strong><?= $until ? 'Ate ' . htmlspecialchars(date('d/m/Y H:i', strtotime((string) $until)), ENT_QUOTES, 'UTF-8') : 'Prazo nao informado' ?></strong>
    </div>
    <div class="error-actions">
        <a class="error-btn" href="/login"><i data-lucide="log-in"></i>Voltar ao login</a>
        <a class="error-btn secondary" href="mailto:suporte@pipocine.site"><i data-lucide="mail"></i>Falar com suporte</a>
    </div>
</main>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof lucide!=='undefined')lucide.createIcons();});</script>
</body>
</html>
