<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$ownerName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Conta Pipocine', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Box - Pipocine</title>
    <link rel="stylesheet" href="/assets/css/notification.css">
    <style>
        :root {
            --box-bg: #000000;
            --box-panel: #0a0a0c;
            --box-panel-soft: #111217;
            --box-input: #1c1c1e;
            --box-border: rgba(255, 255, 255, .12);
            --box-text: #f8fafc;
            --box-muted: #8e8e93;
            --box-red: #ff3b3f;
            --box-red-dark: #6b1519;
            --box-accent: #0a7aff;
            --text-pure: #f8fafc;
            --text-muted: #8e8e93;
            --status-success: #18c37e;
            --status-error: #ef4444;
            --status-warning: #f59e0b;
            --status-info: #38bdf8;
            --transition-fast: .18s ease;
        }

        * { box-sizing: border-box; }

        body.box-body {
            min-height: 100vh;
            margin: 0;
            color: var(--box-text);
            background:
                radial-gradient(circle at 92% 2%, rgba(229, 9, 20, .32), transparent 25%),
                radial-gradient(circle at 0 82%, rgba(229, 9, 20, .26), transparent 28%),
                linear-gradient(135deg, #050509 0%, #0a0b10 46%, #030305 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .box-shell {
            width: min(1240px, calc(100% - 72px));
            margin: 0 auto;
            padding: 32px 0 54px;
        }

        .box-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 44px;
        }

        .box-back,
        .box-account,
        .box-refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 999px;
            background: rgba(15, 16, 22, .72);
            color: var(--box-text);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .035), 0 12px 34px rgba(0, 0, 0, .28);
            backdrop-filter: blur(18px);
            transition: background-color .18s, border-color .18s;
        }

        .box-back {
            min-height: 52px;
            padding: 0 22px;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 850;
        }

        .box-account {
            min-height: 52px;
            padding: 0 18px;
            max-width: min(360px, 55vw);
            font-size: 1rem;
        }

        .box-account span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 750;
        }

        .box-back svg,
        .box-account svg,
        .box-refresh svg {
            width: 24px;
            height: 24px;
        }

        .box-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: 0 10px;
            margin-bottom: 24px;
        }

        .box-label {
            color: #ff6870;
            font-size: .76rem;
            font-weight: 900;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .box-hero h1 {
            margin: 0;
            font-size: clamp(2.35rem, 4.2vw, 3.35rem);
            line-height: 1;
            letter-spacing: 0;
            text-shadow: 0 8px 28px rgba(0, 0, 0, .62);
        }

        .box-hero p {
            max-width: 450px;
            margin: 0 10px 8px 0;
            color: rgba(255, 255, 255, .54);
            font-size: 1rem;
            line-height: 1.5;
            text-align: right;
        }

        .box-panel {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 14px;
            background:
                radial-gradient(circle at 0 0, rgba(229, 9, 20, .20), transparent 22%),
                rgba(7, 8, 12, .82);
            box-shadow: 0 26px 70px rgba(0, 0, 0, .42), inset 0 0 0 1px rgba(255, 255, 255, .035);
            backdrop-filter: blur(18px);
        }

        .box-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 34px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, .10);
        }

        .box-panel-head strong {
            display: block;
            margin-top: 14px;
            font-size: 1.45rem;
            line-height: 1;
        }

        .box-refresh {
            min-height: 54px;
            padding: 0 22px;
            border-radius: 14px;
            cursor: pointer;
            color: rgba(255, 255, 255, .72);
            font-size: 1rem;
            font-weight: 800;
        }

        .box-list { display: grid; }

        .box-day {
            display: grid;
        }

        .box-day-label {
            padding: 20px 34px 10px;
            color: rgba(255, 255, 255, .56);
            background: rgba(0, 0, 0, .14);
            border-bottom: 0;
            font-size: .92rem;
            font-weight: 900;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .box-item {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;
            margin: 12px 34px 32px;
            padding: 28px 28px;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 14px;
            background: rgba(17, 18, 24, .58);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .025);
        }

        .box-item:last-child { border-bottom: 0; }

        .box-item.unread {
            background:
                linear-gradient(90deg, rgba(229, 9, 20, .08), transparent 32%),
                rgba(17, 18, 24, .62);
        }

        .box-item-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 59, 63, .44);
            background: radial-gradient(circle, rgba(255, 59, 63, .18), rgba(255, 59, 63, .05));
            color: #ff4b55;
        }

        .box-item-icon svg {
            width: 23px;
            height: 23px;
        }

        .box-item h2 {
            margin: 0;
            font-size: 1.22rem;
            line-height: 1.2;
        }

        .box-item p {
            max-width: 620px;
            margin: 10px 0 0;
            color: rgba(255, 255, 255, .52);
            font-size: 1rem;
            line-height: 1.45;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .box-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            margin-top: 18px;
            color: rgba(255, 255, 255, .5);
            font-size: .9rem;
        }

        .box-status {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            color: var(--box-muted);
            background: rgba(255, 255, 255, .06);
            font-weight: 800;
        }

        .box-status.accepted::before {
            content: "✓";
            width: 18px;
            height: 18px;
            display: inline-grid;
            place-items: center;
            border: 2px solid currentColor;
            border-radius: 50%;
            font-size: .78rem;
            line-height: 1;
        }

        .box-status.pending {
            color: #fed7aa;
            background: rgba(245, 158, 11, .12);
        }

        .box-status.accepted {
            color: #bbf7d0;
            background: rgba(24, 195, 126, .12);
        }

        .box-status.declined {
            color: #fecaca;
            background: rgba(229, 9, 20, .12);
        }

        .box-btn {
            min-height: 38px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, .12);
            padding: 0 13px;
            color: var(--box-text);
            background: transparent;
            font-weight: 850;
            cursor: pointer;
        }

        .box-btn.accept {
            border-color: rgba(24, 195, 126, .38);
            background: rgba(24, 195, 126, .12);
        }

        .box-open-btn {
            min-width: 132px;
            min-height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 12px;
            border: 1px solid rgba(255, 64, 74, .68);
            padding: 0 20px;
            color: #fff;
            background: linear-gradient(180deg, rgba(255, 67, 76, .92), rgba(127, 21, 27, .82));
            box-shadow: 0 18px 40px rgba(229, 9, 20, .22), inset 0 1px 0 rgba(255, 255, 255, .22);
            font-size: 1rem;
            font-weight: 900;
            cursor: pointer;
        }

        .box-open-btn svg {
            width: 20px;
            height: 20px;
        }

        .box-back:hover,
        .box-open-btn:hover,
        .box-btn.decline:hover,
        .box-refresh:hover {
            border-color: rgba(255, 255, 255, .26);
        }

        .box-open-btn:hover {
            background: linear-gradient(180deg, rgba(255, 75, 85, 1), rgba(147, 25, 32, .9));
        }

        .box-btn.accept:hover { background: rgba(24, 195, 126, .2); }

        .box-empty {
            padding: 28px 20px;
            color: var(--box-muted);
            text-align: center;
        }

        .box-reader {
            position: fixed;
            inset: 0;
            z-index: 5000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, .82);
            backdrop-filter: blur(8px);
        }

        .box-reader.open {
            display: flex;
        }

        .box-reader-card {
            width: min(560px, 100%);
            max-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 18px;
            background: var(--box-panel);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .72);
        }

        .box-reader-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .box-reader-head h2 {
            margin: 5px 0 0;
            font-size: 1.28rem;
            line-height: 1.2;
        }

        .box-reader-close {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
            color: #fff;
            background: rgba(255, 255, 255, .04);
            cursor: pointer;
        }

        .box-reader-close svg {
            width: 18px;
            height: 18px;
        }

        .box-reader-body {
            overflow-y: auto;
            padding: 20px 22px;
            scrollbar-width: none;
        }

        .box-reader-body::-webkit-scrollbar {
            display: none;
        }

        .box-reader-body p {
            margin: 0 0 12px;
            color: #cbd5e1;
            line-height: 1.55;
        }

        .box-reader-body ul {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
        }

        .box-reader-body li {
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            background: rgba(255, 255, 255, .035);
            padding: 11px 12px;
            font-size: .92rem;
            line-height: 1.35;
        }

        .box-reader-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 22px 18px;
            border-top: 1px solid rgba(255, 255, 255, .08);
            background: rgba(10, 10, 12, .98);
        }

        @media (max-width: 720px) {
            .box-shell {
                width: min(100% - 40px, 560px);
                padding: 48px 0 58px;
            }

            .box-topbar {
                align-items: center;
                flex-direction: row;
                justify-content: space-between;
                gap: 14px;
                margin-bottom: 64px;
            }

            .box-back,
            .box-account {
                min-height: 62px;
                padding: 0 22px;
                font-size: 1.08rem;
                border-color: rgba(255, 255, 255, .16);
            }

            .box-back svg,
            .box-account svg,
            .box-refresh svg {
                width: 24px;
                height: 24px;
            }

            .box-hero {
                align-items: flex-start;
                flex-direction: column;
                gap: 16px;
                padding: 0 12px;
                margin-bottom: 34px;
            }

            .box-hero h1 {
                font-size: clamp(2.45rem, 10vw, 3.35rem);
                line-height: .98;
            }

            .box-hero p {
                max-width: 100%;
                font-size: 1.22rem;
                line-height: 1.35;
                text-align: left;
            }

            .box-panel-head {
                align-items: center;
                flex-direction: row;
                gap: 18px;
                padding: 34px 40px;
            }

            .box-panel-head strong {
                margin-top: 18px;
                font-size: 1.92rem;
            }

            .box-account {
                flex: 1 1 auto;
                justify-content: center;
                min-width: 0;
                max-width: calc(100% - 154px);
            }

            .box-account span {
                min-width: 0;
            }

            .box-refresh {
                min-height: 64px;
                width: auto;
                flex: 0 0 auto;
                justify-content: center;
                border-radius: 999px;
                padding: 0 24px;
                font-size: 1.1rem;
            }

            .box-day-label {
                padding: 34px 40px 16px;
                font-size: 1.02rem;
            }

            .box-item {
                grid-template-columns: 72px minmax(0, 1fr);
                align-items: start;
                gap: 22px;
                margin: 18px 36px 40px;
                padding: 42px 32px 38px;
                border-radius: 16px;
            }

            .box-item p {
                white-space: normal;
                margin-top: 16px;
                font-size: 1.22rem;
                line-height: 1.45;
            }

            .box-item h2 {
                font-size: 1.46rem;
            }

            .box-item-icon {
                width: 72px;
                height: 72px;
            }

            .box-item-icon svg {
                width: 30px;
                height: 30px;
            }

            .box-meta {
                gap: 16px;
                margin-top: 26px;
                font-size: 1.08rem;
            }

            .box-open-btn {
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
                min-height: 78px;
                margin-top: 22px;
                border-radius: 16px;
                font-size: 1.2rem;
            }

            .box-reader {
                align-items: end;
                padding: 10px;
            }

            .box-reader-card {
                max-height: calc(100vh - 20px);
                border-radius: 18px 18px 14px 14px;
            }

            .box-reader-head,
            .box-reader-body,
            .box-reader-actions {
                padding-left: 18px;
                padding-right: 18px;
            }

            .box-reader-head {
                align-items: flex-start;
            }

            .box-reader-head h2 {
                font-size: 1.18rem;
            }

            .box-reader-body {
                padding-top: 18px;
                padding-bottom: 18px;
            }

            .box-reader-body p {
                font-size: .96rem;
                line-height: 1.52;
            }

            .box-reader-body ul {
                grid-template-columns: 1fr;
            }

            .box-reader-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .box-btn {
                width: 100%;
            }
        }

        @media (max-width: 430px) {
            .box-shell {
                width: min(100% - 28px, 420px);
                padding-top: 28px;
            }

            .box-topbar {
                gap: 10px;
                margin-bottom: 42px;
            }

            .box-back,
            .box-account {
                min-height: 54px;
                padding: 0 16px;
                font-size: .98rem;
            }

            .box-account {
                max-width: calc(100% - 126px);
            }

            .box-hero {
                padding: 0;
            }

            .box-hero h1 {
                font-size: clamp(2rem, 10vw, 2.45rem);
            }

            .box-hero p {
                font-size: 1rem;
            }

            .box-panel-head {
                padding: 28px 24px;
            }

            .box-panel-head strong {
                font-size: 1.45rem;
            }

            .box-refresh {
                min-height: 54px;
                padding: 0 16px;
                font-size: .98rem;
            }

            .box-day-label {
                padding: 26px 24px 12px;
                font-size: .88rem;
            }

            .box-item {
                grid-template-columns: 60px minmax(0, 1fr);
                gap: 16px;
                margin: 14px 20px 30px;
                padding: 28px 22px;
            }

            .box-item-icon {
                width: 60px;
                height: 60px;
            }

            .box-item-icon svg {
                width: 24px;
                height: 24px;
            }

            .box-item h2 {
                font-size: 1.18rem;
            }

            .box-item p {
                font-size: 1rem;
            }

            .box-meta {
                gap: 10px;
                margin-top: 18px;
                font-size: .9rem;
            }

            .box-open-btn {
                min-height: 62px;
                margin-top: 16px;
                font-size: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/box-minimal.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="box-body">
    <main class="box-shell">
        <header class="box-topbar">
            <a class="box-back" href="/select-profile" aria-label="Voltar aos perfis">
                <i data-lucide="arrow-left"></i>
                <span>Voltar</span>
            </a>
            <div class="box-account">
                <i data-lucide="user-round"></i>
                <span><?= $ownerName ?></span>
                <i data-lucide="chevron-down"></i>
            </div>
        </header>

        <section class="box-hero">
            <div>
                <h1>Notificacoes da conta</h1>
            </div>
            <p>Convites, aprovacoes e avisos importantes.</p>
        </section>

        <section class="box-panel" aria-label="Mensagens da Box">
            <div class="box-panel-head">
                <div>
                    <span class="box-label">Entrada</span>
                    <strong id="box-count">Carregando</strong>
                </div>
                <button class="box-refresh" type="button" id="box-refresh">
                    <i data-lucide="refresh-cw"></i>
                    <span>Atualizar</span>
                </button>
            </div>

            <div class="box-list" id="box-list">
                <div class="box-empty">Carregando sua Box...</div>
            </div>
        </section>
    </main>

    <div class="box-reader" id="box-reader" aria-hidden="true">
        <article class="box-reader-card" role="dialog" aria-modal="true" aria-labelledby="box-reader-title">
            <header class="box-reader-head">
                <div>
                    <span class="box-label" id="box-reader-date">Mensagem</span>
                    <h2 id="box-reader-title">Mensagem</h2>
                </div>
                <button class="box-reader-close" type="button" id="box-reader-close" aria-label="Fechar mensagem">
                    <i data-lucide="x"></i>
                </button>
            </header>

            <div class="box-reader-body" id="box-reader-body"></div>

            <footer class="box-reader-actions" id="box-reader-actions"></footer>
        </article>
    </div>

    <script src="/assets/js/notification.js"></script>
    <script src="/assets/js/box.js"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{ if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
</body>
</html>
