<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
requireCsrf();
$action=$_POST['action'] ?? '';

if ($action === 'logout') {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }
    session_destroy();
    jsonResponse('success','ออกจากระบบแล้ว',['redirect'=>BASE_URL.'index.php']);
}

$db=(new Database())->getConnection();
if (!$db) jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
if ($action === 'resend_verification') {
    $email=strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) jsonResponse('error','กรุณากรอกอีเมลให้ถูกต้อง',['field'=>'email'],422);
    $stmt=$db->prepare('SELECT id,email,full_name,email_verified_at,email_verification_sent_at FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);$user=$stmt->fetch();
    if($user && !$user['email_verified_at']){
        if($user['email_verification_sent_at'] && time()-strtotime($user['email_verification_sent_at'])<60)jsonResponse('error','เพิ่งส่งอีเมลไป กรุณารอประมาณ 1 นาทีก่อนส่งซ้ำ',['retry_after'=>60-(time()-strtotime($user['email_verification_sent_at']))],429);
        try{sendVerificationEmail($db,(int)$user['id'],$user['email'],$user['full_name']);}
        catch(Throwable $e){$db->prepare('UPDATE users SET email_verification_sent_at=NULL WHERE id=?')->execute([(int)$user['id']]);jsonResponse('error','ระบบส่งอีเมลยังไม่พร้อม กรุณาตรวจการตั้งค่า SMTP แล้วลองใหม่',[],503);}
    }
    jsonResponse('success','หากอีเมลนี้รอยืนยัน ระบบได้ส่งลิงก์ฉบับใหม่แล้ว');
}
if ($action === 'register') {
    $username=trim($_POST['username'] ?? ''); $email=strtolower(trim($_POST['email'] ?? '')); $password=$_POST['password'] ?? '';
    $passwordConfirm=$_POST['password_confirm'] ?? '';
    $fullName=trim($_POST['full_name'] ?? ''); $phone=trim($_POST['phone'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/',$username)) jsonResponse('error','ชื่อผู้ใช้ต้องมี 3–50 ตัว และใช้ตัวอักษรอังกฤษ ตัวเลข จุด หรือขีดล่างเท่านั้น',['field'=>'username'],422);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) jsonResponse('error','กรุณากรอกอีเมลให้ถูกต้อง',['field'=>'email'],422);
    if (mb_strlen($fullName)<2 || mb_strlen($fullName)>100) jsonResponse('error','ชื่อ-นามสกุลต้องมี 2–100 ตัวอักษร',['field'=>'full_name'],422);
    if ($phone!=='' && !preg_match('/^[0-9+][0-9 -]{8,18}$/',$phone)) jsonResponse('error','กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง',['field'=>'phone'],422);
    if (strlen($password)<8 || !preg_match('/[A-Za-z]/',$password) || !preg_match('/\d/',$password)) jsonResponse('error','รหัสผ่านต้องมีอย่างน้อย 8 ตัว และประกอบด้วยตัวอักษรกับตัวเลข',['field'=>'password'],422);
    if (!hash_equals($password,$passwordConfirm)) jsonResponse('error','รหัสผ่านทั้งสองช่องไม่ตรงกัน',['field'=>'password_confirm'],422);
    if (($_POST['accept_terms'] ?? '')!=='1') jsonResponse('error','กรุณายอมรับเงื่อนไขการใช้งาน',['field'=>'accept_terms'],422);
    $stmt=$db->prepare('SELECT id FROM users WHERE username=? OR email=?'); $stmt->execute([$username,$email]);
    if ($stmt->fetch()) jsonResponse('error','ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว กรุณาเลือกข้อมูลอื่น',[],409);
    $stmt=$db->prepare("INSERT INTO users (username,email,password_hash,full_name,phone,address,role,email_verified_at) VALUES (?,?,?,?,?,'','customer',NULL)");
    $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),$fullName,$phone]);
    $userId=(int)$db->lastInsertId();$delivery='sent';
    try{sendVerificationEmail($db,$userId,$email,$fullName);}
    catch(Throwable $e){$delivery='failed';$db->prepare('UPDATE users SET email_verification_sent_at=NULL WHERE id=?')->execute([$userId]);}
    $redirect=BASE_URL.'check-email.php?email='.rawurlencode($email).($delivery==='failed'?'&delivery=failed':'');
    jsonResponse('success',$delivery==='sent'?'สมัครสมาชิกแล้ว กรุณาตรวจอีเมลเพื่อยืนยันบัญชี':'สร้างบัญชีแล้ว แต่ยังส่งอีเมลไม่ได้ กรุณาตั้งค่า SMTP และกดส่งซ้ำ',['redirect'=>$redirect,'delivery'=>$delivery]);
}
if ($action === 'login') {
    $identity=trim($_POST['username_email'] ?? ''); $password=$_POST['password'] ?? '';
    $attempts=$_SESSION['login_attempts'] ?? ['count'=>0,'last'=>0];
    if((int)$attempts['count']>=5 && time()-(int)$attempts['last']<60)jsonResponse('error','มีการเข้าสู่ระบบผิดหลายครั้ง กรุณารอ 60 วินาทีแล้วลองใหม่',['retry_after'=>60-(time()-(int)$attempts['last'])],429);
    if(time()-(int)$attempts['last']>=60)$attempts=['count'=>0,'last'=>0];
    if ($identity==='' || $password==='') jsonResponse('error','กรุณากรอกชื่อผู้ใช้หรืออีเมล และรหัสผ่านให้ครบ',['field'=>$identity===''?'username_email':'password'],422);
    $stmt=$db->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1'); $stmt->execute([$identity,$identity]); $user=$stmt->fetch();
    if (!$user || !password_verify($password,$user['password_hash'])) {$_SESSION['login_attempts']=['count'=>(int)$attempts['count']+1,'last'=>time()];jsonResponse('error','ชื่อผู้ใช้ อีเมล หรือรหัสผ่านไม่ถูกต้อง',[],401);}
    $requestHost=$_SERVER['HTTP_HOST'] ?? '';
    $isLocalRequest=(bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i',$requestHost);
    if($user['role']==='admin' && !$isLocalRequest && appConfig('ALLOW_PUBLIC_ADMIN','0')!=='1')jsonResponse('error','ปิดการเข้าสู่ระบบผู้ดูแลผ่านลิงก์สาธารณะเพื่อความปลอดภัย กรุณาเข้าสู่ระบบจากเครื่องเซิร์ฟเวอร์',[],403);
    if (!$user['email_verified_at']) jsonResponse('error','กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ',['field'=>'username_email','email'=>$user['email'],'verification_required'=>true],403);
    unset($_SESSION['login_attempts']);
    session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['username']=$user['username'];
    $_SESSION['full_name']=$user['full_name']; $_SESSION['email']=$user['email']; $_SESSION['user_role']=$user['role'];
    $redirect=BASE_URL.($user['role']==='admin'?'admin/index.php':'index.php');
    $requested=$_SESSION['redirect_url'] ?? '';unset($_SESSION['redirect_url']);
    if(is_string($requested)&&str_starts_with($requested,'/')&&!str_starts_with($requested,'//')&&($user['role']==='admin'||!str_contains($requested,'/admin/')))$redirect=$requested;
    jsonResponse('success','เข้าสู่ระบบสำเร็จ',['redirect'=>$redirect]);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
