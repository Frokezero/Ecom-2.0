<?php
require_once __DIR__.'/functions.php';

function securityClientIp():string{
 $remote=(string)($_SERVER['REMOTE_ADDR']??'unknown');
 if(appConfig('TRUST_CLOUDFLARE','0')==='1'&&!empty($_SERVER['HTTP_CF_CONNECTING_IP'])&&filter_var($_SERVER['HTTP_CF_CONNECTING_IP'],FILTER_VALIDATE_IP))return (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
 return filter_var($remote,FILTER_VALIDATE_IP)?$remote:'unknown';
}
function securityIpHash(?string $ip=null):string{return hash_hmac('sha256',$ip??securityClientIp(),appConfig('APP_KEY','kitchenmart-local-security-key'));}
function securityMaskIp(string $ip):string{if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){$p=explode('.',$ip);return $p[0].'.'.$p[1].'.xxx.xxx';}if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))return substr($ip,0,8).'…';return 'unknown';}
function securitySeverity(int $score):string{return $score>=80?'critical':($score>=60?'high':($score>=30?'medium':'low'));}
function securityIsBlocked(PDO $db,?int $userId=null):?array{
 $ipHash=securityIpHash();$db->prepare("UPDATE security_blocks SET is_active=0,released_at=NOW() WHERE is_active=1 AND blocked_until<=NOW()")->execute();
 $sql="SELECT * FROM security_blocks WHERE is_active=1 AND blocked_until>NOW() AND ((target_type='ip' AND target_hash=?)";$params=[$ipHash];
 if($userId){$sql.=" OR (target_type='user' AND user_id=?)";$params[]=$userId;}$sql.=') ORDER BY risk_score DESC LIMIT 1';$stmt=$db->prepare($sql);$stmt->execute($params);return $stmt->fetch()?:null;
}
function securityBlock(PDO $db,string $targetType,string $targetHash,?int $userId,string $reason,int $score,int $seconds,?int $adminId=null):void{
 $update=$db->prepare("UPDATE security_blocks SET reason=?,risk_score=GREATEST(risk_score,?),blocked_until=GREATEST(blocked_until,DATE_ADD(NOW(),INTERVAL ? SECOND)),created_by=?,released_at=NULL WHERE target_type=? AND target_hash=? AND is_active=1");$update->execute([$reason,$score,$seconds,$adminId,$targetType,$targetHash]);if($update->rowCount())return;
 $stmt=$db->prepare("INSERT INTO security_blocks(target_type,target_hash,user_id,reason,risk_score,blocked_until,created_by) VALUES(?,?,?,?,?,DATE_ADD(NOW(),INTERVAL ? SECOND),?)");$stmt->execute([$targetType,$targetHash,$userId,$reason,$score,$seconds,$adminId]);
}
function recordSecurityEvent(PDO $db,string $type,int $points,?int $userId=null,array $metadata=[],string $action='logged'):int{
 $ip=securityClientIp();$ipHash=securityIpHash($ip);$country=substr(strtoupper((string)($_SERVER['HTTP_CF_IPCOUNTRY']??'')),0,2)?:null;$path=mb_substr((string)($_SERVER['REQUEST_URI']??''),0,500);$agent=mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500);
 $recent=$db->prepare('SELECT COALESCE(MAX(risk_score),0) FROM security_events WHERE created_at>DATE_SUB(NOW(),INTERVAL 1 HOUR) AND (ip_hash=? OR (? IS NOT NULL AND user_id=?))');$recent->execute([$ipHash,$userId,$userId]);$score=min(100,$points+(int)$recent->fetchColumn());$severity=securitySeverity($score);
 $stmt=$db->prepare('INSERT INTO security_events(event_type,severity,risk_score,user_id,ip_hash,ip_masked,country_code,request_method,request_path,user_agent,metadata_json,action_taken) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute([$type,$severity,$score,$userId,$ipHash,securityMaskIp($ip),$country,substr((string)($_SERVER['REQUEST_METHOD']??'CLI'),0,10),$path,$agent,$metadata?json_encode($metadata,JSON_UNESCAPED_UNICODE):null,$action]);
 if($score>=60){$seconds=$score>=80?3600:900;securityBlock($db,'ip',$ipHash,$userId,'ตรวจพบกิจกรรมเสี่ยง: '.$type,$score,$seconds);createRoleNotification($db,'admin','security','ตรวจพบกิจกรรมผิดปกติ','เหตุการณ์ '.$type.' มีคะแนนความเสี่ยง '.$score.'/100',BASE_URL.'admin/security-center.php');}
 return $score;
}
function enforceSecurityBlock(PDO $db,?int $userId=null,bool $json=true):void{
 $block=securityIsBlocked($db,$userId);if(!$block)return;$retry=max(1,strtotime($block['blocked_until'])-time());
 if($json)jsonResponse('error','ระบบระงับการใช้งานชั่วคราวเนื่องจากตรวจพบกิจกรรมผิดปกติ',['retry_after'=>$retry],429);
 http_response_code(429);header('Retry-After: '.$retry);exit('Temporarily blocked');
}
function enforceRequestRate(PDO $db,string $endpoint,int $limit,int $windowSeconds,?int $userId=null):void{
 $ipHash=securityIpHash();$bucket=(int)floor(time()/$windowSeconds);$key=hash('sha256',$ipHash.'|'.$endpoint.'|'.$bucket);$start=date('Y-m-d H:i:s',$bucket*$windowSeconds);$expires=date('Y-m-d H:i:s',($bucket+1)*$windowSeconds);
 $stmt=$db->prepare('INSERT INTO request_rate_counters(bucket_key,endpoint,ip_hash,request_count,window_started_at,expires_at) VALUES(?,?,?,1,?,?) ON DUPLICATE KEY UPDATE request_count=request_count+1');$stmt->execute([$key,substr($endpoint,0,100),$ipHash,$start,$expires]);$read=$db->prepare('SELECT request_count FROM request_rate_counters WHERE bucket_key=?');$read->execute([$key]);$count=(int)$read->fetchColumn();
 if(random_int(1,100)===1)$db->prepare('DELETE FROM request_rate_counters WHERE expires_at<DATE_SUB(NOW(),INTERVAL 1 DAY)')->execute();
 if($count<=$limit)return;$score=recordSecurityEvent($db,'request.rate_limit',30,$userId,['endpoint'=>$endpoint,'count'=>$count,'limit'=>$limit,'window_seconds'=>$windowSeconds],'blocked');securityBlock($db,'ip',$ipHash,$userId,'Request เกินกำหนด: '.$endpoint,max(60,$score),900);jsonResponse('error','ส่งคำขอถี่เกินไป กรุณารอสักครู่',['retry_after'=>max(1,strtotime($expires)-time())],429);
}
