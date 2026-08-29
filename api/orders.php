<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
if(!isLoggedIn())jsonResponse('error','กรุณาเข้าสู่ระบบ',[],401);requireCsrf();
if(($_POST['action']??'')!=='cancel')jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
$id=filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT);if(!$id)jsonResponse('error','รหัสคำสั่งซื้อไม่ถูกต้อง',[],422);
$db=(new Database())->getConnection();if(!$db)jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
try{cancelOrderAndRestock($db,(int)$id,(int)$_SESSION['user_id']);jsonResponse('success','ยกเลิกคำสั่งซื้อและคืนสต็อกแล้ว');}catch(RuntimeException $e){jsonResponse('error',$e->getMessage(),[],409);}catch(Throwable $e){jsonResponse('error','ไม่สามารถยกเลิกคำสั่งซื้อได้',[],500);}
