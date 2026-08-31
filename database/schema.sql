-- KitchenMart schema สำหรับ MySQL/MariaDB (UTF-8)
CREATE DATABASE IF NOT EXISTS `kitchenmart_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kitchenmart_db`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL UNIQUE, email VARCHAR(100) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, phone VARCHAR(20), address TEXT,
 preferred_payment_method ENUM('promptpay','cod') NOT NULL DEFAULT 'promptpay',
 role ENUM('customer','seller','admin') NOT NULL DEFAULT 'customer', email_verified_at DATETIME NULL,
 email_verification_token_hash CHAR(64) NULL, email_verification_expires_at DATETIME NULL, email_verification_sent_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_users_role(role), INDEX idx_users_verification_token(email_verification_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL UNIQUE,
 icon VARCHAR(50) DEFAULT 'fa-utensils', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NOT NULL, seller_id INT UNSIGNED NULL, name VARCHAR(200) NOT NULL, description TEXT,
 price DECIMAL(10,2) UNSIGNED NOT NULL, stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 image_url VARCHAR(255) DEFAULT 'assets/images/products/placeholder.svg', is_featured TINYINT(1) NOT NULL DEFAULT 0, approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved', admin_note VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT, CONSTRAINT fk_products_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_products_category(category_id), INDEX idx_products_featured(is_featured), INDEX idx_products_name(name), INDEX idx_products_seller_approval(seller_id,approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_no VARCHAR(30) NOT NULL UNIQUE, user_id INT UNSIGNED NOT NULL,
 total_amount DECIMAL(10,2) UNSIGNED NOT NULL, coupon_id INT UNSIGNED NULL, coupon_code VARCHAR(40) NULL, discount_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0, shipping_name VARCHAR(100) NOT NULL, shipping_phone VARCHAR(20) NOT NULL,
 shipping_address TEXT NOT NULL, payment_method ENUM('promptpay','cod') NOT NULL,
 payment_status ENUM('pending','paid','cod_pending') NOT NULL DEFAULT 'pending',
 order_status ENUM('pending','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_orders_user_created(user_id,created_at), INDEX idx_orders_status(order_status), INDEX idx_orders_payment(payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
 product_name VARCHAR(200) NOT NULL, price DECIMAL(10,2) UNSIGNED NOT NULL, quantity INT UNSIGNED NOT NULL, subtotal DECIMAL(10,2) UNSIGNED NOT NULL,
 CONSTRAINT fk_order_items_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 INDEX idx_order_items_order(order_id), INDEX idx_order_items_product(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_reviews (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_reviews_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_reviews_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 UNIQUE KEY uq_review_product_user(product_id,user_id),
 INDEX idx_reviews_product_created(product_id,created_at),
 INDEX idx_reviews_rating(rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_profiles (
 user_id INT UNSIGNED PRIMARY KEY, shop_name VARCHAR(80) NOT NULL, primary_category_id INT UNSIGNED NOT NULL, shop_description TEXT NULL, shop_logo VARCHAR(255) NULL, cover_image VARCHAR(255) NULL, promo_image VARCHAR(255) NULL, promo_title VARCHAR(120) NULL, promo_text VARCHAR(250) NULL, promo_url VARCHAR(500) NULL,
 phone VARCHAR(20) NOT NULL, payout_method ENUM('promptpay','bank','both') NOT NULL, promptpay_owner VARCHAR(100) NULL, promptpay_number VARCHAR(100) NULL, payout_bank_name VARCHAR(80) NULL, payout_account_name VARCHAR(100) NOT NULL, payout_account_number VARCHAR(100) NOT NULL,
 return_address TEXT NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', admin_note VARCHAR(500) NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL, reviewed_by INT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_seller_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, CONSTRAINT fk_seller_profile_category FOREIGN KEY(primary_category_id) REFERENCES categories(id) ON DELETE RESTRICT,
 CONSTRAINT fk_seller_profile_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL, INDEX idx_seller_profile_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS seller_payout_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL, amount DECIMAL(10,2) UNSIGNED NOT NULL, payout_method ENUM('promptpay','bank','both') NOT NULL, promptpay_owner VARCHAR(100) NULL, promptpay_number VARCHAR(100) NULL, payout_bank_name VARCHAR(80) NULL,
 payout_account_name VARCHAR(100) NOT NULL, payout_account_number VARCHAR(100) NOT NULL, status ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested', admin_note VARCHAR(500) NULL,
 transfer_reference VARCHAR(100) NULL, requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, processed_at DATETIME NULL, processed_by INT UNSIGNED NULL,
 CONSTRAINT fk_payout_seller FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE, CONSTRAINT fk_payout_processor FOREIGN KEY(processed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_payout_seller_status(seller_id,status), INDEX idx_payout_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, type VARCHAR(40) NOT NULL DEFAULT 'system', title VARCHAR(160) NOT NULL, body VARCHAR(500) NULL, link VARCHAR(500) NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, expires_at DATETIME NULL,
 CONSTRAINT fk_notifications_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_notifications_user_read(user_id,is_read,created_at), INDEX idx_notifications_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories(id,name,slug,icon) VALUES
(1,'หม้อและกระทะ','pots-pans','fa-fire-burner'),(2,'มีดและเขียง','knives-boards','fa-utensils'),
(3,'จาน ชาม และช้อนส้อม','tableware','fa-bowl-food'),(4,'เครื่องใช้ไฟฟ้าในครัว','appliances','fa-blender'),
(5,'อุปกรณ์เบเกอรี','baking','fa-cookie-bite'),(6,'กล่องและภาชนะเก็บอาหาร','food-storage','fa-box'),
(7,'อุปกรณ์ทำความสะอาดครัว','cleaning','fa-pump-soap')
ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),icon=VALUES(icon);

-- ไม่สร้างบัญชีเริ่มต้นที่มีรหัสผ่านร่วมกัน ให้ใช้ tools/create-admin.php หลังติดตั้ง

INSERT INTO products(id,category_id,name,description,price,stock_quantity,image_url,is_featured) VALUES
(1,1,'หม้อสเตนเลสก้นหนา 24 ซม.','หม้อสเตนเลส 304 กระจายความร้อนสม่ำเสมอ พร้อมฝาแก้ว ใช้ได้กับเตาทุกประเภท',1290,24,'assets/images/products/pots.svg',1),
(2,1,'กระทะเคลือบหินอ่อน 28 ซม.','กระทะทรงลึกเคลือบกันติด ใช้น้ำมันน้อย ด้ามจับไม่ร้อนและทำความสะอาดง่าย',890,35,'assets/images/products/pots.svg',1),
(3,1,'ชุดหม้อด้าม 3 ขนาด','ชุดหม้อด้ามสเตนเลสสำหรับอุ่น ต้ม และทำซอส ประหยัดพื้นที่จัดเก็บ',1590,18,'assets/images/products/pots.svg',0),
(4,2,'มีดเชฟสเตนเลส 8 นิ้ว','มีดเชฟคมทน น้ำหนักสมดุล เหมาะสำหรับหั่น ซอย และสับวัตถุดิบประจำวัน',790,42,'assets/images/products/knives.svg',1),
(5,2,'เขียงไม้ยางพาราสองด้าน','เขียงไม้เนื้อแน่นขนาด 35 ซม. มีร่องรองน้ำและจับถนัดมือ',490,30,'assets/images/products/knives.svg',0),
(6,3,'ชุดจานเซรามิก 12 ชิ้น','จานเซรามิกสีครีมเรียบหรู เข้าไมโครเวฟและเครื่องล้างจานได้',1450,20,'assets/images/products/tableware.svg',1),
(7,3,'ชุดช้อนส้อมสเตนเลส 24 ชิ้น','ช้อน ส้อม มีด และช้อนชา สำหรับ 6 ที่นั่ง ผิวเงาทนสนิม',990,26,'assets/images/products/tableware.svg',0),
(8,4,'เครื่องปั่นอเนกประสงค์ 1.5 ลิตร','มอเตอร์ 800 วัตต์ ปรับได้ 3 ระดับ พร้อมโถแก้วและระบบตัดไฟอัตโนมัติ',2190,14,'assets/images/products/appliances.svg',1),
(9,4,'หม้อทอดไร้น้ำมัน 5 ลิตร','ระบบลมร้อนรอบทิศทาง ตั้งเวลาและอุณหภูมิได้ ลดการใช้น้ำมัน',2990,12,'assets/images/products/appliances.svg',1),
(10,4,'กาต้มน้ำไฟฟ้าสเตนเลส','ความจุ 1.7 ลิตร ตัดไฟอัตโนมัติ ฐานหมุนได้ 360 องศา',790,28,'assets/images/products/appliances.svg',0),
(11,5,'พิมพ์เค้กถอดก้น 8 นิ้ว','พิมพ์อะลูมิเนียมเคลือบกันติด ถอดก้นง่าย เหมาะสำหรับชีสเค้กและเค้กทั่วไป',390,40,'assets/images/products/baking.svg',0),
(12,5,'ชุดตวงและพายซิลิโคน','ถ้วยตวง ช้อนตวง และพายซิลิโคนทนความร้อน รวม 10 ชิ้น',590,33,'assets/images/products/baking.svg',1),
(13,6,'ชุดกล่องแก้วถนอมอาหาร 5 ใบ','แก้วบอโรซิลิเกต ฝาล็อกสี่ด้าน เข้าเตาอบ ไมโครเวฟ และช่องแช่แข็งได้',1190,22,'assets/images/products/storage.svg',1),
(14,6,'กล่องข้าวสเตนเลสแบ่งช่อง','กล่องอาหารสามช่อง ฝาปิดสนิท พร้อมช้อนและกระเป๋าเก็บความร้อน',650,31,'assets/images/products/storage.svg',0),
(15,7,'ชุดแปรงทำความสะอาดครัว','แปรงล้างขวด แปรงซอก และแปรงขัดอเนกประสงค์ ด้ามจับกันลื่น',350,50,'assets/images/products/cleaning.svg',0),
(16,7,'ที่กดน้ำยาล้างจานพร้อมฟองน้ำ','จ่ายน้ำยาในครั้งเดียว ลดการสิ้นเปลือง พร้อมฐานรองระบายน้ำ',290,45,'assets/images/products/cleaning.svg',0)
ON DUPLICATE KEY UPDATE name=name;
