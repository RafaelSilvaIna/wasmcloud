<?php
declare(strict_types=1);

final class MaintenanceRouteModal
{
    public static function render(array $lock, string $path): void
    {
        $title = trim((string) ($lock['maintenance_title'] ?? 'Pagina em manutencao'));
        $message = trim((string) ($lock['maintenance_message'] ?? 'Estamos ajustando esta area. Volte em instantes.'));
        if ($title === '') {
            $title = 'Pagina em manutencao';
        }
        if ($message === '') {
            $message = 'Estamos ajustando esta area. Volte em instantes.';
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 300');
        ?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | PipoCine</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07090d;
            --panel: #0d1117;
            --line: rgba(148, 163, 184, .18);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #e50914;
            --link: #93c5fd;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        main {
            width: min(430px, 100%);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            padding: 32px;
        }
        .mark {
            width: 28px;
            height: 3px;
            margin-bottom: 26px;
            border-radius: 999px;
            background: var(--accent);
        }
        h1 {
            margin: 0;
            max-width: 11ch;
            font-size: clamp(1.8rem, 7vw, 2.4rem);
            line-height: 1.08;
            letter-spacing: 0;
        }
        p {
            margin: 16px 0 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 1rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
        }
        a {
            color: var(--link);
            text-decoration: none;
            font-weight: 700;
            font-size: .92rem;
        }
        a:hover {
            text-decoration: underline;
        }
        small {
            display: block;
            margin-top: 26px;
            color: #64748b;
            font-size: .78rem;
        }
    </style>
</head>
<body>
    <main role="dialog" aria-modal="true" aria-labelledby="maintenance-title">
        <div class="mark" aria-hidden="true"></div>
        <h1 id="maintenance-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></p>
        <div class="actions" aria-label="Acoes">
            <a href="/status">Acompanhar status</a>
            <a href="/home">Voltar ao inicio</a>
        </div>
        <small><?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?></small>
    </main>
</body>
</html>
        <?php
    }
}
