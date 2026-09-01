-- ระดับเตือน: ส่งอีเมลเมื่อผิด 5 ครั้ง โดยยังไม่บล็อก
-- ระดับระงับ: พักชื่อผู้ใช้เมื่อผิดครบ 10 ครั้ง และส่งอีเมลอีกครั้ง
UPDATE security_rules
SET threshold_count = 10,
    window_seconds = 900,
    block_seconds = 900
WHERE rule_code = 'login_fail_identity';
