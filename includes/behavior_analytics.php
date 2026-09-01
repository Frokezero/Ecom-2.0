<?php
function behaviorMaskIp(string $ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $p=explode('.',$ip); return $p[0].'.'.$p[1].'.x.x'; }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return substr($ip,0,9).'::xxxx';
    return 'unknown';
}
function behaviorClientIp(): string {
    $ip=trim(explode(',',(string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'))[0]);
    return filter_var($ip,FILTER_VALIDATE_IP) ? $ip : 'unknown';
}
function behaviorLog(PDO $db, array $data): int {
    $ip=(string)($data['ip_address']??behaviorClientIp());
    $stmt=$db->prepare('INSERT INTO user_activity_logs(user_id,occurred_at,ip_address,ip_hash,action,url,http_status,response_time_ms,login_success,request_count,order_amount,actual_label,source,experiment_case_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$data['user_id']??null,$data['occurred_at']??date('Y-m-d H:i:s'),behaviorMaskIp($ip),hash('sha256',$ip),(string)($data['action']??'request'),substr((string)($data['url']??($_SERVER['REQUEST_URI']??'/')),0,500),(int)($data['status']??200),(float)($data['response_time']??0),array_key_exists('login_success',$data)?$data['login_success']:null,max(1,(int)($data['request_count']??1)),max(0,(float)($data['order_amount']??0)),$data['actual_label']??null,$data['source']??'runtime',$data['experiment_case_id']??null]);
    return (int)$db->lastInsertId();
}
function behaviorFeatures(PDO $db, ?int $userId, string $at, string $source='runtime', ?string $caseId=null): array {
    $identity=$userId===null?('user_id IS NULL AND ip_hash='.$db->quote(hash('sha256',behaviorClientIp()))):'user_id='.(int)$userId;
    $scope="source=".$db->quote($source).($caseId!==null?' AND experiment_case_id='.$db->quote($caseId):'');
    $end=$db->quote($at);
    $q=function(string $sql)use($db){return (float)$db->query($sql)->fetchColumn();};
    return [
      'login_per_min'=>$q("SELECT COUNT(*) FROM user_activity_logs WHERE $identity AND $scope AND action IN ('login.attempt','login.success','login.failed') AND occurred_at>DATE_SUB($end,INTERVAL 60 SECOND) AND occurred_at<=$end"),
      'request_per_min'=>$q("SELECT COALESCE(SUM(request_count),0) FROM user_activity_logs WHERE $identity AND $scope AND action='request' AND occurred_at>DATE_SUB($end,INTERVAL 60 SECOND) AND occurred_at<=$end"),
      'order_per_hour'=>$q("SELECT COUNT(*) FROM user_activity_logs WHERE $identity AND $scope AND action='order.created' AND occurred_at>DATE_SUB($end,INTERVAL 3600 SECOND) AND occurred_at<=$end"),
      'failed_login_per_10min'=>$q("SELECT COUNT(*) FROM user_activity_logs WHERE $identity AND $scope AND action='login.failed' AND occurred_at>DATE_SUB($end,INTERVAL 600 SECOND) AND occurred_at<=$end")
    ];
}
function behaviorThresholds(PDO $db): array {
    $rows=$db->query('SELECT * FROM behavior_baselines ORDER BY id')->fetchAll();$out=[];foreach($rows as $r)$out[$r['feature_code']]=$r;return $out;
}
function behaviorEvaluateRuntime(PDO $db, int $activityId, ?int $userId, string $at): array {
    $features=behaviorFeatures($db,$userId,$at);$rules=behaviorThresholds($db);$hits=[];
    foreach($features as $code=>$value){if(!isset($rules[$code])||$value<=(float)$rules[$code]['threshold_value'])continue;$action='alerted';
      if($code==='request_per_min'){$action='temporary_ip_block_15m';if(function_exists('securityBlock'))securityBlock($db,'ip',hash('sha256',behaviorClientIp()),$userId,'Behavior threshold: '.$code,70,900);}
      elseif($code==='order_per_hour'&&$userId){$action='temporary_user_block_30m';if(function_exists('securityBlock'))securityBlock($db,'user',hash('sha256','user:'.$userId),$userId,'Behavior threshold: '.$code,70,1800);}
      $stmt=$db->prepare("INSERT INTO behavior_detections(user_id,activity_log_id,feature_code,window_started_at,window_ended_at,observed_value,threshold_value,predicted_label,detection_time_ms,action_taken) VALUES(?,?,?,DATE_SUB(?,INTERVAL ? SECOND),?,?,?,?,?,?)");
      $stmt->execute([$userId,$activityId,$code,$at,(int)$rules[$code]['window_seconds'],$at,$value,(float)$rules[$code]['threshold_value'],'suspicious',0,$action]);$hits[]=$code;
      if(function_exists('recordSecurityEvent'))recordSecurityEvent($db,'behavior.threshold',70,$userId,['feature'=>$code,'observed'=>$value,'threshold'=>(float)$rules[$code]['threshold_value']],$action);
    } return $hits;
}
