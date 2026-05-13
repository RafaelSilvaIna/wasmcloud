<?php declare(strict_types=1); ?>

<div class="sp-notices" role="region" aria-label="Avisos de suporte">

    <!-- Anti-fraude -->
    <div class="sp-notice sp-notice--warn" role="alert">
        <svg class="sp-notice-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>
            <strong>Atencao — evite golpes.</strong>
            O unico canal oficial de suporte do Pipocine e <strong>pipocine.site/suporte</strong>.
            Desconfie de qualquer outro site, grupo ou contato que se diga ser nosso suporte.
        </span>
    </div>

    <!-- Diretrizes -->
    <div class="sp-notice sp-notice--info">
        <svg class="sp-notice-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>
            <strong>Diretrizes da comunidade.</strong>
            Mantenha o respeito durante o atendimento.
            <button class="sp-notice-expand" type="button" id="sp-guidelines-toggle" aria-expanded="false">Ver regras</button>
        </span>
        <div class="sp-guidelines" id="sp-guidelines" role="region" aria-labelledby="sp-guidelines-toggle">
            <ol>
                <li>Seja respeitoso com os atendentes e outros usuarios.</li>
                <li>Nao compartilhe senhas ou dados de cartao no chat.</li>
                <li>Forneca informacoes precisas para acelerar o atendimento.</li>
                <li>Spam ou mensagens ofensivas podem resultar em bloqueio.</li>
                <li>Imagens enviadas ficam disponiveis por 24 horas.</li>
                <li>Nao utilize o suporte para relatar conteudo ilegal — use os canais legais.</li>
                <li>Um atendimento encerrado nao pode ser reaberto pelo usuario — abra um novo.</li>
            </ol>
        </div>
    </div>

    <!-- Horario -->
    <div class="sp-notice sp-notice--hours">
        <svg class="sp-notice-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        <span>
            <strong>Horario de atendimento:</strong>
            das <strong>12:00</strong> ate as <strong>21:30</strong> (horario de Brasilia), todos os dias.
            Mensagens enviadas fora do horario serao respondidas no proximo expediente.
        </span>
    </div>

</div>

<script>
(function () {
    const toggle = document.getElementById('sp-guidelines-toggle');
    const panel  = document.getElementById('sp-guidelines');
    if (!toggle || !panel) return;

    toggle.addEventListener('click', function () {
        const open = panel.classList.toggle('open');
        toggle.textContent   = open ? 'Fechar' : 'Ver regras';
        toggle.setAttribute('aria-expanded', String(open));
    });
})();
</script>
