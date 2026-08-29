<?php
putenv('SESSION_SAVE_PATH='.__DIR__);
require_once __DIR__.'/../includes/functions.php';
$id='0812345678';
$valid=[1=>'1.00','10.50'=>'10.50',100=>'100.00','159.99'=>'159.99',1000=>'1000.00'];
$failures=[];
foreach($valid as $amount=>$expected){$url=generatePromptPayQRUrl($id,$amount);$want='https://promptpay.io/'.$id.'/'.$expected.'.png';if($url!==$want)$failures[]="amount {$amount}: {$url}";}
$invalid=[0,-100,null,'','abc'];
foreach($invalid as $amount){try{generatePromptPayQRUrl($id,$amount);$failures[]='invalid accepted: '.var_export($amount,true);}catch(InvalidArgumentException $e){}}
try{generatePromptPayQRUrl($id);$failures[]='undefined/omitted amount accepted';}catch(ArgumentCountError $e){}
try{generatePromptPayQRUrl('',100);$failures[]='missing ID accepted';}catch(InvalidArgumentException $e){}
$sessionFile=__DIR__.'/sess_'.session_id();session_write_close();if(is_file($sessionFile))unlink($sessionFile);
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "PromptPay QR helper tests passed\n";
