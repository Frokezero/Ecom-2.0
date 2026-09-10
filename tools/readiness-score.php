<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$checks=[];
function readyCheck(string $group,string $name,bool $passed):void{global $checks;$checks[$group][]=[$name,$passed];}
$testFiles=glob($root.'/tests/*_test.php')?:[];
readyCheck('functionality','automated test coverage',count($testFiles)>=10);
readyCheck('functionality','commerce workflow tests',is_file($root.'/tests/commerce_workflow_test.php'));
readyCheck('functionality','standard page navigation',!is_file($root.'/assets/js/navigation.js'));
readyCheck('functionality','database migrations',count(glob($root.'/database/migrations/*.sql')?:[])>=20);
readyCheck('functionality','health endpoint',is_file($root.'/healthz.php'));
$config=file_get_contents($root.'/config/config.php');$ht=file_get_contents($root.'/.htaccess');
foreach(['Content-Security-Policy','X-Content-Type-Options','Strict-Transport-Security'] as $item)readyCheck('security',$item,str_contains($config,$item));
readyCheck('security','CSRF protection',str_contains(file_get_contents($root.'/includes/functions.php'),'requireCsrf'));
readyCheck('security','rate limiting',str_contains(file_get_contents($root.'/includes/security_monitor.php'),'enforceRequestRate'));
$docker=file_get_contents($root.'/Dockerfile');$compose=file_get_contents($root.'/docker-compose.yml');$ini=file_get_contents($root.'/docker/production.ini');
readyCheck('performance','OPcache enabled',str_contains($ini,'opcache.enable=1'));
readyCheck('performance','compression enabled',str_contains($docker,'deflate'));
readyCheck('performance','static caching',str_contains($ht,'max-age=2592000'));
$optimized=glob($root.'/assets/images/banners/*-v1.jpg')?:[];readyCheck('performance','optimized banners',count($optimized)>=8&&max(array_map('filesize',$optimized))<300*1024);
readyCheck('performance','responsive mobile banners',count(glob($root.'/assets/images/banners/*mobile-v1.jpg')?:[])>=4);
readyCheck('deployment','production environment',str_contains($compose,'APP_ENV:-production'));
readyCheck('deployment','web healthcheck',str_contains($compose,'healthz.php'));
readyCheck('deployment','restart policy',substr_count($compose,'restart: unless-stopped')>=2);
readyCheck('deployment','secret template',is_file($root.'/.env.production.example'));
readyCheck('deployment','persistent uploads',str_contains($compose,'product_videos:'));
$failed=false;foreach($checks as $group=>$items){$passed=count(array_filter($items,fn($x)=>$x[1]));$score=(int)round($passed/count($items)*100);echo strtoupper($group)." $score% ($passed/".count($items).")\n";foreach($items as [$name,$ok])echo '  '.($ok?'PASS':'FAIL').' '.$name."\n";if($score<90)$failed=true;}exit($failed?1:0);
