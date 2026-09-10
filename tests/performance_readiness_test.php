<?php
$root=dirname(__DIR__);
function perfExpect(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$docker=file_get_contents($root.'/Dockerfile');$compose=file_get_contents($root.'/docker-compose.yml');$ini=file_get_contents($root.'/docker/production.ini');$upload=file_get_contents($root.'/includes/image_upload.php');
perfExpect(str_contains($docker,'opcache')&&str_contains($ini,'opcache.enable=1'),'Production OPcache is not enabled');
perfExpect(str_contains($docker,'HEALTHCHECK')&&str_contains($compose,'healthz.php'),'Web health check is missing');
perfExpect(substr_count($compose,'restart: unless-stopped')>=2,'Container restart policies are incomplete');
perfExpect(str_contains($upload,'saveOptimizedRaster')&&str_contains($upload,'imagewebp'),'Uploaded images are not optimized');
$banners=glob($root.'/assets/images/banners/*-v1.jpg')?:[];
perfExpect(count($banners)>=8,'Optimized responsive banners are missing');
perfExpect(max(array_map('filesize',$banners))<300*1024,'An optimized banner exceeds 300 KB');
perfExpect(is_file($root.'/tools/load-test.ps1')&&is_file($root.'/tools/readiness-score.php'),'Performance measurement tools are missing');
echo "Performance readiness tests passed\n";
