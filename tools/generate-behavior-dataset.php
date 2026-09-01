<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only\n");}
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/behavior_analytics.php';
$db=(new Database())->getConnection();if(!$db)exit("Database unavailable\n");
$users=$db->query("SELECT id,username FROM users WHERE email LIKE '%@simulation.kitchenmart.test' ORDER BY id LIMIT 100")->fetchAll();if(count($users)!==100)exit("Need exactly 100 simulated users; run tools/seed-simulated-users.php first\n");
$ids=array_column($users,'id');$marks=implode(',',array_fill(0,count($ids),'?'));$db->beginTransaction();
try{$db->prepare("DELETE FROM user_activity_logs WHERE source='simulation' AND user_id IN ($marks)")->execute($ids);$insert=$db->prepare('INSERT INTO user_activity_logs(user_id,occurred_at,ip_address,ip_hash,action,url,http_status,response_time_ms,login_success,request_count,order_amount,actual_label,source,experiment_case_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,\'simulation\',?)');$count=0;$start=strtotime(date('Y-m-d 00:00:00').' -29 days');
foreach($users as $ui=>$u){$suspiciousUser=$ui>=80;$ip='198.51.100.'.(($ui%200)+1);for($day=0;$day<30;$day++){$case=sprintf('U%03d-D%02d',$ui+1,$day+1);$actual=($suspiciousUser?'suspicious':'normal');$base=$start+$day*86400+9*3600+($ui%20)*60;
  $rows=[[$base,'login.success','/api/auth.php',200,1,1,0],[$base+120,'request','/products.php',200,null,12+($ui+$day)%7,0],[$base+600,'request','/product.php',200,null,14+($ui*3+$day)%9,0]];
  if(!$suspiciousUser&&($ui+$day)%2===0)$rows[]=[$base+1800,'order.created','/api/checkout.php',201,null,1,250+(($ui+$day)%8)*100];
  if(!$suspiciousUser&&($ui+$day)%5===0)$rows[]=[$base+2400,'login.failed','/api/auth.php',401,0,1,0];
  if($suspiciousUser){$variant=($ui+$day)%10;if($variant===0){for($x=0;$x<5;$x++)$rows[]=[$base+3000+$x*61,'login.failed','/api/auth.php',401,0,1,0];}else{for($x=0;$x<10;$x++)$rows[]=[$base+3000+$x*20,'login.failed','/api/auth.php',401,0,1,0];$rows[]=[$base+3300,'request','/admin/index.php',403,null,500,0];for($x=0;$x<6;$x++)$rows[]=[$base+3500+$x*20,'order.created','/api/checkout.php',201,null,1,999];}}
  elseif((($ui*31+$day)%97)===0)$rows[]=[$base+3300,'request','/products.php',200,null,55,0];
  foreach($rows as $r){$insert->execute([(int)$u['id'],date('Y-m-d H:i:s',$r[0]),behaviorMaskIp($ip),hash('sha256',$ip),$r[1],$r[2],$r[3],20+(($ui+$day)%80),$r[4],$r[5],$r[6],$actual,$case]);$count++;}
}}$db->commit();echo "Generated $count log rows, 100 users, 30 days, 2400 normal cases, 600 suspicious cases.\n";}catch(Throwable $e){$db->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
