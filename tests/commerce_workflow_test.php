<?php
require_once __DIR__.'/../includes/commerce_workflow.php';
function workflowExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
workflowExpect(allowedOrderTransitions('pending')===['pending','processing','cancelled'],'Pending transition mismatch');
assertOrderTransition(['order_status'=>'pending','payment_status'=>'paid','payment_method'=>'promptpay'],'processing','paid');
$rejected=false;try{assertOrderTransition(['order_status'=>'completed','payment_status'=>'paid','payment_method'=>'promptpay'],'pending','paid');}catch(RuntimeException $e){$rejected=true;}workflowExpect($rejected,'Completed order was allowed to regress');
$rejected=false;try{assertOrderTransition(['order_status'=>'pending','payment_status'=>'pending','payment_method'=>'promptpay'],'processing','pending');}catch(RuntimeException $e){$rejected=true;}workflowExpect($rejected,'Unpaid PromptPay order was allowed to process');
echo "Commerce workflow tests passed\n";
