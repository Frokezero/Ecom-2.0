<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security_monitor.php';
$db = (new Database())->getConnection();
if (!$db) jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
if($_SERVER['REQUEST_METHOD']==='POST')protectApiMutation($db,'api.notifications',60,60);
if ($action === 'list') {
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 8)));
    $stmt = $db->prepare('SELECT id,type,title,body,link,is_read,created_at FROM notifications WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY created_at DESC LIMIT '.$limit);
    $stmt->execute([$userId]);
    $count = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0 AND (expires_at IS NULL OR expires_at>NOW())');
    $count->execute([$userId]);
    jsonResponse('success','โหลดการแจ้งเตือนแล้ว',['items'=>$stmt->fetchAll(),'unread'=>(int)$count->fetchColumn()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
requireCsrf();
if ($action === 'read') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
    $stmt->execute([$id,$userId]);
    jsonResponse('success','อ่านการแจ้งเตือนแล้ว');
}
if ($action === 'read_all') {
    $stmt = $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0');
    $stmt->execute([$userId]);
    jsonResponse('success','อ่านการแจ้งเตือนทั้งหมดแล้ว');
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
