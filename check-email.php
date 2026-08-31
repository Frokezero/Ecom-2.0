<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'ตรวจสอบอีเมล';
$email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL) ? strtolower((string)$_GET['email']) : '';
$deliveryFailed = ($_GET['delivery'] ?? '') === 'failed';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
<div class="verification-page">
    <section class="verification-shell" aria-labelledby="verificationTitle">
        <aside class="verification-aside">
            <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo">
                <span class="brand-mark"><i class="fa-solid fa-kitchen-set"></i></span>
                <span><strong>KitchenMart</strong><small>ของดีสำหรับทุกครัว</small></span>
            </a>
            <div class="mail-illustration" aria-hidden="true"><i class="fa-regular fa-envelope"></i><span class="mail-illustration-seal"><i class="fa-solid fa-check"></i></span></div>
            <p class="eyebrow">ONE LAST STEP</p>
            <h1>ยืนยันอีเมล<br>ก่อนเริ่มช้อป</h1>
            <p>เราใช้ขั้นตอนนี้เพื่อปกป้องบัญชีและให้ทุกคำสั่งซื้อเป็นของคุณจริง ๆ</p>
            <ol class="verification-steps"><li class="is-done"><span>1</span> เปิดอีเมลจาก KitchenMart</li><li><span>2</span> กดปุ่มยืนยันบัญชี</li><li><span>3</span> กลับมาเลือกสินค้าที่ชอบ</li></ol>
        </aside>
        <div class="verification-content">
            <div class="verification-copy">
                <div class="status-badge <?php echo $deliveryFailed ? 'is-warning' : ''; ?>"><i class="fa-solid <?php echo $deliveryFailed ? 'fa-triangle-exclamation' : 'fa-paper-plane'; ?>"></i> <?php echo $deliveryFailed ? 'ส่งอีเมลไม่สำเร็จ' : 'ส่งอีเมลแล้ว'; ?></div>
                <h2 id="verificationTitle"><?php echo $deliveryFailed ? 'ส่งลิงก์ใหม่ให้คุณได้' : 'ตรวจสอบกล่องอีเมลของคุณ'; ?></h2>
                <?php if ($deliveryFailed): ?>
                    <p class="verification-lead">บัญชีถูกสร้างเรียบร้อยแล้ว แต่การส่งลิงก์ครั้งก่อนมีปัญหา กดส่งอีกครั้งเพื่อรับลิงก์ใหม่ได้ทันที</p>
                <?php else: ?>
                    <p class="verification-lead">เราได้ส่งลิงก์ยืนยันที่ใช้ได้ภายใน <strong>30 นาที</strong> ไปยังอีเมลนี้แล้ว</p>
                <?php endif; ?>
                <?php if ($email): ?><div class="recipient-card"><span>ส่งถึง</span><strong><i class="fa-regular fa-envelope"></i> <?php echo e($email); ?></strong></div><?php endif; ?>
            </div>

            <div class="mail-inbox-note"><i class="fa-solid fa-circle-info"></i><span>ไม่พบอีเมล? ลองตรวจสอบโฟลเดอร์ Spam หรือ Junk Mail ก่อน แล้วค่อยส่งลิงก์ใหม่</span></div>

            <form id="resendForm" class="resend-card" novalidate>
                <label for="resendEmail">อีเมลที่ใช้สมัครสมาชิก</label>
                <div class="resend-controls"><div class="resend-input"><i class="fa-regular fa-envelope"></i><input id="resendEmail" type="email" name="email" required value="<?php echo e($email); ?>" autocomplete="email" placeholder="name@example.com"></div><button class="btn btn-primary" id="resendButton"><i class="fa-solid fa-rotate-right"></i><span>ส่งลิงก์ใหม่</span></button></div>
                <input type="hidden" name="action" value="resend_verification"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                <p class="resend-result" id="resendResult" aria-live="polite"></p>
            </form>

            <div class="verification-footer-links"><a href="<?php echo BASE_URL; ?>login.php"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a><span>ต้องการเปลี่ยนอีเมล? <a href="<?php echo BASE_URL; ?>register.php">สมัครใหม่</a></span></div>
        </div>
    </section>
</div>
<script nonce="<?php echo e(cspNonce()); ?>">
document.getElementById('resendForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget, button = document.getElementById('resendButton'), resultBox = document.getElementById('resendResult');
    if (!form.reportValidity()) return;
    button.disabled = true; button.querySelector('span').textContent = 'กำลังส่ง...'; resultBox.className = 'resend-result'; resultBox.textContent = '';
    try {
        const response = await fetch(`${BASE_URL}api/auth.php`, {method: 'POST', body: new FormData(form)});
        const result = await response.json();
        resultBox.textContent = result.message;
        resultBox.classList.add(response.ok && result.status === 'success' ? 'is-success' : 'is-error');
    } catch (error) {
        resultBox.textContent = 'เชื่อมต่อระบบส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'; resultBox.classList.add('is-error');
    } finally {
        button.disabled = false; button.querySelector('span').textContent = 'ส่งลิงก์ใหม่';
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
