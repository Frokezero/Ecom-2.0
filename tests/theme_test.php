<?php
$root=dirname(__DIR__);$theme=file_get_contents($root.'/assets/css/sunrise-theme.css');
foreach(['--primary:#244A3A','--orange:#E86A33','--paper:#FAF8F3','.admin-sidebar','.seller-hero','footer'] as $needle)if(!str_contains($theme,$needle))throw new RuntimeException('Theme coverage missing: '.$needle);
foreach(['includes/header.php','includes/admin_header.php'] as $file)if(!str_contains(file_get_contents($root.'/'.$file),'sunrise-theme.css'))throw new RuntimeException('Theme is not loaded by '.$file);
echo "Sunrise theme tests passed\n";
