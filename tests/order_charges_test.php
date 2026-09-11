<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$db=(new Database())->getConnection();
if(!$db)throw new RuntimeException('Database unavailable');
$fee=(float)appConfig('MALL_SHIPPING_FEE','50');
$minimum=(float)appConfig('MALL_FREE_SHIPPING_MIN','1000');
$rate=(float)appConfig('VAT_RATE','7');
$low=max(1,min(100,$minimum>1?$minimum-1:1));
$charges=calculateOrderCharges($db,[['id'=>1,'seller_id'=>null,'price'=>$low,'quantity'=>1]]);
$expectedShipping=$low>=$minimum?0:$fee;
$expectedTax=round(($low+$expectedShipping)*$rate/100,2);
if(abs($charges['shipping']-$expectedShipping)>.001||abs($charges['tax']-$expectedTax)>.001||abs($charges['total']-($low+$expectedShipping+$expectedTax))>.001)throw new RuntimeException('Mall shipping/VAT calculation mismatch');
$free=calculateOrderCharges($db,[['id'=>1,'seller_id'=>null,'price'=>$low,'quantity'=>1]],0,['discount_type'=>'free_shipping','seller_id'=>null,'mall_only'=>1]);
if($free['shipping']!==0.0)throw new RuntimeException('Free-shipping coupon was not applied');
$sellerFree=calculateOrderCharges($db,[['id'=>1,'seller_id'=>999999,'price'=>$low,'quantity'=>1]],0,['discount_type'=>'free_shipping','seller_id'=>null,'mall_only'=>1]);
if(abs($sellerFree['platform_shipping_subsidy']-$fee*.60)>.001||abs($sellerFree['seller_shipping_subsidy']-$fee*.40)>.001)throw new RuntimeException('Mall/seller 60/40 shipping subsidy mismatch');
echo "Order charge tests passed\n";
