-- รักษาบัญชีเดิมที่เคยส่งคำขอเปิดร้านให้เป็นบัญชีผู้ขาย
UPDATE users u
JOIN seller_profiles sp ON sp.user_id=u.id
SET u.role='seller', u.auth_version=u.auth_version+1
WHERE u.role='customer';
