<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security_monitor.php';
if (!isLoggedIn()) jsonResponse('error', 'กรุณาเข้าสู่ระบบ', [], 401);
requireCsrf();
if (($_POST['action'] ?? '') !== 'apply') jsonResponse('error', 'คำขอไม่ถูกต้อง', [], 400);
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) jsonResponse('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', [], 503);
protectApiMutation($db,'api.seller',10,300);
$userId = (int)$_SESSION['user_id'];
$account = $db->prepare('SELECT email_verified_at FROM users WHERE id=? LIMIT 1');
$account->execute([$userId]);
if (!$account->fetchColumn()) jsonResponse('error', 'กรุณายืนยันอีเมลก่อนสมัครเป็นผู้ขาย', [], 422);
$shop = trim((string)($_POST['shop_name'] ?? ''));
$category = (int)($_POST['category'] ?? 0);
$description = trim((string)($_POST['description'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$method = $_POST['payout'] ?? '';
$bankName = trim((string)($_POST['payout_bank'] ?? ''));
$bankOwner = trim((string)($_POST['bank_owner'] ?? ''));
$bankNumber = preg_replace('/\s+/', '', (string)($_POST['bank_number'] ?? ''));
$accountName = trim((string)($_POST['payout_name'] ?? ''));
$accountNumber = preg_replace('/\s+/', '', (string)($_POST['payout_number'] ?? ''));
$returnAddress = trim((string)($_POST['return_address'] ?? ''));
if (mb_strlen($shop) < 2 || $category < 1 || $phone === '' || !in_array($method, ['promptpay','bank','both'], true) || (($method==='bank'||$method==='both') && ($bankName===''||$bankOwner===''||mb_strlen($bankNumber)<6)) || $accountName === '' || mb_strlen($accountNumber) < 6 || $returnAddress === '' || !preg_match('/\b\d{5}\b/u', $returnAddress)) jsonResponse('error', 'กรุณากรอกข้อมูลร้าน ข้อมูลรับเงิน และที่อยู่พร้อมรหัสไปรษณีย์ให้ครบถ้วน', [], 422);
$categoryCheck = $db->prepare('SELECT id FROM categories WHERE id=?');$categoryCheck->execute([$category]);
if (!$categoryCheck->fetchColumn()) jsonResponse('error', 'ไม่พบหมวดสินค้าที่เลือก', [], 422);
try {
    $isBank=$method==='bank'||$method==='both';$isPrompt=$method==='promptpay'||$method==='both';
    $sql = "INSERT INTO seller_profiles (user_id,shop_name,primary_category_id,shop_description,phone,payout_method,promptpay_owner,promptpay_number,payout_bank_name,payout_account_name,payout_account_number,return_address,status,admin_note,submitted_at,reviewed_at,reviewed_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending',NULL,NOW(),NULL,NULL) ON DUPLICATE KEY UPDATE shop_name=VALUES(shop_name),primary_category_id=VALUES(primary_category_id),shop_description=VALUES(shop_description),phone=VALUES(phone),payout_method=VALUES(payout_method),promptpay_owner=VALUES(promptpay_owner),promptpay_number=VALUES(promptpay_number),payout_bank_name=VALUES(payout_bank_name),payout_account_name=VALUES(payout_account_name),payout_account_number=VALUES(payout_account_number),return_address=VALUES(return_address),status=IF(status='approved','approved','pending'),admin_note=NULL,submitted_at=IF(status='approved',submitted_at,NOW()),reviewed_at=IF(status='approved',reviewed_at,NULL),reviewed_by=IF(status='approved',reviewed_by,NULL)";
    $save = $db->prepare($sql);$save->execute([$userId,$shop,$category,$description,$phone,$method,$isPrompt?$accountName:null,$isPrompt?$accountNumber:null,$isBank?$bankName:null,$isBank?$bankOwner:$accountName,$isBank?$bankNumber:$accountNumber,$returnAddress]);
    createRoleNotification($db, 'admin', 'seller', 'มีคำขอเปิดร้านใหม่', 'ร้าน '.$shop.' รอตรวจสอบข้อมูล', BASE_URL.'admin/sellers.php');
    jsonResponse('success', 'ส่งคำขอเปิดร้านแล้ว ทีมงานจะตรวจสอบและแจ้งผลทางอีเมล', ['status'=>'pending']);
} catch (Throwable $e) { jsonResponse('error', 'ไม่สามารถบันทึกคำขอได้', [], 500); }
