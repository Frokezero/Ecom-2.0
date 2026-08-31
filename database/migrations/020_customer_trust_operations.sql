USE `kitchenmart_db`;

CREATE TABLE IF NOT EXISTS order_status_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL,
 order_status VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL, note VARCHAR(500) NULL,
 actor_user_id INT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_order_history_order_time(order_id,created_at),
 CONSTRAINT fk_order_history_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_order_history_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_no VARCHAR(30) NOT NULL, user_id INT UNSIGNED NOT NULL,
 order_id INT UNSIGNED NULL, category ENUM('order','payment','return','product','account','privacy','other') NOT NULL DEFAULT 'other',
 subject VARCHAR(180) NOT NULL, priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
 status ENUM('open','waiting_customer','waiting_staff','resolved','closed') NOT NULL DEFAULT 'open',
 last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_support_ticket_no(ticket_no), INDEX idx_support_user_status(user_id,status,last_message_at),
 INDEX idx_support_staff_queue(status,priority,last_message_at),
 CONSTRAINT fk_support_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_support_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id BIGINT UNSIGNED NOT NULL,
 sender_user_id INT UNSIGNED NULL, message TEXT NOT NULL, is_staff TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_support_message_ticket(ticket_id,created_at),
 CONSTRAINT fk_support_message_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
 CONSTRAINT fk_support_message_sender FOREIGN KEY(sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS privacy_consents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
 consent_type VARCHAR(40) NOT NULL, consent_version VARCHAR(20) NOT NULL,
 granted_at DATETIME NULL, revoked_at DATETIME NULL, ip_hash CHAR(64) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_privacy_user_type(user_id,consent_type,created_at),
 CONSTRAINT fk_privacy_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO order_status_history(order_id,order_status,payment_status,note,created_at)
SELECT o.id,o.order_status,o.payment_status,'นำเข้าประวัติเริ่มต้น',o.created_at
FROM orders o LEFT JOIN order_status_history h ON h.order_id=o.id WHERE h.id IS NULL;
