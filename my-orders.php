<?php
// my-orders.php
$page_title = "ออเดอร์ของฉัน";
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$db = (new Database())->getConnection();
$orders = [];

if ($db) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}
?>

<div class="container" style="margin-top: 36px; margin-bottom: 60px;">
    <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 24px;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> ประวัติคำสั่งซื้อของฉัน</h1>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-box-open fa-3x" style="color: var(--text-muted); margin-bottom: 16px;"></i>
            <h2>คุณยังไม่มีประวัติการสั่งซื้อ</h2>
            <p style="color: var(--text-muted); margin-top: 8px;">เมื่อคุณทำการสั่งซื้อ รายการออเดอร์จะแสดงที่หน้านี้</p>
            <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary" style="margin-top: 20px;">ไปเลือกซื้อสินค้า</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($orders as $o): ?>
                <div style="background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; margin-bottom: 16px;">
                        <div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">เลขที่ออเดอร์:</span>
                            <strong style="font-size: 1.1rem; color: var(--secondary); margin-left: 6px;"><?php echo e($o['order_no']); ?></strong>
                            <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 16px;"><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></span>
                        </div>

                        <div>
                            <?php if ($o['payment_status'] === 'paid'): ?>
                                <span style="background: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> ชำระเงินแล้ว</span>
                            <?php elseif ($o['payment_status'] === 'cod_pending'): ?>
                                <span style="background: #e0e7ff; color: #4338ca; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;"><i class="fa-solid fa-truck-ramp-box"></i> รอเก็บเงินปลายทาง (COD)</span>
                            <?php else: ?>
                                <span style="background: #fef3c7; color: #b45309; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;"><i class="fa-solid fa-clock"></i> รอชำระเงิน</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px;">วิธีชำระเงิน: <strong><?php echo $o['payment_method'] === 'promptpay' ? 'PromptPay QR Code' : 'ชำระเงินปลายทาง (COD)'; ?></strong></div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">ผู้รับ: <?php echo e($o['shipping_name']); ?> (<?php echo e($o['shipping_phone']); ?>)</div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; color: var(--text-muted);">ราคารวมสุทธิ</div>
                            <div style="font-size: 1.4rem; font-weight: 700; color: var(--primary);"><?php echo formatCurrency($o['total_amount']); ?></div>
                            <a href="<?php echo BASE_URL; ?>order-detail.php?id=<?php echo $o['id']; ?>" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.85rem; margin-top: 8px;">ดูรายละเอียดคำสั่งซื้อ &rarr;</a>
                            <?php if($o['order_status']==='pending'): ?><button type="button" onclick="cancelOrder(<?php echo (int)$o['id']; ?>)" class="btn btn-danger" style="padding:6px 14px;font-size:.85rem;margin-top:8px">ยกเลิกออเดอร์</button><?php endif; ?>
                            <?php if(in_array($o['order_status'],['shipped','completed'],true)): ?><button type="button" onclick="requestReturn(<?php echo (int)$o['id']; ?>)" class="btn btn-outline" style="padding:6px 14px;font-size:.85rem;margin-top:8px">ขอคืนสินค้า</button><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>async function cancelOrder(id){if(!confirm('ยืนยันยกเลิกคำสั่งซื้อ? สินค้าจะถูกคืนเข้าสต็อก'))return;const data=new FormData();data.append('action','cancel');data.append('order_id',id);data.append('csrf_token',CSRF_TOKEN);const response=await fetch(`${BASE_URL}api/orders.php`,{method:'POST',body:data});const result=await response.json();showToast(result.message,result.status==='success'?'success':'error');if(result.status==='success')setTimeout(()=>location.reload(),600)}async function requestReturn(id){const reason=prompt('กรุณาระบุเหตุผลการคืนสินค้าอย่างน้อย 10 ตัวอักษร');if(!reason)return;const d=new FormData();d.append('action','create');d.append('order_id',id);d.append('reason',reason);d.append('csrf_token',CSRF_TOKEN);const r=await fetch(`${BASE_URL}api/returns.php`,{method:'POST',body:d}),j=await r.json();showToast(j.message,j.status==='success'?'success':'error');if(j.status==='success')setTimeout(()=>location.href=`${BASE_URL}my-returns.php`,600)}</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
