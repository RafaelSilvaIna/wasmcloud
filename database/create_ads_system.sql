CREATE TABLE IF NOT EXISTS ads_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pipocine_user_id BIGINT UNSIGNED NULL,
    brand_name VARCHAR(120) NOT NULL,
    cnpj CHAR(14) NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    contact_name VARCHAR(120) NULL,
    phone_e164 VARCHAR(24) NULL,
    industry VARCHAR(80) NULL,
    company_size ENUM('solo','small','medium','large') NULL,
    business_description VARCHAR(280) NULL,
    onboarding_completed_at DATETIME NULL,
    status ENUM('active','pending_review','suspended') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ads_accounts_email (email),
    UNIQUE KEY uq_ads_accounts_cnpj (cnpj),
    UNIQUE KEY uq_ads_accounts_pipocine_user (pipocine_user_id),
    KEY idx_ads_accounts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ads_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ads_account_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    creative_type ENUM('image','video') NOT NULL,
    creative_url VARCHAR(500) NOT NULL,
    creative_duration_seconds TINYINT UNSIGNED NULL,
    redirect_url VARCHAR(500) NULL,
    can_skip TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('draft','pending_review','active','paused','rejected','finished') NOT NULL DEFAULT 'draft',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ads_campaigns_account FOREIGN KEY (ads_account_id) REFERENCES ads_accounts(id),
    KEY idx_ads_campaigns_account_status (ads_account_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ads_daily_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    metric_date DATE NOT NULL,
    impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
    clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
    conversions BIGINT UNSIGNED NOT NULL DEFAULT 0,
    unique_reach BIGINT UNSIGNED NOT NULL DEFAULT 0,
    completed_views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ads_daily_metrics_campaign FOREIGN KEY (campaign_id) REFERENCES ads_campaigns(id),
    UNIQUE KEY uq_ads_daily_metrics_campaign_date (campaign_id, metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ads_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ads_account_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ads_audit_account (ads_account_id),
    KEY idx_ads_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
