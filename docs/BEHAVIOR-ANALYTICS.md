# Behavior Analytics Experiment

ระบบใช้ข้อมูลผู้ใช้จำลอง 100 คน เป็นเวลา 30 วัน (3,000 user-day cases) และเก็บ `user_id`, `timestamp`, IP แบบปกปิด, `action`, `url`, `status`, `response_time`, `login_success`, `request_count`, `order_amount` โดยข้อมูลทดลองมี `source=simulation` แยกจากระบบจริง

## Reproduce the experiment

```powershell
php tools/migrate.php
php tools/seed-simulated-users.php
php tools/generate-behavior-dataset.php
php tools/analyze-behavior-baseline.php
php tools/run-behavior-experiment.php
```

เปิด `admin/behavior-analytics.php` เพื่อดู Mean, Sample SD, Threshold, Confusion Matrix, Accuracy, Precision, Detection Rate/Recall, F1-score, False Positive Rate, Detection Time และ System Performance หรือ Export CSV สำหรับ Excel

Threshold ที่ใช้จริง: Login/min > 2, Request/min > 50, Order/hour > 5, Failed Login/10 min > 5. ข้อมูล Normal ใช้คำนวณ Mean และ Sample SD เท่านั้น เพื่อป้องกัน data leakage; ป้ายกำกับ Suspicious ใช้ประเมินผลภายหลัง ไม่ใช้ตัดสินผลล่วงหน้า
