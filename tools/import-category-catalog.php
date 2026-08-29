<?php
declare(strict_types=1);

// One-time catalog reset/import. Run from the project root with PHP CLI.
require_once __DIR__ . '/../config/database.php';

$sourceRoot = realpath(__DIR__ . '/../หมวดสินค้า');
$destinationRoot = __DIR__ . '/../assets/images/products/catalog';
if ($sourceRoot === false || !is_dir($sourceRoot)) {
    fwrite(STDERR, "Source folder not found.\n");
    exit(1);
}

$categories = [
    'จานชามและช้อนส้อม',
    'มีดและเขียง',
    'หม้อและกระทะ',
    'อุปกรณ์ทำความสะอาดครัว',
    'อุปกรณ์เบเกอรี่',
    'เครื่องเก็บรักษาอาหาร',
    'เครื่องใช้ไฟฟ้าในครัว',
];

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

if (!is_dir($destinationRoot) && !mkdir($destinationRoot, 0775, true) && !is_dir($destinationRoot)) {
    fwrite(STDERR, "Could not create destination folder.\n");
    exit(1);
}

$db->beginTransaction();
try {
    // Reuse category IDs 1..7 so existing seller profile references remain valid.
    $categoryIds = [];
    $selectCategory = $db->prepare('SELECT id FROM categories WHERE id = ?');
    $updateCategory = $db->prepare('UPDATE categories SET name = ? WHERE id = ?');
    $insertCategory = $db->prepare('INSERT INTO categories (name) VALUES (?)');
    foreach ($categories as $index => $categoryName) {
        $id = $index + 1;
        $selectCategory->execute([$id]);
        if ($selectCategory->fetchColumn() === false) {
            $insertCategory->execute([$categoryName]);
            $id = (int)$db->lastInsertId();
        } else {
            $updateCategory->execute([$categoryName, $id]);
        }
        $categoryIds[$categoryName] = $id;
    }

    // Remove only catalog rows. Order history is intentionally untouched.
    $db->exec('DELETE FROM products');
    $insertProduct = $db->prepare(
        'INSERT INTO products (category_id, seller_id, name, description, price, stock_quantity, image_url, is_featured, approval_status) ' .
        'VALUES (?, NULL, ?, ?, 0.00, 0, ?, 0, \'pending\')'
    );
    $total = 0;
    foreach ($categories as $categoryName) {
        $sourceDir = $sourceRoot . DIRECTORY_SEPARATOR . $categoryName;
        if (!is_dir($sourceDir)) continue;
        $categoryDir = $destinationRoot . DIRECTORY_SEPARATOR . $categoryIds[$categoryName];
        if (!is_dir($categoryDir) && !mkdir($categoryDir, 0775, true) && !is_dir($categoryDir)) {
            throw new RuntimeException('Could not create category image folder.');
        }
        $files = scandir($sourceDir) ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($sourceFile)) continue;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;
            $name = pathinfo($file, PATHINFO_FILENAME);
            $safeFile = hash('sha256', $categoryName . '/' . $file) . '.' . $extension;
            $targetFile = $categoryDir . DIRECTORY_SEPARATOR . $safeFile;
            if (!copy($sourceFile, $targetFile)) throw new RuntimeException('Could not copy image: ' . $file);
            $relativeUrl = 'assets/images/products/catalog/' . $categoryIds[$categoryName] . '/' . $safeFile;
            $insertProduct->execute([$categoryIds[$categoryName], trim($name), '', $relativeUrl]);
            $total++;
        }
    }
    $db->commit();
    echo "Imported {$total} draft products across " . count($categories) . " categories.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
