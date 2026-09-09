# รัน KitchenMart โดยไม่ใช้ XAMPP

ติดตั้ง Docker Desktop แล้วรันจากโฟลเดอร์โปรเจกต์:

```bash
docker compose up --build
```

เปิด `http://localhost:8080` ระบบจะสร้าง MySQL และรัน migration อัตโนมัติ

หยุดระบบด้วย `docker compose down` ข้อมูลจะอยู่ใน Docker volumes
