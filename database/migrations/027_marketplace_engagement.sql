USE `kitchenmart_db`;

ALTER TABLE products
 ADD COLUMN IF NOT EXISTS compare_at_price DECIMAL(10,2) UNSIGNED NULL AFTER price,
 ADD COLUMN IF NOT EXISTS sale_price DECIMAL(10,2) UNSIGNED NULL AFTER compare_at_price,
 ADD COLUMN IF NOT EXISTS sale_starts_at DATETIME NULL AFTER sale_price,
 ADD COLUMN IF NOT EXISTS sale_ends_at DATETIME NULL AFTER sale_starts_at,
 ADD COLUMN IF NOT EXISTS sale_stock INT UNSIGNED NULL AFTER sale_ends_at,
 ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) NULL AFTER image_url,
 ADD COLUMN IF NOT EXISTS fulfillment_type ENUM('platform','seller') NOT NULL DEFAULT 'seller' AFTER video_url;

ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL AFTER name;
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS response_minutes INT UNSIGNED NOT NULL DEFAULT 0 AFTER promo_url;

CREATE TABLE IF NOT EXISTS store_followers (
 seller_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(seller_id,user_id), CONSTRAINT fk_follow_store FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_follow_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_follow_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, reporter_id INT UNSIGNED NOT NULL,
 reason ENUM('counterfeit','prohibited','misleading','inappropriate','other') NOT NULL, detail VARCHAR(1000) NULL,
 status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open', admin_note VARCHAR(1000) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL, reviewed_by INT UNSIGNED NULL,
 CONSTRAINT fk_report_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_user FOREIGN KEY(reporter_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_admin FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_report_status(status,created_at), UNIQUE KEY uq_report_product_user(product_id,reporter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_conversations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL, customer_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NULL, last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_conversation(seller_id,customer_id,product_id), INDEX idx_conversation_seller(seller_id,last_message_at),
 INDEX idx_conversation_customer(customer_id,last_message_at), CONSTRAINT fk_conversation_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_conversation_customer FOREIGN KEY(customer_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_conversation_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL, sender_id INT UNSIGNED NOT NULL,
 message VARCHAR(2000) NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_message_conversation FOREIGN KEY(conversation_id) REFERENCES store_conversations(id) ON DELETE CASCADE,
 CONSTRAINT fk_message_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_message_conversation(conversation_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_bundles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NULL, title VARCHAR(160) NOT NULL,
 discount_percent DECIMAL(5,2) UNSIGNED NOT NULL, minimum_items TINYINT UNSIGNED NOT NULL DEFAULT 2,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
 CONSTRAINT fk_bundle_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_bundle_active(is_active,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_bundle_items (
 bundle_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, PRIMARY KEY(bundle_id,product_id),
 CONSTRAINT fk_bundle_item_bundle FOREIGN KEY(bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
 CONSTRAINT fk_bundle_item_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE coupons ADD COLUMN IF NOT EXISTS seller_id INT UNSIGNED NULL AFTER id;
