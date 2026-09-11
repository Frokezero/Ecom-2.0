<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security_monitor.php';
require_once __DIR__ . '/../includes/behavior_analytics.php';
require_once __DIR__ . '/../includes/commerce_workflow.php';

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
        $productStmt=$db->prepare("SELECT p.id,p.seller_id,p.name,p.price,p.sale_price,p.sale_starts_at,p.sale_ends_at,p.stock_quantity,p.image_url FROM products p WHERE p.id=? AND p.approval_status='approved' AND ".marketplaceVisibilitySql('p')." FOR UPDATE");
        foreach ($_SESSION['cart'] as $cartItem) {
            $id=(int)$cartItem['id']; $qty=(int)$cartItem['quantity'];
            if ($id<1 || $qty<1) throw new RuntimeException('ข้อมูลตะกร้าไม่ถูกต้อง');
            $productStmt->execute([$id]); $product=$productStmt->fetch();
            if (!$product) throw new RuntimeException('สินค้าบางรายการไม่มีอยู่แล้ว');
            if ((int)$product['stock_quantity'] < $qty) throw new RuntimeException('สินค้า “'.$product['name'].'” มีไม่เพียงพอ');
            $price=productEffectivePrice($product); $subtotal=$price*$qty; $total+=$subtotal;
            $variantId=(int)($cartItem['variant_id']??0);$variant=null;if($variantId>0){$variantStmt=$db->prepare('SELECT id,sku,name,price,stock_quantity FROM product_variants WHERE id=? AND product_id=? AND is_active=1 FOR UPDATE');$variantStmt->execute([$variantId,$id]);$variant=$variantStmt->fetch();if(!$variant)throw new RuntimeException('ตัวเลือกสินค้าบางรายการไม่มีอยู่แล้ว');if((int)$variant['stock_quantity']<$qty)throw new RuntimeException('ตัวเลือก “'.$variant['name'].'” มีไม่เพียงพอ');$total-=$subtotal;$price=(float)$variant['price'];$subtotal=$price*$qty;$total+=$subtotal;}
            $items[]=['id'=>$id,'seller_id'=>$product['seller_id']===null?null:(int)$product['seller_id'],'variant_id'=>$variantId,'variant_sku'=>$variant['sku']??null,'variant_name'=>$variant['name']??null,'name'=>productDisplayName($product),'price'=>$price,'quantity'=>$qty,'subtotal'=>$subtotal];
        }
        $items=applyActiveBundles($db,$items);$total=array_sum(array_column($items,'subtotal'));
        $codes=array_values($_SESSION['coupon_codes']??[]);if(!$codes&&($_POST['coupon_code']??'')!=='')$codes[]=(string)$_POST['coupon_code'];$couponResult=calculateCouponSet($db,$codes,(int)$_SESSION['user_id'],$items,$total,true);
        if($couponResult['error']!=='') throw new RuntimeException($couponResult['error']);
        $discount=(float)$couponResult['discount'];$coupons=$couponResult['coupons'];$primaryCoupon=$couponResult['product_coupon'];$charges=$couponResult['charges'];$shipping=(float)$charges['shipping'];$tax=(float)$charges['tax'];$payable=(float)$charges['total'];
        $orderNo='KM'.date('YmdHis').random_int(100,999);
        $paymentStatus=$method==='cod' ? 'cod_pending' : 'pending';
        $stmt=$db->prepare('INSERT INTO orders (order_no,user_id,subtotal_amount,total_amount,coupon_id,coupon_code,discount_amount,shipping_amount,shipping_discount_amount,platform_shipping_subsidy,seller_shipping_subsidy,tax_amount,shipping_name,shipping_phone,shipping_address,payment_method,payment_status,payment_expires_at,order_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$orderNo,(int)$_SESSION['user_id'],$total,$payable,$primaryCoupon['id']??null,$primaryCoupon['code']??null,$discount,$shipping,$charges['shipping_discount'],$charges['platform_shipping_subsidy'],$charges['seller_shipping_subsidy'],$tax,$name,$phone,$address,$method,$paymentStatus,$method==='promptpay'?date('Y-m-d H:i:s',time()+1800):null,'pending']);
        $orderId=(int)$db->lastInsertId();
        recordOrderHistory($db,$orderId,'pending',$paymentStatus,'รับคำสั่งซื้อเข้าระบบ',(int)$_SESSION['user_id']);
        $itemStmt=$db->prepare('INSERT INTO order_items (order_id,product_id,seller_id,variant_id,product_name,variant_sku,variant_name,price,quantity,subtotal) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stockStmt=$db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');
        foreach ($items as $item) {
            $itemStmt->execute([$orderId,$item['id'],$item['seller_id'],$item['variant_id']?:null,$item['name'],$item['variant_sku'],$item['variant_name'],$item['price'],$item['quantity'],$item['subtotal']]);
            if($item['variant_id']){$variantStock=$db->prepare('UPDATE product_variants SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');$variantStock->execute([$item['quantity'],$item['variant_id'],$item['quantity']]);if($variantStock->rowCount()!==1)throw new RuntimeException('ไม่สามารถตัดสต็อกตัวเลือกสินค้าได้');$db->prepare('UPDATE products SET stock_quantity=(SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_id=?) WHERE id=?')->execute([$item['id'],$item['id']]);}else{$stockStmt->execute([$item['quantity'],$item['id'],$item['quantity']]);if($stockStmt->rowCount()!==1)throw new RuntimeException('ไม่สามารถตัดสต็อกสินค้าได้');}
        }
        $sellerIds=array_values(array_unique(array_filter(array_column($items,'seller_id'))));
        if($sellerIds){$fulfillment=$db->prepare("INSERT IGNORE INTO order_fulfillments(order_id,seller_id,status) VALUES(?,?,'pending')");foreach($sellerIds as $sellerId)$fulfillment->execute([$orderId,(int)$sellerId]);}
        foreach($coupons as $coupon){$couponDiscount=$coupon['discount_type']==='free_shipping'?(float)$charges['shipping_discount']:$discount;$claim=$db->prepare('INSERT INTO user_coupons(coupon_id,user_id,used_count) VALUES(?,?,1) ON DUPLICATE KEY UPDATE used_count=used_count+1');$claim->execute([(int)$coupon['id'],(int)$_SESSION['user_id']]);$db->prepare('INSERT INTO coupon_usages(coupon_id,user_id,order_id,discount_amount) VALUES(?,?,?,?)')->execute([(int)$coupon['id'],(int)$_SESSION['user_id'],$orderId,$couponDiscount]);$db->prepare('INSERT INTO order_coupons(order_id,coupon_id,coupon_code,coupon_type,discount_amount,shipping_discount_amount) VALUES(?,?,?,?,?,?)')->execute([$orderId,(int)$coupon['id'],$coupon['code'],$coupon['discount_type']==='free_shipping'?'free_shipping':'product_discount',$coupon['discount_type']==='free_shipping'?0:$discount,$coupon['discount_type']==='free_shipping'?$charges['shipping_discount']:0]);}
        foreach($charges['shipping_lines'] as $line){$db->prepare('INSERT INTO order_shipping_allocations(order_id,seller_id,shipping_amount,customer_paid_amount,platform_subsidy_amount,seller_subsidy_amount) VALUES(?,?,?,?,?,?)')->execute([$orderId,$line['seller_id'],$line['shipping_amount'],max(0,$line['shipping_amount']-($line['shipping_discount']??0)),$line['platform_subsidy']??0,$line['seller_subsidy']??0]);}
        $payment=$db->prepare('INSERT INTO payment_transactions(order_id,provider,idempotency_key,amount,status) VALUES(?,?,?,?,?)');
        $payment->execute([$orderId,$method==='promptpay'?'promptpay':'cod',hash('sha256','order:'.$orderId.':'.$orderNo),$payable,$method==='promptpay'?'pending':'created']);
        $db->commit(); $_SESSION['cart']=[]; unset($_SESSION['coupon_code'],$_SESSION['coupon_codes']);
        $activity=behaviorLog($db,['user_id'=>(int)$_SESSION['user_id'],'action'=>'order.created','status'=>201,'order_amount'=>$payable]);behaviorEvaluateRuntime($db,$activity,(int)$_SESSION['user_id'],date('Y-m-d H:i:s'));
        createNotification($db, (int)$_SESSION['user_id'], 'order', 'สั่งซื้อสำเร็จ', 'คำสั่งซื้อ '.$orderNo.' ถูกบันทึกแล้ว', BASE_URL.'order-detail.php?id='.$orderId);
        createRoleNotification($db, 'admin', 'order', 'มีคำสั่งซื้อใหม่', 'คำสั่งซื้อ '.$orderNo.' รอตรวจสอบ', BASE_URL.'admin/orders.php?order_status=pending');
        jsonResponse('success','สร้างคำสั่งซื้อเรียบร้อย',['order_id'=>$orderId,'order_no'=>$orderNo,'payment_method'=>$method,'total_amount'=>$payable,'discount_amount'=>$discount,'shipping_amount'=>$shipping,'tax_amount'=>$tax,'redirect'=>BASE_URL.'order-success.php?order_id='.$orderId]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonResponse('error',$e instanceof RuntimeException ? $e->getMessage() : 'ไม่สามารถสร้างคำสั่งซื้อได้',[],409);
    }
}

if ($action === 'simulate_payment') {
    jsonResponse('error','การแสดง QR ไม่ใช่การยืนยันชำระเงิน ต้องตรวจสอบธุรกรรมผ่านระบบแยกต่างหาก',[],501);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
