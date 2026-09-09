USE `kitchenmart_db`;
ALTER TABLE coupons ADD COLUMN IF NOT EXISTS mall_only TINYINT(1) NOT NULL DEFAULT 0 AFTER seller_id;
