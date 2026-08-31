USE `kitchenmart_db`;

ALTER TABLE email_delivery_logs
  MODIFY status ENUM('queued','sending','sent','failed','dead') NOT NULL DEFAULT 'queued',
  ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL AFTER subject,
  ADD COLUMN IF NOT EXISTS body_text TEXT NULL AFTER title,
  ADD COLUMN IF NOT EXISTS action_url VARCHAR(1000) NULL AFTER body_text,
  ADD COLUMN IF NOT EXISTS next_attempt_at DATETIME NULL AFTER attempts,
  ADD COLUMN IF NOT EXISTS locked_at DATETIME NULL AFTER next_attempt_at;

CREATE TABLE IF NOT EXISTS webhook_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(40) NOT NULL,
  event_id VARCHAR(190) NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  occurred_at DATETIME NULL,
  processed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_webhook_provider_event(provider,event_id),
  KEY idx_webhook_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(100) PRIMARY KEY,
  checksum CHAR(64) NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
