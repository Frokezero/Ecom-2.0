ALTER TABLE seller_profiles ADD COLUMN payout_bank_name VARCHAR(80) NULL AFTER payout_method;
ALTER TABLE seller_payout_requests ADD COLUMN payout_bank_name VARCHAR(80) NULL AFTER payout_method;
