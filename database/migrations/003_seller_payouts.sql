-- Manual seller onboarding and payout workflow. No banking API or automatic transfer is used.
ALTER TABLE users MODIFY role ENUM('customer','seller','admin') NOT NULL DEFAULT 'customer';

CREATE TABLE IF NOT EXISTS seller_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  shop_name VARCHAR(80) NOT NULL,
  primary_category_id INT UNSIGNED NOT NULL,
  shop_description TEXT NULL,
  phone VARCHAR(20) NOT NULL,
  payout_method ENUM('promptpay','bank') NOT NULL,
  payout_account_name VARCHAR(100) NOT NULL,
  payout_account_number VARCHAR(100) NOT NULL,
  return_address TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(500) NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  reviewed_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_seller_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_seller_profile_category FOREIGN KEY (primary_category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  CONSTRAINT fk_seller_profile_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_seller_profile_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_payout_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) UNSIGNED NOT NULL,
  payout_method ENUM('promptpay','bank') NOT NULL,
  payout_account_name VARCHAR(100) NOT NULL,
  payout_account_number VARCHAR(100) NOT NULL,
  status ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested',
  admin_note VARCHAR(500) NULL,
  transfer_reference VARCHAR(100) NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  processed_by INT UNSIGNED NULL,
  CONSTRAINT fk_payout_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_payout_processor FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_payout_seller_status (seller_id,status),
  INDEX idx_payout_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
