<?php

function allowedOrderTransitions(string $current): array {
    return [
        'pending' => ['pending', 'processing', 'shipped', 'completed', 'cancelled'],
        'processing' => ['processing', 'shipped', 'completed', 'cancelled'],
        'shipped' => ['shipped', 'completed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ][$current] ?? [];
}

function assertOrderTransition(array $order, string $nextOrderStatus, string $nextPaymentStatus): void {
    $currentOrder = (string)($order['order_status'] ?? '');
    $currentPayment = (string)($order['payment_status'] ?? '');
    $method = (string)($order['payment_method'] ?? '');
    if (!in_array($nextOrderStatus, allowedOrderTransitions($currentOrder), true)) {
        throw new RuntimeException('ไม่สามารถย้อนหรือข้ามลำดับสถานะคำสั่งซื้อได้');
    }
    $terminalPayment = ['refunded', 'partially_refunded'];
    if (in_array($currentPayment, $terminalPayment, true) && $nextPaymentStatus !== $currentPayment) {
        throw new RuntimeException('สถานะการคืนเงินต้องแก้ผ่านระบบคืนสินค้าเท่านั้น');
    }
    if ($currentPayment === 'paid' && $nextPaymentStatus !== 'paid') {
        throw new RuntimeException('คำสั่งซื้อที่ชำระแล้วต้องคืนเงินผ่านระบบคืนสินค้า');
    }
    if ($method !== 'cod' && $nextPaymentStatus === 'cod_pending') {
        throw new RuntimeException('สถานะรอเก็บเงินใช้ได้เฉพาะคำสั่งซื้อ COD');
    }
    if ($method === 'cod' && $nextPaymentStatus === 'paid' && !in_array($nextOrderStatus, ['shipped', 'completed'], true)) {
        throw new RuntimeException('COD บันทึกว่าชำระแล้วได้หลังจัดส่งเท่านั้น');
    }
    if ($method !== 'cod' && in_array($nextOrderStatus, ['processing', 'shipped', 'completed'], true) && $nextPaymentStatus !== 'paid') {
        throw new RuntimeException('ต้องยืนยันการชำระเงินก่อนดำเนินการคำสั่งซื้อ');
    }
}

function fulfillmentToOrderStatus(string $status): string {
    return ['accepted'=>'processing','packing'=>'processing','shipped'=>'shipped','delivered'=>'completed'][$status] ?? 'pending';
}

function orderToFulfillmentStatus(string $status): string {
    return ['pending'=>'pending','processing'=>'packing','shipped'=>'shipped','completed'=>'delivered','cancelled'=>'cancelled'][$status] ?? 'pending';
}

function syncOrderFromFulfillments(PDO $db,int $orderId,?int $actorId=null):void {
    $order=$db->prepare('SELECT order_status,payment_status,payment_method FROM orders WHERE id=? FOR UPDATE');$order->execute([$orderId]);$current=$order->fetch();if(!$current||$current['order_status']==='cancelled')return;
    $states=$db->prepare('SELECT status FROM order_fulfillments WHERE order_id=?');$states->execute([$orderId]);$states=$states->fetchAll(PDO::FETCH_COLUMN);if(!$states)return;
    $next=in_array('pending',$states,true)?'pending':(in_array('accepted',$states,true)||in_array('packing',$states,true)?'processing':(in_array('shipped',$states,true)?'shipped':'completed'));
    if($next===$current['order_status'])return;
    if($current['payment_method']!=='cod'&&$current['payment_status']!=='paid'&&$next!=='pending')return;
    $db->prepare('UPDATE orders SET order_status=?,delivered_at=IF(?="completed",COALESCE(delivered_at,NOW()),delivered_at) WHERE id=?')->execute([$next,$next,$orderId]);
    recordOrderHistory($db,$orderId,$next,(string)$current['payment_status'],'ซิงก์จากสถานะจัดส่งของร้านค้า',$actorId);
}

function syncFulfillmentsFromOrder(PDO $db,int $orderId,string $orderStatus):void {
    $status=orderToFulfillmentStatus($orderStatus);
    $db->prepare('UPDATE order_fulfillments SET status=?,shipped_at=IF(?="shipped",COALESCE(shipped_at,NOW()),shipped_at),delivered_at=IF(?="delivered",COALESCE(delivered_at,NOW()),delivered_at) WHERE order_id=?')->execute([$status,$status,$status,$orderId]);
}

function ensureSellerLedgerForOrder(PDO $db,int $orderId):void {
    $stmt=$db->prepare("SELECT oi.seller_id,SUM(oi.subtotal) subtotal,o.subtotal_amount,o.discount_amount FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.order_id=? AND oi.seller_id IS NOT NULL GROUP BY oi.seller_id,o.subtotal_amount,o.discount_amount");$stmt->execute([$orderId]);
    foreach($stmt->fetchAll() as $row){$seller=(int)$row['seller_id'];$gross=(float)$row['subtotal'];$share=(float)$row['subtotal_amount']>0?(float)$row['discount_amount']*$gross/(float)$row['subtotal_amount']:0;$net=max(0,$gross-$share);$commission=round($net*.10,2);$available=date('Y-m-d H:i:s',time()+7*86400);$insert=$db->prepare("INSERT IGNORE INTO seller_ledger(seller_id,order_id,entry_type,amount,description,idempotency_key,available_at) VALUES(?,?,'sale',?,'ยอดขายสุทธิหลังส่วนลด',?,?),(?,?,'commission',?,'ค่าบริการแพลตฟอร์ม 10%',?,?)");$insert->execute([$seller,$orderId,$net,'order:'.$orderId.':seller:'.$seller.':sale',$available,$seller,$orderId,-$commission,'order:'.$orderId.':seller:'.$seller.':commission',$available]);}
    $shipping=$db->prepare('SELECT seller_id,shipping_amount,seller_subsidy_amount FROM order_shipping_allocations WHERE order_id=? AND seller_id IS NOT NULL');$shipping->execute([$orderId]);$available=date('Y-m-d H:i:s',time()+7*86400);$add=$db->prepare("INSERT IGNORE INTO seller_ledger(seller_id,order_id,entry_type,amount,description,idempotency_key,available_at) VALUES(?,?,'adjustment',?,'ค่าจัดส่งสุทธิหลังร่วมสนับสนุนคูปอง',?,?)");foreach($shipping->fetchAll() as $row){$amount=max(0,(float)$row['shipping_amount']-(float)$row['seller_subsidy_amount']);$add->execute([(int)$row['seller_id'],$orderId,$amount,'order:'.$orderId.':seller:'.$row['seller_id'].':shipping',$available]);}
}

function reverseSellerLedgerForRefund(PDO $db,int $orderId,float $refundAmount,int $returnId):void {
    $total=$db->prepare('SELECT total_amount FROM orders WHERE id=?');$total->execute([$orderId]);$orderTotal=(float)$total->fetchColumn();if($orderTotal<=0)return;
    $rows=$db->prepare("SELECT seller_id,SUM(amount) balance FROM seller_ledger WHERE order_id=? AND seller_id IS NOT NULL GROUP BY seller_id");$rows->execute([$orderId]);
    foreach($rows->fetchAll() as $row){$amount=-round((float)$row['balance']*min(1,$refundAmount/$orderTotal),2);$db->prepare("INSERT IGNORE INTO seller_ledger(seller_id,order_id,entry_type,amount,description,idempotency_key,available_at) VALUES(?,?,'refund',?,'หักยอดจากการคืนเงิน',?,NOW())")->execute([(int)$row['seller_id'],$orderId,$amount,'return:'.$returnId.':seller:'.$row['seller_id'].':refund']);}
}
