ALTER TABLE ads_campaigns
    MODIFY COLUMN status ENUM('draft','awaiting_payment','pending_review','in_review','changes_requested','approved','active','paused','rejected','finished') NOT NULL DEFAULT 'draft',
    ADD COLUMN review_lock_admin_id INT NULL AFTER review_notes,
    ADD COLUMN review_lock_expires_at DATETIME NULL AFTER review_lock_admin_id,
    ADD COLUMN review_started_at DATETIME NULL AFTER review_lock_expires_at,
    ADD COLUMN reviewed_by_admin_id INT NULL AFTER review_started_at,
    ADD COLUMN approved_at DATETIME NULL AFTER reviewed_by_admin_id,
    ADD COLUMN activated_at DATETIME NULL AFTER approved_at,
    ADD COLUMN rejected_at DATETIME NULL AFTER activated_at,
    ADD COLUMN changes_requested_at DATETIME NULL AFTER rejected_at,
    ADD KEY idx_ads_campaigns_review_queue (status, submitted_at),
    ADD KEY idx_ads_campaigns_review_lock (review_lock_admin_id, review_lock_expires_at);

ALTER TABLE ads_campaign_status_events
    ADD COLUMN actor_type ENUM('system','advertiser','admin') NOT NULL DEFAULT 'system' AFTER note,
    ADD COLUMN actor_admin_id INT NULL AFTER actor_type,
    ADD COLUMN public_note VARCHAR(500) NULL AFTER actor_admin_id,
    ADD COLUMN internal_note VARCHAR(500) NULL AFTER public_note,
    ADD COLUMN metadata JSON NULL AFTER internal_note,
    ADD KEY idx_ads_campaign_status_events_actor (actor_type, actor_admin_id);
