<?php
/**
 * Componente: SessionModal
 * Descrição: Modal para exibir mensagem de perfil já em uso em outro dispositivo
 */

class SessionModal {
    /**
     * Renderiza o modal de sessão ativa
     */
    public static function render(?array $deviceInfo = null): void {
        ?>
        <div id="sessionModal" class="session-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="sm-title">
            <div class="session-modal">
                <p class="sm-oops">Oops!</p>
                <h2 class="sm-title" id="sm-title">Este perfil ja esta sendo utilizado</h2>
                <p class="sm-body">Por favor, selecione outro perfil.</p>
                <a href="/select-profile" class="sm-btn-back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza o script JavaScript para o modal
     */
    public static function renderScript(): void {
        ?>
        <script>
        window.showSessionModal = function () {
            var modal = document.getElementById('sessionModal');
            if (modal) modal.classList.add('show');
        };
        </script>
        <?php
    }
    
    /**
     * Renderiza CSS inline (opcional, para páginas que não incluem o CSS separado)
     */
    public static function renderInlineCSS(): void {
        ?>
        <style>
        .session-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease, visibility .2s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .session-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .session-modal {
            background: #111113;
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 16px;
            padding: 40px 32px;
            max-width: 360px;
            width: 100%;
            text-align: center;
            transform: translateY(16px);
            opacity: 0;
            transition: transform .25s cubic-bezier(.22, 1, .36, 1), opacity .25s ease;
        }
        .session-modal-overlay.show .session-modal {
            transform: translateY(0);
            opacity: 1;
        }
        .sm-oops {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #555562;
            margin: 0 0 14px;
        }
        .sm-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f0f0f2;
            margin: 0 0 10px;
            line-height: 1.3;
            letter-spacing: -.01em;
        }
        .sm-body {
            font-size: .875rem;
            color: #6b6b7a;
            line-height: 1.6;
            margin: 0 0 28px;
        }
        .sm-btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 22px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            color: #c8c8d0;
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s, border-color .15s, color .15s;
        }
        .sm-btn-back svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }
        .sm-btn-back:hover {
            background: rgba(255, 255, 255, .06);
            border-color: rgba(255, 255, 255, .2);
            color: #fff;
        }
        @media (max-width: 480px) {
            .session-modal { padding: 32px 20px; }
        }
        </style>
        <?php
    }
}
?>
