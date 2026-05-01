/**
 * PIPOCINE — Settings Page JS
 * 1. Sidebar mobile (toggle + overlay + ESC)
 * 2. Navegação entre painéis sem recarregar
 * 3. Fetch /api/v3/account/me e renderização do painel "Minha Conta"
 */
(function () {
    'use strict';

    /* ── Referências DOM ──────────────────────────────── */
    const sidebar      = document.getElementById('settings-sidebar');
    const overlay      = document.getElementById('sidebar-overlay');
    const topbarBtn    = document.getElementById('topbar-menu-btn');
    const topbarTitle  = document.getElementById('topbar-title');
    const navLinks     = document.querySelectorAll('.sidebar-link[data-section]');
    const panels       = document.querySelectorAll('.settings-panel[data-panel]');

    /* ── Sidebar mobile ───────────────────────────────── */
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
        if (topbarBtn) topbarBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        if (topbarBtn) topbarBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (topbarBtn) topbarBtn.addEventListener('click', openSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    /* ── Navegação entre painéis ──────────────────────── */
    function showPanel(section) {
        panels.forEach(function (p) {
            if (p.dataset.panel === section) {
                p.hidden = false;
            } else {
                p.hidden = true;
            }
        });

        navLinks.forEach(function (link) {
            const active = link.dataset.section === section;
            link.classList.toggle('active', active);
            link.setAttribute('aria-current', active ? 'page' : 'false');
            if (active && topbarTitle) {
                const label = link.querySelector('.sidebar-link-label');
                if (label) topbarTitle.textContent = label.textContent;
            }
        });

        /* Atualiza URL sem reload */
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', section);
            history.replaceState(null, '', url.toString());
        } catch (_) {}
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            const section = link.dataset.section;
            if (!section) return;
            showPanel(section);
            closeSidebar();
            if (section === 'minha-conta') loadAccount();
        });
    });

    /* ── Inicialização: lê aba da URL ─────────────────── */
    function init() {
        const params  = new URLSearchParams(window.location.search);
        const initial = params.get('tab') || 'minha-conta';
        showPanel(initial);
        if (initial === 'minha-conta') loadAccount();
    }

    /* ── Fetch da conta (API v3) ──────────────────────── */
    var accountLoaded = false;

    function loadAccount() {
        if (accountLoaded) return;

        const hero    = document.getElementById('account-hero');
        const details = document.getElementById('account-details');
        const profSec = document.getElementById('profiles-section');

        fetch('/api/v3/account/me', { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                const data = json.data || json; /* suporta {success,data} e payload direto */
                if (!data || json.error) { renderHeroError(hero); return; }
                accountLoaded = true;

                renderHero(hero, data);
                renderDetailRows(data);
                renderProfileCards(data.profiles || []);

                if (details) details.style.display = '';
                if (profSec) profSec.style.display  = '';
            })
            .catch(function () { renderHeroError(hero); });
    }

    /* ── Render: Hero ─────────────────────────────────── */
    function renderHero(el, d) {
        if (!el) return;
        var acc      = d.account || d;
        var planType = (acc.plan_type || '').toLowerCase();
        var planCls  = planType === 'premium' ? 'premium'
                     : planType === 'family'  ? 'family'
                     : planType === 'student' ? 'student'
                     : 'free';
        var planLabel = acc.plan_label || 'Gratuito';

        var avatar = acc.profile_photo || acc.profile_pic_url || '';
        var avatarHtml = avatar
            ? '<img src="' + esc(avatar) + '" alt="Foto de perfil" class="account-hero-avatar" crossorigin="anonymous">'
            : '<div class="account-hero-avatar-placeholder">'
              + svgUser(28) + '</div>';

        el.innerHTML =
            avatarHtml
            + '<div class="account-hero-info">'
            +   '<div class="account-hero-name">'     + esc(acc.full_name || acc.username || 'Utilizador') + '</div>'
            +   '<div class="account-hero-username">@' + esc(acc.username || '') + '</div>'
            +   '<div class="account-hero-email">'    + esc(acc.email    || '') + '</div>'
            + '</div>'
            + '<span class="plan-badge ' + planCls + '">' + esc(planLabel) + '</span>';
    }

    function renderHeroError(el) {
        if (!el) return;
        el.innerHTML =
            '<p style="color:var(--set-text-muted);font-size:13.5px;padding:8px 0;">'
            + 'Não foi possível carregar os dados da conta. '
            + '<button onclick="location.reload()" style="color:var(--set-accent);background:none;'
            + 'border:none;cursor:pointer;font-weight:600;font-size:13.5px;">Tentar novamente</button>'
            + '</p>';
    }

    /* ── Render: Rows de detalhe ──────────────────────── */
    function renderDetailRows(d) {
        var acc = d.account || d;
        var set = function (id, val) {
            var el = document.getElementById(id);
            if (!el) return;
            el.textContent = val || '—';
            if (!val) el.classList.add('muted');
        };

        set('detail-name',     acc.full_name);
        set('detail-username', acc.username ? '@' + acc.username : '');
        set('detail-email',    acc.email);

        var planEl = document.getElementById('detail-plan');
        if (planEl) {
            var expiry = acc.plan_expires_at || acc.plan_expiration || '';
            planEl.textContent = (acc.plan_label || 'Gratuito')
                + (expiry ? ' · Expira ' + fmtDate(expiry) : '');
        }
    }

    /* ── Render: Cards de perfil ──────────────────────── */
    function renderProfileCards(profiles) {
        var grid = document.getElementById('profiles-grid-settings');
        if (!grid) return;

        if (!profiles.length) {
            grid.innerHTML = '<p style="color:var(--set-text-muted);font-size:13px;grid-column:1/-1;">Nenhum perfil vinculado.</p>';
            return;
        }

        grid.innerHTML = profiles.map(function (p) {
            var name  = p.profile_name || p.name || 'Perfil';
            var uname = p.username || '';
            var img   = p.profile_image || p.image
                      || 'https://api.dicebear.com/7.x/adventurer/svg?seed=' + encodeURIComponent(name);
            var kids  = p.is_kids ? '<span class="profile-card-tag kids">Infantil</span>' : '';

            return '<div class="profile-card-settings">'
                + '<img src="' + esc(img) + '" alt="' + esc(name) + '" class="profile-card-avatar" crossorigin="anonymous">'
                + '<span class="profile-card-name">'     + esc(name)  + '</span>'
                + (uname ? '<span class="profile-card-username">@' + esc(uname) + '</span>' : '')
                + kids
                + '</div>';
        }).join('');
    }

    /* ── Helpers ──────────────────────────────────────── */
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtDate(iso) {
        try {
            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit', month: 'short', year: 'numeric'
            }).format(new Date(iso));
        } catch (_) { return iso; }
    }

    function svgUser(size) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '"'
            + ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"'
            + ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            + '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>'
            + '</svg>';
    }

    /* ── Boot ─────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
