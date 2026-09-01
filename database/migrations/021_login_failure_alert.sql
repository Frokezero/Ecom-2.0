-- แจ้งเตือนและบล็อกเมื่อพยายามเข้าสู่ระบบด้วยข้อมูลเดิมผิดครบ 10 ครั้งใน 15 นาที
UPDATE security_rules
SET threshold_count = 10,
    window_seconds = 900,
    block_seconds = 900
WHERE rule_code = 'login_fail_identity';
