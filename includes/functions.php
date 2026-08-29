<?php
require_once __DIR__ . '/../config/config.php';

function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function formatCurrency($amount): string { return '฿' . number_format((float)$amount, 2); }
function generatePromptPayQRUrl($promptPayId, $amount): string {
    $promptPayId = trim((string)$promptPayId);
    if ($promptPayId === '') throw new InvalidArgumentException('PromptPay ID is required');
    if ($amount === null || $amount === '' || is_bool($amount) || !is_numeric($amount)) throw new InvalidArgumentException('Invalid payment amount');
    $numericAmount = (float)$amount;
    if (!is_finite($numericAmount) || $numericAmount <= 0) throw new InvalidArgumentException('Invalid payment amount');
    return 'https://promptpay.io/' . rawurlencode($promptPayId) . '/' . number_format($numericAmount, 2, '.', '') . '.png';
}
function productImageUrl($path): string {
    $path=(string)$path;
    return preg_match('#^https?://#i',$path) ? $path : BASE_URL.ltrim($path,'/');
}
function cancelOrderAndRestock(PDO $db, int $orderId, ?int $userId = null): void {
    $db->beginTransaction();
    try {
        $sql='SELECT id,order_status,payment_status FROM orders WHERE id=?'.($userId!==null?' AND user_id=?':'').' FOR UPDATE';
        $stmt=$db->prepare($sql); $params=[$orderId]; if($userId!==null)$params[]=$userId; $stmt->execute($params); $order=$stmt->fetch();
        if(!$order) throw new RuntimeException('ไม่พบคำสั่งซื้อหรือไม่มีสิทธิ์ดำเนินการ');
        if($order['order_status']==='cancelled') throw new RuntimeException('คำสั่งซื้อนี้ถูกยกเลิกแล้ว');
        if($userId!==null && $order['order_status']!=='pending') throw new RuntimeException('ยกเลิกได้เฉพาะคำสั่งซื้อที่ยังไม่เริ่มดำเนินการ');
        if(in_array($order['order_status'],['shipped','completed'],true)) throw new RuntimeException('ไม่สามารถยกเลิกคำสั่งซื้อที่จัดส่งแล้ว');
        if($order['payment_status']==='paid') throw new RuntimeException('คำสั่งซื้อนี้ชำระเงินแล้ว ต้องดำเนินการคืนเงินก่อนยกเลิก');
        $items=$db->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=?');$items->execute([$orderId]);
        $restore=$db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
        foreach($items->fetchAll() as $item)$restore->execute([(int)$item['quantity'],(int)$item['product_id']]);
        $update=$db->prepare("UPDATE orders SET order_status='cancelled' WHERE id=? AND order_status<>'cancelled'");$update->execute([$orderId]);
        if($update->rowCount()!==1)throw new RuntimeException('สถานะคำสั่งซื้อถูกเปลี่ยนไปแล้ว');
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function getCsrfToken(): string { return $_SESSION['csrf_token'] ?? ''; }
function requestCsrfToken(): string { return (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''); }
function verifyCsrfToken($token): bool { return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); }
function requireCsrf(bool $json = true): void {
    if (verifyCsrfToken(requestCsrfToken())) return;
    if ($json) jsonResponse('error', 'โทเคนความปลอดภัยไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่', [], 403);
    http_response_code(403); exit('Invalid CSRF token');
}
function jsonResponse($status, $message, $data = [], $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>$status, 'message'=>$message, 'data'=>$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return ['id'=>(int)$_SESSION['user_id'],'username'=>$_SESSION['username'] ?? '','full_name'=>$_SESSION['full_name'] ?? '','email'=>$_SESSION['email'] ?? '','role'=>$_SESSION['user_role'] ?? 'customer'];
}
function generatePromptPayPayload($mobileNo, $amount): string {
    $mobile = preg_replace('/\D/', '', (string)$mobileNo);
    if (strlen($mobile) === 10 && $mobile[0] === '0') $mobile = '66' . substr($mobile, 1);
    $target = str_pad($mobile, 13, '0', STR_PAD_LEFT);
    $amountText = number_format((float)$amount, 2, '.', '');
    return '00020101021229370016A0000006770101110113'.$target.'5802TH530376454'.str_pad((string)strlen($amountText),2,'0',STR_PAD_LEFT).$amountText.'6304';
}
