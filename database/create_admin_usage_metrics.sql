-- Banco Pipocine: execute no database `pipcine`.
-- Metricas globais de uso, requisicoes e consumo estimado de banda/rede.

CREATE TABLE IF NOT EXISTS pipocine_request_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id CHAR(32) NOT NULL,
    method VARCHAR(12) NOT NULL,
    path VARCHAR(255) NOT NULL,
    route_group VARCHAR(80) NOT NULL DEFAULT 'frontend',
    is_api TINYINT(1) NOT NULL DEFAULT 0,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    request_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    response_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    referer VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_prm_created (created_at),
    KEY idx_prm_path_created (path, created_at),
    KEY idx_prm_group_created (route_group, created_at),
    KEY idx_prm_api_created (is_api, created_at),
    KEY idx_prm_status_created (status_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
