CREATE TABLE IF NOT EXISTS email_delivery_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    dedupe_key VARCHAR(190) NOT NULL,
    status ENUM('sending','sent','failed') NOT NULL DEFAULT 'sending',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    error_message VARCHAR(500) NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_delivery_dedupe (dedupe_key),
    KEY idx_email_delivery_user (user_id, created_at),
    KEY idx_email_delivery_status (status, updated_at),
    CONSTRAINT fk_email_delivery_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
