<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/functions.php';require_once __DIR__.'/../includes/security_monitor.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);if(!isLoggedIn())jsonResponse('error','กรุณาเข้าสู่ระบบก่อน',[],401);requireCsrf();
$db=(new Database())->getConnection();if(!$db)jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);protectApiMutation($db,'api.wishlist',60,60);
$id=(int)($_POST['product_id']??0);$action=$_POST['action']??'';$p=$db->prepare("SELECT id FROM products WHERE id=? AND approval_status='approved'");$p->execute([$id]);if(!$p->fetch())jsonResponse('error','ไม่พบสินค้า',[],404);
if($action==='add'){$db->prepare('INSERT IGNORE INTO wishlists(user_id,product_id) VALUES(?,?)')->execute([(int)$_SESSION['user_id'],$id]);jsonResponse('success','เพิ่มในรายการโปรดแล้ว',['saved'=>true]);}
if($action==='remove'){$db->prepare('DELETE FROM wishlists WHERE user_id=? AND product_id=?')->execute([(int)$_SESSION['user_id'],$id]);jsonResponse('success','นำออกจากรายการโปรดแล้ว',['saved'=>false]);}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
