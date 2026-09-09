<?php
$sellerCurrent = basename($_SERVER['PHP_SELF'] ?? '');
$sellerLinks = [
    ['seller-dashboard.php','fa-chart-line','ภาพรวม'],
    ['my-store.php','fa-store','หน้าร้านและสินค้า'],
    ['seller-inventory.php','fa-boxes-stacked','คลังสินค้า'],
    ['seller-orders.php','fa-truck-fast','ออเดอร์และจัดส่ง'],
    ['seller-marketplace.php','fa-tags','การตลาด'],
    ['seller-wallet.php','fa-wallet','การเงิน'],
    ['messages.php','fa-comments','ข้อความ'],
];
?>
<nav class="seller-center-nav" aria-label="เมนูศูนย์ผู้ขาย">
  <div class="seller-center-nav-title"><i class="fa-solid fa-store"></i><span><strong>ศูนย์ผู้ขาย</strong><small>จัดการร้านในที่เดียว</small></span></div>
  <div class="seller-center-nav-links">
    <?php foreach($sellerLinks as [$file,$icon,$label]): ?>
      <a href="<?php echo BASE_URL.e($file); ?>" class="<?php echo $sellerCurrent===$file?'active':''; ?>"><i class="fa-solid <?php echo e($icon); ?>"></i><span><?php echo e($label); ?></span></a>
    <?php endforeach; ?>
  </div>
</nav>
