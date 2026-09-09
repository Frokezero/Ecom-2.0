<?php
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../config/database.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
if(!isLoggedIn())jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);
requireCsrf();$db=(new Database())->getConnection();if(!$db)jsonResponse('error','เชื่อมต่อฐานข้อมูลไม่ได้',[],503);
protectApiMutation($db,'api.marketplace',30,60);$uid=(int)$_SESSION['user_id'];$action=(string)($_POST['action']??'');
if($action==='follow'){
 $seller=(int)($_POST['seller_id']??0);if(!$seller||$seller===$uid)jsonResponse('error','ร้านค้าไม่ถูกต้อง',[],422);
 $check=$db->prepare("SELECT 1 FROM seller_profiles WHERE user_id=? AND status='approved' AND store_status='active'");$check->execute([$seller]);if(!$check->fetchColumn())jsonResponse('error','ไม่พบร้านค้า',[],404);
 $exists=$db->prepare('SELECT 1 FROM store_followers WHERE seller_id=? AND user_id=?');$exists->execute([$seller,$uid]);$saved=(bool)$exists->fetchColumn();
 if($saved)$db->prepare('DELETE FROM store_followers WHERE seller_id=? AND user_id=?')->execute([$seller,$uid]);else $db->prepare('INSERT INTO store_followers(seller_id,user_id) VALUES(?,?)')->execute([$seller,$uid]);
 $count=$db->prepare('SELECT COUNT(*) FROM store_followers WHERE seller_id=?');$count->execute([$seller]);jsonResponse('success',$saved?'เลิกติดตามร้านแล้ว':'ติดตามร้านแล้ว',['following'=>!$saved,'count'=>(int)$count->fetchColumn()]);
}
if($action==='report'){
 $product=(int)($_POST['product_id']??0);$reason=(string)($_POST['reason']??'other');$detail=trim((string)($_POST['detail']??''));$allowed=['counterfeit','prohibited','misleading','inappropriate','other'];
 if(!$product||!in_array($reason,$allowed,true)||mb_strlen($detail)>1000)jsonResponse('error','ข้อมูลรายงานไม่ถูกต้อง',[],422);
 $stmt=$db->prepare("INSERT INTO product_reports(product_id,reporter_id,reason,detail) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE reason=VALUES(reason),detail=VALUES(detail),status='open',created_at=CURRENT_TIMESTAMP");$stmt->execute([$product,$uid,$reason,$detail]);
 createRoleNotification($db,'admin','security','มีรายงานสินค้าใหม่','สินค้า #'.$product.' ถูกผู้ใช้รายงาน',BASE_URL.'admin/product-reports.php');jsonResponse('success','ส่งรายงานให้ผู้ดูแลตรวจสอบแล้ว');
}
if($action==='message'){
 $seller=(int)($_POST['seller_id']??0);$product=(int)($_POST['product_id']??0)?:null;$conversation=(int)($_POST['conversation_id']??0);$message=trim((string)($_POST['message']??''));if(mb_strlen($message)<1||mb_strlen($message)>2000)jsonResponse('error','ข้อความไม่ถูกต้อง',[],422);
 $db->beginTransaction();if($conversation){$find=$db->prepare('SELECT id,seller_id,customer_id FROM store_conversations WHERE id=? AND (seller_id=? OR customer_id=?) FOR UPDATE');$find->execute([$conversation,$uid,$uid]);$authorized=$find->fetch();if(!$authorized){$db->rollBack();jsonResponse('error','ไม่มีสิทธิ์เข้าถึงบทสนทนา',[],403);}$recipient=$uid===(int)$authorized['seller_id']?(int)$authorized['customer_id']:(int)$authorized['seller_id'];}else{if(!$seller||$seller===$uid){$db->rollBack();jsonResponse('error','ร้านค้าไม่ถูกต้อง',[],422);}$find=$db->prepare('SELECT id FROM store_conversations WHERE seller_id=? AND customer_id=? AND product_id <=> ? FOR UPDATE');$find->execute([$seller,$uid,$product]);$conversation=(int)$find->fetchColumn();$recipient=$seller;}
 if(!$conversation){$db->prepare('INSERT INTO store_conversations(seller_id,customer_id,product_id) VALUES(?,?,?)')->execute([$seller,$uid,$product]);$conversation=(int)$db->lastInsertId();}
 $db->prepare('INSERT INTO store_messages(conversation_id,sender_id,message) VALUES(?,?,?)')->execute([$conversation,$uid,$message]);$db->prepare('UPDATE store_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversation]);$db->commit();
 createNotification($db,$recipient,'seller','ข้อความ Marketplace ใหม่',mb_substr($message,0,120),BASE_URL.'messages.php?conversation='.$conversation);jsonResponse('success','ส่งข้อความแล้ว',['conversation_id'=>$conversation]);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
