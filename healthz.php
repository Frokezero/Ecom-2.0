<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$started = microtime(true);
$db = (new Database())->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode(['status'=>'unhealthy','database'=>'down']);
    exit;
}
try {
    $db->query('SELECT 1')->fetchColumn();
    $migration = $db->query('SELECT MAX(applied_at) FROM schema_migrations')->fetchColumn();
    echo json_encode(['status'=>'healthy','database'=>'up','migrations'=>(bool)$migration,'response_ms'=>round((microtime(true)-$started)*1000,2)]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status'=>'unhealthy','database'=>'error']);
}
