<?php
function hardeningExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__);$mail=file_get_contents($root.'/includes/transactional_mail.php');hardeningExpect(!str_contains($mail,"status IN ('queued','failed','sending')"),'Email worker can claim sending rows');
$login=file_get_contents($root.'/login.php');hardeningExpect(!str_contains($login,'fillDemo(')&&!str_contains($login,"value='password123'"),'Demo credentials remain on login page');
$webhook=file_get_contents($root.'/api/payment-webhook.php');hardeningExpect(str_contains($webhook,'payload_hash')&&str_contains($webhook,'hash_equals'),'Webhook payload mismatch is not checked');
$returns=file_get_contents($root.'/api/returns.php');hardeningExpect(str_contains($returns,'deleteManagedUpload($evidence)'),'Failed return leaves an orphan upload');
$config=file_get_contents($root.'/config/config.php');hardeningExpect(str_contains($config,"script-src 'self' 'nonce-")&&!str_contains($config,"script-src 'self' 'unsafe-inline'"),'CSP does not enforce script nonces');
$functions=file_get_contents($root.'/includes/functions.php');hardeningExpect(str_contains($functions,"mutation_guard")&&str_contains($functions,"429"),'Global mutation guard is missing');
$protected=['addresses','cart','orders','password-reset','promotions','returns','reviews','seller','wishlist'];foreach($protected as $api){$source=file_get_contents($root.'/api/'.$api.'.php');hardeningExpect(str_contains($source,'protectApiMutation'),'Missing mutation protection: '.$api);}
echo "Production hardening tests passed\n";
