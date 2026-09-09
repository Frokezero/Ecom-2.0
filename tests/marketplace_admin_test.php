<?php
require_once __DIR__.'/../includes/functions.php';
function marketplaceExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
marketplaceExpect(productDisplayName(['name'=>'กระทะ','seller_id'=>null])==='MALL กระทะ','Platform products must receive the MALL prefix');
marketplaceExpect(productDisplayName(['name'=>'กระทะ','seller_id'=>7])==='กระทะ','Seller products must not receive the MALL prefix');
marketplaceExpect(str_contains(marketplaceVisibilitySql('p'),'store_status'), 'Marketplace visibility must enforce store status');
$root=dirname(__DIR__);foreach(['admin/stores.php','admin/store-detail.php','database/migrations/026_marketplace_store_controls.sql'] as $file)marketplaceExpect(is_file($root.'/'.$file),'Missing marketplace file: '.$file);
$detail=file_get_contents($root.'/admin/store-detail.php');foreach(['seller.store.','seller.permissions.update','product_submission_enabled','order_processing_enabled','payout_enabled','security_events','audit_logs'] as $needle)marketplaceExpect(str_contains($detail,$needle),'Missing store administration capability: '.$needle);
echo "Marketplace admin tests passed\n";
