<?php
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
if (!$db) {
    http_response_code(503);
    exit('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT id,username,email,full_name,phone,address,preferred_payment_method,email_verified_at,created_at FROM users WHERE id=? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION = [];
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
$orderStats = ['orders' => 0, 'spent' => 0];
$statsStmt = $db->prepare("SELECT COUNT(*) AS orders,COALESCE(SUM(CASE WHEN order_status<>'cancelled' THEN total_amount ELSE 0 END),0) AS spent FROM orders WHERE user_id=?");
$statsStmt->execute([$userId]);
$orderStats = $statsStmt->fetch() ?: $orderStats;
$initial = strtoupper(substr(trim($user['full_name'] ?: $user['username']), 0, 1));
$page_title = 'โปรไฟล์ของฉัน';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/profile.css">
<div class="profile-page">
    <div class="container">
        <div class="profile-breadcrumb"><a href="<?php echo BASE_URL; ?>index.php">หน้าแรก</a><i class="fa-solid fa-chevron-right"></i><span>โปรไฟล์ของฉัน</span></div>
        <section class="profile-hero">
            <div class="profile-avatar" aria-hidden="true"><?php echo e($initial); ?></div>
            <div class="profile-hero-copy"><p class="eyebrow">MY KITCHENMART</p><h1 class="profile-name"><?php echo e($user['full_name']); ?></h1><p>@<?php echo e($user['username']); ?> · สมาชิกตั้งแต่ <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p></div>
            <div class="profile-verified <?php echo $user['email_verified_at'] ? '' : 'is-pending'; ?>"><i class="fa-solid <?php echo $user['email_verified_at'] ? 'fa-circle-check' : 'fa-clock'; ?>"></i><?php echo $user['email_verified_at'] ? 'ยืนยันอีเมลแล้ว' : 'รอยืนยันอีเมล'; ?></div>
        </section>

        <section class="profile-stat-grid" aria-label="สรุปบัญชี">
            <a href="<?php echo BASE_URL; ?>my-orders.php"><i class="fa-solid fa-bag-shopping"></i><span><small>คำสั่งซื้อทั้งหมด</small><strong><?php echo (int)$orderStats['orders']; ?> รายการ</strong></span><i class="fa-solid fa-arrow-right"></i></a>
            <div><i class="fa-solid fa-receipt"></i><span><small>ยอดสั่งซื้อสะสม</small><strong><?php echo formatCurrency($orderStats['spent']); ?></strong></span></div>
            <button type="button" data-profile-tab="payment"><i class="fa-solid <?php echo $user['preferred_payment_method'] === 'promptpay' ? 'fa-qrcode' : 'fa-truck-ramp-box'; ?>"></i><span><small>วิธีชำระเงินหลัก</small><strong><?php echo $user['preferred_payment_method'] === 'promptpay' ? 'PromptPay' : 'เก็บเงินปลายทาง'; ?></strong></span><i class="fa-solid fa-pen"></i></button>
        </section>

        <div class="profile-layout">
            <aside class="profile-sidebar">
                <p class="profile-sidebar-label">จัดการบัญชี</p>
                <nav class="profile-nav" aria-label="เมนูโปรไฟล์">
                    <button class="active" type="button" data-profile-tab="details"><i class="fa-regular fa-user"></i> ข้อมูลส่วนตัว</button>
                    <button type="button" data-profile-tab="address"><i class="fa-solid fa-location-dot"></i> ที่อยู่จัดส่ง</button>
                    <button type="button" data-profile-tab="payment"><i class="fa-regular fa-credit-card"></i> วิธีชำระเงิน</button>
                    <button type="button" data-profile-tab="security"><i class="fa-solid fa-shield-halved"></i> ความปลอดภัย</button>
                    <a href="<?php echo BASE_URL; ?>my-orders.php"><i class="fa-solid fa-box"></i> คำสั่งซื้อของฉัน <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </nav>
                <div class="profile-help"><i class="fa-regular fa-circle-question"></i><span><strong>ต้องการความช่วยเหลือ?</strong>ข้อมูลบัญชีของคุณได้รับการปกป้องด้วยการยืนยันอีเมล</span></div>
            </aside>

            <div class="profile-panels">
                <section class="profile-panel active" data-profile-panel="details">
                    <header class="panel-heading"><div><p class="eyebrow">PERSONAL DETAILS</p><h2>ข้อมูลส่วนตัว</h2><p>ใช้สำหรับระบุตัวตนและติดต่อเกี่ยวกับคำสั่งซื้อ</p></div></header>
                    <form class="profile-form" data-profile-form="update_profile">
                        <div class="profile-form-grid"><label>ชื่อผู้ใช้<div class="readonly-input"><i class="fa-regular fa-user"></i><span><?php echo e($user['username']); ?></span></div><small>ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</small></label><label>อีเมล<div class="readonly-input"><i class="fa-regular fa-envelope"></i><span><?php echo e($user['email']); ?></span></div><small>เปลี่ยนได้จากเมนูความปลอดภัย</small></label><label>ชื่อ-นามสกุล<input name="full_name" required minlength="2" maxlength="100" value="<?php echo e($user['full_name']); ?>" autocomplete="name"></label><label>เบอร์โทรศัพท์<input name="phone" maxlength="20" value="<?php echo e($user['phone']); ?>" autocomplete="tel" placeholder="เช่น 0812345678"></label></div>
                        <input type="hidden" name="action" value="update_profile"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><div class="panel-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึกข้อมูล</button></div>
                    </form>
                </section>

                <section class="profile-panel" data-profile-panel="address">
                    <header class="panel-heading"><div><p class="eyebrow">DELIVERY ADDRESS</p><h2>ที่อยู่จัดส่ง</h2><p>บันทึกที่อยู่หลักไว้เพื่อกรอกข้อมูลสั่งซื้อได้รวดเร็วขึ้น</p></div><i class="fa-solid fa-location-dot panel-heading-icon"></i></header>
                    <form class="profile-form" data-profile-form="update_profile">
                        <label for="profileAddress">ที่อยู่หลัก<textarea id="profileAddress" name="address" maxlength="1000" rows="6" placeholder="บ้านเลขที่ ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด รหัสไปรษณีย์"><?php echo e($user['address']); ?></textarea><small>ที่อยู่นี้จะช่วยกรอกในหน้าชำระเงิน แต่ยังแก้ไขได้ก่อนยืนยันคำสั่งซื้อ</small></label>
                        <input type="hidden" name="full_name" value="<?php echo e($user['full_name']); ?>"><input type="hidden" name="phone" value="<?php echo e($user['phone']); ?>"><input type="hidden" name="action" value="update_profile"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><div class="panel-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-location-dot"></i> บันทึกที่อยู่</button></div>
                    </form>
                </section>

                <section class="profile-panel" data-profile-panel="payment">
                    <header class="panel-heading"><div><p class="eyebrow">PAYMENT PREFERENCE</p><h2>วิธีชำระเงินที่ต้องการ</h2><p>เลือกวิธีที่ต้องการให้ระบบเลือกไว้ก่อนเมื่อสั่งซื้อ</p></div></header>
                    <form class="profile-form" data-profile-form="update_payment_preference">
                        <div class="payment-preference-grid"><label class="payment-preference"><input type="radio" name="preferred_payment_method" value="promptpay" <?php echo $user['preferred_payment_method'] === 'promptpay' ? 'checked' : ''; ?>><span class="payment-preference-card"><i class="fa-solid fa-qrcode"></i><strong>PromptPay</strong><small>สแกน QR ชำระเงินได้ทันที</small><b><i class="fa-solid fa-check"></i></b></span></label><label class="payment-preference"><input type="radio" name="preferred_payment_method" value="cod" <?php echo $user['preferred_payment_method'] === 'cod' ? 'checked' : ''; ?>><span class="payment-preference-card"><i class="fa-solid fa-truck-ramp-box"></i><strong>เก็บเงินปลายทาง</strong><small>ชำระเมื่อได้รับสินค้า</small><b><i class="fa-solid fa-check"></i></b></span></label></div>
                        <input type="hidden" name="action" value="update_payment_preference"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><div class="panel-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึกวิธีชำระเงิน</button></div>
                    </form>
                </section>

                <section class="profile-panel" data-profile-panel="security">
                    <header class="panel-heading"><div><p class="eyebrow">ACCOUNT SECURITY</p><h2>ความปลอดภัยของบัญชี</h2><p>เปลี่ยนรหัสผ่านหรืออีเมลได้อย่างปลอดภัยด้วยการยืนยันรหัสผ่านปัจจุบัน</p></div><i class="fa-solid fa-shield-halved panel-heading-icon"></i></header>
                    <div class="security-grid">
                        <form class="security-card" data-profile-form="update_password"><header><span class="security-icon"><i class="fa-solid fa-key"></i></span><div><h3>เปลี่ยนรหัสผ่าน</h3><p>ใช้รหัสผ่านอย่างน้อย 8 ตัว พร้อมตัวอักษรและตัวเลข</p></div></header><label>รหัสผ่านปัจจุบัน<input type="password" name="current_password" required autocomplete="current-password"></label><label>รหัสผ่านใหม่<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><label>ยืนยันรหัสผ่านใหม่<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label><input type="hidden" name="action" value="update_password"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><button class="btn btn-outline" type="submit">เปลี่ยนรหัสผ่าน</button></form>
                        <form class="security-card" data-profile-form="update_email"><header><span class="security-icon"><i class="fa-regular fa-envelope"></i></span><div><h3>เปลี่ยนอีเมล</h3><p>ระบบจะส่งลิงก์ยืนยันไปยังอีเมลใหม่ แล้วให้เข้าสู่ระบบอีกครั้ง</p></div></header><label>อีเมลใหม่<input type="email" name="email" required autocomplete="email" placeholder="name@example.com"></label><label>รหัสผ่านปัจจุบัน<input type="password" name="current_password" required autocomplete="current-password"></label><div class="security-note"><i class="fa-solid fa-circle-info"></i> หลังบันทึก คุณจะออกจากระบบเพื่อยืนยันอีเมลใหม่</div><input type="hidden" name="action" value="update_email"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><button class="btn btn-outline" type="submit">ส่งลิงก์ยืนยันอีเมลใหม่</button></form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<div class="profile-notice" id="profileNotice" role="status" aria-live="polite"></div>
<script>
const profilePanels=document.querySelectorAll('[data-profile-panel]'),profileTabs=document.querySelectorAll('[data-profile-tab]');
function showProfileTab(name){profilePanels.forEach(panel=>panel.classList.toggle('active',panel.dataset.profilePanel===name));profileTabs.forEach(tab=>tab.classList.toggle('active',tab.dataset.profileTab===name));history.replaceState(null,'','#'+name)}
profileTabs.forEach(tab=>tab.addEventListener('click',()=>showProfileTab(tab.dataset.profileTab)));if(location.hash){const target=location.hash.slice(1);if(document.querySelector(`[data-profile-panel="${target}"]`))showProfileTab(target)}
function profileNotice(message,type){const notice=document.getElementById('profileNotice');notice.textContent=message;notice.className=`profile-notice show ${type}`;clearTimeout(window.profileNoticeTimer);window.profileNoticeTimer=setTimeout(()=>notice.classList.remove('show'),4200)}
document.querySelectorAll('[data-profile-form]').forEach(form=>form.addEventListener('submit',async event=>{event.preventDefault();if(!form.reportValidity())return;const button=form.querySelector('[type="submit"]'),original=button.innerHTML;button.disabled=true;button.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึก';try{const response=await fetch(`${BASE_URL}api/profile.php`,{method:'POST',body:new FormData(form)}),result=await response.json();if(!response.ok||result.status!=='success')throw Object.assign(new Error(result.message||'บันทึกข้อมูลไม่สำเร็จ'),{field:result.data?.field});profileNotice(result.message,'success');if(result.data?.full_name){document.querySelectorAll('.profile-name').forEach(item=>item.textContent=result.data.full_name)}if(result.data?.redirect)setTimeout(()=>location.href=result.data.redirect,700);else form.querySelectorAll('input[type="password"]').forEach(input=>input.value='')}catch(error){profileNotice(error.message,'error');if(error.field)form.querySelector(`[name="${error.field}"]`)?.focus()}finally{button.disabled=false;button.innerHTML=original}}));
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
