<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
if (!isLoggedIn()) jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);
requireCsrf();
$db=(new Database())->getConnection();
if (!$db) jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
$action=$_POST['action'] ?? '';

if ($action === 'create_order') {
    if (empty($_SESSION['cart'])) jsonResponse('error','ตะกร้าสินค้าว่าง',[],422);
    $name=trim($_POST['shipping_name'] ?? ''); $phone=trim($_POST['shipping_phone'] ?? '');
    $address=trim($_POST['shipping_address'] ?? ''); $method=$_POST['payment_method'] ?? '';
    if ($name==='' || $phone==='' || $address==='') jsonResponse('error','กรุณากรอกข้อมูลจัดส่งให้ครบ',[],422);
    if (!preg_match('/^[0-9+ -]{8,20}$/',$phone)) jsonResponse('error','รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง',[],422);
    if (!in_array($method,['promptpay','cod'],true)) jsonResponse('error','วิธีชำระเงินไม่ถูกต้อง',[],422);
    try {
        $db->beginTransaction(); $items=[]; $total=0.0;
        $productStmt=$db->prepare('SELECT id,name,price,stock_quantity,image_url FROM products WHERE id=? FOR UPDATE');
        foreach ($_SESSION['cart'] as $cartItem) {
            $id=(int)$cartItem['id']; $qty=(int)$cartItem['quantity'];
            if ($id<1 || $qty<1) throw new RuntimeException('ข้อมูลตะกร้าไม่ถูกต้อง');
            $productStmt->execute([$id]); $product=$productStmt->fetch();
            if (!$product) throw new RuntimeException('สินค้าบางรายการไม่มีอยู่แล้ว');
            if ((int)$product['stock_quantity'] < $qty) throw new RuntimeException('สินค้า “'.$product['name'].'” มีไม่เพียงพอ');
            $price=(float)$product['price']; $subtotal=$price*$qty; $total+=$subtotal;
            $items[]=['id'=>$id,'name'=>$product['name'],'price'=>$price,'quantity'=>$qty,'subtotal'=>$subtotal];
        }
        $orderNo='KM'.date('YmdHis').random_int(100,999);
        $paymentStatus=$method==='cod' ? 'cod_pending' : 'pending';
        $stmt=$db->prepare('INSERT INTO orders (order_no,user_id,total_amount,shipping_name,shipping_phone,shipping_address,payment_method,payment_status,order_status) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$orderNo,(int)$_SESSION['user_id'],$total,$name,$phone,$address,$method,$paymentStatus,'pending']);
        $orderId=(int)$db->lastInsertId();
        $itemStmt=$db->prepare('INSERT INTO order_items (order_id,product_id,product_name,price,quantity,subtotal) VALUES (?,?,?,?,?,?)');
        $stockStmt=$db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');
        foreach ($items as $item) {
            $itemStmt->execute([$orderId,$item['id'],$item['name'],$item['price'],$item['quantity'],$item['subtotal']]);
            $stockStmt->execute([$item['quantity'],$item['id'],$item['quantity']]);
            if ($stockStmt->rowCount() !== 1) throw new RuntimeException('ไม่สามารถตัดสต็อกสินค้าได้');
        }
        $db->commit(); $_SESSION['cart']=[];
        jsonResponse('success','สร้างคำสั่งซื้อเรียบร้อย',['order_id'=>$orderId,'order_no'=>$orderNo,'payment_method'=>$method,'total_amount'=>$total,'redirect'=>BASE_URL.'order-success.php?order_id='.$orderId]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonResponse('error',$e instanceof RuntimeException ? $e->getMessage() : 'ไม่สามารถสร้างคำสั่งซื้อได้',[],409);
    }
}

if ($action === 'simulate_payment') {
    jsonResponse('error','การแสดง QR ไม่ใช่การยืนยันชำระเงิน ต้องตรวจสอบธุรกรรมผ่านระบบแยกต่างหาก',[],501);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
