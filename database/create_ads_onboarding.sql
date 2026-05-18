ALTER TABLE ads_accounts
    ADD COLUMN logo_url VARCHAR(500) NULL AFTER password_hash,
    ADD COLUMN website_url VARCHAR(500) NULL AFTER logo_url,
    ADD COLUMN contact_name VARCHAR(120) NULL AFTER website_url,
    ADD COLUMN phone_e164 VARCHAR(24) NULL AFTER contact_name,
    ADD COLUMN industry VARCHAR(80) NULL AFTER phone_e164,
    ADD COLUMN company_size ENUM('solo','small','medium','large') NULL AFTER industry,
    ADD COLUMN business_description VARCHAR(280) NULL AFTER company_size,
    ADD COLUMN onboarding_completed_at DATETIME NULL AFTER business_description;
