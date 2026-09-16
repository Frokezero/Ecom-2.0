<?php
require_once __DIR__ . '/../config/database.php';
echo 'DB_HOST=' . appConfig('DB_HOST', '127.0.0.1') . ':' . appConfig('DB_PORT', '3306') . "\n";
$db = (new Database())->getConnection();
if (!$db) { echo "DB_DOWN\n"; exit(1); }
foreach (['users','products','orders','sellers','categories'] as $table) {
    try { echo $table . '=' . $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() . "\n"; }
    catch (Throwable $e) { echo $table . "=ERR\n"; }
}
foreach ($db->query('SELECT role, COUNT(*) n, MIN(id) first_id FROM users GROUP BY role') as $row) {
    echo 'role=' . $row['role'] . ',count=' . $row['n'] . ',first_id=' . $row['first_id'] . "\n";
}
foreach ($db->query('SELECT status, COUNT(*) n FROM seller_profiles GROUP BY status') as $row) {
    echo 'seller_status=' . $row['status'] . ',count=' . $row['n'] . "\n";
}
foreach ($db->query("SELECT u.id,COUNT(p.id) n FROM users u LEFT JOIN products p ON p.seller_id=u.id WHERE u.role='seller' GROUP BY u.id") as $row) {
    echo 'seller_id=' . $row['id'] . ',products=' . $row['n'] . "\n";
}
