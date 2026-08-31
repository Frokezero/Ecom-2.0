<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/security_monitor.php';
$db=(new Database())->getConnection();if(!$db)throw new RuntimeException('Database unavailable');
$_SERVER['REMOTE_ADDR']='198.51.100.77';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']='/security-test';$_SERVER['HTTP_USER_AGENT']='KitchenMartSecurityTest/1.0';
$db->beginTransaction();try{$score1=recordSecurityEvent($db,'test.anomaly',10,null,['test'=>true],'tested');$score2=recordSecurityEvent($db,'test.anomaly',10,null,['test'=>true],'tested');if($score1!==10||$score2!==20)throw new RuntimeException('Risk score accumulation failed');securityBlock($db,'ip',securityIpHash(),null,'test block',60,60);if(!securityIsBlocked($db))throw new RuntimeException('Temporary block check failed');$db->rollBack();echo "Security monitor tests passed\n";}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
