<?php
$shopId = (int)($_GET['id'] ?? 0);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$db = (new Database())->getConnection();
if (!$db || !$shopId) { http_response_code(404); exit('ไม่พบร้านค้า'); }
$stmt = $db->prepare('SELECT sp.*,u.username FROM seller_profiles sp JOIN users u ON u.id=sp.user_id WHERE sp.user_id=? AND sp.status="approved" LIMIT 1');
$stmt->execute([$shopId]);
$shop = $stmt->fetch();
if (!$shop) { http_response_code(404); exit('ไม่พบร้านค้า'); }
$products = $db->prepare("SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.seller_id=? AND p.approval_status='approved' ORDER BY p.is_featured DESC,p.created_at DESC");
$products->execute([$shopId]);
$products = $products->fetchAll();
$page_title = $shop['shop_name'];
require_once __DIR__ . '/includes/header.php';
$coverStyle = $shop['cover_image'] ? ' style="background-image:linear-gradient(90deg,rgba(13,51,40,.93),rgba(13,51,40,.26)),url('.e(productImageUrl($shop['cover_image'])).')"' : '';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/seller.css">
<main class="shop-page">
    <section class="shop-cover"<?php echo $coverStyle; ?>><div class="container"><div class="shop-cover-content"><span class="shop-logo"><?php if($shop['shop_logo']): ?><img src="<?php echo e(productImageUrl($shop['shop_logo'])); ?>" alt="โลโก้ <?php echo e($shop['shop_name']); ?>"><?php else: ?><i class="fa-solid fa-store"></i><?php endif; ?></span><div><p class="eyebrow">KITCHENMART STORE</p><h1><?php echo e($shop['shop_name']); ?></h1><p><?php echo e($shop['shop_description'] ?: 'ร้านค้าที่คัดสรรสินค้าเพื่อทุกครัว'); ?></p><small><i class="fa-solid fa-circle-check"></i> ร้านค้าได้รับการยืนยันแล้ว</small></div></div></div></section>
    <div class="container">
        <?php if ($shop['promo_image'] || $shop['promo_title']): ?><section class="shop-promo"><?php if($shop['promo_image']): ?><img src="<?php echo e(productImageUrl($shop['promo_image'])); ?>" alt="<?php echo e($shop['promo_title'] ?: 'โปรโมชันร้านค้า'); ?>"><?php endif; ?><div><p class="eyebrow">SPECIAL FROM THIS STORE</p><h2><?php echo e($shop['promo_title'] ?: 'โปรโมชันพิเศษจากร้าน'); ?></h2><?php if($shop['promo_text']): ?><p><?php echo e($shop['promo_text']); ?></p><?php endif; ?><?php if($shop['promo_url']): ?><a class="btn btn-primary" href="<?php echo e($shop['promo_url']); ?>" target="_blank" rel="noopener">ดูโปรโมชัน <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?></div></section><?php endif; ?>
        <section class="shop-products"><header><div><p class="eyebrow">SHOP COLLECTION</p><h2>สินค้าจาก <?php echo e($shop['shop_name']); ?></h2></div><span><?php echo count($products); ?> รายการ</span></header><div class="product-grid"><?php if(!$products): ?><div class="store-empty"><i class="fa-solid fa-box-open"></i><strong>ร้านนี้กำลังเตรียมสินค้า</strong><span>กลับมาเลือกชมใหม่ได้เร็ว ๆ นี้</span></div><?php endif; ?><?php foreach($products as $product): ?><article class="product-card"><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$product['id']; ?>" class="product-img-wrapper"><img src="<?php echo e(productImageUrl($product['image_url'])); ?>" alt="<?php echo e($product['name']); ?>" class="product-img"></a><div class="product-info"><span class="product-category"><?php echo e($product['category_name']); ?></span><a class="product-title" href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$product['id']; ?>"><?php echo e($product['name']); ?></a><div class="product-meta"><strong class="product-price"><?php echo formatCurrency($product['price']); ?></strong><span class="stock-text <?php echo $product['stock_quantity']?'in-stock':''; ?>"><?php echo $product['stock_quantity']?'พร้อมส่ง':'สินค้าหมด'; ?></span></div><button class="btn btn-cart" onclick="addToCart(<?php echo (int)$product['id']; ?>,1)" <?php echo !$product['stock_quantity']?'disabled':''; ?>><i class="fa-solid fa-plus"></i> เพิ่มลงตะกร้า</button></div></article><?php endforeach; ?></div></section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
