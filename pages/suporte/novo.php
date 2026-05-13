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
    <title>Novo chamado — Suporte Pipocine</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/support.css">
</head>
<body class="sp-page sp-novo-page"
    data-auth="<?= $isAuthenticated ? '1' : '0' ?>"
    <?php if ($isAuthenticated): ?>data-user-id="<?= (int) $_SESSION['user_id'] ?>"<?php endif; ?>
>

<!-- ====== NAVBAR ====== -->
<nav class="sp-nav" role="navigation" aria-label="Navegacao principal">
    <a href="/suporte" class="sp-nav-brand" aria-label="Voltar ao suporte">
        <img src="/assets/img/logo-pipocine.png" alt="Pipocine" class="sp-nav-logo">
        <span class="sp-nav-brand-name">Pipocine</span>
        <span class="sp-nav-breadcrumb">
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                 fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
            Suporte
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                 fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
            Novo chamado
        </span>
    </a>

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

<!-- ====== FORM ====== -->
<main class="sp-novo-main">

    <div class="sp-novo-card">

        <!-- Step indicator -->
        <div class="sp-steps" aria-label="Etapas">
            <div class="sp-step sp-step--active">
                <span class="sp-step-num" aria-hidden="true">1</span>
                <span class="sp-step-label">Descricao</span>
            </div>
            <div class="sp-step-line" aria-hidden="true"></div>
            <div class="sp-step" id="sp-step-2">
                <span class="sp-step-num" aria-hidden="true">2</span>
                <span class="sp-step-label">Chat</span>
            </div>
        </div>

        <div class="sp-novo-body">
            <h1 class="sp-novo-title">Descreva seu problema</h1>
            <p class="sp-novo-sub">Quanto mais detalhes voce fornecer, mais rapido conseguimos ajudar.</p>

            <form id="sp-novo-form" novalidate>

                <?php if (!$isAuthenticated): ?>
                <!-- Guest name -->
                <div class="sp-field">
                    <label class="sp-label" for="sp-guest-name">
                        Seu nome <span class="sp-label-opt">(opcional)</span>
                    </label>
                    <input
                        id="sp-guest-name"
                        type="text"
                        class="sp-input"
                        placeholder="Como devemos te chamar?"
                        maxlength="80"
                        autocomplete="name"
                    >
                </div>
                <?php endif; ?>

                <!-- Subject -->
                <div class="sp-field">
                    <label class="sp-label" for="sp-subject">
                        Assunto <span class="sp-label-req" aria-hidden="true">*</span>
                    </label>
                    <div class="sp-subject-suggestions" role="group" aria-label="Sugestoes de assunto">
                        <button type="button" class="sp-suggestion" data-val="Problema no pagamento">Pagamento</button>
                        <button type="button" class="sp-suggestion" data-val="Duvida sobre assinatura">Assinatura</button>
                        <button type="button" class="sp-suggestion" data-val="Conteudo nao carrega">Conteudo</button>
                        <button type="button" class="sp-suggestion" data-val="Problema com minha conta">Conta</button>
                        <button type="button" class="sp-suggestion" data-val="Outro assunto">Outro</button>
                    </div>
                    <input
                        id="sp-subject"
                        type="text"
                        class="sp-input"
                        placeholder="Ex: Pagamento nao foi processado"
                        maxlength="180"
                        required
                        autocomplete="off"
                        aria-required="true"
                        aria-describedby="sp-subject-err"
                    >
                    <span id="sp-subject-err" class="sp-field-err" aria-live="polite" style="display:none">Informe um assunto.</span>
                </div>

                <!-- Description -->
                <div class="sp-field">
                    <label class="sp-label" for="sp-description">
                        Descricao <span class="sp-label-opt">(opcional)</span>
                    </label>
                    <textarea
                        id="sp-description"
                        class="sp-input sp-input--textarea"
                        placeholder="Descreva o que aconteceu, quando ocorreu e o que voce tentou fazer..."
                        rows="4"
                        maxlength="1000"
                    ></textarea>
                    <span class="sp-char-count" id="sp-char-count">0 / 1000</span>
                </div>

                <!-- Error banner -->
                <p id="sp-novo-error" class="sp-form-error" role="alert" style="display:none"></p>

                <!-- Submit -->
                <div class="sp-novo-actions">
                    <a href="/suporte" class="sp-btn sp-btn--ghost">Cancelar</a>
                    <button type="submit" id="sp-novo-submit" class="sp-btn sp-btn--primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        Continuar
                    </button>
                </div>

            </form>
        </div>
    </div>

</main>

<script>
(function () {
    // ---- User menu ----
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

    // ---- Subject suggestions ----
    document.querySelectorAll('.sp-suggestion').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById('sp-subject');
            if (input) {
                input.value = btn.dataset.val;
                input.focus();
            }
            document.querySelectorAll('.sp-suggestion').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // ---- Char count ----
    const descEl     = document.getElementById('sp-description');
    const countEl    = document.getElementById('sp-char-count');
    if (descEl && countEl) {
        descEl.addEventListener('input', function () {
            countEl.textContent = descEl.value.length + ' / 1000';
        });
    }

    // ---- Form submit ----
    const form      = document.getElementById('sp-novo-form');
    const submitBtn = document.getElementById('sp-novo-submit');
    const errorEl   = document.getElementById('sp-novo-error');
    const step2     = document.getElementById('sp-step-2');

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const subject   = document.getElementById('sp-subject')?.value.trim();
        const subErr    = document.getElementById('sp-subject-err');
        const guestName = document.getElementById('sp-guest-name')?.value.trim() || null;

        if (!subject) {
            if (subErr) subErr.style.display = '';
            document.getElementById('sp-subject')?.focus();
            return;
        }
        if (subErr) subErr.style.display = 'none';

        submitBtn.disabled = true;
        submitBtn.textContent = 'Abrindo...';
        if (errorEl) errorEl.style.display = 'none';

        // Activate step 2 visually
        step2?.classList.add('sp-step--active');

        try {
            const res  = await fetch('/api/suporte/chat/create', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ subject, guest_name: guestName }),
            });
            const data = await res.json();

            if (!res.ok || !data.success) throw new Error(data.error || 'Erro ao criar chamado.');

            // Persist to localStorage
            const STORAGE_KEY   = 'pipo_support';
            const HISTORY_KEY   = 'pipo_support_history';

            const existing = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
            if (existing && existing.chatId && existing.chatId !== data.chat_id) {
                // Archive the old one
                try {
                    const hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                    hist.unshift(existing);
                    if (hist.length > 20) hist.length = 20;
                    localStorage.setItem(HISTORY_KEY, JSON.stringify(hist));
                } catch (_) {}
            }

            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                chatId:       data.chat_id,
                sessionToken: data.session_token,
                subject:      subject,
                status:       'open',
                lastMsgId:    0,
                synced:       false,
                updatedAt:    new Date().toISOString(),
            }));

            // Redirect to chat
            window.location.href = '/suporte?view=chat&id=' + data.chat_id;

        } catch (err) {
            step2?.classList.remove('sp-step--active');
            if (errorEl) {
                errorEl.textContent = err.message;
                errorEl.style.display = '';
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Continuar';
        }
    });
})();
</script>
</body>
</html>
