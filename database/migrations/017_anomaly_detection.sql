CREATE TABLE IF NOT EXISTS security_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_type VARCHAR(80) NOT NULL,
 severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
 risk_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 user_id INT UNSIGNED NULL,
 ip_hash CHAR(64) NOT NULL,
 ip_masked VARCHAR(64) NULL,
 country_code CHAR(2) NULL,
 request_method VARCHAR(10) NULL,
 request_path VARCHAR(500) NULL,
 user_agent VARCHAR(500) NULL,
 metadata_json JSON NULL,
 action_taken VARCHAR(80) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_security_event_ip (ip_hash,created_at),
 KEY idx_security_event_user (user_id,created_at),
 KEY idx_security_event_type (event_type,created_at),
 KEY idx_security_event_risk (risk_score,created_at),
 CONSTRAINT fk_security_event_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_blocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 target_type ENUM('ip','user','session') NOT NULL,
 target_hash CHAR(64) NOT NULL,
 user_id INT UNSIGNED NULL,
 reason VARCHAR(255) NOT NULL,
 risk_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 blocked_until DATETIME NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 released_at DATETIME NULL,
 KEY idx_security_block_target (target_type,target_hash,is_active),
 KEY idx_security_block_expiry (is_active,blocked_until),
 CONSTRAINT fk_security_block_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_security_block_admin FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_rules (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 rule_code VARCHAR(80) NOT NULL UNIQUE,
 event_type VARCHAR(80) NOT NULL,
 threshold_count INT UNSIGNED NOT NULL,
 window_seconds INT UNSIGNED NOT NULL,
 risk_points SMALLINT UNSIGNED NOT NULL,
 block_seconds INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_rate_counters (
 bucket_key CHAR(64) PRIMARY KEY,
 endpoint VARCHAR(100) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 request_count INT UNSIGNED NOT NULL DEFAULT 1,
 window_started_at DATETIME NOT NULL,
 expires_at DATETIME NOT NULL,
 KEY idx_rate_expiry (expires_at),
 KEY idx_rate_ip_endpoint (ip_hash,endpoint,window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_login_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 country_code CHAR(2) NULL,
 user_agent_hash CHAR(64) NULL,
 login_count INT UNSIGNED NOT NULL DEFAULT 1,
 first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_user_login_ip (user_id,ip_hash),
 KEY idx_login_location_seen (user_id,last_seen_at),
 CONSTRAINT fk_login_location_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO security_rules(rule_code,event_type,threshold_count,window_seconds,risk_points,block_seconds) VALUES
('login_fail_identity','login.failed',8,900,10,900),
('login_fail_ip','login.failed',20,900,10,3600),
('unauthorized_url','access.denied',5,600,25,900),
('request_burst','request.rate_limit',120,60,20,900),
('checkout_burst','checkout.burst',5,600,20,1800)
ON DUPLICATE KEY UPDATE event_type=VALUES(event_type);
