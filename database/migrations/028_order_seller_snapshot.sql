USE `kitchenmart_db`;

ALTER TABLE order_items
 ADD COLUMN IF NOT EXISTS seller_id INT UNSIGNED NULL AFTER product_id,
 ADD INDEX IF NOT EXISTS idx_order_items_seller_order(seller_id,order_id);

UPDATE order_items oi JOIN products p ON p.id=oi.product_id
SET oi.seller_id=p.seller_id WHERE oi.seller_id IS NULL AND p.seller_id IS NOT NULL;

