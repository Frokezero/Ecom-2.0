<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/transactional_mail.php';
$db=(new Database())->getConnection();if(!$db){fwrite(STDERR,"Database unavailable\n");exit(1);}
$limit=max(1,min(100,(int)($argv[1]??20)));
$db->exec("UPDATE email_delivery_logs SET status='failed',locked_at=NULL,next_attempt_at=NOW() WHERE status='sending' AND locked_at<DATE_SUB(NOW(),INTERVAL 15 MINUTE)");
$stmt=$db->prepare("SELECT dedupe_key FROM email_delivery_logs WHERE status IN ('queued','failed') AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY id LIMIT $limit");$stmt->execute();$sent=0;$failed=0;
foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $key){processQueuedEmail($db,(string)$key)?$sent++:$failed++;}
echo "processed=".($sent+$failed)." sent=$sent failed=$failed\n";exit($failed?2:0);
