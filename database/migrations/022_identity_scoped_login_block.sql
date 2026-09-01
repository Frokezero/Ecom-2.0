-- ปลดบล็อก IP ที่เกิดจากกฎ login.failed รุ่นเดิม
-- รุ่นใหม่พักเฉพาะชื่อผู้ใช้ที่กรอกรหัสผิดครบ 10 ครั้ง และบล็อก IP เมื่อโจมตีหลายบัญชีเท่านั้น
UPDATE security_blocks
SET is_active = 0,
    released_at = NOW()
WHERE is_active = 1
  AND reason LIKE '%login.failed%';
