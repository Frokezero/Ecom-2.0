<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = (new Database())->getConnection();

function cartSummary(): array {
    $items=[]; $count=0; $total=0.0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal=(float)$item['price']*(int)$item['quantity'];
        $item['subtotal']=$subtotal; $item['formatted_price']=formatCurrency($item['price']);
        $item['formatted_subtotal']=formatCurrency($subtotal); $items[]=$item;
        $count+=(int)$item['quantity']; $total+=$subtotal;
    }
    return ['items'=>$items,'total_items'=>$count,'grand_total'=>$total,'formatted_grand_total'=>formatCurrency($total)];
}

if ($action === 'get') jsonResponse('success', 'โหลดตะกร้าสำเร็จ', cartSummary());
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error', 'อนุญาตเฉพาะ POST', [], 405);
requireCsrf();
if (!$db) jsonResponse('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', [], 503);

$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) jsonResponse('error', 'รหัสสินค้าไม่ถูกต้อง', [], 422);

if ($action === 'remove') {
    unset($_SESSION['cart'][$productId]);
    jsonResponse('success', 'นำสินค้าออกจากตะกร้าแล้ว', cartSummary());
}

$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
if ($quantity === false || $quantity === null) jsonResponse('error', 'จำนวนสินค้าต้องเป็นจำนวนเต็ม', [], 422);
if ($action === 'update' && $quantity === 0) {
    unset($_SESSION['cart'][$productId]);
    jsonResponse('success', 'นำสินค้าออกจากตะกร้าแล้ว', cartSummary());
}
if ($quantity < 1) jsonResponse('error', 'จำนวนสินค้าต้องอย่างน้อย 1 ชิ้น', [], 422);

$stmt=$db->prepare('SELECT id,name,price,stock_quantity,image_url FROM products WHERE id=?');
$stmt->execute([$productId]); $product=$stmt->fetch();
if (!$product) jsonResponse('error', 'ไม่พบสินค้า', [], 404);
$targetQty = $action === 'add' ? (int)($_SESSION['cart'][$productId]['quantity'] ?? 0)+$quantity : $quantity;
if ($targetQty > (int)$product['stock_quantity']) jsonResponse('error', 'สินค้าในสต็อกไม่เพียงพอ', ['available'=>(int)$product['stock_quantity']], 409);
if (!in_array($action, ['add','update'], true)) jsonResponse('error', 'คำสั่งไม่ถูกต้อง', [], 400);

$_SESSION['cart'][$productId]=['id'=>(int)$product['id'],'name'=>$product['name'],'price'=>(float)$product['price'],'image_url'=>$product['image_url'],'quantity'=>$targetQty];
jsonResponse('success', $action==='add' ? 'เพิ่มสินค้าลงตะกร้าแล้ว' : 'อัปเดตจำนวนสินค้าแล้ว', cartSummary());
