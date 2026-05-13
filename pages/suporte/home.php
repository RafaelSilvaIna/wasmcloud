<?php
declare(strict_types=1);

$isAuthenticated = isset($_SESSION['user_id']);
$userDisplayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? null;
$userAvatar      = $_SESSION['profile_pic_url'] ?? null;

// Server-side chat history for authenticated users
$serverChats = [];
if ($isAuthenticated && isset($pdo)) {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, subject, status, created_at, updated_at
               FROM support_chats
              WHERE user_id = ?
           ORDER BY updated_at DESC
              LIMIT 20'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $serverChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable) {}
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Suporte — Pipocine</title>
    <meta name="description" content="Central de suporte oficial do Pipocine. Atendimento 12h–21h30.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/support.css">
</head>
<body class="sp-page sp-home-page"
    data-auth="<?= $isAuthenticated ? '1' : '0' ?>"
    <?php if ($isAuthenticated): ?>data-user-id="<?= (int) $_SESSION['user_id'] ?>"<?php endif; ?>
>

<!-- ====== NAVBAR ====== -->
<nav class="sp-nav" role="navigation" aria-label="Navegacao principal">
    <a href="/" class="sp-nav-brand" aria-label="Voltar ao Pipocine">
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="sp-nav-logo">
        <span class="sp-nav-brand-name">Pipocine</span>
        <span class="sp-nav-breadcrumb">/ Suporte</span>
    </a>

    <!-- User context menu -->
    <div class="sp-nav-end">
        <button type="button" class="sp-user-btn" id="sp-user-btn" aria-label="Menu do usuario" aria-haspopup="true" aria-expanded="false">
            <?php if ($userAvatar): ?>
                <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES) ?>" alt="" class="sp-user-avatar-img">
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            <?php endif; ?>
        </button>

        <div class="sp-user-menu" id="sp-user-menu" role="menu" aria-hidden="true">
            <?php if ($isAuthenticated && $userDisplayName): ?>
            <div class="sp-user-menu-name"><?= htmlspecialchars($userDisplayName, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($isAuthenticated): ?>
            <a href="/" class="sp-user-menu-item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Voltar a home
            </a>
            <?php else: ?>
            <a href="/login" class="sp-user-menu-item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Voltar ao login
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ====== HERO ====== -->
<header class="sp-hero" role="banner">
    <div class="sp-hero-inner">
        <div class="sp-hero-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                 fill="none" stroke="#e50914" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div>
            <h1 class="sp-hero-title">Central de Suporte</h1>
            <p class="sp-hero-sub">Atendimento das <strong>12:00</strong> as <strong>21:30</strong> &mdash; respondemos o mais rapido possivel.</p>
        </div>
    </div>
</header>

<!-- ====== MAIN ====== -->
<main class="sp-home-main" id="sp-main">

    <!-- ---- INFO CARDS ---- -->
    <section class="sp-info-grid" aria-label="Informacoes do suporte">

        <div class="sp-info-card">
            <div class="sp-info-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h2 class="sp-info-card-title">Horario</h2>
            <p class="sp-info-card-desc">Segunda a domingo<br><strong>12:00 – 21:30</strong> (Brasilia)</p>
        </div>

        <div class="sp-info-card">
            <div class="sp-info-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h2 class="sp-info-card-title">Canal oficial</h2>
            <p class="sp-info-card-desc">Somente em<br><strong>pipocine.site/suporte</strong></p>
        </div>

        <div class="sp-info-card">
            <div class="sp-info-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <h2 class="sp-info-card-title">Evite golpes</h2>
            <p class="sp-info-card-desc">Nunca pedimos senhas<br>ou dados de pagamento no chat.</p>
        </div>

        <div class="sp-info-card">
            <div class="sp-info-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <h2 class="sp-info-card-title">Diretrizes</h2>
            <p class="sp-info-card-desc">Seja respeitoso. Spam ou<br>ofensas resultam em bloqueio.</p>
        </div>

    </section>

    <!-- ---- NEW CHAT CTA ---- -->
    <section class="sp-cta-section" aria-label="Abrir novo chamado">
        <div class="sp-cta-card">
            <div class="sp-cta-left">
                <span class="sp-online-dot" aria-hidden="true"></span>
                <div>
                    <p class="sp-cta-title">Precisa de ajuda?</p>
                    <p class="sp-cta-desc">Abra um chamado e nossa equipe responde em breve.</p>
                </div>
            </div>
            <a href="/suporte?view=novo" class="sp-btn sp-btn--primary sp-cta-btn" role="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo chamado
            </a>
        </div>
    </section>

    <!-- ---- CHAT HISTORY ---- -->
    <section class="sp-history-section" aria-label="Historico de chamados">
        <div class="sp-history-header">
            <h2 class="sp-section-title">Chamados anteriores</h2>
            <span class="sp-history-source" id="sp-history-source"></span>
        </div>

        <!-- Server-side chats (authenticated) -->
        <?php if ($isAuthenticated && !empty($serverChats)): ?>
        <ul class="sp-chat-list" id="sp-server-list" aria-label="Chamados da conta">
            <?php foreach ($serverChats as $chat): ?>
            <?php
                $statusLabel = ['open' => 'Aberto', 'pending' => 'Pendente', 'closed' => 'Encerrado'][$chat['status']] ?? $chat['status'];
                $statusCls   = $chat['status'] === 'open' ? 'sp-badge--open' : ($chat['status'] === 'closed' ? 'sp-badge--closed' : 'sp-badge--pending');
                $date = date('d/m/Y H:i', strtotime($chat['updated_at']));
            ?>
            <li>
                <a href="/suporte?view=chat&id=<?= (int) $chat['id'] ?>" class="sp-chat-item">
                    <div class="sp-chat-item-left">
                        <span class="sp-chat-item-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="sp-chat-item-subject"><?= htmlspecialchars($chat['subject'], ENT_QUOTES) ?></p>
                            <p class="sp-chat-item-meta">Atualizado em <?= $date ?></p>
                        </div>
                    </div>
                    <div class="sp-chat-item-right">
                        <span class="sp-badge <?= $statusCls ?>"><?= $statusLabel ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                             fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php elseif ($isAuthenticated): ?>
        <div class="sp-history-empty" id="sp-server-list">
            <p>Nenhum chamado encontrado para esta conta.</p>
        </div>
        <?php endif; ?>

        <!-- LocalStorage chats (guests + fallback) — rendered client-side -->
        <?php if (!$isAuthenticated): ?>
        <div id="sp-local-list-wrap">
            <ul class="sp-chat-list" id="sp-local-list" aria-label="Chamados locais" style="display:none"></ul>
            <div class="sp-history-empty" id="sp-local-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"
                     fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p>Nenhum chamado anterior encontrado.</p>
                <p style="font-size:.78rem">Crie um novo chamado para comecar.</p>
            </div>
        </div>
        <?php else: ?>
        <!-- For authenticated users, also show any local-only chats not yet synced -->
        <div id="sp-local-list-wrap" style="margin-top:8px"></div>
        <?php endif; ?>

    </section>

</main>

<!-- ====== FOOTER ====== -->
<footer class="sp-footer">
    <span>&copy; <?= date('Y') ?> Pipocine &mdash; suporte oficial em <strong>pipocine.site/suporte</strong></span>
</footer>

<script>
(function () {
    // ---- User context menu ----
    const userBtn  = document.getElementById('sp-user-btn');
    const userMenu = document.getElementById('sp-user-menu');

    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = userMenu.classList.toggle('open');
            userBtn.setAttribute('aria-expanded', String(open));
            userMenu.setAttribute('aria-hidden', String(!open));
        });

        document.addEventListener('click', function () {
            userMenu.classList.remove('open');
            userBtn.setAttribute('aria-expanded', 'false');
            userMenu.setAttribute('aria-hidden', 'true');
        });

        userMenu.addEventListener('click', e => e.stopPropagation());
    }

    // ---- LocalStorage history ----
    const STORAGE_KEY  = 'pipo_support';
    const HISTORY_KEY  = 'pipo_support_history'; // array of past chats
    const isAuth       = document.body.dataset.auth === '1';

    function loadLocalHistory() {
        const chats = [];
        // Current active chat
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                const d = JSON.parse(raw);
                if (d.chatId) chats.push({
                    id:      d.chatId,
                    subject: d.subject || 'Chamado #' + d.chatId,
                    status:  d.status  || 'open',
                    date:    d.updatedAt || null,
                    token:   d.sessionToken,
                });
            }
        } catch (_) {}

        // History archive
        try {
            const raw = localStorage.getItem(HISTORY_KEY);
            if (raw) {
                const arr = JSON.parse(raw);
                arr.forEach(function (d) {
                    if (d.chatId && !chats.find(c => c.id === d.chatId)) {
                        chats.push({
                            id:      d.chatId,
                            subject: d.subject || 'Chamado #' + d.chatId,
                            status:  d.status  || 'closed',
                            date:    d.updatedAt || null,
                            token:   d.sessionToken,
                        });
                    }
                });
            }
        } catch (_) {}

        return chats;
    }

    function renderLocalHistory() {
        const chats = loadLocalHistory();
        const list  = document.getElementById('sp-local-list');
        const empty = document.getElementById('sp-local-empty');
        const wrap  = document.getElementById('sp-local-list-wrap');

        if (!isAuth) {
            // Guest mode — replace the whole empty state
            if (!chats.length) {
                if (list) list.style.display = 'none';
                if (empty) empty.style.display = 'flex';
                return;
            }
            if (empty) empty.style.display = 'none';
            if (list) {
                list.style.display = '';
                list.innerHTML = '';
                chats.forEach(function (chat) {
                    const statusMap = { open: 'Aberto', pending: 'Pendente', closed: 'Encerrado' };
                    const clsMap    = { open: 'sp-badge--open', pending: 'sp-badge--pending', closed: 'sp-badge--closed' };
                    const url = '/suporte?view=chat&id=' + chat.id + (chat.token ? '&token=' + encodeURIComponent(chat.token) : '');
                    const li  = document.createElement('li');
                    li.innerHTML = [
                        '<a href="' + url + '" class="sp-chat-item">',
                        '  <div class="sp-chat-item-left">',
                        '    <span class="sp-chat-item-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>',
                        '    <div>',
                        '      <p class="sp-chat-item-subject">' + escHtml(chat.subject) + '</p>',
                        '      <p class="sp-chat-item-meta">' + (chat.date ? 'Atualizado em ' + formatDate(chat.date) : 'Local') + '</p>',
                        '    </div>',
                        '  </div>',
                        '  <div class="sp-chat-item-right">',
                        '    <span class="sp-badge ' + (clsMap[chat.status] || '') + '">' + (statusMap[chat.status] || chat.status) + '</span>',
                        '    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
                        '  </div>',
                        '</a>',
                    ].join('');
                    list.appendChild(li);
                });
            }
        } else if (wrap && chats.length) {
            // Authenticated: show unsynced local chats below server list
            const heading = document.createElement('p');
            heading.className = 'sp-history-local-note';
            heading.textContent = 'Chamados nao sincronizados (locais):';
            wrap.appendChild(heading);

            const ul = document.createElement('ul');
            ul.className = 'sp-chat-list';
            chats.forEach(function (chat) {
                const url = '/suporte?view=chat&id=' + chat.id + (chat.token ? '&token=' + encodeURIComponent(chat.token) : '');
                const li  = document.createElement('li');
                li.innerHTML = '<a href="' + url + '" class="sp-chat-item"><div class="sp-chat-item-left"><div><p class="sp-chat-item-subject">' + escHtml(chat.subject) + '</p><p class="sp-chat-item-meta">Local</p></div></div><div class="sp-chat-item-right"><span class="sp-badge sp-badge--local">Local</span></div></a>';
                ul.appendChild(li);
            });
            wrap.appendChild(ul);
        }
    }

    function escHtml(str) {
        return String(str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    function formatDate(str) {
        try {
            const d = new Date(str);
            return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        } catch (_) { return str || ''; }
    }

    renderLocalHistory();
})();
</script>
</body>
</html>
