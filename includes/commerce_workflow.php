<?php

function allowedOrderTransitions(string $current): array {
    return [
        'pending' => ['pending', 'processing', 'cancelled'],
        'processing' => ['processing', 'shipped', 'cancelled'],
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
