CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_days INT NOT NULL DEFAULT 30,
    device_limit INT NOT NULL DEFAULT 1,
    profile_limit INT NOT NULL DEFAULT 2,
    family_member_limit INT NOT NULL DEFAULT 0,
    benefits_json JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO subscription_plans
    (code, name, price, duration_days, device_limit, profile_limit, family_member_limit, benefits_json)
VALUES
    ('casual', 'Plano Casual', 0.00, 30, 1, 2, 0, JSON_ARRAY(
        '1 dispositivo',
        '2 perfis',
        'Acesso completo ao catálogo',
        '1 download por dia',
        'Com anúncios',
        'Qualidade até 1080p'
    )),
    ('gold', 'Plano Gold', 20.99, 30, 4, 8, 2, JSON_ARRAY(
        'Aplicativo Mobile Pipocine',
        'Download offline de filmes e séries',
        '4 dispositivos',
        '8 perfis',
        'Até 2 membros na família',
        'Download ilimitado no aplicativo',
        'Filmes e séries em qualidade 2K',
        'Personalização de perfil',
        'Mais camadas de segurança para perfil',
        'Suporte prioritário',
        'Sem anúncios'
    ))
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price = VALUES(price),
    duration_days = VALUES(duration_days),
    device_limit = VALUES(device_limit),
    profile_limit = VALUES(profile_limit),
    family_member_limit = VALUES(family_member_limit),
    benefits_json = VALUES(benefits_json),
    is_active = 1;

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_code VARCHAR(40) NOT NULL,
    status ENUM('active','expired','canceled') NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    canceled_at DATETIME NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_id BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_status_expiry (user_id, status, expires_at),
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscription_payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_code VARCHAR(40) NOT NULL,
    provider VARCHAR(40) NOT NULL DEFAULT 'evopay',
    provider_txid VARCHAR(160) NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','canceled','expired','failed') NOT NULL DEFAULT 'pending',
    qr_code TEXT NULL,
    qr_code_image TEXT NULL,
    pix_payload JSON NULL,
    checkout_payload JSON NULL,
    cancel_reason VARCHAR(255) NULL,
    paid_at DATETIME NULL,
    canceled_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_status (user_id, status),
    INDEX idx_provider_txid (provider_txid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscription_activation_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    session_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    consumed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_payment (user_id, payment_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscription_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    payment_id BIGINT NULL,
    subscription_id BIGINT NULL,
    event_type VARCHAR(80) NOT NULL,
    payload JSON NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_event (user_id, event_type),
    INDEX idx_payment_event (payment_id, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
