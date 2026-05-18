ALTER TABLE ads_accounts
    ADD COLUMN first_ad_demo_claimed_at DATETIME NULL AFTER onboarding_completed_at;

ALTER TABLE ads_campaigns
    ADD COLUMN draft_token CHAR(64) NULL AFTER ads_account_id,
    ADD COLUMN cdn_token CHAR(64) NULL AFTER draft_token,
    ADD COLUMN description VARCHAR(500) NULL AFTER name,
    MODIFY COLUMN creative_url VARCHAR(500) NOT NULL DEFAULT '',
    MODIFY COLUMN creative_duration_seconds SMALLINT UNSIGNED NULL,
    ADD COLUMN creative_mime_type VARCHAR(80) NULL AFTER creative_duration_seconds,
    ADD COLUMN media_provider ENUM('imgbb','vids_st') NULL AFTER creative_mime_type,
    ADD COLUMN media_provider_file_id VARCHAR(120) NULL AFTER media_provider,
    ADD COLUMN original_filename VARCHAR(255) NULL AFTER media_provider_file_id,
    ADD COLUMN file_size_bytes BIGINT UNSIGNED NULL AFTER original_filename,
    ADD COLUMN price_cents INT UNSIGNED NOT NULL DEFAULT 1000 AFTER can_skip,
    ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER price_cents,
    MODIFY COLUMN status ENUM('draft','awaiting_payment','pending_review','in_review','changes_requested','approved','active','paused','rejected','finished') NOT NULL DEFAULT 'draft',
    ADD COLUMN submitted_at DATETIME NULL AFTER status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER submitted_at,
    ADD COLUMN review_notes VARCHAR(500) NULL AFTER reviewed_at,
    ADD UNIQUE KEY uq_ads_campaigns_draft_token (draft_token),
    ADD UNIQUE KEY uq_ads_campaigns_cdn_token (cdn_token),
    ADD KEY idx_ads_campaigns_review_queue (status, submitted_at);

CREATE TABLE IF NOT EXISTS ads_campaign_status_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(40) NULL,
    to_status VARCHAR(40) NOT NULL,
    note VARCHAR(255) NULL,
    actor_type ENUM('system','advertiser','admin') NOT NULL DEFAULT 'system',
    actor_admin_id INT NULL,
    public_note VARCHAR(500) NULL,
    internal_note VARCHAR(500) NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ads_campaign_status_events_campaign FOREIGN KEY (campaign_id) REFERENCES ads_campaigns(id),
    KEY idx_ads_campaign_status_events_campaign (campaign_id, created_at),
    KEY idx_ads_campaign_status_events_actor (actor_type, actor_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
