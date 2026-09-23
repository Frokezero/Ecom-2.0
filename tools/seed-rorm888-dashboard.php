<?php
// Populate the Rorm888 seller dashboard with clearly marked, removable demo data.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
if (appConfig('DEMO_MODE', '0') !== '1' || appConfig('APP_ENV', 'development') === 'production') {
    fwrite(STDERR, "Set DEMO_MODE=1 outside production before seeding demo data.\n");
    exit(1);
}
$db = (new Database())->getConnection();
if (!$db) { fwrite(STDERR, "Database unavailable\n"); exit(1); }
$seller = $db->prepare("SELECT user_id FROM seller_profiles WHERE shop_name='Rorm888' AND status='approved' LIMIT 1");
$seller->execute();
$sellerId = (int)$seller->fetchColumn();
if (!$sellerId) { fwrite(STDERR, "Approved Rorm888 store not found\n"); exit(1); }
$products = $db->prepare("SELECT id,name,price,stock_quantity FROM products WHERE seller_id=? AND approval_status='approved' ORDER BY id LIMIT 6");
$products->execute([$sellerId]);
$products = $products->fetchAll();
if (count($products) < 5) { fwrite(STDERR, "Rorm888 needs at least five approved products.\n"); exit(1); }

$scenarios = [
    ['demo_rorm_mali', 'มะลิ ใจดี', 'RORM-DEMO-001', 0, 'pending', 'pending', 0],
    ['demo_rorm_narin', 'นรินทร์ ครัวบ้าน', 'RORM-DEMO-002', 1, 'accepted', 'processing', 1],
    ['demo_rorm_pim', 'พิมพ์ชนก รักทำอาหาร', 'RORM-DEMO-003', 3, 'packing', 'processing', 2],
    ['demo_rorm_ton', 'ต้นกล้า วันหยุด', 'RORM-DEMO-004', 5, 'shipped', 'shipped', 3],
    ['demo_rorm_nok', 'นกน้อย ชอบอบ', 'RORM-DEMO-005', 12, 'delivered', 'completed', 4],
];
$created = $skipped = 0;
try {
    $db->beginTransaction();
    $findOrder = $db->prepare('SELECT id FROM orders WHERE order_no=?');
    $findUser = $db->prepare('SELECT id FROM users WHERE username=?');
    $newUser = $db->prepare("INSERT INTO users(username,email,password_hash,full_name,phone,address,role,email_verified_at) VALUES(?,?,?,?,?,'','customer',NOW())");
    $newOrder = $db->prepare("INSERT INTO orders(order_no,user_id,subtotal_amount,total_amount,shipping_name,shipping_phone,shipping_address,payment_method,payment_status,order_status,is_demo,created_at) VALUES(?,?,?,?,?,?,?,'promptpay','paid',?,1,DATE_SUB(NOW(),INTERVAL ? DAY))");
    $newItem = $db->prepare('INSERT INTO order_items(order_id,product_id,seller_id,product_name,price,quantity,subtotal) VALUES(?,?,?,?,?,1,?)');
    $fulfillment = $db->prepare('INSERT INTO order_fulfillments(order_id,seller_id,status,carrier,tracking_number,shipped_at,delivered_at) VALUES(?,?,?,?,?,?,?)');
    foreach ($scenarios as [$username, $fullName, $orderNo, $daysAgo, $status, $orderStatus, $productIndex]) {
        $findOrder->execute([$orderNo]);
        if ($findOrder->fetchColumn()) { $skipped++; continue; }
        $findUser->execute([$username]);
        $userId = (int)$findUser->fetchColumn();
        if (!$userId) {
            $newUser->execute([$username, $username . '@example.invalid', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), $fullName, '0800000000']);
            $userId = (int)$db->lastInsertId();
        }
        $product = $products[$productIndex];
        $price = (float)$product['price'];
        $newOrder->execute([$orderNo, $userId, $price, $price, $fullName, '0800000000', 'ข้อมูลทดลอง 123 ถนนตัวอย่าง กรุงเทพฯ 10000', $orderStatus, $daysAgo]);
        $orderId = (int)$db->lastInsertId();
        $newItem->execute([$orderId, (int)$product['id'], $sellerId, $product['name'], $price, $price]);
        $shippedAt = in_array($status, ['shipped', 'delivered'], true) ? date('Y-m-d H:i:s', strtotime('-' . max(0, $daysAgo - 1) . ' days')) : null;
        $deliveredAt = $status === 'delivered' ? date('Y-m-d H:i:s', strtotime('-10 days')) : null;
        $fulfillment->execute([$orderId, $sellerId, $status, $shippedAt ? 'Demo Express' : null, $shippedAt ? 'DEMO' . $orderId : null, $shippedAt, $deliveredAt]);
        if ($status === 'delivered') {
            $review = $db->prepare("INSERT INTO product_reviews(product_id,user_id,rating,comment,is_demo) VALUES(?,?,5,'ข้อมูลทดลอง: สินค้าตรงตามรายละเอียด จัดส่งเรียบร้อย',1) ON DUPLICATE KEY UPDATE id=id");
            $review->execute([(int)$product['id'], $userId]);
        }
        $created++;
    }
    // One visible low-stock card, with a matching inventory movement.
    $low = $products[5];
    $before = (int)$low['stock_quantity'];
    if ($before > 5) {
        $db->prepare('UPDATE products SET stock_quantity=4 WHERE id=? AND seller_id=?')->execute([(int)$low['id'], $sellerId]);
        $db->prepare("INSERT INTO inventory_movements(seller_id,product_id,movement_type,quantity_change,quantity_before,quantity_after,note,created_by) VALUES(?,?,'adjustment',?,?,4,'ปรับสต็อกเพื่อสาธิตแดชบอร์ด',?)")
            ->execute([$sellerId, (int)$low['id'], 4 - $before, $before, $sellerId]);
    }
    $db->commit();
    echo "Rorm888 dashboard demo: created=$created skipped=$skipped\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
