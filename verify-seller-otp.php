<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) { header('Location: '.BASE_URL.(isSeller()?'seller-dashboard.php':'index.php')); exit; }
$hasChallenge=(int)($_SESSION['seller_otp_user_id']??0)>0;
$page_title='ยืนยัน OTP ผู้ขาย';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/seller-otp.css">
<main class="verification-page" id="main-content"><section class="verification-result seller-otp-card">
 <div class="result-icon <?php echo $hasChallenge?'is-success':'is-warning'; ?>"><i class="fa-solid fa-key"></i></div>
 <p class="eyebrow">SELLER VERIFICATION</p><h1>ยืนยันอีเมลด้วย OTP</h1>
 <?php if($hasChallenge):?><p class="result-lead">กรอกรหัสตัวเลข 6 หลักที่ส่งไปยังอีเมล รหัสมีอายุ 10 นาทีและใช้ได้ครั้งเดียว</p>
 <div class="auth-error" id="otpError"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
 <form id="sellerOtpForm" class="auth-form"><input type="hidden" name="action" value="verify_seller_otp"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
  <div class="auth-field"><label for="otpCode">รหัส OTP</label><div class="auth-input"><i class="fa-solid fa-shield-halved"></i><input id="otpCode" class="otp-input" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus></div></div>
  <button class="btn btn-primary auth-submit" id="verifyOtpBtn" type="submit">ยืนยันและเข้าสู่ระบบ</button>
 </form><button class="otp-resend" id="resendOtpBtn" type="button">ส่ง OTP ใหม่</button><p class="resend-result" id="resendResult"></p>
 <?php else:?><p class="result-lead">ไม่พบคำขอยืนยัน กรุณากลับไปสมัครบัญชีผู้ขายใหม่</p><a class="btn btn-primary result-action" href="<?php echo BASE_URL; ?>seller-register.php">สมัครบัญชีผู้ขาย</a><?php endif;?>
</section></main>
<?php if($hasChallenge):?><script>
const otpForm=document.getElementById('sellerOtpForm'),otpError=document.getElementById('otpError');
otpForm.addEventListener('submit',async e=>{e.preventDefault();const b=document.getElementById('verifyOtpBtn');b.disabled=true;otpError.classList.remove('show');try{const r=await fetch(`${BASE_URL}api/auth.php`,{method:'POST',body:new FormData(otpForm)}),j=await r.json();if(!r.ok||j.status!=='success')throw new Error(j.message);location.href=j.data.redirect}catch(x){otpError.querySelector('span').textContent=x.message;otpError.classList.add('show');b.disabled=false}});
document.getElementById('resendOtpBtn').addEventListener('click',async e=>{const b=e.currentTarget,out=document.getElementById('resendResult'),d=new FormData();d.set('action','resend_seller_otp');d.set('csrf_token','<?php echo e(getCsrfToken()); ?>');b.disabled=true;try{const r=await fetch(`${BASE_URL}api/auth.php`,{method:'POST',body:d}),j=await r.json();out.textContent=j.message;out.className='resend-result '+(r.ok&&j.status==='success'?'is-success':'is-error')}catch(x){out.textContent='เชื่อมต่อระบบไม่ได้';out.className='resend-result is-error'}setTimeout(()=>b.disabled=false,60000)});
</script><?php endif;?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
