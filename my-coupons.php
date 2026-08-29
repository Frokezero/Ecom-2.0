<?php
$page_title='ศูนย์รวมคูปอง';
require_once __DIR__.'/includes/auth_check.php';
require_once __DIR__.'/config/database.php';
$db=(new Database())->getConnection();
$uid=isLoggedIn()?(int)$_SESSION['user_id']:0;
$stmt=$db->prepare('SELECT c.*,uc.claimed_at,COALESCE(uc.used_count,0) used_count,(SELECT COUNT(*) FROM coupon_usages cu WHERE cu.coupon_id=c.id) total_used FROM coupons c LEFT JOIN user_coupons uc ON uc.coupon_id=c.id AND uc.user_id=? WHERE c.is_active=1 AND c.starts_at<=NOW() AND c.ends_at>=NOW() ORDER BY c.ends_at,c.id DESC');
$stmt->execute([$uid]);$coupons=$stmt->fetchAll();
$claimedCount=count(array_filter($coupons,fn($c)=>!empty($c['claimed_at'])));
function couponBenefit(array $c): string {if($c['discount_type']==='percent')return 'ลด '.number_format((float)$c['discount_value'],0).'%';if($c['discount_type']==='fixed')return 'ลด '.formatCurrency($c['discount_value']);return 'ส่งฟรี';}
require_once __DIR__.'/includes/header.php';
?>
<div class="coupon-center">
 <div class="container">
  <section class="coupon-center-hero"><div><p class="eyebrow">KITCHENMART COUPON CENTER</p><h1>รวมโค้ดดี คูปองคุ้ม<br>สำหรับทุกมื้อที่บ้าน</h1><p>รับคูปองเก็บไว้ก่อน แล้วค่อยเลือกว่าจะใช้ใบไหนในตะกร้าหรือหน้า Checkout</p><a href="#all-coupons" class="btn btn-primary">ดูคูปองทั้งหมด <i class="fa-solid fa-arrow-down"></i></a></div><aside><i class="fa-solid fa-ticket"></i><strong><?php echo count($coupons); ?> คูปอง</strong><small>คูปองของคุณ <?php echo $claimedCount; ?> ใบ</small></aside></section>
  <section class="coupon-code-claim"><label for="couponCodeInput"><i class="fa-solid fa-key"></i><span><strong>มีโค้ดพิเศษ?</strong><small>กรอกโค้ดเพื่อรับเข้าบัญชี</small></span></label><div><input id="couponCodeInput" maxlength="40" placeholder="เช่น KITCHEN10"><button id="claimCouponCode" class="btn btn-primary" type="button">รับโค้ด</button></div><p id="couponCodeMessage" aria-live="polite"></p></section>

  <?php if($coupons): ?><section class="coupon-featured" data-coupon-carousel><header><div><p class="eyebrow">FEATURED COUPONS</p><h2>คูปองเด่นสำหรับคุณ</h2></div><div class="coupon-carousel-controls"><button type="button" data-coupon-prev aria-label="ก่อนหน้า"><i class="fa-solid fa-chevron-left"></i></button><button type="button" data-coupon-next aria-label="ถัดไป"><i class="fa-solid fa-chevron-right"></i></button></div></header><div class="coupon-featured-track" data-coupon-track><?php foreach(array_slice($coupons,0,6) as $c): ?><article class="coupon-ticket compact"><span class="coupon-ticket-value"><?php echo e(couponBenefit($c)); ?></span><div><strong><?php echo e($c['title']); ?></strong><small>ขั้นต่ำ <?php echo formatCurrency($c['min_order_amount']); ?> · <?php echo e($c['code']); ?></small></div><?php if($c['claimed_at']): ?><a href="<?php echo BASE_URL; ?>cart.php" class="coupon-action claimed">ใช้ทันที</a><?php else: ?><button class="coupon-action claim-coupon" data-id="<?php echo (int)$c['id']; ?>">รับ</button><?php endif; ?></article><?php endforeach; ?></div></section><?php endif; ?>

  <section id="all-coupons" class="coupon-catalog"><header><div><p class="eyebrow">ALL COUPONS</p><h2>เลือกคูปองที่เหมาะกับคุณ</h2></div><a href="<?php echo BASE_URL; ?>cart.php">ไปเลือกใช้ในตะกร้า <i class="fa-solid fa-arrow-right"></i></a></header><nav class="coupon-tabs" aria-label="กรองคูปอง"><button class="active" data-coupon-filter="all">ทั้งหมด <b><?php echo count($coupons); ?></b></button><button data-coupon-filter="mine">คูปองของฉัน <b><?php echo $claimedCount; ?></b></button><button data-coupon-filter="percent">ส่วนลด %</button><button data-coupon-filter="fixed">ส่วนลดบาท</button><button data-coupon-filter="expiring">ใกล้หมดอายุ</button></nav><div class="coupon-grid">
   <?php if(!$coupons): ?><p class="coupon-empty">ยังไม่มีคูปองที่เปิดใช้งาน</p><?php endif; ?>
   <?php foreach($coupons as $c): $expiring=strtotime($c['ends_at'])-time()<7*86400; ?><article class="coupon-ticket" data-kind="<?php echo e($c['discount_type']); ?>" data-claimed="<?php echo $c['claimed_at']?'1':'0'; ?>" data-expiring="<?php echo $expiring?'1':'0'; ?>"><div class="coupon-ticket-side"><i class="fa-solid <?php echo $c['discount_type']==='free_shipping'?'fa-truck-fast':'fa-ticket'; ?>"></i><strong><?php echo e(couponBenefit($c)); ?></strong></div><div class="coupon-ticket-body"><span class="coupon-code"><?php echo e($c['code']); ?></span><h3><?php echo e($c['title']); ?></h3><p><?php echo e($c['description']); ?></p><ul><li>ขั้นต่ำ <?php echo formatCurrency($c['min_order_amount']); ?></li><?php if($c['max_discount']): ?><li>ลดสูงสุด <?php echo formatCurrency($c['max_discount']); ?></li><?php endif; ?></ul><small class="coupon-expiry <?php echo $expiring?'urgent':''; ?>">หมดอายุ <?php echo date('d/m/Y H:i',strtotime($c['ends_at'])); ?></small></div><div class="coupon-ticket-action"><?php if($c['claimed_at']): ?><span><i class="fa-solid fa-circle-check"></i> รับแล้ว</span><a href="<?php echo BASE_URL; ?>cart.php">ใช้ทันที</a><?php else: ?><button class="claim-coupon" data-id="<?php echo (int)$c['id']; ?>">รับคูปอง</button><?php endif; ?><button class="coupon-detail-toggle" type="button">เงื่อนไข</button></div></article><?php endforeach; ?>
  </div></section>
 </div>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/coupon-center.js?v=1"></script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
