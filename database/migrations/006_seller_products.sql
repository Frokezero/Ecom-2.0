ALTER TABLE products ADD COLUMN seller_id INT UNSIGNED NULL AFTER category_id;
ALTER TABLE products ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER is_featured;
ALTER TABLE products ADD COLUMN admin_note VARCHAR(500) NULL AFTER approval_status;
ALTER TABLE products ADD CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE products ADD INDEX idx_products_seller_approval (seller_id,approval_status);
