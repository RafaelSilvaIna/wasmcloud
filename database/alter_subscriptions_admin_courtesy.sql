-- Execute no banco `pipcine`.
-- Atualizacao minima para diferenciar assinatura paga de cortesia administrativa.

ALTER TABLE user_subscriptions
    ADD COLUMN IF NOT EXISTS source ENUM('paid','admin_courtesy') NOT NULL DEFAULT 'paid' AFTER status,
    ADD COLUMN IF NOT EXISTS granted_by_admin_id INT NULL AFTER payment_id,
    ADD COLUMN IF NOT EXISTS grant_reason VARCHAR(255) NULL AFTER granted_by_admin_id;

CREATE INDEX IF NOT EXISTS idx_user_subscriptions_source ON user_subscriptions (source, status, expires_at);
