<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro no player - Pipocine</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <style>
        :root {
            --bg: #07080a;
            --panel: #101217;
            --line: rgba(148, 163, 184, .16);
            --text: #f8fafc;
            --muted: #9ca3af;
            --red: #e50914;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.65;
        }

        .doc-shell {
            width: min(820px, calc(100% - 32px));
            margin: 0 auto;
            padding: 36px 0 60px;
        }

        .doc-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 52px;
        }

        .doc-brand {
            color: #fff;
            text-decoration: none;
            font-weight: 850;
            letter-spacing: 0;
        }

        .doc-back {
            color: var(--muted);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 700;
        }

        .doc-back:hover { color: #fff; }

        .doc-kicker {
            color: var(--red);
            font-size: .78rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        h1 {
            margin: 10px 0 18px;
            max-width: 720px;
            font-size: clamp(2rem, 5vw, 3.35rem);
            line-height: 1.05;
        }

        .lead {
            margin: 0 0 34px;
            color: #cbd5e1;
            font-size: 1.05rem;
            max-width: 720px;
        }

        .doc-panel {
            border: 1px solid var(--line);
            background: var(--panel);
            border-radius: 8px;
            padding: 24px;
            margin: 18px 0;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 1.12rem;
        }

        p { color: var(--muted); margin: 0 0 12px; }
        p:last-child { margin-bottom: 0; }

        ul {
            margin: 0;
            padding-left: 19px;
            color: var(--muted);
        }

        li { margin: 8px 0; }

        strong { color: #fff; }
    </style>
</head>
<body>
<main class="doc-shell">
    <nav class="doc-topbar" aria-label="Navegacao">
        <a class="doc-brand" href="/home">Pipocine</a>
        <a class="doc-back" href="/home">Voltar ao inicio</a>
    </nav>

    <span class="doc-kicker">Suporte do player</span>
    <h1>Por que o player pode exibir erro?</h1>
    <p class="lead">
        Quando o player encontra uma falha, o Pipocine registra automaticamente um log tecnico para que a equipe consiga diagnosticar o problema sem depender de uma abertura manual de chamado.
    </p>

    <section class="doc-panel">
        <h2>Causas mais comuns</h2>
        <ul>
            <li><strong>Uso de VPN ou proxy:</strong> alguns provedores de video bloqueiam conexoes intermediadas ou mudancas bruscas de localidade.</li>
            <li><strong>Navegadores internos:</strong> players podem falhar dentro de apps como Instagram, Facebook, Telegram, TikTok ou outros webviews. O ideal e abrir no Chrome, Firefox, Safari ou Edge.</li>
            <li><strong>Entrega do link de reproducao:</strong> em alguns casos o Pipocine pode receber uma falha interna ao gerar ou entregar o link de video daquele conteudo.</li>
            <li><strong>Falha local do player:</strong> extensoes, bloqueadores, cache corrompido, codecs ou recursos indisponiveis no navegador tambem podem impedir a reproducao.</li>
        </ul>
    </section>

    <section class="doc-panel">
        <h2>O que acontece depois do erro</h2>
        <p>
            O erro e enviado automaticamente para o painel administrativo do Pipocine com informacoes tecnicas de reproducao, conteudo, etapa da falha, navegador e diagnosticos basicos do player.
        </p>
        <p>
            Na maioria dos casos, as falhas relacionadas a VPN e navegador interno sao resolvidas pelo proprio usuario ao trocar de rede ou abrir em um navegador completo. Quando a causa nao e local, a equipe corrige a entrega em poucas horas.
        </p>
    </section>

    <section class="doc-panel">
        <h2>Como tentar resolver agora</h2>
        <ul>
            <li>Desative VPN, proxy ou DNS com filtro e tente novamente.</li>
            <li>Abra o Pipocine em Chrome, Firefox, Safari ou Edge.</li>
            <li>Atualize a pagina e tente trocar entre audio DUB/LEG, quando disponivel.</li>
            <li>Se continuar falhando, aguarde a correcao automatica pela equipe.</li>
        </ul>
    </section>
</main>
</body>
</html>
