USE `kitchenmart_db`;
CREATE TABLE IF NOT EXISTS chat_conversations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(32) NOT NULL, user_id INT UNSIGNED NULL,
 status ENUM('bot','waiting_staff','staff','closed') NOT NULL DEFAULT 'bot', last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_chat_public(public_id), INDEX idx_chat_user_time(user_id,last_message_at), INDEX idx_chat_status(status,last_message_at),
 CONSTRAINT fk_chat_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS chat_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL,
 sender ENUM('user','bot','staff') NOT NULL, message TEXT NOT NULL, metadata_json JSON NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_chat_messages_conversation(conversation_id,id), CONSTRAINT fk_chat_message_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS chat_knowledge_base (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL, keywords VARCHAR(500) NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1, priority SMALLINT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_chat_kb_active(is_active,priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO chat_knowledge_base(question,answer,keywords,priority) VALUES
('จัดส่งฟรีเมื่อไร','จัดส่งฟรีเมื่อยอดสินค้าในตะกร้าครบ 1,000 บาท','ส่งฟรี ค่าจัดส่ง ส่งสินค้า',100),
('ชำระเงินอย่างไร','รองรับ PromptPay QR และเก็บเงินปลายทาง โดยสถานะชำระเงินตรวจสอบได้จากคำสั่งซื้อของฉัน','ชำระเงิน promptpay qr cod เก็บเงินปลายทาง',100),
('คืนสินค้าได้อย่างไร','ยื่นคำขอคืนสินค้าจากประวัติคำสั่งซื้อภายใน 7 วันหลังได้รับสินค้า แล้วติดตามผลได้ที่หน้าการคืนสินค้า','คืนสินค้า คืนเงิน เปลี่ยนสินค้า',100),
('สมัครเป็นผู้ขาย','เข้าสู่ระบบแล้วเปิดเมนูสมัครเป็นผู้ขาย กรอกข้อมูลร้านและช่องทางรับเงิน จากนั้นรอผู้ดูแลอนุมัติ','สมัครผู้ขาย เปิดร้าน ขายสินค้า',90),
('ใช้คูปองอย่างไร','รับคูปองจากศูนย์รวมคูปอง แล้วเลือกใช้หรือไม่ใช้ได้ในหน้าตะกร้าและ Checkout','คูปอง โค้ด ส่วนลด โปรโมชั่น',90);
