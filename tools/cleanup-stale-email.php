<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
if (!$db) { fwrite(STDERR, "Database unavailable\n"); exit(1); }

$where = "status IN ('queued','failed','sending') AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
$count = (int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE $where")->fetchColumn();
if (!in_array('--delete', $argv, true)) {
    echo "Stale email records: $count\nRun with --delete to remove them.\n";
    exit;
}

try {
    $db->beginTransaction();
    $deleted = $db->exec("DELETE FROM email_delivery_logs WHERE $where");
    $db->commit();
    echo "Deleted stale email records: $deleted\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Unable to delete stale email records\n");
    exit(1);
}
