<?php
$page_title = 'แดชบอร์ดผู้ดูแล';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$metrics = ['products'=>0,'low_stock'=>0,'orders'=>0,'pending'=>0,'revenue'=>0,'month_revenue'=>0,'mail_pending'=>0,'mail_dead'=>0,'mail_stale'=>0];
$paymentCounts = ['promptpay'=>0,'cod'=>0];
$recentOrders = [];
if ($db) {
    $metrics['products'] = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $metrics['low_stock'] = (int)$db->query('SELECT COUNT(*) FROM products WHERE stock_quantity <= 5')->fetchColumn();
    $metrics['orders'] = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $metrics['pending'] = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
    $metrics['revenue'] = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' OR order_status='completed'")->fetchColumn();
    $metrics['month_revenue'] = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE (payment_status='paid' OR order_status='completed') AND YEAR(created_at)=YEAR(CURRENT_DATE()) AND MONTH(created_at)=MONTH(CURRENT_DATE())")->fetchColumn();
    $metrics['mail_pending']=(int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE status IN ('queued','failed')")->fetchColumn();
    $metrics['mail_dead']=(int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE status='dead'")->fetchColumn();
    $metrics['mail_stale']=(int)$db->query("SELECT COUNT(*) FROM email_delivery_logs WHERE status IN ('queued','failed','sending') AND created_at<DATE_SUB(NOW(),INTERVAL 5 MINUTE)")->fetchColumn();
    foreach ($db->query('SELECT payment_method,COUNT(*) count FROM orders GROUP BY payment_method') as $row) $paymentCounts[$row['payment_method']] = (int)$row['count'];
    $recentOrders = $db->query('SELECT o.*,u.full_name customer_name FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 8')->fetchAll();
}
require_once __DIR__ . '/../includes/admin_header.php';
?>
<?php if($metrics['mail_stale']||$metrics['mail_dead']):?><div class="admin-alert error"><i class="fa-solid fa-envelope-circle-xmark"></i> คิวอีเมลผิดปกติ: ค้างเกิน 5 นาที <?php echo $metrics['mail_stale'];?> รายการ และหยุดส่ง <?php echo $metrics['mail_dead'];?> รายการ — <a href="<?php echo BASE_URL;?>admin/email-logs.php">ตรวจสอบคิว</a></div><?php endif;?>
<header class="admin-page-header"><div><p class="eyebrow">STORE OVERVIEW</p><h1>ภาพรวมร้านวันนี้</h1><p>ติดตามยอดขาย คำสั่งซื้อ และงานที่ต้องจัดการจากหน้าเดียว</p></div><div class="admin-actions"><a href="<?php echo BASE_URL; ?>admin/products.php?action=add" class="btn btn-outline"><i class="fa-solid fa-plus"></i> เพิ่มสินค้า</a><a href="<?php echo BASE_URL; ?>admin/orders.php?order_status=pending" class="btn btn-primary"><i class="fa-solid fa-clipboard-check"></i> จัดการออเดอร์</a></div></header>

<section class="stat-grid" aria-label="สถิติร้าน">
    <article class="stat-card"><div><small>ยอดขายรวม</small><strong><?php echo formatCurrency($metrics['revenue']); ?></strong></div><i class="fa-solid fa-chart-line"></i></article>
    <article class="stat-card"><div><small>ยอดขายเดือนนี้</small><strong><?php echo formatCurrency($metrics['month_revenue']); ?></strong></div><i class="fa-solid fa-calendar-check"></i></article>
    <article class="stat-card"><div><small>คำสั่งซื้อทั้งหมด</small><strong><?php echo number_format($metrics['orders']); ?> รายการ</strong></div><i class="fa-solid fa-bag-shopping"></i></article>
    <article class="stat-card warning"><div><small>รอดำเนินการ</small><strong><?php echo number_format($metrics['pending']); ?> รายการ</strong></div><i class="fa-solid fa-clock"></i></article>
</section>

<section class="stat-grid admin-secondary-stats" aria-label="ข้อมูลสินค้าและการชำระเงิน">
    <article class="stat-card"><div><small>สินค้าในระบบ</small><strong><?php echo number_format($metrics['products']); ?> รายการ</strong></div><i class="fa-solid fa-boxes-stacked"></i></article>
    <article class="stat-card <?php echo $metrics['low_stock'] ? 'warning' : ''; ?>"><div><small>สต็อกต่ำ ≤ 5 ชิ้น</small><strong><?php echo number_format($metrics['low_stock']); ?> รายการ</strong></div><i class="fa-solid fa-box-open"></i></article>
    <article class="stat-card"><div><small>ชำระด้วย PromptPay</small><strong><?php echo number_format($paymentCounts['promptpay']); ?> ออเดอร์</strong></div><i class="fa-solid fa-qrcode"></i></article>
    <article class="stat-card"><div><small>เก็บเงินปลายทาง</small><strong><?php echo number_format($paymentCounts['cod']); ?> ออเดอร์</strong></div><i class="fa-solid fa-truck-ramp-box"></i></article>
</section>

<section class="admin-panel">
    <header class="admin-panel-header"><div><h2>คำสั่งซื้อล่าสุด</h2><p>8 รายการล่าสุดจากหน้าร้าน</p></div><a href="<?php echo BASE_URL; ?>admin/orders.php" class="btn btn-outline">ดูทั้งหมด <i class="fa-solid fa-arrow-right"></i></a></header>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>คำสั่งซื้อ</th><th>ลูกค้า</th><th>วันที่</th><th>ยอดรวม</th><th>การชำระ</th><th>สถานะ</th><th></th></tr></thead><tbody>
    <?php if (!$recentOrders): ?><tr><td colspan="7" class="admin-empty"><i class="fa-solid fa-inbox"></i>ยังไม่มีคำสั่งซื้อ</td></tr><?php endif; ?>
    <?php foreach ($recentOrders as $order): ?><tr>
        <td><span class="cell-title"><?php echo e($order['order_no']); ?></span><?php if(!empty($order['is_demo'])):?><span class="demo-mode-badge">สาธิต</span><?php endif;?></td>
        <td><?php echo e($order['customer_name'] ?: $order['shipping_name']); ?></td>
        <td><span class="cell-subtitle"><?php echo date('d/m/Y H:i',strtotime($order['created_at'])); ?></span></td>
        <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
        <td><span class="status-badge <?php echo e($order['payment_status']); ?>"><?php echo e(adminStatusLabel($order['payment_status'])); ?></span></td>
        <td><span class="status-badge <?php echo e($order['order_status']); ?>"><?php echo e(adminStatusLabel($order['order_status'])); ?></span></td>
        <td><a href="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-outline">ดูรายละเอียด</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
