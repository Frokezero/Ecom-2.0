USE `kitchenmart_db`;
CREATE TABLE IF NOT EXISTS inventory_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, variant_id INT UNSIGNED NULL,
 movement_type ENUM('initial','manual_add','manual_remove','sale','return','adjustment','import') NOT NULL,
 quantity_change INT NOT NULL, quantity_before INT UNSIGNED NOT NULL, quantity_after INT UNSIGNED NOT NULL, note VARCHAR(255) NULL,
 created_by INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_inventory_seller_created(seller_id,created_at), INDEX idx_inventory_product(product_id,created_at),
 CONSTRAINT fk_inventory_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_inventory_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_inventory_variant FOREIGN KEY(variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
 CONSTRAINT fk_inventory_actor FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_strikes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL, severity ENUM('warning','major','critical') NOT NULL DEFAULT 'warning',
 reason VARCHAR(500) NOT NULL, points TINYINT UNSIGNED NOT NULL DEFAULT 1, status ENUM('active','appealed','resolved') NOT NULL DEFAULT 'active',
 issued_by INT UNSIGNED NULL, expires_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, resolved_at DATETIME NULL,
 INDEX idx_strike_seller_status(seller_id,status), CONSTRAINT fk_strike_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_strike_admin FOREIGN KEY(issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
