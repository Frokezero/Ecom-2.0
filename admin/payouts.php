<?php
$page_title = 'คำขอถอนเงินผู้ขาย';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf(false);
    $id = (int)($_POST['payout_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!$id || !in_array($action, ['pay','reject'], true)) $error = 'ข้อมูลไม่ถูกต้อง';
    else {
        $status = $action === 'pay' ? 'paid' : 'rejected';
        $reference = $action === 'pay' ? 'MOCK-' . date('YmdHis') . '-' . $id : null;
        $note = trim((string)($_POST['admin_note'] ?? ''));
        $stmt = $db->prepare('UPDATE seller_payout_requests SET status=?,admin_note=?,transfer_reference=?,processed_at=NOW(),processed_by=? WHERE id=? AND status="requested"');
        $stmt->execute([$status, $note ?: ($action === 'pay' ? 'โอนเงินจำลองสำเร็จ' : 'ปฏิเสธคำขอถอนเงิน'), $reference, (int)$_SESSION['user_id'], $id]);
        $message = $stmt->rowCount() ? ($action === 'pay' ? 'อนุมัติและบันทึกการโอนจำลองแล้ว' : 'ปฏิเสธคำขอถอนแล้ว') : 'รายการนี้ถูกดำเนินการไปแล้ว';
    }
}
$requests = $db->query('SELECT r.*,u.username,u.full_name,sp.shop_name FROM seller_payout_requests r JOIN users u ON u.id=r.seller_id LEFT JOIN seller_profiles sp ON sp.user_id=r.seller_id ORDER BY FIELD(r.status,"requested","paid","rejected"),r.requested_at DESC')->fetchAll();
require_once __DIR__ . '/../includes/admin_header.php';
?>
<header class="admin-page-header"><div><p class="eyebrow">MOCK PAYOUTS</p><h1>คำขอถอนเงินผู้ขาย</h1><p>จัดการการโอนเงินจำลองสำหรับทดสอบระบบเท่านั้น ไม่มีการโอนเงินจริง</p></div></header>
<?php if($message): ?><div class="admin-alert success"><?php echo e($message); ?></div><?php endif; ?><?php if($error): ?><div class="admin-alert error"><?php echo e($error); ?></div><?php endif; ?>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ร้านค้า</th><th>ยอดถอน</th><th>ช่องทาง</th><th>ปลายทาง</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody><?php if(!$requests): ?><tr><td colspan="6" class="admin-empty">ยังไม่มีคำขอถอนเงิน</td></tr><?php endif; ?><?php foreach($requests as $request): ?><tr><td><strong><?php echo e($request['shop_name'] ?: $request['full_name'] ?: $request['username']); ?></strong><span class="cell-subtitle"><?php echo e($request['username']); ?></span></td><td><strong><?php echo formatCurrency($request['amount']); ?></strong><span class="cell-subtitle"><?php echo date('d/m/Y H:i',strtotime($request['requested_at'])); ?></span></td><td><?php echo e(strtoupper($request['payout_method'])); ?></td><td><span class="cell-subtitle"><?php echo e($request['payout_account_name']); ?></span><span class="cell-subtitle"><?php echo e($request['payout_account_number']); ?></span></td><td><span class="status-badge <?php echo $request['status']==='paid'?'paid':($request['status']==='rejected'?'cancelled':'pending'); ?>"><?php echo $request['status']==='paid'?'โอนแล้ว':($request['status']==='rejected'?'ปฏิเสธ':'รอตรวจสอบ'); ?></span><?php if($request['transfer_reference']): ?><span class="cell-subtitle"><?php echo e($request['transfer_reference']); ?></span><?php endif; ?></td><td><?php if($request['status']==='requested'): ?><form method="POST" class="table-actions"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="payout_id" value="<?php echo (int)$request['id']; ?>"><input name="admin_note" maxlength="500" placeholder="หมายเหตุ"><button class="btn btn-primary" name="action" value="pay">โอนจำลอง</button><button class="btn btn-danger" name="action" value="reject">ปฏิเสธ</button></form><?php else: ?><small><?php echo e($request['admin_note'] ?: '-'); ?></small><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
