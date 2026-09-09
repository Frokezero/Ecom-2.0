<?php
// api/products.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = (new Database())->getConnection();

if (!$db) {
    jsonResponse('error', 'Database connection failed');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.approval_status='approved' AND ".marketplaceVisibilitySql('p'));
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        $product['name']=productDisplayName($product);
        $product['original_price']=(float)($product['compare_at_price']?:$product['price']);
        $product['price']=productEffectivePrice($product);
        $product['on_sale']=$product['price']<$product['original_price'];
        jsonResponse('success', 'พบข้อมูลสินค้า', $product);
    } else {
        jsonResponse('error', 'ไม่พบสินค้า', [], 404);
    }
}

$q = trim($_GET['q'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.approval_status='approved' AND ".marketplaceVisibilitySql('p');
$params = [];

if (!empty($q)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

jsonResponse('success', 'ดึงข้อมูลสินค้าสำเร็จ', $products);
