USE `kitchenmart_db`;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER order_status, ADD INDEX IF NOT EXISTS idx_orders_demo(is_demo,created_at);
ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER comment, ADD INDEX IF NOT EXISTS idx_reviews_demo(product_id,is_demo);
