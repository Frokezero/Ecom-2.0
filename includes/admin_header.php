<?php
require_once __DIR__ . '/functions.php';
$admin_page = basename($_SERVER['PHP_SELF']);
$admin_title = $page_title ?? 'ระบบหลังบ้าน';
function adminStatusLabel(string $status): string {
    return [
        'pending'=>'รอดำเนินการ','processing'=>'กำลังแพ็ก','shipped'=>'จัดส่งแล้ว','completed'=>'สำเร็จ','cancelled'=>'ยกเลิก',
        'paid'=>'ชำระแล้ว','cod_pending'=>'รอเก็บเงิน COD'
    ][$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#173f32">
    <title><?php echo e($admin_title); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notifications.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notification-hover.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/promotions.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/accessibility.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?php echo BASE_URL; ?>admin/index.php"><span><i class="fa-solid fa-kitchen-set"></i></span><div><strong>KitchenMart</strong><small>ADMIN CONSOLE</small></div></a>
        <nav class="admin-nav" aria-label="เมนูผู้ดูแล">
            <small>ภาพรวม</small>
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="<?php echo $admin_page==='index.php'?'active':''; ?>"><i class="fa-solid fa-chart-pie"></i><span>แดชบอร์ด</span></a>
            <small>จัดการร้าน</small>
            <a href="<?php echo BASE_URL; ?>admin/products.php" class="<?php echo $admin_page==='products.php'?'active':''; ?>"><i class="fa-solid fa-boxes-stacked"></i><span>สินค้าและสต็อก</span></a>
            <a href="<?php echo BASE_URL; ?>admin/promotions.php" class="<?php echo $admin_page==='promotions.php'?'active':''; ?>"><i class="fa-solid fa-ticket"></i><span>โปรโมชั่นและคูปอง</span></a>
            <a href="<?php echo BASE_URL; ?>admin/orders.php" class="<?php echo in_array($admin_page,['orders.php','order-detail.php'],true)?'active':''; ?>"><i class="fa-solid fa-clipboard-list"></i><span>คำสั่งซื้อ</span></a>
            <a href="<?php echo BASE_URL; ?>admin/returns.php" class="<?php echo $admin_page==='returns.php'?'active':''; ?>"><i class="fa-solid fa-arrow-rotate-left"></i><span>คืนสินค้าและคืนเงิน</span></a>
            <a href="<?php echo BASE_URL; ?>admin/sellers.php" class="<?php echo $admin_page==='sellers.php'?'active':''; ?>"><i class="fa-solid fa-store"></i><span>คำขอผู้ขาย</span></a>
            <a href="<?php echo BASE_URL; ?>admin/seller-products.php" class="<?php echo $admin_page==='seller-products.php'?'active':''; ?>"><i class="fa-solid fa-box-open"></i><span>ตรวจสินค้าผู้ขาย</span></a>
            <a href="<?php echo BASE_URL; ?>admin/payouts.php" class="<?php echo $admin_page==='payouts.php'?'active':''; ?>"><i class="fa-solid fa-money-bill-transfer"></i><span>ถอนเงินผู้ขาย</span></a>
            <a href="<?php echo BASE_URL; ?>admin/email-logs.php" class="<?php echo $admin_page==='email-logs.php'?'active':''; ?>"><i class="fa-solid fa-envelope-circle-check"></i><span>ประวัติการส่งอีเมล</span></a>
        </nav>
        <div class="admin-sidebar-footer"><a href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> ดูหน้าร้าน</a><button type="button" onclick="adminLogout()"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</button></div>
    </aside>
    <div class="admin-sidebar-scrim" id="adminSidebarScrim"></div>
    <div class="admin-workspace">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="เปิดเมนู"><i class="fa-solid fa-bars"></i></button>
            <div><span>ระบบจัดการร้าน</span><small><?php echo date('d/m/Y'); ?></small></div>
            <div class="admin-user"><div class="notification-menu"><button type="button" class="notification-trigger" id="notificationTrigger" aria-label="การแจ้งเตือน"><i class="fa-regular fa-bell"></i><b id="notificationBadge" hidden>0</b></button><div class="notification-dropdown" id="notificationDropdown"><header><strong>การแจ้งเตือน</strong><a href="<?php echo BASE_URL; ?>notifications.php">ดูทั้งหมด</a></header><div class="notification-preview-list" id="notificationPreviewList"><p class="notification-preview-empty">กำลังโหลด...</p></div></div></div><span><strong><?php echo e($_SESSION['username'] ?? 'Admin'); ?></strong><small>ผู้ดูแลระบบ</small></span><i class="fa-solid fa-user-shield"></i></div>
        </header>
        <main class="admin-main">
<script>
async function adminLogout(){const body=new FormData();body.append('action','logout');body.append('csrf_token','<?php echo e(getCsrfToken()); ?>');const response=await fetch('<?php echo BASE_URL; ?>api/auth.php',{method:'POST',body});const result=await response.json();if(result.status==='success')location.href=result.data.redirect;}
</script>
