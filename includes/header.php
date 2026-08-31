<?php
require_once __DIR__ . '/functions.php';
$cart_items = array_values($_SESSION['cart'] ?? []);
$cart_count = array_sum(array_map(fn($item) => (int)($item['quantity'] ?? 0), $cart_items));
$cart_total = array_sum(array_map(fn($item) => (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0), $cart_items));
$cart_preview_items = array_slice($cart_items, 0, 3);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#173f32">
    <title><?php echo isset($page_title) ? e($page_title).' - '.APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/address-picker.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notifications.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notification-hover.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/promotions.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/coupons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/accessibility.css">
</head>
<body>
    <div class="announcement"><div class="container"><span><i class="fa-solid fa-truck-fast"></i> จัดส่งฟรีเมื่อสั่งซื้อครบ ฿1,000</span><span class="announcement-detail"><i class="fa-solid fa-shield-heart"></i> ชำระปลอดภัย · ตรวจสอบคำสั่งซื้อได้ทุกขั้นตอน</span></div></div>
    <header class="site-header">
        <div class="container header-main">
            <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo" aria-label="KitchenMart หน้าแรก">
                <span class="brand-mark"><i class="fa-solid fa-kitchen-set"></i></span>
                <span><strong>KitchenMart</strong><small>ของดีสำหรับทุกครัว</small></span>
            </a>
            <form action="<?php echo BASE_URL; ?>products.php" method="GET" class="search-box" role="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" aria-label="ค้นหาสินค้า" placeholder="ค้นหาสินค้า เช่น กระทะ มีด หม้อทอด..." value="<?php echo e($_GET['q'] ?? ''); ?>">
                <button type="submit">ค้นหา</button>
            </form>
            <div class="header-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="account-menu">
                        <a href="<?php echo BASE_URL; ?>profile.php" class="icon-link account-trigger" aria-label="โปรไฟล์ของฉัน"><i class="fa-regular fa-user"></i><span><?php echo e($_SESSION['username']); ?></span><i class="fa-solid fa-chevron-down account-chevron"></i></a>
                        <div class="account-dropdown">
                            <div class="account-dropdown-head"><span class="account-mini-avatar"><?php echo e(strtoupper(mb_substr(trim($_SESSION['full_name'] ?: $_SESSION['username']),0,1))); ?></span><span><strong><?php echo e($_SESSION['full_name'] ?: $_SESSION['username']); ?></strong><small><?php echo e($_SESSION['email'] ?? ''); ?></small></span></div>
                            <a href="<?php echo BASE_URL; ?>profile.php"><i class="fa-regular fa-user"></i> ข้อมูลส่วนตัว</a>
                            <a href="<?php echo BASE_URL; ?>profile.php#address"><i class="fa-solid fa-location-dot"></i> ที่อยู่จัดส่ง</a>
                            <a href="<?php echo BASE_URL; ?>address-book.php"><i class="fa-solid fa-address-book"></i> สมุดที่อยู่</a>
                            <a href="<?php echo BASE_URL; ?>profile.php#payment"><i class="fa-regular fa-credit-card"></i> วิธีชำระเงิน</a>
                            <a href="<?php echo BASE_URL; ?>profile.php#security"><i class="fa-solid fa-shield-halved"></i> ความปลอดภัย</a>
                            <a href="<?php echo BASE_URL; ?>wishlist.php"><i class="fa-regular fa-heart"></i> รายการโปรด</a>
                            <a href="<?php echo BASE_URL; ?>my-orders.php" class="account-orders-link"><i class="fa-solid fa-box"></i> คำสั่งซื้อของฉัน <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="<?php echo BASE_URL; ?>my-returns.php"><i class="fa-solid fa-arrow-rotate-left"></i> การคืนสินค้า</a>
                            <button type="button" onclick="secureLogout()"><i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ</button>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="icon-link"><i class="fa-regular fa-user"></i><span>เข้าสู่ระบบ</span></a>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?><div class="notification-menu"><button type="button" class="notification-trigger" id="notificationTrigger" aria-label="การแจ้งเตือน"><i class="fa-regular fa-bell"></i><b id="notificationBadge" hidden>0</b></button><div class="notification-dropdown" id="notificationDropdown"><header><strong>การแจ้งเตือน</strong><a href="<?php echo BASE_URL; ?>notifications.php">ดูทั้งหมด</a></header><div class="notification-preview-list" id="notificationPreviewList"><p class="notification-preview-empty">กำลังโหลด...</p></div></div></div><?php endif; ?>
                <div class="cart-menu">
                    <a href="<?php echo BASE_URL; ?>cart.php" class="cart-link" aria-label="ตะกร้า มี <?php echo $cart_count; ?> ชิ้น"><i class="fa-solid fa-basket-shopping"></i><span>ตะกร้า</span><b id="cartCountBadge"><?php echo $cart_count; ?></b></a>
                    <div class="cart-dropdown">
                        <header><strong>ตะกร้าสินค้าของคุณ</strong><span><?php echo $cart_count; ?> ชิ้น</span></header>
                        <?php if ($cart_preview_items): ?>
                            <div class="cart-preview-list"><?php foreach ($cart_preview_items as $item): ?><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$item['id']; ?>" class="cart-preview-item"><img src="<?php echo e(productImageUrl($item['image_url'])); ?>" alt="<?php echo e($item['name']); ?>"><span><strong><?php echo e($item['name']); ?></strong><small><?php echo (int)$item['quantity']; ?> ชิ้น · <?php echo formatCurrency($item['price']); ?></small></span><b><?php echo formatCurrency((float)$item['price'] * (int)$item['quantity']); ?></b></a><?php endforeach; ?></div>
                            <?php if (count($cart_items) > count($cart_preview_items)): ?><p class="cart-more-items">และอีก <?php echo count($cart_items) - count($cart_preview_items); ?> รายการ</p><?php endif; ?>
                            <div class="cart-preview-total"><span>ยอดรวม</span><strong><?php echo formatCurrency($cart_total); ?></strong></div>
                            <div class="cart-dropdown-actions"><a href="<?php echo BASE_URL; ?>cart.php" class="cart-view-link">ดูตะกร้าสินค้า</a><a href="<?php echo BASE_URL; ?>checkout.php" class="cart-checkout-link">ชำระเงิน <i class="fa-solid fa-arrow-right"></i></a></div>
                        <?php else: ?>
                            <div class="cart-preview-empty"><i class="fa-solid fa-basket-shopping"></i><strong>ตะกร้ายังว่างอยู่</strong><p>เลือกของดีเข้าครัวได้เลย</p><a href="<?php echo BASE_URL; ?>products.php">เลือกซื้อสินค้า</a></div>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="mobile-toggle" type="button" onclick="document.getElementById('siteNav').classList.toggle('open')" aria-label="เปิดเมนู"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
        <nav class="site-nav" id="siteNav" aria-label="เมนูหลัก">
            <div class="container">
                <div class="category-menu">
                    <button type="button" class="category-menu-trigger" aria-expanded="false"><i class="fa-solid fa-bars-staggered"></i> เลือกหมวดสินค้า <i class="fa-solid fa-chevron-down"></i></button>
                    <div class="category-dropdown">
                        <a href="<?php echo BASE_URL; ?>products.php?category=1"><i class="fa-solid fa-fire-burner"></i><span>หม้อและกระทะ<small>อุปกรณ์ปรุงอาหาร</small></span></a>
                        <a href="<?php echo BASE_URL; ?>products.php?category=2"><i class="fa-solid fa-utensils"></i><span>มีดและเขียง<small>เตรียมวัตถุดิบอย่างมั่นใจ</small></span></a>
                        <a href="<?php echo BASE_URL; ?>products.php?category=3"><i class="fa-solid fa-bowl-food"></i><span>จาน ชาม และช้อนส้อม<small>สำหรับทุกมื้ออาหาร</small></span></a>
                        <a href="<?php echo BASE_URL; ?>products.php?category=4"><i class="fa-solid fa-blender"></i><span>เครื่องใช้ไฟฟ้า<small>ตัวช่วยประหยัดเวลา</small></span></a>
                        <a href="<?php echo BASE_URL; ?>products.php?category=5"><i class="fa-solid fa-cookie-bite"></i><span>อุปกรณ์เบเกอรี<small>ครบสำหรับสายอบ</small></span></a>
                        <a href="<?php echo BASE_URL; ?>products.php"><i class="fa-solid fa-border-all"></i><span>ดูสินค้าทั้งหมด<small>เลือกจากทุกหมวดหมู่</small></span></a>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $current_page==='index.php'?'active':''; ?>">หน้าแรก</a>
                <a href="<?php echo BASE_URL; ?>products.php" class="<?php echo $current_page==='products.php'?'active':''; ?>">สินค้าทั้งหมด</a>
                <a href="<?php echo BASE_URL; ?>products.php?sort=price_asc">สินค้าราคาคุ้ม</a>
                <a href="<?php echo BASE_URL; ?>index.php#featured">สินค้าแนะนำ</a>
                <a href="<?php echo BASE_URL; ?>my-coupons.php" class="<?php echo $current_page==='my-coupons.php'?'active':''; ?>"><i class="fa-solid fa-ticket"></i> ศูนย์รวมคูปอง</a>
                <?php if (isLoggedIn()): ?><a href="<?php echo BASE_URL; ?>profile.php">โปรไฟล์ของฉัน</a><?php endif; ?>
                <?php if (isSeller()): ?><a href="<?php echo BASE_URL; ?>my-store.php">ร้านค้าของฉัน</a><a href="<?php echo BASE_URL; ?>seller-orders.php">ออเดอร์ร้านค้า</a><a href="<?php echo BASE_URL; ?>seller-wallet.php">ยอดเงินร้านค้า</a><?php endif; ?>
                <?php if (isAdmin()): ?><a href="<?php echo BASE_URL; ?>admin/index.php">จัดการร้าน</a><?php endif; ?>
                <?php if (isLoggedIn()): ?><button type="button" onclick="secureLogout()">ออกจากระบบ</button><?php else: ?><a class="mobile-account" href="<?php echo BASE_URL; ?>register.php">สมัครสมาชิก</a><?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
    <script>
    async function secureLogout(){const body=new FormData();body.append('action','logout');body.append('csrf_token','<?php echo e(getCsrfToken()); ?>');const response=await fetch('<?php echo BASE_URL; ?>api/auth.php',{method:'POST',body});const result=await response.json();if(result.status==='success')location.href=result.data.redirect;}
    </script>
