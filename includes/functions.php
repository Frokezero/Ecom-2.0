<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/transactional_mail.php';

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
        $sql='SELECT id,user_id,coupon_id,order_status,payment_status FROM orders WHERE id=?'.($userId!==null?' AND user_id=?':'').' FOR UPDATE';
        $stmt=$db->prepare($sql); $params=[$orderId]; if($userId!==null)$params[]=$userId; $stmt->execute($params); $order=$stmt->fetch();
        if(!$order) throw new RuntimeException('ไม่พบคำสั่งซื้อหรือไม่มีสิทธิ์ดำเนินการ');
        if($order['order_status']==='cancelled') throw new RuntimeException('คำสั่งซื้อนี้ถูกยกเลิกแล้ว');
        if($userId!==null && $order['order_status']!=='pending') throw new RuntimeException('ยกเลิกได้เฉพาะคำสั่งซื้อที่ยังไม่เริ่มดำเนินการ');
        if(in_array($order['order_status'],['shipped','completed'],true)) throw new RuntimeException('ไม่สามารถยกเลิกคำสั่งซื้อที่จัดส่งแล้ว');
        if($order['payment_status']==='paid') throw new RuntimeException('คำสั่งซื้อนี้ชำระเงินแล้ว ต้องดำเนินการคืนเงินก่อนยกเลิก');
        $items=$db->prepare('SELECT product_id,variant_id,quantity FROM order_items WHERE order_id=?');$items->execute([$orderId]);
        $restore=$db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
        $restoreVariant=$db->prepare('UPDATE product_variants SET stock_quantity=stock_quantity+? WHERE id=?');
        foreach($items->fetchAll() as $item){if(!empty($item['variant_id']))$restoreVariant->execute([(int)$item['quantity'],(int)$item['variant_id']]);else $restore->execute([(int)$item['quantity'],(int)$item['product_id']]);}
        $update=$db->prepare("UPDATE orders SET order_status='cancelled' WHERE id=? AND order_status<>'cancelled'");$update->execute([$orderId]);
        if($update->rowCount()!==1)throw new RuntimeException('สถานะคำสั่งซื้อถูกเปลี่ยนไปแล้ว');
        if (!empty($order['coupon_id'])) {
            $usage=$db->prepare('DELETE FROM coupon_usages WHERE order_id=?');
            $usage->execute([$orderId]);
            if ($usage->rowCount() > 0) {
                $release=$db->prepare('UPDATE user_coupons SET used_count=GREATEST(0,used_count-1) WHERE coupon_id=? AND user_id=?');
                $release->execute([(int)$order['coupon_id'],(int)$order['user_id']]);
            }
        }
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
function isSeller(): bool { return ($_SESSION['user_role'] ?? '') === 'seller'; }
function createNotification(PDO $db, int $userId, string $type, string $title, string $body = '', ?string $link = null): void {
    if ($userId < 1) return;
    $stmt = $db->prepare('INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, substr($type, 0, 40), mb_substr($title, 0, 160), mb_substr($body, 0, 500) ?: null, $link]);
    if (in_array($type, ['order','payment','return','seller','security','payout'], true)) {
        $notificationId=(int)$db->lastInsertId();$actionUrl='';
        if($link){$actionUrl=preg_match('#^https?://#i',$link)?$link:mailAppUrl().ltrim($link,'/');}
        sendUserEventEmail($db,$userId,'notification.'.$type,$title,$title,$body,$actionUrl,'notification:'.$notificationId);
    }
}
function createRoleNotification(PDO $db, string $role, string $type, string $title, string $body = '', ?string $link = null): void {
    $users = $db->prepare('SELECT id FROM users WHERE role=?');
    $users->execute([$role]);
    foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $userId) createNotification($db, (int)$userId, $type, $title, $body, $link);
}
function auditLog(PDO $db,string $action,string $entityType,$entityId=null,$before=null,$after=null): void {
    $key=appConfig('APP_KEY','kitchenmart-local-audit-key');$ip=(string)($_SERVER['HTTP_CF_CONNECTING_IP']??$_SERVER['REMOTE_ADDR']??'unknown');
    $stmt=$db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,before_json,after_json,ip_hash) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id']??null,substr($action,0,80),substr($entityType,0,60),$entityId===null?null:(string)$entityId,$before===null?null:json_encode($before,JSON_UNESCAPED_UNICODE),$after===null?null:json_encode($after,JSON_UNESCAPED_UNICODE),hash_hmac('sha256',$ip,$key)]);
}
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

function activePromotionalBanners(PDO $db, string $placement = 'hero', int $categoryId = 0): array {
    $allowed = ['hero','category','cart','floating'];
    if (!in_array($placement, $allowed, true)) return [];
    $sql = "SELECT b.*, c.code coupon_code FROM promotional_banners b LEFT JOIN coupons c ON c.id=b.coupon_id WHERE b.placement=? AND b.is_active=1 AND b.starts_at<=NOW() AND b.ends_at>=NOW()";
    $params = [$placement];
    if ($categoryId > 0) { $sql .= ' AND (b.category_id IS NULL OR b.category_id=?)'; $params[] = $categoryId; }
    $sql .= ' ORDER BY b.sort_order DESC,b.id DESC';
    $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
}
function formatCouponBenefit(array $coupon): string {if(($coupon['discount_type']??'')==='percent')return 'ลด '.number_format((float)$coupon['discount_value'],0).'%';if(($coupon['discount_type']??'')==='fixed')return 'ลด '.formatCurrency($coupon['discount_value']);return 'ส่งฟรี';}

function calculateCouponDiscount(PDO $db, string $code, int $userId, array $items, float $subtotal, bool $lock = false): array {
    $code = strtoupper(trim($code));
    if ($code === '') return ['coupon'=>null,'discount'=>0.0,'error'=>''];
    $stmt = $db->prepare('SELECT * FROM coupons WHERE code=? AND is_active=1 AND starts_at<=NOW() AND ends_at>=NOW() LIMIT 1'.($lock?' FOR UPDATE':''));
    $stmt->execute([$code]); $coupon = $stmt->fetch();
    if (!$coupon) return ['coupon'=>null,'discount'=>0.0,'error'=>'ไม่พบคูปองหรือคูปองหมดอายุแล้ว'];
    if ($subtotal < (float)$coupon['min_order_amount']) return ['coupon'=>null,'discount'=>0.0,'error'=>'ยอดซื้อยังไม่ถึงขั้นต่ำของคูปองนี้'];
    if ($coupon['usage_limit'] !== null) { $used=(int)$db->query('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id='.(int)$coupon['id'])->fetchColumn(); if ($used >= (int)$coupon['usage_limit']) return ['coupon'=>null,'discount'=>0.0,'error'=>'คูปองถูกใช้ครบจำนวนแล้ว']; }
    $userStmt=$db->prepare('SELECT used_count FROM user_coupons WHERE coupon_id=? AND user_id=?'.($lock?' FOR UPDATE':'')); $userStmt->execute([(int)$coupon['id'],$userId]); $userCoupon=$userStmt->fetch();
    if ($userCoupon && (int)$userCoupon['used_count'] >= (int)$coupon['per_user_limit']) return ['coupon'=>null,'discount'=>0.0,'error'=>'คุณใช้คูปองนี้ครบจำนวนแล้ว'];
    $eligibleSubtotal=$subtotal;
    if($coupon['product_id']!==null||$coupon['category_id']!==null){$eligibleSubtotal=0.0;$productCheck=$db->prepare('SELECT category_id FROM products WHERE id=?');foreach($items as $item){$productId=(int)($item['id']??$item['product_id']??0);$productCheck->execute([$productId]);$categoryId=(int)$productCheck->fetchColumn();if(($coupon['product_id']===null||(int)$coupon['product_id']===$productId)&&($coupon['category_id']===null||(int)$coupon['category_id']===$categoryId))$eligibleSubtotal+=(float)$item['price']*(int)$item['quantity'];}if($eligibleSubtotal<=0)return ['coupon'=>null,'discount'=>0.0,'error'=>'ไม่มีสินค้าในตะกร้าที่ร่วมรายการกับคูปองนี้'];}
    $discount = $coupon['discount_type']==='percent' ? $eligibleSubtotal*((float)$coupon['discount_value']/100) : min($eligibleSubtotal,(float)$coupon['discount_value']);
    if ($coupon['discount_type']==='free_shipping') $discount=0.0;
    if ($coupon['max_discount'] !== null) $discount=min($discount,(float)$coupon['max_discount']);
    return ['coupon'=>$coupon,'discount'=>min($subtotal,max(0,$discount)),'error'=>''];
}
