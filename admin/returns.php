<?php
$page_title='คืนสินค้าและคืนเงิน';require_once __DIR__.'/../includes/auth_check.php';requireAdmin();require_once __DIR__.'/../config/database.php';
$db=(new Database())->getConnection();$message=$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf(false);$id=(int)($_POST['id']??0);$action=(string)($_POST['action']??'');$note=trim((string)($_POST['admin_note']??''));
 if(!in_array($action,['approve','reject','received','refund'],true))$error='คำสั่งไม่ถูกต้อง';else try{
  $db->beginTransaction();$stmt=$db->prepare('SELECT r.*,o.total_amount,o.payment_status,o.order_no,o.coupon_id FROM return_requests r JOIN orders o ON o.id=r.order_id WHERE r.id=? FOR UPDATE');$stmt->execute([$id]);$r=$stmt->fetch();if(!$r)throw new RuntimeException('ไม่พบคำขอ');
  $next=['approve'=>'approved','reject'=>'rejected','received'=>'received','refund'=>'refunded'][$action];
  if($action==='refund'){
   if($r['status']!=='received')throw new RuntimeException('ต้องยืนยันว่าได้รับสินค้าคืนก่อน');
   $db->prepare("INSERT INTO refunds(order_id,return_request_id,amount,status,reason,processed_at) VALUES(?,?,?,'succeeded',?,NOW())")->execute([(int)$r['order_id'],$id,(float)$r['total_amount'],$note?:'คืนเงินเต็มจำนวน']);
   $items=$db->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=?');$items->execute([(int)$r['order_id']]);$restore=$db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');foreach($items->fetchAll() as $item)$restore->execute([(int)$item['quantity'],(int)$item['product_id']]);
   if(!empty($r['coupon_id'])){$used=$db->prepare('DELETE FROM coupon_usages WHERE order_id=?');$used->execute([(int)$r['order_id']]);if($used->rowCount())$db->prepare('UPDATE user_coupons SET used_count=GREATEST(0,used_count-1) WHERE coupon_id=? AND user_id=?')->execute([(int)$r['coupon_id'],(int)$r['user_id']]);}
   $db->prepare("UPDATE orders SET payment_status='refunded',order_status='cancelled' WHERE id=?")->execute([(int)$r['order_id']]);
  }
  $db->prepare('UPDATE return_requests SET status=?,admin_note=? WHERE id=?')->execute([$next,$note?:null,$id]);createNotification($db,(int)$r['user_id'],'return','อัปเดตคำขอคืนสินค้า','คำขอสำหรับ '.$r['order_no'].' เปลี่ยนเป็น '.$next,BASE_URL.'my-returns.php');$db->commit();$message='อัปเดตคำขอเรียบร้อย';
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$error=$e->getMessage();}
}
$rows=$db->query('SELECT r.*,o.order_no,o.total_amount,u.username FROM return_requests r JOIN orders o ON o.id=r.order_id JOIN users u ON u.id=r.user_id ORDER BY r.id DESC')->fetchAll();require_once __DIR__.'/../includes/admin_header.php';
?>
<header class="admin-page-header"><div><p class="eyebrow">AFTER SALES</p><h1>คืนสินค้าและคืนเงิน</h1></div></header><?php if($message):?><div class="admin-alert success"><?php echo e($message);?></div><?php endif;?><?php if($error):?><div class="admin-alert error"><?php echo e($error);?></div><?php endif;?><section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ออเดอร์</th><th>เหตุผล</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?php echo e($r['order_no']);?></strong><small class="cell-subtitle"><?php echo e($r['username']);?> · <?php echo formatCurrency($r['total_amount']);?></small></td><td><?php echo e($r['reason']);?></td><td><?php echo e($r['status']);?></td><td><form method="post" class="table-actions"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken());?>"><input type="hidden" name="id" value="<?php echo (int)$r['id'];?>"><input name="admin_note" maxlength="500" placeholder="หมายเหตุ"><button name="action" value="approve">อนุมัติ</button><button name="action" value="reject">ปฏิเสธ</button><button name="action" value="received">รับคืนแล้ว</button><button name="action" value="refund">คืนเงิน</button></form></td></tr><?php endforeach;?></tbody></table></div></section><?php require_once __DIR__.'/../includes/admin_footer.php';?>
