<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';$db=(new Database())->getConnection();if(!$db){fwrite(STDERR,"CRITICAL database unavailable\n");exit(2);}$issues=[];
$stale=(int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE status IN ('queued','failed','sending') AND created_at<DATE_SUB(NOW(),INTERVAL 5 MINUTE)")->fetchColumn();$dead=(int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE status='dead'")->fetchColumn();if($stale)$issues[]="email_stale=$stale";if($dead)$issues[]="email_dead=$dead";
$migration=(int)$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='020_customer_trust_operations.sql'")->fetchColumn();if(!$migration)$issues[]='migration_020_missing';
if($issues){fwrite(STDERR,'WARNING '.implode(' ',$issues)."\n");exit(1);}echo "OK database=up email_queue=healthy migrations=current\n";
