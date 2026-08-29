<?php
$page_title = 'ยอดเงินร้านค้า';
require_once __DIR__ . '/includes/auth_check.php';
requireSeller();
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
if (!$db) { http_response_code(503); exit('ไม่สามารถเชื่อมต่อฐานข้อมูลได้'); }
$sellerId = (int)$_SESSION['user_id'];
$commissionRate = 0.10;
$message = '';
$error = '';

$profileStmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id=? AND status='approved' LIMIT 1");
$profileStmt->execute([$sellerId]);
$profile = $profileStmt->fetch();
if (!$profile) { header('Location: '.BASE_URL.'seller.php'); exit; }

function walletTotals(PDO $db, int $sellerId, float $commissionRate): array {
    $sales = $db->prepare("SELECT COALESCE(SUM(s.seller_subtotal-GREATEST(0,o.discount_amount)*(s.seller_subtotal/NULLIF(t.order_subtotal,0))),0) FROM orders o JOIN (SELECT oi.order_id,SUM(oi.subtotal) seller_subtotal FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE p.seller_id=? GROUP BY oi.order_id) s ON s.order_id=o.id JOIN (SELECT order_id,SUM(subtotal) order_subtotal FROM order_items GROUP BY order_id) t ON t.order_id=o.id WHERE o.order_status='completed' AND (o.payment_status='paid' OR (o.payment_method='cod' AND o.payment_status IN ('cod_pending','paid')))");
    $sales->execute([$sellerId]);
    $gross = (float)$sales->fetchColumn();
    $net = round($gross * (1 - $commissionRate), 2);
    $reserved = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM seller_payout_requests WHERE seller_id=? AND status IN ('requested','paid')");
    $reserved->execute([$sellerId]);
    $reservedAmount = (float)$reserved->fetchColumn();
    return ['gross'=>$gross,'commission'=>round($gross*$commissionRate,2),'net'=>$net,'reserved'=>$reservedAmount,'available'=>max(0,round($net-$reservedAmount,2))];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_payout') {
    requireCsrf(false);
    try {
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($amount === false || $amount <= 0) throw new RuntimeException('กรุณาระบุยอดถอนให้ถูกต้อง');
        $db->beginTransaction();
        $totals = walletTotals($db, $sellerId, $commissionRate);
        if ($amount > $totals['available']) throw new RuntimeException('ยอดถอนเกินยอดที่ถอนได้');
        $method = (string)$profile['payout_method'];
        $accountName = $method === 'promptpay' ? (string)$profile['promptpay_owner'] : (string)$profile['payout_account_name'];
        $accountNumber = $method === 'promptpay' ? (string)$profile['promptpay_number'] : (string)$profile['payout_account_number'];
        if ($method === 'both') {
            $accountName = 'PromptPay: '.($profile['promptpay_owner'] ?: $profile['payout_account_name']).' / ธนาคาร: '.$profile['payout_account_name'];
            $accountNumber = 'PromptPay: '.($profile['promptpay_number'] ?: '-').' / บัญชี: '.$profile['payout_account_number'];
        }
        $insert = $db->prepare('INSERT INTO seller_payout_requests (seller_id,amount,payout_method,payout_bank_name,promptpay_owner,promptpay_number,payout_account_name,payout_account_number,status) VALUES (?,?,?,?,?,?,?,?,\'requested\')');
        $insert->execute([$sellerId, $amount, $method, $profile['payout_bank_name'], $profile['promptpay_owner'], $profile['promptpay_number'], $accountName, $accountNumber]);
        $db->commit();
        $message = 'ส่งคำขอถอนเงินจำลองแล้ว รอแอดมินอนุมัติ';
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $exception->getMessage();
    }
}

$totals = walletTotals($db, $sellerId, $commissionRate);
$history = $db->prepare('SELECT * FROM seller_payout_requests WHERE seller_id=? ORDER BY requested_at DESC');
$history->execute([$sellerId]);
$requests = $history->fetchAll();
function maskAccount(string $value): string {
    $value = trim($value);
    if (mb_strlen($value) <= 4) return $value;
    return str_repeat('•', max(0, mb_strlen($value)-4)).mb_substr($value, -4);
}
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/seller.css">
<div class="seller-page seller-wallet-page"><div class="container">
    <nav class="seller-breadcrumb"><a href="<?php echo BASE_URL; ?>my-store.php">ร้านค้าของฉัน</a><i class="fa-solid fa-chevron-right"></i><span>ยอดเงินร้านค้า</span></nav>
    <section class="wallet-hero"><div><p class="eyebrow">MOCK PAYOUT CENTER</p><h1>ยอดเงินร้านค้า</h1><p>ระบบจำลองสำหรับทดสอบการขอถอนเงิน ยังไม่มีการโอนเงินจริง</p></div><i class="fa-solid fa-wallet"></i></section>
    <section class="wallet-stats"><div class="wallet-available"><small>ยอดที่ถอนได้</small><strong><?php echo formatCurrency($totals['available']); ?></strong><span>หลังหักค่าบริการ 10%</span></div><div><small>ยอดขายรวมที่เสร็จแล้ว</small><strong><?php echo formatCurrency($totals['gross']); ?></strong></div><div><small>รอจ่าย/จ่ายแล้ว</small><strong><?php echo formatCurrency($totals['reserved']); ?></strong></div></section>
    <?php if ($message): ?><p class="seller-success"><?php echo e($message); ?></p><?php endif; ?><?php if ($error): ?><p class="seller-error"><?php echo e($error); ?></p><?php endif; ?>
    <section class="wallet-layout"><div class="wallet-card"><header><p class="eyebrow">REQUEST PAYOUT</p><h2>ขอถอนเงินจำลอง</h2><p>เมื่อกดส่ง ระบบจะสร้างรายการให้แอดมินกดอนุมัติ</p></header><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" value="request_payout"><label>จำนวนเงินที่ต้องการถอน<input type="number" name="amount" min="1" max="<?php echo e($totals['available']); ?>" step="0.01" placeholder="เช่น 500.00" required></label><div class="wallet-destination"><i class="fa-solid fa-shield-halved"></i><span><small>ปลายทางที่บันทึกไว้</small><strong><?php echo e(strtoupper((string)$profile['payout_method'])); ?></strong><em><?php echo e(maskAccount((string)($profile['promptpay_number'] ?: $profile['payout_account_number']))); ?></em></span></div><button class="btn btn-primary" type="submit" <?php echo $totals['available']<=0?'disabled':''; ?>>ส่งคำขอถอนเงิน <i class="fa-solid fa-arrow-right"></i></button></form></div><div class="wallet-card"><header><p class="eyebrow">PAYOUT HISTORY</p><h2>ประวัติการถอน</h2></header><div class="payout-history"><?php if(!$requests): ?><p class="wallet-empty">ยังไม่มีคำขอถอนเงิน</p><?php endif; ?><?php foreach($requests as $request): ?><article><div><strong><?php echo formatCurrency($request['amount']); ?></strong><small><?php echo date('d/m/Y H:i', strtotime($request['requested_at'])); ?></small></div><span class="status-badge <?php echo $request['status']==='paid'?'paid':($request['status']==='rejected'?'cancelled':'pending'); ?>"><?php echo $request['status']==='paid'?'โอนแล้ว':($request['status']==='rejected'?'ปฏิเสธ':'รอตรวจสอบ'); ?></span><?php if($request['admin_note']): ?><p><?php echo e($request['admin_note']); ?></p><?php endif; ?></article><?php endforeach; ?></div></div></section>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
