-- Banco Pipocine: execute no database `pipcine`.
-- Moderacao de contas, logs de usuario e auditoria administrativa.

ALTER TABLE platform_users
    ADD COLUMN IF NOT EXISTS moderation_status ENUM('active','suspended','banned') NOT NULL DEFAULT 'active' AFTER status,
    ADD COLUMN IF NOT EXISTS moderation_reason VARCHAR(500) NULL AFTER moderation_status,
    ADD COLUMN IF NOT EXISTS moderation_until DATETIME NULL AFTER moderation_reason,
    ADD COLUMN IF NOT EXISTS moderated_by INT NULL AFTER moderation_until,
    ADD COLUMN IF NOT EXISTS moderated_at DATETIME NULL AFTER moderated_by;

CREATE TABLE IF NOT EXISTS platform_user_activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pual_user_created (user_id, created_at),
    KEY idx_pual_event (event_type),
    CONSTRAINT fk_pual_user
        FOREIGN KEY (user_id) REFERENCES platform_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_user_moderation_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    admin_id INT NULL,
    action ENUM('suspend','ban','reactivate') NOT NULL,
    reason VARCHAR(500) NULL,
    duration_minutes INT NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pumh_user_created (user_id, created_at),
    KEY idx_pumh_admin (admin_id),
    CONSTRAINT fk_pumh_user
        FOREIGN KEY (user_id) REFERENCES platform_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
