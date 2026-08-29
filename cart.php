<?php
// cart.php
$page_title = "ตะกร้าสินค้า";
require_once __DIR__ . '/includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$grand_total = 0;
foreach ($cart as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}
?>

<div class="container" style="margin-top: 36px; margin-bottom: 60px;">
    <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 24px;"><i class="fa-solid fa-cart-shopping" style="color: var(--primary);"></i> ตะกร้าสินค้าของคุณ</h1>

    <?php if (empty($cart)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-cart-arrow-down fa-3x" style="color: var(--text-muted); margin-bottom: 16px;"></i>
            <h2>ตะกร้าสินค้าของคุณยังว่างเปล่า</h2>
            <p style="color: var(--text-muted); margin-top: 8px;">เลือกอุปกรณ์ครัวชิ้นโปรด แล้วเพิ่มลงในตะกร้าได้เลย</p>
            <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary" style="margin-top: 24px; padding: 12px 28px;">ไปเลือกซื้อสินค้า</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 32px;">
            <!-- Cart Table -->
            <div style="background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.9rem; color: var(--text-muted);">
                            <th style="padding-bottom: 12px;">สินค้า</th>
                            <th style="padding-bottom: 12px;">ราคา</th>
                            <th style="padding-bottom: 12px; text-align: center;">จำนวน</th>
                            <th style="padding-bottom: 12px; text-align: right;">รวม</th>
                            <th style="padding-bottom: 12px; text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <?php foreach ($cart as $id => $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 16px 0;">
                                    <div style="display: flex; align-items: center; gap: 14px;">
                                        <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name']); ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                                        <span style="font-weight: 600; font-size: 0.95rem; color: var(--secondary);"><?php echo e($item['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatCurrency($item['price']); ?></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                                        <button onclick="updateCartQuantity(<?php echo $id; ?>, <?php echo $item['quantity'] - 1; ?>)" style="padding: 4px 10px; border: none; background: #f1f5f9; cursor: pointer;">-</button>
                                        <span style="padding: 0 12px; font-weight: 600; font-size: 0.9rem;"><?php echo $item['quantity']; ?></span>
                                        <button onclick="updateCartQuantity(<?php echo $id; ?>, <?php echo $item['quantity'] + 1; ?>)" style="padding: 4px 10px; border: none; background: #f1f5f9; cursor: pointer;">+</button>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);"><?php echo formatCurrency($subtotal); ?></td>
                                <td style="text-align: center;">
                                    <button onclick="removeFromCart(<?php echo $id; ?>)" style="color: var(--danger); background: none; border: none; cursor: pointer; font-size: 1.1rem;" title="ลบออกจากตะกร้า"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Order Summary Card -->
            <div style="background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px; height: fit-content; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--secondary); margin-bottom: 20px;">สรุปยอดสั่งซื้อ</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--text-muted);">
                    <span>ยอดรวมสินค้า</span>
                    <span style="font-weight: 600; color: var(--text-main);"><?php echo formatCurrency($grand_total); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; color: var(--text-muted);">
                    <span>ค่าจัดส่ง</span>
                    <span style="font-weight: 600; color: var(--accent);">ฟรีค่าจัดส่ง</span>
                </div>

                <div style="border-top: 2px dashed var(--border-color); padding-top: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; font-size: 1.1rem; color: var(--secondary);">ยอดรวมสุทธิ</span>
                    <span style="font-weight: 700; font-size: 1.6rem; color: var(--primary);"><?php echo formatCurrency($grand_total); ?></span>
                </div>

                <a href="<?php echo BASE_URL; ?>checkout.php" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; font-weight: 700;">
                    เข้าสู่การชำระเงิน <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
