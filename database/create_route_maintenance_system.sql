CREATE TABLE IF NOT EXISTS pipocine_route_locks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_path VARCHAR(255) NOT NULL,
    match_type ENUM('exact','prefix','regex') NOT NULL DEFAULT 'exact',
    page_file VARCHAR(255) NULL,
    route_label VARCHAR(160) NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    maintenance_title VARCHAR(120) NOT NULL DEFAULT 'Pagina em manutencao',
    maintenance_message VARCHAR(500) NULL,
    locked_by_admin_id BIGINT UNSIGNED NULL,
    locked_at DATETIME NULL,
    unlocked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_prl_route_type (route_path, match_type),
    KEY idx_prl_locked (is_locked, match_type),
    KEY idx_prl_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipocine_frontend_route_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id CHAR(32) NOT NULL,
    method VARCHAR(12) NOT NULL,
    path VARCHAR(255) NOT NULL,
    matched_route VARCHAR(255) NULL,
    page_file VARCHAR(255) NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    was_locked TINYINT(1) NOT NULL DEFAULT 0,
    lock_id BIGINT UNSIGNED NULL,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    referer VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pfrl_created (created_at),
    KEY idx_pfrl_path_created (path, created_at),
    KEY idx_pfrl_locked_created (was_locked, created_at),
    KEY idx_pfrl_duration (duration_ms)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
