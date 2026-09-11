ALTER TABLE coupon_usages ADD INDEX idx_coupon_usage_order(order_id);
ALTER TABLE coupon_usages DROP INDEX uq_coupon_order;
ALTER TABLE coupon_usages ADD UNIQUE KEY uq_coupon_order_pair(order_id,coupon_id);
ALTER TABLE coupons MODIFY per_user_limit INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE orders
 ADD COLUMN shipping_discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_amount,
 ADD COLUMN platform_shipping_subsidy DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_discount_amount,
 ADD COLUMN seller_shipping_subsidy DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER platform_shipping_subsidy;
CREATE TABLE order_coupons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, coupon_id INT UNSIGNED NOT NULL,
 coupon_code VARCHAR(40) NOT NULL, coupon_type ENUM('product_discount','free_shipping') NOT NULL,
 discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, shipping_discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_order_coupon(order_id,coupon_id),
 CONSTRAINT fk_order_coupon_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_order_coupon_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE order_shipping_allocations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, seller_id INT UNSIGNED NULL,
 shipping_amount DECIMAL(10,2) UNSIGNED NOT NULL, customer_paid_amount DECIMAL(10,2) UNSIGNED NOT NULL,
 platform_subsidy_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, seller_subsidy_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
 CONSTRAINT fk_shipping_allocation_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_shipping_allocation_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE RESTRICT,
 UNIQUE KEY uq_order_shipping_seller(order_id,seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
