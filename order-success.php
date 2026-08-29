<?php
// order-success.php
$page_title = "สั่งซื้อสำเร็จ";
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$db = (new Database())->getConnection();
$order = null;
$order_items = [];

if ($db && $order_id > 0) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if ($order) {
        $stmt_items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        $order_items = $stmt_items->fetchAll();
    }
}

if (!$order) {
    header("Location: " . BASE_URL . "my-orders.php");
    exit;
}
$promptPayQrUrl = null;
$promptPayQrError = null;
if ($order['payment_method'] === 'promptpay' && $order['payment_status'] === 'pending') {
    try { $promptPayQrUrl = generatePromptPayQRUrl(PROMPTPAY_ID, $order['total_amount']); }
    catch (InvalidArgumentException $e) { $promptPayQrError = $e->getMessage(); }
}
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px; max-width: 800px;">
    <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 40px; text-align: center; box-shadow: var(--shadow-md);">
        <div style="width: 72px; height: 72px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 20px auto;">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">การสั่งซื้อของคุณเสร็จสมบูรณ์!</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 24px;">หมายเลขคำสั่งซื้อ: <strong style="color: var(--primary);"><?php echo e($order['order_no']); ?></strong></p>

        <?php if ($order['payment_method'] === 'promptpay' && $order['payment_status'] === 'pending'): ?>
            <div style="background: #f8fafc; border: 2px dashed var(--primary); border-radius: var(--radius-md); padding: 28px; margin-bottom: 32px; text-align: center;">
                <h3 style="color: #003b6d; margin-bottom: 8px;"><i class="fa-solid fa-qrcode"></i> ชำระเงินผ่าน PromptPay QR Code</h3>
                <p style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 16px;">กรุณาสแกน QR Code ด้านล่างเพื่อชำระเงินจำนวน <strong style="color: var(--primary); font-size: 1.2rem;"><?php echo formatCurrency($order['total_amount']); ?></strong></p>
                
                <?php if ($promptPayQrUrl): ?>
                    <img id="promptPayQrImage" src="<?php echo e($promptPayQrUrl); ?>" alt="PromptPay QR Code สำหรับคำสั่งซื้อ <?php echo e($order['order_no']); ?>" width="300" height="300" style="width:300px;max-width:100%;height:auto;margin:16px auto;background:white;" onerror="this.hidden=true;document.getElementById('promptPayQrLoadError').hidden=false;">
                    <p id="promptPayQrLoadError" hidden style="color:var(--danger);margin:16px 0;">ไม่สามารถโหลด QR Code จาก PromptPay.io ได้ กรุณาลองใหม่ภายหลัง</p>
                    <p style="font-size:0.9rem;color:var(--text-muted);">สแกน QR Code ด้วยแอปธนาคารเพื่อชำระเงินผ่าน PromptPay</p>
                <?php else: ?>
                    <p style="color:var(--danger);margin:16px 0;">ไม่สามารถสร้าง QR Code ได้: <?php echo e($promptPayQrError ?: 'กรุณาตรวจสอบการตั้งค่า PromptPay'); ?></p>
                <?php endif; ?>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-top:14px;">การแสดง QR ไม่ใช่การยืนยันการชำระเงิน สถานะจะยังคงรอชำระจนกว่าจะมีระบบตรวจสอบธุรกรรมแยกต่างหาก</p>
            </div>
        <?php elseif ($order['payment_method'] === 'promptpay' && $order['payment_status'] === 'paid'): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: var(--radius-md); margin-bottom: 32px; color: #166534;">
                <i class="fa-solid fa-circle-check fa-2x" style="margin-bottom: 8px;"></i>
                <h3 style="margin-bottom: 4px;">ชำระเงินผ่าน PromptPay เรียบร้อยแล้ว!</h3>
                <p style="font-size: 0.9rem;">สถานะคำสั่งซื้อกำลังเตรียมจัดส่งสินค้า</p>
            </div>
        <?php else: ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: var(--radius-md); margin-bottom: 32px; color: #166534;">
                <i class="fa-solid fa-truck-ramp-box fa-2x" style="margin-bottom: 8px;"></i>
                <h3 style="margin-bottom: 4px;">วิธีชำระเงิน: เก็บเงินปลายทาง (COD)</h3>
                <p style="font-size: 0.9rem;">เจ้าหน้าที่จะทำการเก็บเงินจำนวน <strong><?php echo formatCurrency($order['total_amount']); ?></strong> เมื่อนำพัสดุไปส่งถึงบ้านคุณ</p>
            </div>
        <?php endif; ?>

        <!-- Order Items Receipt Table -->
        <div style="text-align: left; background: #f8fafc; padding: 24px; border-radius: var(--radius-md); margin-bottom: 32px;">
            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">รายละเอียดใบเสร็จสั่งซื้อ</h4>
            
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                <?php foreach ($order_items as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                        <span><?php echo e($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span style="font-weight: 600;"><?php echo formatCurrency($item['subtotal']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top: 2px dashed var(--border-color); padding-top: 12px; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; color: var(--secondary);">
                <span>ยอดเงินสุทธิ</span>
                <span style="color: var(--primary);"><?php echo formatCurrency($order['total_amount']); ?></span>
            </div>
        </div>

        <div style="display: flex; gap: 16px; justify-content: center;">
            <a href="<?php echo BASE_URL; ?>my-orders.php" class="btn btn-outline"><i class="fa-solid fa-clock-rotate-left"></i> ดูประวัติคำสั่งซื้อ</a>
            <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary"><i class="fa-solid fa-bag-shopping"></i> เลือกซื้อสินค้าต่อ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
