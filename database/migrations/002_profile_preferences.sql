-- Run once for databases created before the profile preferences feature.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS preferred_payment_method ENUM('promptpay','cod') NOT NULL DEFAULT 'promptpay' AFTER address;
