<?php
function expect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$secret='test-secret-that-is-not-used-in-production';
$payload=json_encode(['reference'=>'PAY-001','order_no'=>'KMTEST','status'=>'paid','amount'=>890.00],JSON_UNESCAPED_SLASHES);
$signature=hash_hmac('sha256',$payload,$secret);
expect(strlen($signature)===64,'HMAC must be SHA-256 hex');
expect(hash_equals($signature,hash_hmac('sha256',$payload,$secret)),'Valid webhook signature rejected');
expect(!hash_equals($signature,hash_hmac('sha256',$payload.'x',$secret)),'Modified webhook payload accepted');
$token=bin2hex(random_bytes(32));expect((bool)preg_match('/^[a-f0-9]{64}$/',$token),'Reset token format invalid');
expect(hash_equals(hash('sha256',$token),hash('sha256',$token)),'Reset token hash comparison failed');
echo "Commerce security helper tests passed\n";
