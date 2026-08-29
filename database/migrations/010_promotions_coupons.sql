USE `kitchenmart_db`;
CREATE TABLE IF NOT EXISTS coupons (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE, title VARCHAR(160) NOT NULL, description VARCHAR(500) NULL,
 discount_type ENUM('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent',
 discount_value DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, max_discount DECIMAL(10,2) UNSIGNED NULL,
 min_order_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, usage_limit INT UNSIGNED NULL, per_user_limit INT UNSIGNED NOT NULL DEFAULT 1,
 category_id INT UNSIGNED NULL, product_id INT UNSIGNED NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_coupon_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 CONSTRAINT fk_coupon_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
 INDEX idx_coupon_active_dates(is_active,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS user_coupons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coupon_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, used_count INT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_user_coupon(coupon_id,user_id), CONSTRAINT fk_user_coupon_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
 CONSTRAINT fk_user_coupon_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS coupon_usages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coupon_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, order_id INT UNSIGNED NOT NULL,
 discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_coupon_order(order_id), CONSTRAINT fk_coupon_usage_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE RESTRICT,
 CONSTRAINT fk_coupon_usage_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_coupon_usage_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS promotional_banners (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, subtitle VARCHAR(300) NULL,
 image_desktop VARCHAR(255) NOT NULL, image_mobile VARCHAR(255) NULL, button_label VARCHAR(60) NULL, target_url VARCHAR(500) NULL,
 placement ENUM('hero','category','cart','floating') NOT NULL DEFAULT 'hero', category_id INT UNSIGNED NULL, coupon_id INT UNSIGNED NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_banner_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 CONSTRAINT fk_banner_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
 INDEX idx_banner_active(placement,is_active,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_id INT UNSIGNED NULL AFTER total_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(40) NULL AFTER coupon_id;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER coupon_code;
SET @fk_orders_coupon_exists=(SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_orders_coupon');
SET @fk_orders_coupon_sql=IF(@fk_orders_coupon_exists=0,'ALTER TABLE orders ADD CONSTRAINT fk_orders_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE SET NULL','SELECT 1');
PREPARE fk_orders_coupon_stmt FROM @fk_orders_coupon_sql;EXECUTE fk_orders_coupon_stmt;DEALLOCATE PREPARE fk_orders_coupon_stmt;
