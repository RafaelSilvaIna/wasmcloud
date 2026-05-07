-- ============================================================
-- TABELA: profile_pins
-- Descrição: Armazena PINs de segurança de 4 dígitos por usuário
-- para acesso a funcionalidades extras no Pipocine
-- ============================================================

CREATE TABLE IF NOT EXISTS profile_pins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    pin_hash VARCHAR(255) NOT NULL COMMENT 'Hash SHA-256 do PIN de 4 dígitos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índice único por usuário (apenas 1 PIN por usuário)
    UNIQUE KEY unique_user_pin (user_id),
    
    -- Índice para busca rápida
    KEY idx_user_id (user_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabela de PINs de segurança para usuários Pipocine (sem ligação com Cineveo)';

-- ============================================================
-- TABELA: pin_attempts
-- Descrição: Registra tentativas de acesso ao PIN para rate limiting
-- ============================================================

CREATE TABLE IF NOT EXISTS pin_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 ou IPv6',
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    
    -- Índices para limpeza e busca
    KEY idx_user_ip (user_id, ip_address),
    KEY idx_attempted_at (attempted_at),
    KEY idx_user_time (user_id, attempted_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de tentativas de validação de PIN para rate limiting';
