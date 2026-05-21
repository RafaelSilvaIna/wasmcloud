<?php

declare(strict_types=1);

final class SuspiciousActivityModal
{
    public static function render(string $token, string $targetPath = '/home', int $statusCode = 429): void
    {
        $safeToken  = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $safeTarget = htmlspecialchars($targetPath, ENT_QUOTES, 'UTF-8');

        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividade suspeita detectada — Pipocine</title>
    <style>
        :root { color-scheme: dark; --bg:#080a0f; --panel:#11141b; --panel2:#171b24; --text:#f8fafc; --muted:#b6c0cf; --accent:#e50914; --border:rgba(255,255,255,.1); }
        * { box-sizing:border-box; }
        html { min-height:100%; background:var(--bg); }
        body { margin:0; min-height:100vh; min-height:100dvh; display:grid; place-items:center; padding:24px; background:radial-gradient(circle at top, rgba(229,9,20,.18), transparent 34%), var(--bg); color:var(--text); font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; overflow-x:hidden; }
        .card { width:min(460px,100%); background:linear-gradient(180deg,var(--panel2),var(--panel)); border:1px solid var(--border); border-radius:18px; padding:28px; box-shadow:0 24px 80px rgba(0,0,0,.5); }
        .icon { width:52px; height:52px; display:grid; place-items:center; border-radius:16px; background:rgba(229,9,20,.14); color:#ff5a63; margin-bottom:18px; }
        h1 { margin:0 0 10px; font-size:24px; line-height:1.15; letter-spacing:0; }
        p { margin:0; color:var(--muted); line-height:1.6; font-size:14px; }
        .actions { display:flex; gap:12px; margin-top:24px; align-items:stretch; }
        button, a { min-height:44px; border-radius:12px; font-weight:700; font-size:14px; line-height:1.2; text-decoration:none; white-space:normal; text-align:center; }
        button { flex:1; border:0; color:#fff; background:var(--accent); cursor:pointer; }
        a { display:inline-flex; align-items:center; justify-content:center; padding:0 16px; color:var(--text); border:1px solid var(--border); background:rgba(255,255,255,.03); }
        .note { margin-top:16px; font-size:12px; }
        @media (max-width: 520px) {
            body { place-items:start center; padding:18px 14px; }
            .card { width:100%; min-height:auto; margin-top:clamp(18px,8vh,64px); padding:22px; border-radius:16px; }
            .icon { width:48px; height:48px; border-radius:14px; margin-bottom:16px; }
            h1 { font-size:clamp(21px,7vw,28px); max-width:12ch; }
            p { font-size:14px; line-height:1.55; }
            .actions { flex-direction:column; gap:10px; margin-top:22px; }
            button, a { width:100%; min-height:48px; padding:0 14px; }
            .note { font-size:12px; line-height:1.5; }
        }
        @media (max-width: 340px) {
            body { padding:12px; }
            .card { padding:18px; }
            h1 { font-size:22px; }
        }
    </style>
</head>
<body>
    <main class="card" role="dialog" aria-modal="true" aria-labelledby="security-title">
        <div class="icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/>
                <path d="M12 8v5"/>
                <path d="M12 17h.01"/>
            </svg>
        </div>
        <h1 id="security-title">Atividade suspeita detectada</h1>
        <p>Identificamos um padrão incomum no seu IP e bloqueamos novas requisições para proteger o site. Se foi você mesmo, confirme abaixo para continuar navegando.</p>
        <form method="post" action="/security/continue">
            <input type="hidden" name="token" value="<?= $safeToken ?>">
            <input type="hidden" name="target" value="<?= $safeTarget ?>">
            <div class="actions">
                <button type="submit">Sim, quero prosseguir</button>
                <a href="/home">Voltar ao início</a>
            </div>
        </form>
        <p class="note">Ao confirmar, o bloqueio temporário deste IP será removido.</p>
    </main>
</body>
</html>
        <?php
    }
}
