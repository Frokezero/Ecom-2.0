<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$token = (string)($_GET['token'] ?? '');
$verified = false;
$expired = false;
$email = '';

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $db = (new Database())->getConnection();
    if ($db) {
        $stmt = $db->prepare('SELECT id,email,email_verification_expires_at FROM users WHERE email_verification_token_hash=? AND email_verified_at IS NULL LIMIT 1');
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch();
        if ($user) {
            $email = $user['email'];
            if (strtotime($user['email_verification_expires_at']) < time()) {
                $expired = true;
            } else {
                $update = $db->prepare('UPDATE users SET email_verified_at=NOW(),email_verification_token_hash=NULL,email_verification_expires_at=NULL,email_verification_sent_at=NULL WHERE id=? AND email_verified_at IS NULL');
                $update->execute([(int)$user['id']]);
                $verified = $update->rowCount() === 1;
            }
        }
    }
}

$page_title = $verified ? 'ยืนยันอีเมลสำเร็จ' : 'ลิงก์ยืนยันอีเมล';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
<div class="verification-page verification-result-page">
    <section class="verification-result" aria-labelledby="resultTitle">
        <div class="result-orbit" aria-hidden="true"></div>
        <?php if ($verified): ?>
            <div class="result-icon is-success"><i class="fa-solid fa-check"></i></div>
            <p class="eyebrow">ACCOUNT ACTIVATED</p>
            <h1 id="resultTitle">ยืนยันอีเมลสำเร็จแล้ว</h1>
            <p class="result-lead">บัญชี KitchenMart ของคุณพร้อมใช้งานแล้ว เลือกของดีเข้าครัวได้เลย</p>
            <?php if ($email): ?><div class="result-email"><i class="fa-regular fa-envelope"></i><?php echo e($email); ?></div><?php endif; ?>
            <a class="btn btn-primary result-action" href="<?php echo BASE_URL; ?>login.php"><i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบเพื่อเริ่มช้อป</a>
            <div class="result-perks"><span><i class="fa-solid fa-bag-shopping"></i> สั่งซื้อสะดวก</span><span><i class="fa-solid fa-truck-fast"></i> ติดตามสถานะได้</span><span><i class="fa-solid fa-star"></i> รีวิวสินค้าได้</span></div>
        <?php else: ?>
            <div class="result-icon is-warning"><i class="fa-solid <?php echo $expired ? 'fa-clock-rotate-left' : 'fa-link-slash'; ?>"></i></div>
            <p class="eyebrow">VERIFICATION LINK</p>
            <h1 id="resultTitle"><?php echo $expired ? 'ลิงก์ยืนยันหมดอายุแล้ว' : 'ลิงก์นี้ใช้งานไม่ได้'; ?></h1>
            <p class="result-lead"><?php echo $expired ? 'ลิงก์ยืนยันมีอายุ 30 นาที เพื่อความปลอดภัยของบัญชี ขอรับลิงก์ใหม่ได้ทันที' : 'ลิงก์อาจถูกใช้งานแล้ว หรือข้อมูลในลิงก์ไม่ครบ ลองขอลิงก์ยืนยันฉบับใหม่อีกครั้ง'; ?></p>
            <?php if ($email): ?><div class="result-email"><i class="fa-regular fa-envelope"></i><?php echo e($email); ?></div><?php endif; ?>
            <a class="btn btn-primary result-action" href="<?php echo BASE_URL; ?>check-email.php<?php echo $email ? '?email=' . rawurlencode($email) : ''; ?>"><i class="fa-regular fa-paper-plane"></i> ส่งลิงก์ยืนยันใหม่</a>
            <a class="result-back-link" href="<?php echo BASE_URL; ?>login.php"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
