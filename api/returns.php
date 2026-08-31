<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/functions.php';require_once __DIR__.'/../includes/image_upload.php';require_once __DIR__.'/../includes/security_monitor.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);if(!isLoggedIn())jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);requireCsrf();
$db=(new Database())->getConnection();if(!$db)jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);protectApiMutation($db,'api.returns',10,300);$uid=(int)$_SESSION['user_id'];$action=$_POST['action']??'';
if($action==='ship_return'){
 $id=(int)($_POST['return_id']??0);$carrier=trim((string)($_POST['carrier']??''));$tracking=trim((string)($_POST['tracking_number']??''));
 if(mb_strlen($carrier)<2||!preg_match('/^[A-Za-z0-9ก-๙_-]{5,120}$/u',$tracking))jsonResponse('error','กรุณากรอกบริษัทขนส่งและเลขพัสดุให้ถูกต้อง',[],422);
 $stmt=$db->prepare("UPDATE return_requests SET return_carrier=?,return_tracking_number=?,return_shipped_at=NOW() WHERE id=? AND user_id=? AND status='approved' AND return_tracking_number IS NULL");$stmt->execute([$carrier,$tracking,$id,$uid]);if(!$stmt->rowCount())jsonResponse('error','รายการนี้ไม่อยู่ในสถานะที่ส่งคืนได้',[],409);createRoleNotification($db,'admin','return','ลูกค้าส่งสินค้าคืนแล้ว','เลขพัสดุ '.$tracking,BASE_URL.'admin/returns.php');jsonResponse('success','บันทึกเลขพัสดุส่งคืนแล้ว');
}
if($action!=='create')jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
$orderId=(int)($_POST['order_id']??0);$reason=trim((string)($_POST['reason']??''));$quantities=$_POST['quantities']??[];
if(mb_strlen($reason)<10||mb_strlen($reason)>500||!is_array($quantities))jsonResponse('error','กรุณาเลือกรายการและระบุเหตุผล 10–500 ตัวอักษร',[],422);
$evidence=null;
try{
 $evidence=saveReturnEvidenceUpload($_FILES['evidence_image']??[]);$db->beginTransaction();
 $order=$db->prepare("SELECT id,order_no,COALESCE(delivered_at,created_at) delivered_at FROM orders WHERE id=? AND user_id=? AND order_status IN ('shipped','completed') FOR UPDATE");$order->execute([$orderId,$uid]);$row=$order->fetch();if(!$row)throw new RuntimeException('ออเดอร์นี้ยังไม่เข้าเงื่อนไขการคืนสินค้า');if(strtotime($row['delivered_at'])<strtotime('-7 days'))throw new RuntimeException('หมดระยะเวลาขอคืนสินค้า 7 วันแล้ว');
 $exists=$db->prepare("SELECT id FROM return_requests WHERE order_id=? AND status NOT IN ('rejected','cancelled') LIMIT 1");$exists->execute([$orderId]);if($exists->fetch())throw new RuntimeException('ออเดอร์นี้มีคำขอคืนสินค้าอยู่แล้ว');
 $db->prepare('INSERT INTO return_requests(order_id,user_id,reason,evidence_image) VALUES(?,?,?,?)')->execute([$orderId,$uid,$reason,$evidence]);$returnId=(int)$db->lastInsertId();$itemStmt=$db->prepare('SELECT id,quantity,price FROM order_items WHERE id=? AND order_id=?');$insert=$db->prepare('INSERT INTO return_request_items(return_request_id,order_item_id,quantity,refund_amount) VALUES(?,?,?,?)');$selected=0;
 foreach($quantities as $itemId=>$qty){$qty=(int)$qty;if($qty<1)continue;$itemStmt->execute([(int)$itemId,$orderId]);$item=$itemStmt->fetch();if(!$item||$qty>(int)$item['quantity'])throw new RuntimeException('จำนวนสินค้าที่คืนไม่ถูกต้อง');$insert->execute([$returnId,(int)$item['id'],$qty,(float)$item['price']*$qty]);$selected++;}
 if(!$selected)throw new RuntimeException('กรุณาเลือกสินค้าที่ต้องการคืนอย่างน้อย 1 รายการ');createRoleNotification($db,'admin','return','มีคำขอคืนสินค้าใหม่','ออเดอร์ '.$row['order_no'].' รอตรวจสอบ',BASE_URL.'admin/returns.php');$db->commit();jsonResponse('success','ส่งคำขอคืนสินค้าแล้ว ทีมงานจะตรวจสอบโดยเร็ว',['redirect'=>BASE_URL.'my-returns.php']);
}catch(RuntimeException $e){if($db->inTransaction())$db->rollBack();deleteManagedUpload($evidence);jsonResponse('error',$e->getMessage(),[],409);}catch(Throwable $e){if($db->inTransaction())$db->rollBack();deleteManagedUpload($evidence);jsonResponse('error','ไม่สามารถส่งคำขอได้',[],500);}
