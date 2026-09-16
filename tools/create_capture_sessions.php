<?php
// Local, temporary read-only browser sessions for documentation screenshots.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) { fwrite(STDERR, "Database unavailable\n"); exit(1); }
$queries = [
    'customer' => "SELECT u.* FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE u.role='customer' GROUP BY u.id ORDER BY COUNT(o.id) DESC,u.id LIMIT 1",
    'seller' => "SELECT u.* FROM users u JOIN seller_profiles sp ON sp.user_id=u.id AND sp.status='approved' LEFT JOIN products p ON p.seller_id=u.id WHERE u.role='seller' GROUP BY u.id ORDER BY COUNT(p.id) DESC,u.id LIMIT 1",
    'admin' => "SELECT * FROM users WHERE role='admin' ORDER BY id LIMIT 1",
];
$result = [];
session_write_close();
foreach ($queries as $role => $sql) {
    $user = $db->query($sql)->fetch();
    if (!$user) continue;
    $sid = 'cap' . bin2hex(random_bytes(12));
    session_id($sid);
    session_start();
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_role'] = $role;
    $_SESSION['auth_version'] = (int)$user['auth_version'];
    session_write_close();
    $result[$role] = ['session_id' => $sid, 'user_id' => (int)$user['id']];
}
$result['product_id'] = (int)$db->query("SELECT id FROM products WHERE approval_status='approved' ORDER BY id LIMIT 1")->fetchColumn();
$result['seller_product_id'] = (int)$db->query("SELECT id FROM products WHERE seller_id=" . (int)($result['seller']['user_id'] ?? 0) . " ORDER BY id LIMIT 1")->fetchColumn();
$result['order_id'] = (int)$db->query("SELECT id FROM orders ORDER BY id LIMIT 1")->fetchColumn();
$result['customer_order_id'] = (int)$db->query("SELECT id FROM orders WHERE user_id=" . (int)($result['customer']['user_id'] ?? 0) . " ORDER BY id LIMIT 1")->fetchColumn();
$result['seller_id'] = (int)($result['seller']['user_id'] ?? 0);
$productStmt = $db->prepare("SELECT id,seller_id,name,price,image_url FROM products WHERE id=? LIMIT 1");
$productStmt->execute([$result['product_id']]);
$product = $productStmt->fetch();
if ($product && isset($result['customer'])) {
    session_id($result['customer']['session_id']);
    session_start();
    $_SESSION['cart'] = [(string)$product['id'] => [
        'id' => (int)$product['id'], 'seller_id' => $product['seller_id'] === null ? null : (int)$product['seller_id'],
        'variant_id' => 0, 'variant_sku' => null, 'variant_name' => null,
        'name' => $product['name'], 'price' => (float)$product['price'],
        'image_url' => $product['image_url'], 'quantity' => 1,
    ]];
    session_write_close();
}
echo json_encode($result, JSON_UNESCAPED_UNICODE);
