<?php
function qualityExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}$root=dirname(__DIR__);
$header=file_get_contents($root.'/includes/header.php');foreach(['name="description"','rel="canonical"','property="og:title"','twitter:card','skip-link','application/ld+json'] as $needle)qualityExpect(str_contains($header,$needle),'Missing storefront metadata: '.$needle);
$product=file_get_contents($root.'/product-detail.php');qualityExpect(str_contains($product,"'@type'=>'Product'")&&str_contains($product,"'@type'=>'Offer'"),'Product structured data missing');
qualityExpect(is_file($root.'/robots.txt')&&is_file($root.'/sitemap.php')&&is_file($root.'/site.webmanifest'),'Crawler or manifest files missing');
$apache=file_get_contents($root.'/.htaccess');qualityExpect(str_contains($apache,'Cache-Control')&&str_contains($apache,'DEFLATE'),'Static caching or compression missing');
echo "Storefront quality tests passed\n";
