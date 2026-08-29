<?php
declare(strict_types=1);

// Populate imported catalog demo values. Prices are intentionally editable estimates.
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) { fwrite(STDERR, "Database connection failed.\n"); exit(1); }

$rows = $db->query('SELECT p.id, p.name, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id')->fetchAll();
$update = $db->prepare('UPDATE products SET description = ?, price = ?, stock_quantity = ?, is_featured = ?, approval_status = \'approved\' WHERE id = ?');
$featuredPerCategory = [];
$count = 0;

foreach ($rows as $row) {
    $category = (string)$row['category'];
    $name = trim((string)$row['name']);
    $lower = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    $isFeatured = (($featuredPerCategory[$category] ?? 0) < 2) ? 1 : 0;
    $featuredPerCategory[$category] = ($featuredPerCategory[$category] ?? 0) + $isFeatured;

    if (str_contains($category, 'เครื่องใช้ไฟฟ้า')) {
        $price = str_contains($lower, 'ไมโครเวฟ') || str_contains($lower, 'เตาอบ') ? 2490 : 890;
        if (str_contains($lower, 'ตู้')) $price = 6990;
        $stock = 8;
        $use = 'ใช้งานในครัวเรือน ช่วยประหยัดเวลาและทำอาหารได้สะดวกยิ่งขึ้น';
    } elseif (str_contains($category, 'เครื่องเก็บ')) {
        $price = str_contains($lower, 'ตู้แช่') ? 6990 : (str_contains($lower, 'ซีล') ? 1290 : 249);
        $stock = 12;
        $use = 'ช่วยจัดเก็บและถนอมอาหารให้เป็นระเบียบ รักษาความสดได้นานขึ้น';
    } elseif (str_contains($category, 'หม้อ') || str_contains($category, 'กระทะ')) {
        $price = str_contains($lower, 'ชุดหม้อ') ? 1590 : (str_contains($lower, 'สเตนเลส') ? 890 : 590);
        $stock = 15;
        $use = 'เหมาะสำหรับทำอาหารประจำวัน ใช้งานง่ายและดูแลรักษาไม่ยุ่งยาก';
    } elseif (str_contains($category, 'มีด')) {
        $price = str_contains($lower, 'เขียง') ? 390 : 249;
        $stock = 20;
        $use = 'อุปกรณ์เตรียมอาหาร จับถนัดมือ เหมาะสำหรับใช้งานในครัว';
    } elseif (str_contains($category, 'เบเกอรี่')) {
        $price = str_contains($lower, 'เตาอบ') || str_contains($lower, 'เครื่องผสม') ? 1290 : 199;
        $stock = 18;
        $use = 'เหมาะสำหรับทำขนมและเบเกอรี่ที่บ้าน ใช้งานได้หลากหลาย';
    } elseif (str_contains($category, 'ทำความสะอาด')) {
        $price = str_contains($lower, 'ม็อบ') || str_contains($lower, 'ไม้กวาด') ? 399 : 99;
        $stock = 25;
        $use = 'ช่วยให้การทำความสะอาดครัวสะดวก รวดเร็ว และถูกสุขอนามัย';
    } else {
        $price = str_contains($lower, 'ชุด') ? 499 : 159;
        $stock = 20;
        $use = 'ดีไซน์เรียบง่าย เหมาะสำหรับใช้งานในครัวและรับประทานอาหาร';
    }

    $description = "{$name} สินค้าคุณภาพสำหรับครัว {$use} วัสดุแข็งแรง ทำความสะอาดง่าย เหมาะสำหรับบ้าน ร้านอาหาร และคาเฟ่";
    $update->execute([$description, $price, $stock, $isFeatured, (int)$row['id']]);
    $count++;
}

echo "Enriched {$count} catalog products.\n";
