CREATE TABLE IF NOT EXISTS seller_email_otps (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 code_hash VARCHAR(255) NOT NULL,
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 expires_at DATETIME NOT NULL,
 sent_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 INDEX idx_seller_otp_user(user_id,used_at,expires_at),
 CONSTRAINT fk_seller_otp_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
