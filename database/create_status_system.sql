CREATE TABLE IF NOT EXISTS pipocine_status_components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    component_key VARCHAR(90) NOT NULL,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    is_critical TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_psc_key (component_key),
    KEY idx_psc_parent (parent_id),
    KEY idx_psc_public_order (is_public, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipocine_status_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    incident_type VARCHAR(40) NOT NULL DEFAULT 'incident',
    category VARCHAR(80) NOT NULL DEFAULT 'Degraded Performance',
    impact VARCHAR(40) NOT NULL DEFAULT 'degraded_performance',
    status VARCHAR(40) NOT NULL DEFAULT 'investigating',
    visibility VARCHAR(20) NOT NULL DEFAULT 'public',
    public_description TEXT NULL,
    internal_description TEXT NULL,
    systems_affected TEXT NULL,
    started_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    scheduled_start_at DATETIME NULL,
    scheduled_end_at DATETIME NULL,
    owner_admin_id BIGINT UNSIGNED NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    archived_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_psi_slug (slug),
    KEY idx_psi_status_impact (status, impact),
    KEY idx_psi_visibility_started (visibility, started_at),
    KEY idx_psi_type (incident_type),
    KEY idx_psi_owner (owner_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipocine_status_incident_components (
    incident_id BIGINT UNSIGNED NOT NULL,
    component_id BIGINT UNSIGNED NOT NULL,
    impact VARCHAR(40) NOT NULL DEFAULT 'degraded_performance',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (incident_id, component_id),
    KEY idx_psic_component (component_id),
    KEY idx_psic_impact (impact)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipocine_status_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incident_id BIGINT UNSIGNED NOT NULL,
    update_type VARCHAR(60) NOT NULL DEFAULT 'Update',
    status VARCHAR(40) NOT NULL,
    impact VARCHAR(40) NOT NULL,
    public_message TEXT NULL,
    internal_note TEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_psu_incident_created (incident_id, created_at),
    KEY idx_psu_public_created (is_public, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipocine_status_internal_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incident_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    message VARCHAR(500) NULL,
    payload_json JSON NULL,
    admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_psil_incident_created (incident_id, created_at),
    KEY idx_psil_action_created (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pipocine_status_components
    (parent_id, component_key, name, description, is_public, is_critical, sort_order)
VALUES
    (NULL, 'platform', 'PipoCine Platform', 'Core public experience.', 1, 1, 10)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order);

INSERT INTO pipocine_status_components
    (parent_id, component_key, name, description, is_public, is_critical, sort_order)
SELECT p.id, x.component_key, x.name, x.description, x.is_public, x.is_critical, x.sort_order
FROM pipocine_status_components p
JOIN (
    SELECT 'web-app' AS component_key, 'Web App' AS name, 'Frontend pages and user sessions.' AS description, 1 AS is_public, 1 AS is_critical, 20 AS sort_order
    UNION ALL SELECT 'api', 'API', 'Public and authenticated API surface.', 1, 1, 30
    UNION ALL SELECT 'auth', 'Auth Service', 'Login, sessions, profiles and device checks.', 1, 1, 40
    UNION ALL SELECT 'player', 'Player', 'Playback, streams and watch experience.', 1, 1, 50
    UNION ALL SELECT 'database', 'Database', 'Primary data layer.', 1, 1, 60
    UNION ALL SELECT 'cdn', 'CDN', 'Static and media delivery.', 1, 0, 70
    UNION ALL SELECT 'ads', 'Ads', 'Advertiser dashboard and campaigns.', 1, 0, 80
    UNION ALL SELECT 'support', 'Support', 'Support chat and ticket flow.', 1, 0, 90
) x
WHERE p.component_key = 'platform'
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    description = VALUES(description),
    is_public = VALUES(is_public),
    is_critical = VALUES(is_critical),
    sort_order = VALUES(sort_order);
