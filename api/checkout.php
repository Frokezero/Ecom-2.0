<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security_monitor.php';
require_once __DIR__ . '/../includes/behavior_analytics.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
if (!isLoggedIn()) jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);
requireCsrf();
$db=(new Database())->getConnection();
if (!$db) jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
enforceSecurityBlock($db,(int)$_SESSION['user_id'],true);
enforceRequestRate($db,'api.checkout',30,60,(int)$_SESSION['user_id']);
$action=$_POST['action'] ?? '';

if ($action === 'create_order') {
    $burst=$db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)');$burst->execute([(int)$_SESSION['user_id']]);if((int)$burst->fetchColumn()>=5){$score=recordSecurityEvent($db,'checkout.burst',40,(int)$_SESSION['user_id'],['window_minutes'=>10],'blocked');securityBlock($db,'user',hash('sha256','user:'.(int)$_SESSION['user_id']),(int)$_SESSION['user_id'],'สร้างคำสั่งซื้อถี่ผิดปกติ',max(60,$score),1800);jsonResponse('error','ระบบพักการสั่งซื้อชั่วคราวเพื่อตรวจสอบความปลอดภัย',['retry_after'=>1800],429);}
    if (empty($_SESSION['cart'])) jsonResponse('error','ตะกร้าสินค้าว่าง',[],422);
    $name=trim($_POST['shipping_name'] ?? ''); $phone=trim($_POST['shipping_phone'] ?? '');
    $address=trim($_POST['shipping_address'] ?? ''); $method=$_POST['payment_method'] ?? '';
    if ($name==='' || $phone==='' || $address==='' || !preg_match('/\b\d{5}\b/u', $address)) jsonResponse('error','กรุณาเลือกจังหวัด อำเภอ ตำบล และรหัสไปรษณีย์ให้ครบ',[],422);
    if (!preg_match('/^[0-9+ -]{8,20}$/',$phone)) jsonResponse('error','รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง',[],422);
    if (!in_array($method,['promptpay','cod'],true)) jsonResponse('error','วิธีชำระเงินไม่ถูกต้อง',[],422);
    try {
        $db->beginTransaction(); $items=[]; $total=0.0;
        $productStmt=$db->prepare("SELECT id,name,price,stock_quantity,image_url FROM products WHERE id=? AND approval_status='approved' FOR UPDATE");
        foreach ($_SESSION['cart'] as $cartItem) {
            $id=(int)$cartItem['id']; $qty=(int)$cartItem['quantity'];
            if ($id<1 || $qty<1) throw new RuntimeException('ข้อมูลตะกร้าไม่ถูกต้อง');
            $productStmt->execute([$id]); $product=$productStmt->fetch();
            if (!$product) throw new RuntimeException('สินค้าบางรายการไม่มีอยู่แล้ว');
            if ((int)$product['stock_quantity'] < $qty) throw new RuntimeException('สินค้า “'.$product['name'].'” มีไม่เพียงพอ');
            $price=(float)$product['price']; $subtotal=$price*$qty; $total+=$subtotal;
            $variantId=(int)($cartItem['variant_id']??0);$variant=null;if($variantId>0){$variantStmt=$db->prepare('SELECT id,sku,name,price,stock_quantity FROM product_variants WHERE id=? AND product_id=? AND is_active=1 FOR UPDATE');$variantStmt->execute([$variantId,$id]);$variant=$variantStmt->fetch();if(!$variant)throw new RuntimeException('ตัวเลือกสินค้าบางรายการไม่มีอยู่แล้ว');if((int)$variant['stock_quantity']<$qty)throw new RuntimeException('ตัวเลือก “'.$variant['name'].'” มีไม่เพียงพอ');$price=(float)$variant['price'];$subtotal=$price*$qty;$total-=(float)$product['price']*$qty;$total+=$subtotal;}
            $items[]=['id'=>$id,'variant_id'=>$variantId,'variant_sku'=>$variant['sku']??null,'variant_name'=>$variant['name']??null,'name'=>$product['name'],'price'=>$price,'quantity'=>$qty,'subtotal'=>$subtotal];
        }
        $couponResult=calculateCouponDiscount($db,(string)($_POST['coupon_code']??$_SESSION['coupon_code']??''),(int)$_SESSION['user_id'],$items,$total,true);
        if($couponResult['error']!=='') throw new RuntimeException($couponResult['error']);
        $discount=(float)$couponResult['discount']; $coupon=$couponResult['coupon']; $payable=max(0,$total-$discount);
        $orderNo='KM'.date('YmdHis').random_int(100,999);
        $paymentStatus=$method==='cod' ? 'cod_pending' : 'pending';
        $stmt=$db->prepare('INSERT INTO orders (order_no,user_id,subtotal_amount,total_amount,coupon_id,coupon_code,discount_amount,shipping_amount,shipping_name,shipping_phone,shipping_address,payment_method,payment_status,payment_expires_at,order_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$orderNo,(int)$_SESSION['user_id'],$total,$payable,$coupon['id']??null,$coupon['code']??null,$discount,0,$name,$phone,$address,$method,$paymentStatus,$method==='promptpay'?date('Y-m-d H:i:s',time()+1800):null,'pending']);
        $orderId=(int)$db->lastInsertId();
        recordOrderHistory($db,$orderId,'pending',$paymentStatus,'รับคำสั่งซื้อเข้าระบบ',(int)$_SESSION['user_id']);
        $itemStmt=$db->prepare('INSERT INTO order_items (order_id,product_id,variant_id,product_name,variant_sku,variant_name,price,quantity,subtotal) VALUES (?,?,?,?,?,?,?,?,?)');
        $stockStmt=$db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');
        foreach ($items as $item) {
            $itemStmt->execute([$orderId,$item['id'],$item['variant_id']?:null,$item['name'],$item['variant_sku'],$item['variant_name'],$item['price'],$item['quantity'],$item['subtotal']]);
            if($item['variant_id']){$variantStock=$db->prepare('UPDATE product_variants SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');$variantStock->execute([$item['quantity'],$item['variant_id'],$item['quantity']]);if($variantStock->rowCount()!==1)throw new RuntimeException('ไม่สามารถตัดสต็อกตัวเลือกสินค้าได้');}else{$stockStmt->execute([$item['quantity'],$item['id'],$item['quantity']]);if($stockStmt->rowCount()!==1)throw new RuntimeException('ไม่สามารถตัดสต็อกสินค้าได้');}
        }
        if($coupon){$claim=$db->prepare('INSERT INTO user_coupons(coupon_id,user_id,used_count) VALUES(?,?,1) ON DUPLICATE KEY UPDATE used_count=used_count+1');$claim->execute([(int)$coupon['id'],(int)$_SESSION['user_id']]);$use=$db->prepare('INSERT INTO coupon_usages(coupon_id,user_id,order_id,discount_amount) VALUES(?,?,?,?)');$use->execute([(int)$coupon['id'],(int)$_SESSION['user_id'],$orderId,$discount]);}
        $payment=$db->prepare('INSERT INTO payment_transactions(order_id,provider,idempotency_key,amount,status) VALUES(?,?,?,?,?)');
        $payment->execute([$orderId,$method==='promptpay'?'promptpay':'cod',hash('sha256','order:'.$orderId.':'.$orderNo),$payable,$method==='promptpay'?'pending':'created']);
        $db->commit(); $_SESSION['cart']=[]; unset($_SESSION['coupon_code']);
        $activity=behaviorLog($db,['user_id'=>(int)$_SESSION['user_id'],'action'=>'order.created','status'=>201,'order_amount'=>$payable]);behaviorEvaluateRuntime($db,$activity,(int)$_SESSION['user_id'],date('Y-m-d H:i:s'));
        createNotification($db, (int)$_SESSION['user_id'], 'order', 'สั่งซื้อสำเร็จ', 'คำสั่งซื้อ '.$orderNo.' ถูกบันทึกแล้ว', BASE_URL.'order-detail.php?id='.$orderId);
        createRoleNotification($db, 'admin', 'order', 'มีคำสั่งซื้อใหม่', 'คำสั่งซื้อ '.$orderNo.' รอตรวจสอบ', BASE_URL.'admin/orders.php?order_status=pending');
        jsonResponse('success','สร้างคำสั่งซื้อเรียบร้อย',['order_id'=>$orderId,'order_no'=>$orderNo,'payment_method'=>$method,'total_amount'=>$payable,'discount_amount'=>$discount,'redirect'=>BASE_URL.'order-success.php?order_id='.$orderId]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonResponse('error',$e instanceof RuntimeException ? $e->getMessage() : 'ไม่สามารถสร้างคำสั่งซื้อได้',[],409);
    }
}

if ($action === 'simulate_payment') {
    jsonResponse('error','การแสดง QR ไม่ใช่การยืนยันชำระเงิน ต้องตรวจสอบธุรกรรมผ่านระบบแยกต่างหาก',[],501);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
