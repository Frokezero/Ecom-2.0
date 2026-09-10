# รัน KitchenMart โดยไม่ใช้ XAMPP

ติดตั้ง Docker Desktop แล้วรันจากโฟลเดอร์โปรเจกต์:

```bash
docker compose up --build
```

สำหรับ Production ให้คัดลอก `.env.production.example` เป็น `.env` แล้วเปลี่ยนค่า secret และโดเมนทั้งหมดก่อนรัน ห้ามใช้ค่าตัวอย่างบนอินเทอร์เน็ตสาธารณะ

ตรวจความพร้อมและทดสอบโหลด:

```powershell
C:\xampp\php\php.exe tools\readiness-score.php
powershell -File tools\load-test.ps1 -BaseUrl http://localhost:8080 -Requests 100 -Concurrency 10
```

เปิด `http://localhost:8080` ระบบจะสร้าง MySQL และรัน migration อัตโนมัติ

หยุดระบบด้วย `docker compose down` ข้อมูลจะอยู่ใน Docker volumes
