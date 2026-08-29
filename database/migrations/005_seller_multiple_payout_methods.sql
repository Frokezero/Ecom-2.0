ALTER TABLE seller_profiles MODIFY payout_method ENUM('promptpay','bank','both') NOT NULL;
ALTER TABLE seller_profiles ADD COLUMN promptpay_owner VARCHAR(100) NULL AFTER payout_method;
ALTER TABLE seller_profiles ADD COLUMN promptpay_number VARCHAR(100) NULL AFTER promptpay_owner;
ALTER TABLE seller_payout_requests MODIFY payout_method ENUM('promptpay','bank','both') NOT NULL;
ALTER TABLE seller_payout_requests ADD COLUMN promptpay_owner VARCHAR(100) NULL AFTER payout_method;
ALTER TABLE seller_payout_requests ADD COLUMN promptpay_number VARCHAR(100) NULL AFTER promptpay_owner;
