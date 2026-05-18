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
    <title>Cadastro — PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <style>
        :root {
            --bg:#05070d;
            --surface:rgba(255,255,255,.045);
            --surface-strong:rgba(255,255,255,.065);
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
                radial-gradient(circle at 18% 12%, rgba(10,122,255,.18), transparent 28%),
                radial-gradient(circle at 82% 8%, rgba(124,92,255,.16), transparent 24%),
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
            width:min(1080px, calc(100% - 32px));
            margin:0 auto;
        }
        .ads-topbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
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
            grid-template-columns:minmax(320px, 1fr) 520px;
            gap:42px;
            align-items:center;
            padding:20px 0 42px;
        }
        .intro {
            max-width:430px;
        }
        .eyebrow {
            color:#a8cbff;
            text-transform:uppercase;
            font-size:.74rem;
            letter-spacing:.15em;
            font-weight:800;
        }
        .intro h1 {
            margin:16px 0;
            font-size:clamp(2rem, 4vw, 3.3rem);
            line-height:1;
            letter-spacing:-.055em;
        }
        .intro p {
            margin:0;
            color:var(--muted);
            line-height:1.75;
        }
        .benefits {
            display:grid;
            gap:12px;
            margin-top:28px;
        }
        .benefit {
            display:flex;
            gap:12px;
            align-items:flex-start;
            color:#dce7ff;
        }
        .dot {
            width:8px;
            height:8px;
            flex:0 0 auto;
            margin-top:8px;
            border-radius:50%;
            background:linear-gradient(135deg,var(--blue),var(--violet));
        }
        .card {
            border:1px solid var(--line);
            border-radius:30px;
            padding:30px;
            background:linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.035));
            box-shadow:0 28px 80px rgba(0,0,0,.34);
            backdrop-filter:blur(18px);
        }
        .card-head h2 {
            margin:0 0 8px;
            font-size:1.6rem;
            letter-spacing:-.03em;
        }
        .card-head p {
            margin:0 0 24px;
            color:var(--muted);
            line-height:1.6;
        }
        .field {
            display:grid;
            gap:8px;
            margin-bottom:16px;
        }
        .field label {
            color:var(--muted);
            font-size:.78rem;
            text-transform:uppercase;
            letter-spacing:.08em;
            font-weight:750;
        }
        input {
            width:100%;
            min-height:50px;
            border:1px solid var(--line);
            border-radius:16px;
            padding:0 16px;
            color:var(--text);
            background:rgba(255,255,255,.035);
            font:inherit;
            outline:none;
            transition:border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }
        input::placeholder { color:#74829c; }
        input:focus {
            border-color:var(--line-focus);
            background:rgba(255,255,255,.055);
            box-shadow:0 0 0 4px rgba(10,122,255,.12);
        }
        .helper {
            color:#7f8ba3;
            font-size:.82rem;
            line-height:1.45;
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
            transition:transform .18s ease, opacity .18s ease;
        }
        .btn:hover { transform:translateY(-2px); }
        .footer-link {
            display:block;
            margin-top:18px;
            color:var(--muted);
            text-align:center;
            text-decoration:none;
        }
        .footer-link strong { color:#fff; }
        @media (max-width: 860px) {
            .layout { grid-template-columns:1fr; gap:28px; align-items:start; }
            .intro { max-width:none; padding-top:6px; }
        }
        @media (max-width: 560px) {
            .shell { width:min(100% - 24px, 1080px); }
            .card { padding:22px; border-radius:24px; }
            .layout { min-height:auto; padding-bottom:24px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php AdsHeader::render(); ?>
        <main class="layout">
            <section class="intro">
                <span class="eyebrow">Conta comercial</span>
                <h1>Crie sua base para anunciar com clareza.</h1>
                <p>Abra sua conta Ads, organize sua marca e prepare campanhas que possam ser medidas desde o primeiro dia.</p>
                <div class="benefits">
                    <div class="benefit"><span class="dot"></span><span>CNPJ opcional, útil para credibilidade e verificações futuras.</span></div>
                    <div class="benefit"><span class="dot"></span><span>Email profissional para centralizar acesso e comunicação.</span></div>
                    <div class="benefit"><span class="dot"></span><span>Depois do cadastro, você pode vincular à sua conta Pipocine.</span></div>
                </div>
            </section>

            <section class="card" aria-label="Formulário de cadastro">
                <div class="card-head">
                    <h2>Criar conta</h2>
                    <p>Leva menos de um minuto. Os campos essenciais são validados antes da criação.</p>
                </div>
                <form id="f" novalidate>
                    <div class="field">
                        <label for="brand_name">Empresa ou marca</label>
                        <input id="brand_name" name="brand_name" required minlength="2" maxlength="120" placeholder="Ex: Aurora Studio">
                    </div>
                    <div class="field">
                        <label for="cnpj">CNPJ <span style="text-transform:none;letter-spacing:0;font-weight:500;">(opcional)</span></label>
                        <input id="cnpj" name="cnpj" inputmode="numeric" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="field">
                        <label for="email">Email profissional</label>
                        <input id="email" name="email" type="email" required placeholder="contato@suaempresa.com">
                    </div>
                    <div class="field">
                        <label for="password">Senha</label>
                        <input id="password" name="password" type="password" required minlength="8" placeholder="Crie uma senha segura">
                        <span class="helper">Mínimo de 8 caracteres, com maiúscula, minúscula e número.</span>
                    </div>
                    <div class="msg" id="m"></div>
                    <button class="btn">Criar conta comercial</button>
                </form>
                <a class="footer-link" href="/ads/login">Já possui conta? <strong>Entrar</strong></a>
            </section>
        </main>
    </div>
    <script>
        f.onsubmit = async (e) => {
            e.preventDefault();
            m.textContent = '';
            const d = Object.fromEntries(new FormData(f).entries());
            const r = await fetch('/api/ads/register', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(d)
            });
            const j = await r.json();
            if (j.success) location.href = j.redirect;
            else m.textContent = j.message || 'Erro ao cadastrar.';
        };
    </script>
</body>
</html>
