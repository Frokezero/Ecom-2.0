<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';

if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
if(!isLoggedIn())jsonResponse('error','กรุณาเข้าสู่ระบบก่อนเขียนรีวิว',[],401);
requireCsrf();
$db=(new Database())->getConnection();
if(!$db)jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
$action=$_POST['action']??'';
$productId=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);
if(!$productId||$productId<1)jsonResponse('error','รหัสสินค้าไม่ถูกต้อง',[],422);
$product=$db->prepare('SELECT id FROM products WHERE id=?');$product->execute([$productId]);
if(!$product->fetch())jsonResponse('error','ไม่พบสินค้า',[],404);

if($action==='save'){
    $purchase=$db->prepare("SELECT 1 FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.user_id=? AND o.order_status<>'cancelled' LIMIT 1");
    $purchase->execute([(int)$productId,(int)$_SESSION['user_id']]);
    if(!$purchase->fetchColumn())jsonResponse('error','คุณต้องสั่งซื้อสินค้านี้ก่อนจึงจะเขียนรีวิวได้',[],403);
    $rating=filter_var($_POST['rating']??null,FILTER_VALIDATE_INT);
    $comment=trim((string)($_POST['comment']??''));
    if($rating===false||$rating<1||$rating>5)jsonResponse('error','กรุณาเลือกคะแนน 1–5 ดาว',[],422);
    $length=mb_strlen($comment);
    if($length<5||$length>1000)jsonResponse('error','ความคิดเห็นต้องมีความยาว 5–1,000 ตัวอักษร',[],422);
    $stmt=$db->prepare('INSERT INTO product_reviews(product_id,user_id,rating,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([(int)$productId,(int)$_SESSION['user_id'],(int)$rating,$comment]);
    jsonResponse('success','บันทึกรีวิวเรียบร้อยแล้ว');
}
if($action==='delete'){
    $stmt=$db->prepare('DELETE FROM product_reviews WHERE product_id=? AND user_id=?');
    $stmt->execute([(int)$productId,(int)$_SESSION['user_id']]);
    if($stmt->rowCount()!==1)jsonResponse('error','ไม่พบรีวิวของคุณ',[],404);
    jsonResponse('success','ลบรีวิวแล้ว');
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
