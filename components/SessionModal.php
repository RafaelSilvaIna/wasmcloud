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
        $deviceInfo = $deviceInfo ?? [];
        ?>
        
        <!-- Modal de Sessão Ativa -->
        <div id="sessionModal" class="session-modal-overlay">
            <div class="session-modal">
                <!-- Logo PipoCine -->
                <img src="/assets/img/logo-pipocine.png" alt="PipoCine" class="session-modal-logo">
                
                <!-- Header -->
                <div class="session-modal-header">
                    <div class="session-modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h2 class="session-modal-title">Perfil em Uso</h2>
                    <p class="session-modal-subtitle">Este perfil já está sendo utilizado em outro dispositivo</p>
                </div>
                
                <!-- Content -->
                <div class="session-modal-content">
                    <p class="session-modal-message">
                        Por segurança, permitimos apenas uma sessão ativa por perfil. Para continuar, 
                        encerre a sessão no outro dispositivo ou tente novamente mais tarde.
                    </p>
                    
                    <?php if (!empty($deviceInfo)): ?>
                    <div class="session-modal-device-info">
                        <div class="session-modal-device-label">Dispositivo Ativo</div>
                        <div class="session-modal-device-value">
                            <?= htmlspecialchars($deviceInfo['device'] ?? 'Dispositivo desconhecido') ?>
                        </div>
                        <?php if (!empty($deviceInfo['location'])): ?>
                        <div class="session-modal-device-label" style="margin-top: 8px;">Localização</div>
                        <div class="session-modal-device-value">
                            <?= htmlspecialchars($deviceInfo['location']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($deviceInfo['time'])): ?>
                        <div class="session-modal-device-label" style="margin-top: 8px;">Última atividade</div>
                        <div class="session-modal-device-value">
                            <?= htmlspecialchars($deviceInfo['time']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="session-modal-actions">
                    <button id="sessionModalOk" class="session-modal-btn session-modal-btn-primary">
                        Entendido
                    </button>
                    <a href="/select-profile" class="session-modal-btn session-modal-btn-secondary">
                        Escolher Outro Perfil
                    </a>
                </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('sessionModal');
            const okBtn = document.getElementById('sessionModalOk');
            
            // Função para mostrar o modal
            window.showSessionModal = function(deviceInfo) {
                // Atualiza informações do dispositivo se fornecidas
                if (deviceInfo) {
                    updateDeviceInfo(deviceInfo);
                }
                
                // Adiciona classe show após um pequeno delay para animação
                setTimeout(() => {
                    modal.classList.add('show');
                }, 100);
                
                // Impede navegação para trás
                history.pushState(null, null, location.href);
                window.addEventListener('popstate', preventBack);
            };
            
            // Função para esconder o modal
            window.hideSessionModal = function() {
                modal.classList.remove('show');
                window.removeEventListener('popstate', preventBack);
                
                // Redireciona para select-profile após animação
                setTimeout(() => {
                    window.location.href = '/select-profile';
                }, 300);
            };
            
            // Previne navegação para trás
            function preventBack(e) {
                e.preventDefault();
                history.pushState(null, null, location.href);
            }
            
            // Atualiza informações do dispositivo no modal
            function updateDeviceInfo(info) {
                const deviceValue = modal.querySelector('.session-modal-device-value');
                if (deviceValue && info.device) {
                    deviceValue.textContent = info.device;
                }
            }
            
            // Botão OK
            okBtn.addEventListener('click', function() {
                this.classList.add('loading');
                setTimeout(() => {
                    hideSessionModal();
                }, 500);
            });
            
            // Fecha modal ao clicar no overlay (desabilitado para segurança)
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    // Não permite fechar clicando fora por segurança
                    return;
                }
            });
            
            // Tecla ESC (desabilitada por segurança)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    // Não permite fechar com ESC por segurança
                    return;
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderiza CSS inline (opcional, para páginas que não incluem o CSS separado)
     */
    public static function renderInlineCSS(): void {
        ?>
        <style>
        /* CSS inline para o modal (versão simplificada) */
        .session-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            padding: 20px;
        }
        
        .session-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .session-modal {
            background: linear-gradient(135deg, #141414 0%, #1a1a1a 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            max-width: 480px;
            width: 100%;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        
        .session-modal-overlay.show .session-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        
        .session-modal-header {
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .session-modal-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #e50914 0%, #b20610 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(229, 9, 20, 0.3);
        }
        
        .session-modal-icon svg {
            width: 32px;
            height: 32px;
            color: #fff;
        }
        
        .session-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 12px;
            letter-spacing: -0.02em;
        }
        
        .session-modal-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
            margin: 0;
        }
        
        .session-modal-content {
            padding: 24px 32px;
        }
        
        .session-modal-message {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin: 0 0 20px;
        }
        
        .session-modal-actions {
            padding: 0 32px 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .session-modal-btn {
            padding: 14px 24px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
            display: block;
            width: 100%;
        }
        
        .session-modal-btn-primary {
            background: linear-gradient(135deg, #e50914 0%, #b20610 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
        }
        
        .session-modal-btn-primary:hover {
            background: linear-gradient(135deg, #f51520 0%, #c20610 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }
        
        .session-modal-btn-secondary {
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .session-modal-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.25);
        }
        
        .session-modal-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 80px;
            height: auto;
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }
        
        .session-modal:hover .session-modal-logo {
            opacity: 0.5;
        }
        
        @media (max-width: 640px) {
            .session-modal {
                margin: 20px;
                max-width: none;
            }
            
            .session-modal-header {
                padding: 24px 20px 20px;
            }
            
            .session-modal-content {
                padding: 20px;
            }
            
            .session-modal-actions {
                padding: 0 20px 24px;
            }
            
            .session-modal-title {
                font-size: 1.3rem;
            }
            
            .session-modal-icon {
                width: 56px;
                height: 56px;
            }
            
            .session-modal-icon svg {
                width: 28px;
                height: 28px;
            }
            
            .session-modal-logo {
                width: 60px;
                top: 16px;
                left: 16px;
            }
        }
        </style>
        <?php
    }
}
?>
