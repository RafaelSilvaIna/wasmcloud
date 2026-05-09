-- Execute no banco `pipcine`.
-- Login por QR Code: uso unico, expiracao curta, logs e ajuste por usuario.

CREATE TABLE IF NOT EXISTS platform_user_security (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    qr_login_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_platform_user_security_user
        FOREIGN KEY (user_id) REFERENCES platform_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_qr_login_challenges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL,
    verifier_hash CHAR(64) NOT NULL,
    status ENUM('pending', 'approved', 'consumed', 'expired', 'rejected') NOT NULL DEFAULT 'pending',
    approved_user_id INT UNSIGNED NULL,
    transfer_token_hash CHAR(64) NULL,
    requester_ip VARCHAR(45) NULL,
    requester_user_agent VARCHAR(255) NULL,
    scanner_ip VARCHAR(45) NULL,
    scanner_user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    consumed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_qr_login_token_hash (token_hash),
    KEY idx_qr_login_status_expires (status, expires_at),
    KEY idx_qr_login_approved_user (approved_user_id),
    CONSTRAINT fk_qr_login_approved_user
        FOREIGN KEY (approved_user_id) REFERENCES platform_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_qr_login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    status VARCHAR(24) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_qr_logs_user_created (user_id, created_at),
    CONSTRAINT fk_qr_logs_user
        FOREIGN KEY (user_id) REFERENCES platform_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
