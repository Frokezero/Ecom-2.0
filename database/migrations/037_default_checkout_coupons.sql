INSERT INTO coupons (
 seller_id,mall_only,code,title,description,discount_type,discount_value,max_discount,
 min_order_amount,usage_limit,per_user_limit,category_id,product_id,starts_at,ends_at,is_active
) VALUES
 (NULL,1,'FREESHIP500','ส่งฟรีเมื่อซื้อครบ 500 บาท','ใช้ได้กับค่าจัดส่งของสินค้าทุกร้าน','free_shipping',0,NULL,500,NULL,0,NULL,NULL,'2026-09-11 00:00:00','2036-09-11 23:59:59',1),
 (NULL,0,'SAVE50','ลด 50 บาท เมื่อซื้อครบ 200 บาท','ส่วนลดสินค้า 50 บาท เมื่อยอดสินค้าครบ 200 บาท','fixed',50,50,200,NULL,0,NULL,NULL,'2026-09-11 00:00:00','2036-09-11 23:59:59',1)
ON DUPLICATE KEY UPDATE
 seller_id=VALUES(seller_id),mall_only=VALUES(mall_only),title=VALUES(title),description=VALUES(description),
 discount_type=VALUES(discount_type),discount_value=VALUES(discount_value),max_discount=VALUES(max_discount),
 min_order_amount=VALUES(min_order_amount),usage_limit=VALUES(usage_limit),per_user_limit=VALUES(per_user_limit),
 category_id=VALUES(category_id),product_id=VALUES(product_id),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),is_active=VALUES(is_active);
