USE `kitchenmart_db`;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER role,
    ADD COLUMN IF NOT EXISTS email_verification_token_hash CHAR(64) NULL AFTER email_verified_at,
    ADD COLUMN IF NOT EXISTS email_verification_expires_at DATETIME NULL AFTER email_verification_token_hash,
    ADD COLUMN IF NOT EXISTS email_verification_sent_at DATETIME NULL AFTER email_verification_expires_at,
    ADD INDEX IF NOT EXISTS idx_users_verification_token (email_verification_token_hash);

-- บัญชีที่มีอยู่ก่อนเปิดใช้ระบบยืนยันอีเมลยังคงเข้าใช้งานได้
UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL AND email_verification_token_hash IS NULL;
