# KitchenMart — ร้านอุปกรณ์ครัวออนไลน์

KitchenMart เป็นระบบ E-Commerce ภาษาไทยสำหรับขายอุปกรณ์ครัว มีหน้าร้าน ตะกร้า ชำระเงินจำลอง ประวัติคำสั่งซื้อ และระบบผู้ดูแล เขียนด้วย PHP แบบไม่ใช้ Framework

## เทคโนโลยีและความต้องการ

- PHP 8.0+ พร้อม PDO MySQL, MySQL/MariaDB และ Apache
- แนะนำ XAMPP บน Windows
- HTML, CSS, Vanilla JavaScript โดยไม่ต้องติดตั้ง Composer หรือ npm

## ติดตั้งบน XAMPP

1. เปิด XAMPP Control Panel แล้ว Start Apache และ MySQL
2. วางโปรเจกต์ที่ `C:\xampp\htdocs\kitchenmart`
3. เปิด `http://localhost/phpmyadmin` แล้ว Import ไฟล์ `database/schema.sql`
4. คัดลอก `config/local.php.example` เป็น `config/local.php` แล้วแก้ค่าฐานข้อมูลและ PromptPay
5. หลัง Import schema ครั้งแรกให้รัน `php tools/migrate.php --baseline` จากนั้นการอัปเดตครั้งต่อไปใช้ `php tools/migrate.php`
6. รัน `php tools/create-admin.php` เพื่อสร้างผู้ดูแลด้วยรหัสผ่านเฉพาะ
7. ตั้ง Cron/Task Scheduler ให้รัน `php tools/process-email-queue.php 50` ทุกหนึ่งนาที
8. เปิด `http://localhost/kitchenmart/`

บน Windows สามารถติดตั้งงานส่งอีเมลด้วย `powershell -ExecutionPolicy Bypass -File tools/install-email-worker.ps1` ตรวจสุขภาพด้วย `php tools/health-check.php` และตั้งงานรายวันสำหรับ `php tools/cleanup-orphan-uploads.php` เพื่อล้างรูปที่ไม่มีข้อมูลอ้างอิงและเก่ากว่า 24 ชั่วโมง

ทดสอบหน้าเว็บจริงแบบ HTTP ได้ด้วย `powershell -ExecutionPolicy Bypass -File tools/smoke-test.ps1` โดยสคริปต์จะเปิด PHP development server เฉพาะ localhost ชั่วคราว ตรวจหน้าหลักและ API guard แล้วปิดเซิร์ฟเวอร์ให้อัตโนมัติ

หากคงชื่อโฟลเดอร์เดิม URL จะมีช่องว่างและภาษาไทย จึงแนะนำให้เปลี่ยนชื่อเป็น `kitchenmart`

## การตั้งค่า

`config/local.php` ถูกละเว้นจาก Git และรองรับ `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `STORE_NAME`, `STORE_TAGLINE`, `PROMPTPAY_ID` และ `PROMPTPAY_NAME` ดูรูปแบบจาก `config/local.php.example` ได้ นอกจากนี้ใช้ environment variables ชื่อเดียวกันได้ โดย environment จะมีลำดับความสำคัญสูงกว่า ห้ามใส่ secrets จริงในไฟล์ตัวอย่าง

ระบบสร้าง QR จาก `https://promptpay.io/{PROMPTPAY_ID}/{AMOUNT}.png` หลัง backend สร้างคำสั่งซื้อแล้วเท่านั้น ยอดเงินมาจาก `orders.total_amount` ในฐานข้อมูล การโหลดหรือแสดง QR จะไม่เปลี่ยนสถานะเป็นชำระแล้ว เพราะยังไม่มีระบบตรวจสอบธุรกรรมธนาคาร

### การยืนยันอีเมลสมาชิก

สมาชิกใหม่ต้องกดลิงก์ยืนยันอีเมลภายใน 30 นาทีก่อนเข้าสู่ระบบ ตั้งค่า SMTP ใน `config/local.php` หรือ environment variables โดยใช้ `APP_URL`, `MAIL_TRANSPORT`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM_EMAIL` และ `SMTP_FROM_NAME` ตามตัวอย่างใน `config/local.php.example`

ตัวอย่างในโปรเจกต์ใช้ Cloudflare Email Service: host `smtp.mx.cloudflare.net`, port `465`, encryption `ssl`, username ต้องเป็น `api_token` และ password คือ Cloudflare API Token ที่มีสิทธิ์ `Email Sending: Edit` โดเมนของอีเมลผู้ส่งต้องผ่าน Onboard Domain ใน Cloudflare Email Sending ก่อน ห้าม commit API Token สำหรับระบบที่อัปเกรดจากเวอร์ชันเดิม ให้รัน `database/migrations/001_email_verification.sql` หนึ่งครั้ง บัญชีเดิมจะยังเข้าใช้งานได้ตามปกติ

`APP_URL` ต้องเป็น URL ที่ผู้รับอีเมลเปิดถึงได้ เช่น URL production ของร้าน ไม่ควรใช้ `127.0.0.1` เมื่อส่งให้ผู้ใช้บนอุปกรณ์อื่น

## บัญชีตัวอย่าง

โปรเจกต์ไม่สร้างบัญชีที่ใช้รหัสผ่านตัวอย่าง ให้รัน `php tools/create-admin.php` หลังติดตั้งฐานข้อมูลและกำหนดรหัสผ่านเฉพาะของผู้ดูแล

## โครงสร้างโปรเจกต์

- `admin/` Dashboard, จัดการสินค้าและคำสั่งซื้อ
- `api/` Auth, product, cart และ checkout API
- `assets/` CSS, JavaScript และรูปสินค้า local
- `config/` การตั้งค่าและ PDO
- `database/schema.sql` Schema และ seed data
- `includes/` Layout, auth guard และฟังก์ชันร่วม
- ไฟล์ PHP ระดับบนเป็นหน้าร้านและหน้าของลูกค้า

## ทดสอบฟังก์ชันหลัก

1. สมัครสมาชิก ล็อกอิน และออกจากระบบ
2. ค้นหา กรองหมวด และเรียงสินค้าตามชื่อ/ราคา
3. เพิ่ม เปลี่ยนจำนวน และลบสินค้าในตะกร้า
4. Checkout ด้วย PromptPay แล้วทดลองยืนยันการชำระเงิน
5. สร้างออเดอร์ COD และตรวจหน้าประวัติ/รายละเอียด
6. ล็อกอิน Admin ตรวจ Dashboard, CRUD สินค้า, ดูออเดอร์ และเปลี่ยนสถานะ
7. ใช้ Responsive Mode ของ Developer Tools ตรวจ desktop, tablet และ mobile

## ระบบรีวิวสินค้า

- สมาชิกต้องเคยสั่งซื้อสินค้าชิ้นนั้น และคำสั่งซื้อต้องไม่ถูกยกเลิก จึงจะให้คะแนน 1–5 ดาวและเขียนความคิดเห็นได้
- จำกัดหนึ่งรีวิวต่อผู้ใช้ต่อสินค้า โดยรีวิวเดิมสามารถแก้ไขหรือลบได้
- หน้าแสดงสินค้าสรุปคะแนนเฉลี่ยและจำนวนรีวิว
- รีวิวจากบัญชีที่เคยมีคำสั่งซื้อสินค้านั้นและออเดอร์ไม่ถูกยกเลิกจะแสดงป้าย `ซื้อแล้ว`
- การเพิ่ม แก้ไข และลบรีวิวตรวจ CSRF และเจ้าของรีวิวฝั่งเซิร์ฟเวอร์

## ข้อจำกัดระบบชำระเงิน

PromptPay.io ใช้สร้างและแสดง QR เท่านั้น ไม่มีการเชื่อมธนาคารหรือ webhook และห้ามใช้การโหลด QR เป็นหลักฐานรับเงินจริง ออเดอร์จะคงสถานะรอชำระจนกว่าจะมีระบบตรวจสอบธุรกรรมแยกต่างหาก Production ต้องใช้ payment gateway, HTTPS และระบบจัดเก็บ secrets ที่เหมาะสม
