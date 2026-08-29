<?php
// checkout.php
$page_title = "ชำระเงิน";
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$grand_total = 0;
foreach ($cart as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}

$user = getCurrentUser();
$checkoutProfile = ['phone' => '', 'address' => '', 'preferred_payment_method' => 'promptpay'];
$savedAddresses = [];
$profileDb = (new Database())->getConnection();
if ($profileDb) {
    $profileStmt = $profileDb->prepare('SELECT phone,address,preferred_payment_method FROM users WHERE id=? LIMIT 1');
    $profileStmt->execute([(int)$user['id']]);
    $checkoutProfile = array_merge($checkoutProfile, $profileStmt->fetch() ?: []);
    $addressStmt=$profileDb->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id DESC');$addressStmt->execute([(int)$user['id']]);$savedAddresses=$addressStmt->fetchAll();
    $selectedAddressId=(int)($_GET['address_id']??0);$selectedAddress=null;foreach($savedAddresses as $saved){if(($selectedAddressId>0&&(int)$saved['id']===$selectedAddressId)||($selectedAddressId===0&&!$selectedAddress&&$saved['is_default']))$selectedAddress=$saved;}
    if($selectedAddress){$checkoutProfile['phone']=$selectedAddress['phone'];$checkoutProfile['address']=$selectedAddress['address_line'];$user['full_name']=$selectedAddress['recipient_name'];}
}
?>

<div class="container" style="margin-top: 36px; margin-bottom: 60px;">
    <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">ชำระเงิน & กรอกข้อมูลจัดส่ง</h1>
    <p style="color: var(--text-muted); margin-bottom: 28px;">เลือกวิธีการชำระเงินระหว่าง PromptPay QR Code จำลอง หรือชำระเงินปลายทาง (COD)</p>

    <form id="checkoutForm" onsubmit="handleCheckoutSubmit(event)">
        <div class="checkout-grid">
            <!-- Left Form: Shipping Details & Payment Selection -->
            <div>
                <div class="checkout-card" style="margin-bottom: 24px;">
                    <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 18px; color: var(--secondary);"><i class="fa-solid fa-truck" style="color: var(--primary);"></i> ข้อมูลผู้รับและที่อยู่จัดส่ง</h3>
                    <?php if($savedAddresses):?><div style="margin-bottom:16px"><label style="display:block;font-weight:700;margin-bottom:6px">เลือกจากสมุดที่อยู่</label><select onchange="location.href='?address_id='+this.value" style="width:100%;padding:10px"><option value="0">เลือกที่อยู่</option><?php foreach($savedAddresses as $saved):?><option value="<?php echo (int)$saved['id'];?>" <?php echo isset($selectedAddress)&&(int)$selectedAddress['id']===(int)$saved['id']?'selected':'';?>><?php echo e($saved['label'].' — '.$saved['recipient_name'].' '.$saved['postal_code']);?></option><?php endforeach;?></select></div><?php else:?><p style="margin-bottom:16px"><a href="<?php echo BASE_URL;?>address-book.php">+ บันทึกที่อยู่เพื่อเลือกใช้งานครั้งต่อไป</a></p><?php endif;?>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">ชื่อ-นามสกุล ผู้รับ *</label>
                        <input type="text" name="shipping_name" value="<?php echo e($user['full_name']); ?>" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">เบอร์โทรศัพท์ติดต่อ *</label>
                        <input type="text" name="shipping_phone" value="<?php echo e($checkoutProfile['phone']); ?>" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">ที่อยู่จัดส่งโดยละเอียด *</label>
                        <textarea name="shipping_address" rows="3" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"><?php echo e($checkoutProfile['address']);?></textarea>
                    </div>
                </div>

                <div class="checkout-card">
                    <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 18px; color: var(--secondary);"><i class="fa-solid fa-wallet" style="color: var(--primary);"></i> เลือกวิธีการชำระเงิน</h3>
                    
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="<?php echo e($checkoutProfile['preferred_payment_method']); ?>">
                    <input type="hidden" name="action" value="create_order">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                    <div class="payment-method-selector">
                        <div class="payment-option <?php echo $checkoutProfile['preferred_payment_method']==='promptpay'?'active':''; ?>" data-method="promptpay">
                            <i class="fa-solid fa-qrcode"></i>
                            <div class="payment-option-title">PromptPay QR Code</div>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">รับ QR หลังยืนยันคำสั่งซื้อ</span>
                        </div>

                        <div class="payment-option <?php echo $checkoutProfile['preferred_payment_method']==='cod'?'active':''; ?>" data-method="cod">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            <div class="payment-option-title">ชำระเงินปลายทาง (COD)</div>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">จ่ายเมื่อได้รับสินค้า</span>
                        </div>
                    </div>

                    <!-- PromptPay Details Box -->
                    <div id="promptPayDetails" class="promptpay-box">
                        <h4 style="color: #003b6d; margin-bottom: 8px;"><i class="fa-solid fa-qrcode"></i> ชำระเงินด้วย PromptPay QR Code</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">ยอดเงินชำระสุทธิ: <strong style="color: var(--primary); font-size: 1.1rem;"><?php echo formatCurrency($grand_total); ?></strong></p>
                        
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 12px;">ระบบจะสร้าง QR จากยอดคำสั่งซื้อที่ตรวจสอบแล้ว หลังจากกดยืนยันคำสั่งซื้อ</p>
                    </div>

                    <!-- COD Details Box -->
                    <div id="codDetails" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: var(--radius-md); margin-top: 20px; text-align: center;">
                        <i class="fa-solid fa-truck-ramp-box fa-2x" style="color: var(--accent); margin-bottom: 10px;"></i>
                        <h4 style="color: #166534; margin-bottom: 6px;">ชำระเงินปลายทาง (Cash on Delivery)</h4>
                        <p style="font-size: 0.9rem; color: #15803d;">พนักงานจัดส่งจะทำการเก็บเงินจำนวน <strong><?php echo formatCurrency($grand_total); ?></strong> เมื่อสินค้าจัดส่งถึงที่อยู่ของคุณ</p>
                    </div>
                </div>
            </div>

            <!-- Right Summary Column -->
            <div>
                <div class="checkout-card" style="position: sticky; top: 100px;">
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--secondary); margin-bottom: 20px;">รายการสั่งซื้อ</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 14px; max-height: 280px; overflow-y: auto; padding-right: 6px; margin-bottom: 20px;">
                        <?php foreach ($cart as $item): ?>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name']); ?>" style="width: 48px; height: 48px; border-radius: 6px; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="font-size: 0.9rem; font-weight: 600; line-height: 1.3;"><?php echo e($item['name']); ?></div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $item['quantity']; ?> x <?php echo formatCurrency($item['price']); ?></span>
                                </div>
                                <span style="font-weight: 700; font-size: 0.95rem; color: var(--secondary);"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="coupon-box" data-coupon-selector data-subtotal="<?php echo (float)$grand_total; ?>" data-total-target="checkoutTotal" data-discount-target="couponDiscount" data-discount-row="couponDiscountRow" style="margin-bottom:18px;padding:14px;background:#fff8ef;border:1px dashed var(--orange);">
                        <label style="display:block;font-weight:700;font-size:12px;margin-bottom:7px;">เลือกคูปองที่ต้องการใช้</label>
                        <select data-coupon-select style="width:100%;padding:9px;margin-bottom:8px;"><option value="">ไม่ใช้คูปอง</option></select>
                        <div style="display:flex;gap:7px;"><input data-coupon-input name="coupon_code" value="<?php echo e($_SESSION['coupon_code']??''); ?>" placeholder="หรือกรอกรหัสคูปอง" style="min-width:0;flex:1;padding:9px;"><button type="button" data-coupon-apply class="btn btn-outline" style="padding:7px 10px;font-size:11px;">ใช้โค้ด</button></div>
                        <small data-coupon-message style="display:block;margin-top:6px;color:var(--muted);"></small>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 16px; margin-bottom: 24px;">
                        <div style="display:flex;justify-content:space-between;color:var(--muted);font-size:12px;"><span>ยอดสินค้า</span><span><?php echo formatCurrency($grand_total); ?></span></div>
                        <div id="couponDiscountRow" style="display:none;justify-content:space-between;color:#b85b2c;font-size:12px;margin-top:7px;"><span>ส่วนลดคูปอง</span><span id="couponDiscount">-฿0.00</span></div>
                        <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.3rem; color: var(--secondary);">
                            <span>ยอดชำระทั้งหมด</span>
                            <span id="checkoutTotal" style="color: var(--primary);"><?php echo formatCurrency($grand_total); ?></span>
                        </div>
                    </div>

                    <button type="submit" id="placeOrderBtn" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem; font-weight: 700;">
                        <i class="fa-solid fa-lock"></i> ยืนยันการสั่งซื้อ
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const savedCheckoutAddress=<?php echo json_encode((string)$checkoutProfile['address'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const savedPaymentMethod=<?php echo json_encode((string)$checkoutProfile['preferred_payment_method']); ?>;
const shippingAddressField=document.querySelector('[name="shipping_address"]');
if(shippingAddressField&&savedCheckoutAddress!=='')shippingAddressField.value=savedCheckoutAddress;
if(savedPaymentMethod==='cod'){document.getElementById('promptPayDetails').style.display='none';document.getElementById('codDetails').style.display='block';}
async function handleCheckoutSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึกออเดอร์...`;

    const formData = new FormData(e.target);

    try {
        const response = await fetch(`${BASE_URL}api/checkout.php`, {
            method: 'POST',
            body: formData
        });

        const res = await response.json();
        if (res.status === 'success') {
            showToast('บันทึกคำสั่งซื้อเรียบร้อยแล้ว!', 'success');
            setTimeout(() => {
                location.href = res.data.redirect;
            }, 800);
        } else {
            showToast(res.message || 'เกิดข้อผิดพลาดในการบันทึกคำสั่งซื้อ', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-lock"></i> ยืนยันการสั่งซื้อ`;
        }
    } catch (err) {
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-lock"></i> ยืนยันการสั่งซื้อ`;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
