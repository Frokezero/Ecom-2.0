<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/mailer.php';
require_once __DIR__.'/../includes/security_monitor.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
requireCsrf();
$db=(new Database())->getConnection();if(!$db)jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
protectApiMutation($db,'api.password_reset',10,300);
$action=$_POST['action']??'';
if($action==='request'){
    $email=strtolower(trim((string)($_POST['email']??'')));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))jsonResponse('error','กรุณากรอกอีเมลให้ถูกต้อง',['field'=>'email'],422);
    $stmt=$db->prepare('SELECT id,email,full_name FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);$user=$stmt->fetch();
    if($user){
        $recent=$db->prepare('SELECT created_at FROM password_reset_tokens WHERE user_id=? ORDER BY id DESC LIMIT 1');$recent->execute([(int)$user['id']]);$last=$recent->fetchColumn();
        if(!$last||time()-strtotime($last)>=60){
            $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
            $db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$user['id']]);
            $db->prepare('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(?,?,?)')->execute([(int)$user['id'],$hash,date('Y-m-d H:i:s',time()+1800)]);
            try{sendPasswordResetEmail($user['email'],$user['full_name'],$token);}catch(Throwable $e){}
        }
    }
    jsonResponse('success','หากอีเมลนี้มีบัญชีอยู่ ระบบจะส่งลิงก์ตั้งรหัสผ่านใหม่ให้');
}
if($action==='reset'){
    $token=(string)($_POST['token']??'');$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirm']??'');
    if(!preg_match('/^[a-f0-9]{64}$/',$token))jsonResponse('error','ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุ',[],422);
    if(strlen($password)<10||strlen($password)>72||!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/\d/',$password)||preg_match('/\s/',$password))jsonResponse('error','รหัสผ่านต้องมี 10–72 ตัว พร้อมตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข',['field'=>'password'],422);
    if(!hash_equals($password,$confirm))jsonResponse('error','รหัสผ่านทั้งสองช่องไม่ตรงกัน',['field'=>'password_confirm'],422);
    try{$db->beginTransaction();$stmt=$db->prepare('SELECT id,user_id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1 FOR UPDATE');$stmt->execute([hash('sha256',$token)]);$row=$stmt->fetch();if(!$row)throw new RuntimeException('ลิงก์ตั้งรหัสผ่านไม่ถูกต้อง ถูกใช้แล้ว หรือหมดอายุ');$db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),(int)$row['user_id']]);$db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$row['user_id']]);$db->commit();jsonResponse('success','ตั้งรหัสผ่านใหม่เรียบร้อย',['redirect'=>BASE_URL.'login.php']);}catch(RuntimeException $e){if($db->inTransaction())$db->rollBack();jsonResponse('error',$e->getMessage(),[],422);}catch(Throwable $e){if($db->inTransaction())$db->rollBack();jsonResponse('error','ไม่สามารถตั้งรหัสผ่านใหม่ได้',[],500);}
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
