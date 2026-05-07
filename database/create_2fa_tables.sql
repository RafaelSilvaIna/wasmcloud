-- ============================================================
-- TABELAS PARA VERIFICAÇÃO EM DUAS ETAPAS (2FA)
-- Google Authenticator / TOTP
-- ============================================================

-- Tabela principal de configuração 2FA dos usuários
CREATE TABLE IF NOT EXISTS user_two_factor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    secret_key VARCHAR(32) NOT NULL COMMENT 'Chave secreta TOTP (base32)',
    backup_codes TEXT COMMENT 'Códigos de backup (JSON array)',
    is_enabled TINYINT(1) DEFAULT 0 COMMENT '2FA ativado?',
    verified_at DATETIME NULL COMMENT 'Quando foi verificado pela primeira vez',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_user_id (user_id),
    KEY idx_enabled (is_enabled)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuração de 2FA dos usuários';

-- Tabela de logs de eventos 2FA (ativação, desativação, tentativas)
CREATE TABLE IF NOT EXISTS two_factor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'enable, disable, verify, backup_used, etc',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL COMMENT 'success, failed',
    details TEXT NULL COMMENT 'Detalhes adicionais (JSON)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user_id (user_id),
    KEY idx_action (action),
    KEY idx_created_at (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de eventos 2FA';

-- Tabela de dispositivos confiáveis ("lembrar este dispositivo")
CREATE TABLE IF NOT EXISTS two_factor_trusted_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_token VARCHAR(64) NOT NULL COMMENT 'Hash único do dispositivo',
    device_name VARCHAR(100) NULL COMMENT 'Nome do dispositivo (navegador/OS)',
    ip_address VARCHAR(45) NULL,
    expires_at DATETIME NOT NULL COMMENT 'Quando o trust expira',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_user_device (user_id, device_token),
    KEY idx_expires (expires_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispositivos confiáveis para 2FA';

-- Tabela de tentativas de verificação 2FA (rate limiting)
CREATE TABLE IF NOT EXISTS two_factor_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user_ip (user_id, ip_address),
    KEY idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tentativas de verificação 2FA';
