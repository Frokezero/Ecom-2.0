USE `kitchenmart_db`;
CREATE TABLE IF NOT EXISTS order_fulfillments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, seller_id INT UNSIGNED NOT NULL,
 status ENUM('pending','accepted','packing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending', carrier VARCHAR(80) NULL, tracking_number VARCHAR(120) NULL,
 shipped_at DATETIME NULL, delivered_at DATETIME NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_fulfillment_order_seller(order_id,seller_id), INDEX idx_fulfillment_seller_status(seller_id,status),
 CONSTRAINT fk_fulfillment_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, CONSTRAINT fk_fulfillment_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS user_addresses (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,label VARCHAR(50) NOT NULL DEFAULT 'บ้าน',recipient_name VARCHAR(100) NOT NULL,phone VARCHAR(20) NOT NULL,
 address_line TEXT NOT NULL,subdistrict VARCHAR(100) NOT NULL,district VARCHAR(100) NOT NULL,province VARCHAR(100) NOT NULL,postal_code CHAR(5) NOT NULL,is_default TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_address_user_default(user_id,is_default),CONSTRAINT fk_address_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS wishlists (user_id INT UNSIGNED NOT NULL,product_id INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(user_id,product_id),CONSTRAINT fk_wishlist_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,CONSTRAINT fk_wishlist_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS product_images (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,image_url VARCHAR(255) NOT NULL,sort_order SMALLINT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_product_images(product_id,sort_order),CONSTRAINT fk_product_image_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS product_variants (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,sku VARCHAR(80) NOT NULL,name VARCHAR(160) NOT NULL,price DECIMAL(10,2) UNSIGNED NOT NULL,stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_variant_sku(sku),INDEX idx_variant_product_active(product_id,is_active),CONSTRAINT fk_variant_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
