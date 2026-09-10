<?php
$root=dirname(__DIR__);$css=file_get_contents($root.'/assets/css/thai-typography.css');
if(!str_contains($css,'html[lang="th"]')||!str_contains($css,'Leelawadee UI'))throw new RuntimeException('Thai typography rules are missing');
foreach(['includes/header.php','includes/admin_header.php'] as $file)if(!str_contains(file_get_contents($root.'/'.$file),'thai-typography.css'))throw new RuntimeException("Thai typography is not loaded by $file");
echo "Thai typography tests passed\n";
