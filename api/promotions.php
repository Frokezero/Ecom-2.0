<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security_monitor.php';
$db=(new Database())->getConnection(); if(!$db) jsonResponse('error','เชื่อมต่อฐานข้อมูลไม่ได้',[],503);
$action=$_REQUEST['action']??'banners';
if($action==='banners'){
    $placement=$_GET['placement']??'floating'; $category=(int)($_GET['category_id']??0);
    jsonResponse('success','โหลดโปรโมชั่นสำเร็จ',['banners'=>activePromotionalBanners($db,$placement,$category)]);
}
if(!isLoggedIn()) jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);
if($action==='claim'){
    if($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405); requireCsrf();
    protectApiMutation($db,'api.promotions.claim',30,60);
    $couponId=(int)($_POST['coupon_id']??0); $stmt=$db->prepare('SELECT id FROM coupons WHERE id=? AND is_active=1 AND starts_at<=NOW() AND ends_at>=NOW()');$stmt->execute([$couponId]);
    if(!$stmt->fetchColumn()) jsonResponse('error','คูปองนี้ไม่พร้อมใช้งาน',[],422);
    try{$ins=$db->prepare('INSERT INTO user_coupons(coupon_id,user_id) VALUES(?,?)');$ins->execute([$couponId,(int)$_SESSION['user_id']]);}catch(PDOException $e){if((int)$e->errorInfo[1]===1062)jsonResponse('success','คุณรับคูปองนี้แล้ว');throw $e;}
    jsonResponse('success','รับคูปองเรียบร้อยแล้ว');
}
if($action==='claim_code'){
    if($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405); requireCsrf();
    $code=strtoupper(trim((string)($_POST['code']??'')));$stmt=$db->prepare('SELECT id FROM coupons WHERE code=? AND is_active=1 AND starts_at<=NOW() AND ends_at>=NOW()');$stmt->execute([$code]);$couponId=(int)$stmt->fetchColumn();
    if($couponId<1)jsonResponse('error','ไม่พบโค้ดคูปองหรือคูปองหมดอายุแล้ว',[],422);
    try{$ins=$db->prepare('INSERT INTO user_coupons(coupon_id,user_id) VALUES(?,?)');$ins->execute([$couponId,(int)$_SESSION['user_id']]);}catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1062)jsonResponse('success','คุณรับคูปองนี้แล้ว',['coupon_id'=>$couponId]);throw $e;}
    jsonResponse('success','รับคูปองเข้าบัญชีแล้ว',['coupon_id'=>$couponId]);
}
if($action==='validate'){
    $items=$_SESSION['cart']??[];$subtotal=0;foreach($items as $item)$subtotal+=(float)$item['price']*(int)$item['quantity'];
    $result=calculateCouponDiscount($db,(string)($_POST['code']??$_GET['code']??''),(int)$_SESSION['user_id'],$items,$subtotal);
    if($result['error']!=='')jsonResponse('error',$result['error'],[],422);
    $_SESSION['coupon_code']=$result['coupon']['code']; jsonResponse('success','ใช้คูปองได้',['discount'=>$result['discount'],'code'=>$result['coupon']['code'],'title'=>$result['coupon']['title'],'subtotal'=>$subtotal,'total'=>max(0,$subtotal-$result['discount'])]);
}
if($action==='clear'){if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);requireCsrf();unset($_SESSION['coupon_code']);jsonResponse('success','ยกเลิกคูปองแล้ว');}
if($action==='mine'){$stmt=$db->prepare('SELECT c.id,c.code,c.title,c.description,c.discount_type,c.discount_value,c.max_discount,c.min_order_amount,c.per_user_limit,c.ends_at,uc.used_count FROM user_coupons uc JOIN coupons c ON c.id=uc.coupon_id WHERE uc.user_id=? AND c.is_active=1 AND c.starts_at<=NOW() AND c.ends_at>=NOW() AND uc.used_count<c.per_user_limit ORDER BY c.ends_at');$stmt->execute([(int)$_SESSION['user_id']]);jsonResponse('success','โหลดคูปองสำเร็จ',['coupons'=>$stmt->fetchAll(),'selected'=>$_SESSION['coupon_code']??'']);}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
