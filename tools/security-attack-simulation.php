<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/security_monitor.php';
if(appConfig('APP_ENV','development')==='production'){fwrite(STDERR,"Refusing to run attack simulation in production.\n");exit(2);}
$base=$argv[1]??'http://127.0.0.1:8000';$url=parse_url($base);
if(!$url||!in_array(strtolower((string)($url['host']??'')),['127.0.0.1','localhost'],true)){fwrite(STDERR,"Only localhost targets are allowed.\n");exit(2);}
$db=(new Database())->getConnection();if(!$db){fwrite(STDERR,"Database unavailable\n");exit(1);}
$checks=[];$assert=function(bool $ok,string $name,string $detail='')use(&$checks){$checks[]=['check'=>$name,'passed'=>$ok,'detail'=>$detail];if(!$ok)throw new RuntimeException($name.($detail!==''?': '.$detail:''));};
$_SERVER['REMOTE_ADDR']='198.51.100.200';$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REQUEST_URI']='/api/auth.php';$_SERVER['HTTP_USER_AGENT']='KitchenMartControlledAttackSimulation/1.0';
$db->beginTransaction();
try{
    $identityRule=securityRule($db,'login_fail_identity',['threshold_count'=>10,'window_seconds'=>900,'risk_points'=>10,'block_seconds'=>900]);
    $ipRule=securityRule($db,'login_fail_ip',['threshold_count'=>20,'window_seconds'=>900,'risk_points'=>10,'block_seconds'=>3600]);
    $assert((int)$identityRule['threshold_count']===10,'Identity lock threshold','expected 10');
    $assert((int)$ipRule['threshold_count']===20,'IP stuffing threshold','expected 20');
    $identityHash=hash_hmac('sha256','simuser001',appConfig('APP_KEY','kitchenmart-local-rate-key'));$ipHash=securityIpHash();
    $attempt=$db->prepare('INSERT INTO login_attempts(identifier_hash,ip_hash,was_successful) VALUES(?,?,0)');
    for($i=1;$i<=10;$i++){$attempt->execute([$identityHash,$ipHash]);recordSecurityEvent($db,'login.failed',10,null,['identifier_hash'=>$identityHash,'attempt'=>$i],'simulated');}
    $count=$db->prepare('SELECT COUNT(*) FROM login_attempts WHERE identifier_hash=? AND was_successful=0');$count->execute([$identityHash]);
    $assert((int)$count->fetchColumn()===10,'Ten failed logins detected');
    $event=$db->prepare("SELECT COUNT(*) FROM security_events WHERE event_type='login.failed' AND action_taken='simulated' AND ip_hash=?");$event->execute([$ipHash]);
    $assert((int)$event->fetchColumn()===10,'Security events recorded');
    securityBlock($db,'ip',$ipHash,null,'controlled credential stuffing simulation',70,(int)$ipRule['block_seconds']);
    $assert((bool)securityIsBlocked($db),'Temporary block enforcement');
    $db->rollBack();
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();$checks[]=['check'=>'Transactional simulation','passed'=>false,'detail'=>$e->getMessage()];}

function simulationRequest(string $url,string $method='GET',array $fields=[]):array{
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_USERAGENT=>'KitchenMartControlledAttackSimulation/1.0']);
    if($method==='POST'){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($fields));}
    $start=microtime(true);$body=curl_exec($ch);$elapsed=(microtime(true)-$start)*1000;$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);return ['status'=>$status,'ms'=>$elapsed,'body'=>(string)$body,'error'=>$error];
}
if(!function_exists('curl_init')){$checks[]=['check'=>'Local HTTP simulation','passed'=>false,'detail'=>'PHP cURL extension unavailable'];}
else{
    $home=simulationRequest(rtrim($base,'/').'/');$checks[]=['check'=>'Homepage availability','passed'=>$home['status']===200,'detail'=>'HTTP '.$home['status']];
    $admin=simulationRequest(rtrim($base,'/').'/admin/index.php');$checks[]=['check'=>'Unauthorized admin access rejected','passed'=>in_array($admin['status'],[302,403],true),'detail'=>'HTTP '.$admin['status']];
    $csrf=simulationRequest(rtrim($base,'/').'/api/auth.php','POST',['action'=>'login','username_email'=>'simuser001','password'=>'wrong']);$checks[]=['check'=>'CSRF protection','passed'=>$csrf['status']===403,'detail'=>'HTTP '.$csrf['status']];
    $times=[];$success=0;for($i=0;$i<50;$i++){$response=simulationRequest(rtrim($base,'/').'/');$times[]=$response['ms'];if($response['status']===200)$success++;}
    sort($times);$p95=$times[(int)floor((count($times)-1)*.95)]??0;$checks[]=['check'=>'Controlled request burst','passed'=>$success===50,'detail'=>'50 requests, success='.$success.', p95='.number_format($p95,1).'ms'];
}
$passed=count(array_filter($checks,fn($item)=>$item['passed']));$failed=count($checks)-$passed;
echo json_encode(['target'=>$base,'scope'=>'localhost-only','database_changes'=>'rolled_back','passed'=>$passed,'failed'=>$failed,'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";exit($failed?1:0);
