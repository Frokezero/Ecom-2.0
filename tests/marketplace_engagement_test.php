<?php
function engagementExpect(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}$root=dirname(__DIR__);
foreach(['database/migrations/027_marketplace_engagement.sql','api/marketplace.php','seller-marketplace.php','messages.php','admin/product-reports.php'] as $file)engagementExpect(is_file($root.'/'.$file),'Missing marketplace module: '.$file);
$migration=file_get_contents($root.'/database/migrations/027_marketplace_engagement.sql');foreach(['store_followers','product_reports','store_conversations','store_messages','product_bundles','sale_price','fulfillment_type'] as $needle)engagementExpect(str_contains($migration,$needle),'Missing marketplace schema: '.$needle);
$detail=file_get_contents($root.'/product-detail.php');foreach(['FLASH SALE','Bundle Deals','toggleStoreFollow','reportProduct','shareProduct','สินค้าอื่นจากร้านนี้'] as $needle)engagementExpect(str_contains($detail,$needle),'Missing product marketplace feature: '.$needle);
$checkout=file_get_contents($root.'/api/checkout.php');engagementExpect(str_contains($checkout,'applyActiveBundles'),'Bundle discount is not applied at checkout');
echo "Marketplace engagement tests passed\n";
