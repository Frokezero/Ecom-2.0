ALTER TABLE seller_profiles
    ADD COLUMN store_status ENUM('active','suspended','closed') NOT NULL DEFAULT 'active' AFTER status,
    ADD COLUMN product_submission_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER store_status,
    ADD COLUMN order_processing_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER product_submission_enabled,
    ADD COLUMN payout_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER order_processing_enabled,
    ADD COLUMN suspension_reason VARCHAR(500) NULL AFTER payout_enabled,
    ADD COLUMN suspended_at DATETIME NULL AFTER suspension_reason,
    ADD COLUMN suspended_by INT UNSIGNED NULL AFTER suspended_at,
    ADD INDEX idx_seller_store_status(status,store_status),
    ADD CONSTRAINT fk_seller_suspended_by FOREIGN KEY(suspended_by) REFERENCES users(id) ON DELETE SET NULL;
