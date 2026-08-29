<?php
require_once __DIR__.'/../config/database.php'; $db=(new Database())->getConnection(); if(!$db)exit(1);
$coupon=$db->prepare('INSERT INTO coupons(code,title,description,discount_type,discount_value,min_order_amount,per_user_limit,starts_at,ends_at) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE is_active=1');
$coupon->execute(['KITCHEN10','ส่วนลดเปิดร้าน 10%','ลด 10% สำหรับอุปกรณ์ครัวทุกหมวด','percent',10,500,1,date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+30 days'))]);
$coupon->execute(['SAVE50','ลดทันที 50 บาท','ลด 50 บาท เมื่อซื้อครบ 700 บาท','fixed',50,700,1,date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+30 days'))]);
$coupon->execute(['KITCHEN15','ครัวใหม่ลด 15%','ลด 15% สูงสุดตามเงื่อนไขโปรโมชั่น','percent',15,1000,1,date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+14 days'))]);
$id=(int)$db->query("SELECT id FROM coupons WHERE code='KITCHEN10'")->fetchColumn();
$existing=$db->prepare("SELECT id FROM promotional_banners WHERE placement='floating' AND coupon_id=? LIMIT 1");$existing->execute([$id]);
if(!$existing->fetchColumn()){$banner=$db->prepare('INSERT INTO promotional_banners(title,subtitle,image_desktop,image_mobile,button_label,target_url,placement,coupon_id,starts_at,ends_at,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,1)');$banner->execute(['ลด 10% สำหรับครัวของคุณ','ใช้โค้ด KITCHEN10 เมื่อช้อปครบ 500 บาท','assets/images/products/pots.svg','assets/images/products/pots.svg','รับคูปอง','products.php','floating',$id,date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+30 days')),10]);}
echo "Seeded coupon and floating promotion.\n";
