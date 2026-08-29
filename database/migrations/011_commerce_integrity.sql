-- Commerce integrity foundation. MariaDB 10.3+ / MySQL 8.0+
USE `kitchenmart_db`;

ALTER TABLE orders
 ADD COLUMN IF NOT EXISTS subtotal_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER user_id,
 ADD COLUMN IF NOT EXISTS shipping_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER discount_amount,
 ADD COLUMN IF NOT EXISTS payment_expires_at DATETIME NULL AFTER payment_status;

ALTER TABLE orders MODIFY payment_status ENUM('pending','paid','cod_pending','failed','expired','refunded','partially_refunded') NOT NULL DEFAULT 'pending';

UPDATE orders o
JOIN (SELECT order_id,SUM(subtotal) subtotal FROM order_items GROUP BY order_id) x ON x.order_id=o.id
SET o.subtotal_amount=x.subtotal
WHERE o.subtotal_amount=0;

CREATE TABLE IF NOT EXISTS payment_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 provider VARCHAR(40) NOT NULL,
 provider_reference VARCHAR(120) NULL,
 idempotency_key CHAR(64) NOT NULL,
 amount DECIMAL(10,2) UNSIGNED NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'THB',
 status ENUM('created','pending','paid','failed','expired','refunded','partially_refunded') NOT NULL DEFAULT 'created',
 payload_json JSON NULL,
 paid_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_payment_idempotency(idempotency_key),
 UNIQUE KEY uq_payment_provider_reference(provider,provider_reference),
 INDEX idx_payment_order_status(order_id,status),
 CONSTRAINT fk_payment_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS return_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 reason VARCHAR(500) NOT NULL,
 status ENUM('requested','approved','rejected','received','refunded','cancelled') NOT NULL DEFAULT 'requested',
 admin_note VARCHAR(500) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_return_user_status(user_id,status),
 INDEX idx_return_order(order_id),
 CONSTRAINT fk_return_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT fk_return_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refunds (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 return_request_id BIGINT UNSIGNED NULL,
 payment_transaction_id BIGINT UNSIGNED NULL,
 amount DECIMAL(10,2) UNSIGNED NOT NULL,
 status ENUM('requested','processing','succeeded','failed') NOT NULL DEFAULT 'requested',
 provider_reference VARCHAR(120) NULL,
 reason VARCHAR(500) NULL,
 processed_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_refund_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT fk_refund_return FOREIGN KEY(return_request_id) REFERENCES return_requests(id) ON DELETE SET NULL,
 CONSTRAINT fk_refund_payment FOREIGN KEY(payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,
 INDEX idx_refund_order_status(order_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_ledger (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id INT UNSIGNED NOT NULL,
 order_id INT UNSIGNED NULL,
 payout_request_id INT UNSIGNED NULL,
 entry_type ENUM('sale','platform_discount','seller_discount','commission','refund','adjustment','payout','payout_reversal') NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 description VARCHAR(255) NULL,
 idempotency_key VARCHAR(160) NOT NULL,
 available_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_seller_ledger_idempotency(idempotency_key),
 INDEX idx_seller_ledger_seller_available(seller_id,available_at),
 CONSTRAINT fk_ledger_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_ledger_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT fk_ledger_payout FOREIGN KEY(payout_request_id) REFERENCES seller_payout_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_user_id INT UNSIGNED NULL,
 action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(60) NOT NULL,
 entity_id VARCHAR(80) NULL,
 before_json JSON NULL,
 after_json JSON NULL,
 ip_hash CHAR(64) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_entity(entity_type,entity_id),
 INDEX idx_audit_actor_created(actor_user_id,created_at),
 CONSTRAINT fk_audit_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identifier_hash CHAR(64) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 was_successful TINYINT(1) NOT NULL DEFAULT 0,
 attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_login_attempt_lookup(identifier_hash,ip_hash,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_password_reset_hash(token_hash),
 INDEX idx_password_reset_user(user_id,expires_at),
 CONSTRAINT fk_password_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
