<?php
require_once __DIR__.'/includes/functions.php';
if(isLoggedIn()){header('Location: '.BASE_URL.(isAdmin()?'admin/index.php':'index.php'));exit;}
$page_title='เข้าสู่ระบบ';
require_once __DIR__.'/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
<div class="auth-page"><section class="auth-shell">
    <aside class="auth-intro">
        <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo"><span class="brand-mark"><i class="fa-solid fa-kitchen-set"></i></span><span><strong>KitchenMart</strong><small>ของดีสำหรับทุกครัว</small></span></a>
        <p class="eyebrow">WELCOME BACK</p><h1>กลับมาจัดการทุกเรื่องในครัวของคุณ</h1><p>เข้าสู่ระบบเพื่อสั่งซื้อสินค้า ติดตามสถานะ และดูประวัติคำสั่งซื้อได้ในที่เดียว</p>
        <ul class="auth-benefits"><li><i class="fa-solid fa-clock-rotate-left"></i> ดูประวัติและรายละเอียดคำสั่งซื้อ</li><li><i class="fa-solid fa-truck-fast"></i> ติดตามสถานะการจัดส่ง</li><li><i class="fa-solid fa-star"></i> รีวิวสินค้าที่ซื้อแล้ว</li></ul>
        <div class="auth-security-note"><i class="fa-solid fa-lock"></i> ข้อมูลเข้าสู่ระบบถูกส่งผ่านการตรวจสอบ CSRF และรหัสผ่านถูกจัดเก็บแบบเข้ารหัส</div>
    </aside>
    <div class="auth-card">
        <header class="auth-heading"><p class="eyebrow">MEMBER SIGN IN</p><h2>เข้าสู่ระบบ</h2><p>กรอกชื่อผู้ใช้หรืออีเมลที่ลงทะเบียนไว้</p></header>
        <div class="auth-error" id="authError" role="alert" aria-live="polite"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
        <form class="auth-form" id="loginForm" novalidate>
            <div class="auth-field"><label for="loginIdentity">ชื่อผู้ใช้หรืออีเมล</label><div class="auth-input"><i class="fa-regular fa-user"></i><input id="loginIdentity" name="username_email" required autocomplete="username" autofocus placeholder="เช่น somchai99 หรือ email@example.com"></div></div>
            <div class="auth-field"><label for="loginPassword">รหัสผ่าน</label><div class="auth-input"><i class="fa-solid fa-key"></i><input type="password" id="loginPassword" name="password" required minlength="8" autocomplete="current-password" placeholder="กรอกรหัสผ่าน"><button class="password-toggle" type="button" data-password-toggle="loginPassword" aria-label="แสดงรหัสผ่าน"><i class="fa-regular fa-eye"></i></button></div></div>
            <input type="hidden" name="action" value="login"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
            <button type="submit" id="loginBtn" class="btn btn-primary auth-submit"><span>เข้าสู่ระบบ</span><i class="fa-solid fa-arrow-right"></i></button>
        </form>
        <p class="auth-switch">ยังไม่มีบัญชี? <a href="<?php echo BASE_URL; ?>register.php">สมัครสมาชิกฟรี</a></p>
        <details class="demo-access"><summary>ใช้บัญชีทดลองระบบ</summary><div class="demo-buttons"><button type="button" onclick="fillDemo('admin')"><i class="fa-solid fa-user-shield"></i> ทดลองเป็น Admin</button><button type="button" onclick="fillDemo('customer')"><i class="fa-solid fa-user"></i> ทดลองเป็นลูกค้า</button></div></details>
    </div>
</section></div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(button=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.passwordToggle),show=input.type==='password';input.type=show?'text':'password';button.innerHTML=`<i class="fa-regular fa-eye${show?'-slash':''}"></i>`;button.setAttribute('aria-label',show?'ซ่อนรหัสผ่าน':'แสดงรหัสผ่าน')}));
function fillDemo(role){document.getElementById('loginIdentity').value=role==='admin'?'admin@kitchenmart.local':'customer@kitchenmart.local';document.getElementById('loginPassword').value='password123';document.getElementById('loginIdentity').focus()}
function showAuthError(message,field){const box=document.getElementById('authError');box.querySelector('span').textContent=message;box.classList.add('show');document.querySelectorAll('.auth-field').forEach(el=>el.classList.remove('invalid'));if(field){const input=document.querySelector(`[name="${field}"]`);if(input){input.closest('.auth-field')?.classList.add('invalid');input.focus()}}}
document.getElementById('loginForm').addEventListener('submit',async event=>{event.preventDefault();const form=event.currentTarget,button=document.getElementById('loginBtn'),errorBox=document.getElementById('authError');errorBox.classList.remove('show');if(!form.reportValidity())return;button.disabled=true;button.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> กำลังตรวจสอบ';try{const response=await fetch(`${BASE_URL}api/auth.php`,{method:'POST',body:new FormData(form)});const result=await response.json();if(!response.ok||result.status!=='success')throw Object.assign(new Error(result.message||'ไม่สามารถเข้าสู่ระบบได้'),{field:result.data?.field,retryAfter:result.data?.retry_after});showToast(result.message,'success');setTimeout(()=>location.href=result.data.redirect,350)}catch(error){showAuthError(error.message,error.field);button.disabled=false;button.innerHTML='<span>เข้าสู่ระบบ</span><i class="fa-solid fa-arrow-right"></i>'}});
</script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
