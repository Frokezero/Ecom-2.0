<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error', 'อนุญาตเฉพาะ POST', [], 405);
requireCsrf();
if (!isLoggedIn()) jsonResponse('error', 'กรุณาเข้าสู่ระบบก่อน', [], 401);

$db = (new Database())->getConnection();
if (!$db) jsonResponse('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', [], 503);

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT id,username,email,password_hash,full_name,email_verified_at,two_factor_enabled,role FROM users WHERE id=? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) jsonResponse('error', 'ไม่พบบัญชีผู้ใช้', [], 404);

$action = $_POST['action'] ?? '';
if($action==='update_two_factor'){$current=$_POST['current_password']??'';$enabled=($_POST['enabled']??'0')==='1';if(!password_verify($current,$user['password_hash']))jsonResponse('error','รหัสผ่านปัจจุบันไม่ถูกต้อง',['field'=>'current_password'],422);if($enabled&&!in_array($user['role'],['seller','admin'],true))jsonResponse('error','การยืนยันสองขั้นตอนเปิดใช้สำหรับผู้ขายและผู้ดูแลระบบ',[],403);$db->prepare('UPDATE users SET two_factor_enabled=? WHERE id=?')->execute([$enabled?1:0,$userId]);auditLog($db,$enabled?'security.2fa.enable':'security.2fa.disable','user',$userId);jsonResponse('success',$enabled?'เปิดการยืนยันสองขั้นตอนแล้ว':'ปิดการยืนยันสองขั้นตอนแล้ว');}

if ($action === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) jsonResponse('error', 'ชื่อ-นามสกุลต้องมี 2–100 ตัวอักษร', ['field' => 'full_name'], 422);
    if ($phone !== '' && !preg_match('/^[0-9+][0-9 -]{8,18}$/', $phone)) jsonResponse('error', 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง', ['field' => 'phone'], 422);
    if (mb_strlen($address) > 1000) jsonResponse('error', 'ที่อยู่ยาวเกิน 1,000 ตัวอักษร', ['field' => 'address'], 422);
    if ($address !== '' && !preg_match('/\b\d{5}\b/u', $address)) jsonResponse('error', 'กรุณาเลือกตำบลและรหัสไปรษณีย์ให้ครบถ้วน', ['field' => 'address'], 422);
    $update = $db->prepare('UPDATE users SET full_name=?,phone=?,address=? WHERE id=?');
    $update->execute([$fullName, $phone, $address, $userId]);
    $_SESSION['full_name'] = $fullName;
    jsonResponse('success', 'บันทึกข้อมูลโปรไฟล์แล้ว', ['full_name' => $fullName]);
}

if ($action === 'update_payment_preference') {
    $method = $_POST['preferred_payment_method'] ?? '';
    if (!in_array($method, ['promptpay', 'cod'], true)) jsonResponse('error', 'กรุณาเลือกวิธีชำระเงิน', ['field' => 'preferred_payment_method'], 422);
    $update = $db->prepare('UPDATE users SET preferred_payment_method=? WHERE id=?');
    $update->execute([$method, $userId]);
    jsonResponse('success', 'บันทึกวิธีชำระเงินที่ต้องการแล้ว');
}

if ($action === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    if (!password_verify($currentPassword, $user['password_hash'])) jsonResponse('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง', ['field' => 'current_password'], 422);
    if (strlen($password)<10 || strlen($password)>72 || !preg_match('/[A-Z]/',$password) || !preg_match('/[a-z]/',$password) || !preg_match('/\d/',$password) || preg_match('/\s|[\x00-\x1F\x7F]/',$password)) jsonResponse('error','รหัสผ่านต้องมี 10–72 ตัว พร้อมตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข โดยห้ามมีช่องว่าง',['field'=>'password'],422);
    if (!hash_equals($password, $passwordConfirm)) jsonResponse('error', 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน', ['field' => 'password_confirm'], 422);
    $update = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
    $update->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
    session_regenerate_id(true);
    jsonResponse('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
}

if ($action === 'update_email') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $currentPassword = $_POST['current_password'] ?? '';
    if (!password_verify($currentPassword, $user['password_hash'])) jsonResponse('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง', ['field' => 'current_password'], 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) jsonResponse('error', 'กรุณากรอกอีเมลให้ถูกต้อง', ['field' => 'email'], 422);
    if ($email === strtolower($user['email'])) jsonResponse('error', 'นี่คืออีเมลที่ใช้งานอยู่แล้ว', ['field' => 'email'], 422);
    $exists = $db->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
    $exists->execute([$email, $userId]);
    if ($exists->fetch()) jsonResponse('error', 'อีเมลนี้ถูกใช้งานแล้ว', ['field' => 'email'], 409);

    $update = $db->prepare('UPDATE users SET email=?,email_verified_at=NULL,email_verification_token_hash=NULL,email_verification_expires_at=NULL,email_verification_sent_at=NULL WHERE id=?');
    $update->execute([$email, $userId]);
    $delivery = 'sent';
    try {
        sendVerificationEmail($db, $userId, $email, $user['full_name']);
    } catch (Throwable $e) {
        $delivery = 'failed';
        $db->prepare('UPDATE users SET email_verification_sent_at=NULL WHERE id=?')->execute([$userId]);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    $redirect = BASE_URL . 'check-email.php?email=' . rawurlencode($email) . ($delivery === 'failed' ? '&delivery=failed' : '');
    jsonResponse('success', $delivery === 'sent' ? 'ส่งลิงก์ยืนยันไปยังอีเมลใหม่แล้ว' : 'เปลี่ยนอีเมลแล้ว แต่ยังส่งลิงก์ไม่สำเร็จ', ['redirect' => $redirect, 'delivery' => $delivery]);
}

jsonResponse('error', 'คำสั่งไม่ถูกต้อง', [], 400);
