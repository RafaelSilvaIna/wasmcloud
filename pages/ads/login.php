<?php
declare(strict_types=1);
require_once __DIR__ . '/../../components/ads/AdsHeader.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title>Login — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <style>
        :root {
            --bg:#05070d;
            --line:rgba(255,255,255,.10);
            --line-focus:rgba(138,181,255,.58);
            --text:#f8fbff;
            --muted:#9aa6bd;
            --blue:#0a7aff;
            --violet:#7c5cff;
            --error:#ff8c8c;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            color:var(--text);
            font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:
                radial-gradient(circle at 20% 12%, rgba(10,122,255,.18), transparent 28%),
                radial-gradient(circle at 80% 10%, rgba(124,92,255,.16), transparent 24%),
                var(--bg);
        }
        body::before {
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.22;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size:52px 52px;
            mask-image:linear-gradient(to bottom, black, transparent 80%);
        }
        .shell {
            position:relative;
            z-index:1;
            width:min(980px, calc(100% - 32px));
            margin:0 auto;
        }
        .ads-topbar {
            padding:24px 0;
        }
        .ads-brand img {
            width:54px;
            height:54px;
            display:block;
            object-fit:contain;
        }
        .layout {
            min-height:calc(100vh - 102px);
            display:grid;
            place-items:center;
            padding:20px 0 42px;
        }
        .card {
            width:min(460px, 100%);
            border:1px solid var(--line);
            border-radius:30px;
            padding:30px;
            background:linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.035));
            box-shadow:0 28px 80px rgba(0,0,0,.34);
            backdrop-filter:blur(18px);
        }
        .eyebrow {
            color:#a8cbff;
            text-transform:uppercase;
            font-size:.74rem;
            letter-spacing:.15em;
            font-weight:800;
        }
        h1 {
            margin:14px 0 8px;
            font-size:2rem;
            letter-spacing:-.04em;
        }
        .subhead {
            margin:0 0 24px;
            color:var(--muted);
            line-height:1.65;
        }
        .field {
            display:grid;
            gap:8px;
            margin-bottom:16px;
        }
        label {
            color:var(--muted);
            font-size:.78rem;
            text-transform:uppercase;
            letter-spacing:.08em;
            font-weight:750;
        }
        input {
            min-height:50px;
            border:1px solid var(--line);
            border-radius:16px;
            padding:0 16px;
            color:var(--text);
            background:rgba(255,255,255,.035);
            font:inherit;
            outline:none;
        }
        input:focus {
            border-color:var(--line-focus);
            background:rgba(255,255,255,.055);
            box-shadow:0 0 0 4px rgba(10,122,255,.12);
        }
        .msg {
            min-height:20px;
            margin-top:6px;
            color:var(--error);
            font-size:.9rem;
        }
        .btn {
            width:100%;
            min-height:52px;
            margin-top:10px;
            border:0;
            border-radius:16px;
            color:#fff;
            background:linear-gradient(135deg,var(--blue),var(--violet));
            font-size:.98rem;
            font-weight:780;
            cursor:pointer;
            box-shadow:0 18px 42px rgba(10,122,255,.22);
        }
        .footer-link {
            display:block;
            margin-top:18px;
            color:var(--muted);
            text-align:center;
            text-decoration:none;
        }
        .footer-link strong { color:#fff; }
        @media (max-width:560px) {
            .shell { width:min(100% - 24px, 980px); }
            .card { padding:22px; border-radius:24px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php AdsHeader::render(); ?>
        <main class="layout">
            <section class="card" aria-label="Formulário de login">
                <span class="eyebrow">Conta comercial</span>
                <h1>Entrar no Ads</h1>
                <p class="subhead">Acesse seu painel, acompanhe campanhas e retome o trabalho de onde parou.</p>
                <form id="f" novalidate>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" required placeholder="contato@suaempresa.com">
                    </div>
                    <div class="field">
                        <label for="password">Senha</label>
                        <input id="password" name="password" type="password" required placeholder="Sua senha">
                    </div>
                    <div class="msg" id="m"></div>
                    <button class="btn">Entrar</button>
                </form>
                <a class="footer-link" href="/ads/register">Ainda não tem conta? <strong>Criar conta comercial</strong></a>
            </section>
        </main>
    </div>
    <script>
        f.onsubmit = async (e) => {
            e.preventDefault();
            m.textContent = '';
            const d = Object.fromEntries(new FormData(f).entries());
            const r = await fetch('/api/ads/login', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify(d)
            });
            const j = await r.json();
            if (j.success) location.href = j.redirect;
            else m.textContent = j.message || 'Erro ao entrar.';
        };
    </script>
</body>
</html>
