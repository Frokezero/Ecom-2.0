<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

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
if ($action === 'register') {
    $username=trim($_POST['username'] ?? ''); $email=strtolower(trim($_POST['email'] ?? '')); $password=$_POST['password'] ?? '';
    $passwordConfirm=$_POST['password_confirm'] ?? '';
    $fullName=trim($_POST['full_name'] ?? ''); $phone=trim($_POST['phone'] ?? ''); $address=trim($_POST['address'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/',$username)) jsonResponse('error','ชื่อผู้ใช้ต้องมี 3–50 ตัว และใช้ตัวอักษรอังกฤษ ตัวเลข จุด หรือขีดล่างเท่านั้น',['field'=>'username'],422);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) jsonResponse('error','กรุณากรอกอีเมลให้ถูกต้อง',['field'=>'email'],422);
    if (mb_strlen($fullName)<2 || mb_strlen($fullName)>100) jsonResponse('error','ชื่อ-นามสกุลต้องมี 2–100 ตัวอักษร',['field'=>'full_name'],422);
    if ($phone!=='' && !preg_match('/^[0-9+][0-9 -]{8,18}$/',$phone)) jsonResponse('error','กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง',['field'=>'phone'],422);
    if (strlen($password)<8 || !preg_match('/[A-Za-z]/',$password) || !preg_match('/\d/',$password)) jsonResponse('error','รหัสผ่านต้องมีอย่างน้อย 8 ตัว และประกอบด้วยตัวอักษรกับตัวเลข',['field'=>'password'],422);
    if (!hash_equals($password,$passwordConfirm)) jsonResponse('error','รหัสผ่านทั้งสองช่องไม่ตรงกัน',['field'=>'password_confirm'],422);
    if (($_POST['accept_terms'] ?? '')!=='1') jsonResponse('error','กรุณายอมรับเงื่อนไขการใช้งาน',['field'=>'accept_terms'],422);
    $stmt=$db->prepare('SELECT id FROM users WHERE username=? OR email=?'); $stmt->execute([$username,$email]);
    if ($stmt->fetch()) jsonResponse('error','ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว กรุณาเลือกข้อมูลอื่น',[],409);
    $stmt=$db->prepare("INSERT INTO users (username,email,password_hash,full_name,phone,address,role) VALUES (?,?,?,?,?,?,'customer')");
    $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),$fullName,$phone,$address]);
    session_regenerate_id(true); $_SESSION['user_id']=(int)$db->lastInsertId(); $_SESSION['username']=$username;
    $_SESSION['full_name']=$fullName; $_SESSION['email']=$email; $_SESSION['user_role']='customer';
    jsonResponse('success','สมัครสมาชิกสำเร็จ',['redirect'=>BASE_URL.'index.php']);
}
if ($action === 'login') {
    $identity=trim($_POST['username_email'] ?? ''); $password=$_POST['password'] ?? '';
    $attempts=$_SESSION['login_attempts'] ?? ['count'=>0,'last'=>0];
    if((int)$attempts['count']>=5 && time()-(int)$attempts['last']<60)jsonResponse('error','มีการเข้าสู่ระบบผิดหลายครั้ง กรุณารอ 60 วินาทีแล้วลองใหม่',['retry_after'=>60-(time()-(int)$attempts['last'])],429);
    if(time()-(int)$attempts['last']>=60)$attempts=['count'=>0,'last'=>0];
    if ($identity==='' || $password==='') jsonResponse('error','กรุณากรอกชื่อผู้ใช้หรืออีเมล และรหัสผ่านให้ครบ',['field'=>$identity===''?'username_email':'password'],422);
    $stmt=$db->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1'); $stmt->execute([$identity,$identity]); $user=$stmt->fetch();
    if (!$user || !password_verify($password,$user['password_hash'])) {$_SESSION['login_attempts']=['count'=>(int)$attempts['count']+1,'last'=>time()];jsonResponse('error','ชื่อผู้ใช้ อีเมล หรือรหัสผ่านไม่ถูกต้อง',[],401);}
    unset($_SESSION['login_attempts']);
    session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['username']=$user['username'];
    $_SESSION['full_name']=$user['full_name']; $_SESSION['email']=$user['email']; $_SESSION['user_role']=$user['role'];
    $redirect=BASE_URL.($user['role']==='admin'?'admin/index.php':'index.php');
    $requested=$_SESSION['redirect_url'] ?? '';unset($_SESSION['redirect_url']);
    if(is_string($requested)&&str_starts_with($requested,'/')&&!str_starts_with($requested,'//')&&($user['role']==='admin'||!str_contains($requested,'/admin/')))$redirect=$requested;
    jsonResponse('success','เข้าสู่ระบบสำเร็จ',['redirect'=>$redirect]);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
