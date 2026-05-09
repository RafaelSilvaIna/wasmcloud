-- Banco Pipocine: execute no database `pipcine`.
-- Estrutura para login nativo da plataforma em /api/v4/auth/*.
-- O identificador de acesso pode ser email ou celular.

CREATE TABLE IF NOT EXISTS platform_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    avatar_url VARCHAR(255) NULL,
    status ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_users_email (email),
    UNIQUE KEY uk_platform_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizacao para bancos onde a tabela ja foi criada antes do suporte a celular.
ALTER TABLE platform_users MODIFY email VARCHAR(190) NULL;
ALTER TABLE platform_users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email;
CREATE UNIQUE INDEX IF NOT EXISTS uk_platform_users_phone ON platform_users (phone);

CREATE TABLE IF NOT EXISTS platform_user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_user_sessions_token (token_hash),
    KEY idx_platform_user_sessions_user (user_id),
    KEY idx_platform_user_sessions_expires (expires_at),
    CONSTRAINT fk_platform_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES platform_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A verificacao em duas etapas reaproveita as tabelas v4 ja existentes.
-- Se ainda nao executou os scripts de 2FA, execute tambem:
--   database/create_2fa_tables.sql
--   database/create_2fa_login_challenges.sql
