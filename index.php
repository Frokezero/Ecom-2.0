<?php
require_once __DIR__.'/config/config.php';
$page_title='หน้าแรก - ร้านอุปกรณ์ครัว';
$page_description='KitchenMart คัดสรรอุปกรณ์ครัวที่ทน ใช้ง่าย พร้อมโปรโมชั่น รีวิวจากผู้ซื้อ และจัดส่งทั่วประเทศ';
$page_canonical=BASE_URL.'index.php';
$structured_data=['@context'=>'https://schema.org','@type'=>'Organization','name'=>APP_NAME,'url'=>$page_canonical,'description'=>$page_description];
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/config/database.php';
$db=(new Database())->getConnection();$categories=[];$featured_products=[];$best_sellers=[];$home_coupons=[];
if($db){
 $categories=$db->query('SELECT * FROM categories ORDER BY id')->fetchAll();
 $featured_products=$db->query("SELECT p.*,c.name category_name,(SELECT COUNT(*) FROM product_reviews r WHERE r.product_id=p.id) review_count,(SELECT COALESCE(AVG(rating),0) FROM product_reviews r WHERE r.product_id=p.id) average_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_featured=1 AND p.approval_status='approved' ORDER BY p.id DESC LIMIT 8")->fetchAll();
 $best_sellers=$db->query("SELECT p.*,c.name category_name,COALESCE(SUM(oi.quantity),0) sold_count,(SELECT COUNT(*) FROM product_reviews r WHERE r.product_id=p.id) review_count,(SELECT COALESCE(AVG(rating),0) FROM product_reviews r WHERE r.product_id=p.id) average_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN order_items oi ON oi.product_id=p.id WHERE p.approval_status='approved' GROUP BY p.id,c.name ORDER BY sold_count DESC,p.id DESC LIMIT 4")->fetchAll();
 $uid=isLoggedIn()?(int)$_SESSION['user_id']:0;$couponStmt=$db->prepare('SELECT c.*,uc.claimed_at FROM coupons c LEFT JOIN user_coupons uc ON uc.coupon_id=c.id AND uc.user_id=? WHERE c.is_active=1 AND c.starts_at<=NOW() AND c.ends_at>=NOW() ORDER BY c.ends_at LIMIT 8');$couponStmt->execute([$uid]);$home_coupons=$couponStmt->fetchAll();
}
$hero_product=$featured_products[0]??null;
$promo_banners=$db?activePromotionalBanners($db,'hero'):[];
?>
<div class="container home-page">
    <?php if($promo_banners): ?><section class="promo-hero-slider" aria-label="โปรโมชั่น"><div class="promo-hero-track"><?php foreach($promo_banners as $banner): ?><a href="<?php echo e($banner['target_url'] ?: '#'); ?>" class="promo-hero-slide"><picture><?php if($banner['image_mobile']): ?><source media="(max-width:720px)" srcset="<?php echo BASE_URL.e($banner['image_mobile']); ?>"><?php endif; ?><img src="<?php echo BASE_URL.e($banner['image_desktop']); ?>" alt="<?php echo e($banner['title']); ?>"></picture><span><strong><?php echo e($banner['title']); ?></strong><?php if($banner['subtitle']): ?><small><?php echo e($banner['subtitle']); ?></small><?php endif; ?><?php if($banner['button_label']): ?><em><?php echo e($banner['button_label']); ?> <i class="fa-solid fa-arrow-right"></i></em><?php endif; ?></span></a><?php endforeach; ?></div></section><?php endif; ?>
    <section class="hero-banner">
        <div class="hero-copy">
            <p class="eyebrow">KITCHEN ESSENTIALS · 2026</p>
            <h1>ครัวที่ดี<br>เริ่มจากของที่ใช้ถนัด</h1>
            <p>เราเลือกอุปกรณ์ที่ทน ใช้ง่าย และดูแลไม่ยุ่งยาก เพื่อให้การทำอาหารทุกวันเป็นเรื่องน่าสนุกขึ้น</p>
            <div class="hero-actions"><a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">เลือกซื้อสินค้า <i class="fa-solid fa-arrow-right"></i></a><a href="#categories" class="text-link">ดูตามหมวดหมู่</a></div>
        </div>
        <div class="hero-feature">
            <span>สินค้าแนะนำประจำสัปดาห์</span>
            <?php if($hero_product): ?><a class="hero-feature-image" href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$hero_product['id']; ?>"><img src="<?php echo e(productImageUrl($hero_product['image_url'])); ?>" alt="<?php echo e($hero_product['name']); ?>"></a>
            <div><strong><?php echo formatCurrency($hero_product['price']); ?></strong><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$hero_product['id']; ?>">ดูรายละเอียดสินค้า <i class="fa-solid fa-arrow-right"></i></a></div>
            <?php else: ?><img src="<?php echo BASE_URL; ?>assets/images/products/placeholder.svg" alt="สินค้าแนะนำ KitchenMart"><div><strong>สินค้าใหม่เร็ว ๆ นี้</strong><a href="<?php echo BASE_URL; ?>products.php">ดูสินค้าทั้งหมด <i class="fa-solid fa-arrow-right"></i></a></div><?php endif; ?>
        </div>
    </section>
    <section class="service-strip" aria-label="บริการของร้าน"><div><i class="fa-solid fa-truck-fast"></i><span><strong>ส่งฟรี ฿1,000+</strong><small>ทั่วประเทศ</small></span></div><div><i class="fa-solid fa-box-open"></i><span><strong>แพ็กอย่างดี</strong><small>ลดความเสียหายระหว่างส่ง</small></span></div><div><i class="fa-solid fa-shield-halved"></i><span><strong>ชำระปลอดภัย</strong><small>PromptPay หรือ COD</small></span></div></section>

    <section class="quick-paths" aria-label="เลือกซื้อตามงาน">
        <a href="<?php echo BASE_URL; ?>products.php?category=1"><span><i class="fa-solid fa-fire-burner"></i></span><div><small>ทำอาหารทุกวัน</small><strong>อุปกรณ์ปรุงอาหาร</strong><em>เลือกดูสินค้า <i class="fa-solid fa-arrow-right"></i></em></div></a>
        <a href="<?php echo BASE_URL; ?>products.php?category=2"><span><i class="fa-solid fa-utensils"></i></span><div><small>เตรียมวัตถุดิบ</small><strong>มีด เขียง และอุปกรณ์</strong><em>เลือกดูสินค้า <i class="fa-solid fa-arrow-right"></i></em></div></a>
        <a href="<?php echo BASE_URL; ?>products.php?category=5"><span><i class="fa-solid fa-cookie-bite"></i></span><div><small>ทำขนมที่บ้าน</small><strong>อุปกรณ์เบเกอรี</strong><em>เลือกดูสินค้า <i class="fa-solid fa-arrow-right"></i></em></div></a>
    </section>

    <?php if($home_coupons): ?><section class="home-coupon-section" data-coupon-carousel><header class="section-header"><div><p class="eyebrow">COUPONS FOR YOU</p><h2 class="section-title">คูปองสำหรับคุณ</h2></div><div class="home-coupon-controls"><button type="button" data-coupon-prev aria-label="คูปองก่อนหน้า"><i class="fa-solid fa-chevron-left"></i></button><button type="button" data-coupon-next aria-label="คูปองถัดไป"><i class="fa-solid fa-chevron-right"></i></button><a href="<?php echo BASE_URL; ?>my-coupons.php" class="text-link">ดูทั้งหมด</a></div></header><div class="home-coupon-track" data-coupon-track><?php foreach($home_coupons as $c): ?><article class="coupon-ticket compact"><span class="coupon-ticket-value"><?php echo e(formatCouponBenefit($c)); ?></span><div><strong><?php echo e($c['title']); ?></strong><small>ขั้นต่ำ <?php echo formatCurrency($c['min_order_amount']); ?> · <?php echo e($c['code']); ?></small></div><?php if($c['claimed_at']): ?><a href="<?php echo BASE_URL; ?>cart.php" class="coupon-action claimed">ใช้ทันที</a><?php else: ?><button class="coupon-action claim-coupon" data-id="<?php echo (int)$c['id']; ?>">รับ</button><?php endif; ?></article><?php endforeach; ?></div></section><?php endif; ?>

    <section id="categories" class="home-section">
        <header class="section-header"><div><p class="eyebrow">SHOP BY CATEGORY</p><h2 class="section-title">เลือกของให้ตรงงาน</h2></div><a href="<?php echo BASE_URL; ?>products.php" class="text-link">ดูทั้งหมด →</a></header>
        <div class="category-grid"><?php foreach($categories as $cat): ?><a href="<?php echo BASE_URL; ?>products.php?category=<?php echo (int)$cat['id']; ?>" class="category-card"><span class="category-icon"><i class="fa-solid <?php echo e($cat['icon']); ?>"></i></span><span><strong><?php echo e($cat['name']); ?></strong><small>เลือกดูสินค้า</small></span><i class="fa-solid fa-arrow-right"></i></a><?php endforeach; ?></div>
    </section>

    <section class="home-section">
        <header class="section-header"><div><p class="eyebrow">CUSTOMER FAVORITES</p><h2 class="section-title">ของที่ลูกค้าเลือกบ่อย</h2></div></header>
        <div class="product-grid compact-grid"><?php foreach($best_sellers as $p): ?>
            <article class="product-card"><span class="product-badge">ขายดี</span><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>" class="product-img-wrapper"><img src="<?php echo e(productImageUrl($p['image_url'])); ?>" alt="<?php echo e($p['name']); ?>" class="product-img"></a><div class="product-info"><span class="product-category"><?php echo e($p['category_name']); ?></span><a class="product-title" href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>"><?php echo e($p['name']); ?></a><div class="product-rating"><span>★</span> <?php echo number_format((float)$p['average_rating'],1); ?> <small>(<?php echo (int)$p['review_count']; ?> รีวิว)</small></div><div class="product-meta"><strong class="product-price"><?php echo formatCurrency($p['price']); ?></strong><span class="stock-text in-stock"><i class="fa-solid fa-circle-check"></i> พร้อมส่ง</span></div><button class="btn btn-cart" onclick="addToCart(<?php echo (int)$p['id']; ?>,1)" <?php echo $p['stock_quantity']<1?'disabled':''; ?>><i class="fa-solid fa-plus"></i> เพิ่มลงตะกร้า</button></div></article>
        <?php endforeach; ?></div>
    </section>

    <section id="featured" class="home-section featured-section" data-featured-carousel>
        <header class="section-header"><div><p class="eyebrow">EDITOR'S PICKS</p><h2 class="section-title">คัดมาให้แล้วสำหรับครัวบ้าน</h2></div><a href="<?php echo BASE_URL; ?>products.php" class="text-link">ดูสินค้าทั้งหมด →</a></header>
        <div class="featured-carousel-controls" aria-label="ควบคุมสินค้าแนะนำ">
            <button type="button" class="carousel-control" data-carousel-prev aria-label="ดูสินค้าแนะนำก่อนหน้า" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <span class="featured-carousel-status" data-carousel-status aria-live="polite">1 / <?php echo count($featured_products); ?></span>
            <button type="button" class="carousel-control" data-carousel-next aria-label="ดูสินค้าแนะนำถัดไป"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <div class="featured-carousel-track" data-carousel-track tabindex="0" aria-label="รายการสินค้าแนะนำ"><?php foreach($featured_products as $p): ?>
            <article class="product-card"><span class="product-badge badge-soft">แนะนำ</span><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>" class="product-img-wrapper"><img src="<?php echo e(productImageUrl($p['image_url'])); ?>" alt="<?php echo e($p['name']); ?>" class="product-img"></a><div class="product-info"><span class="product-category"><?php echo e($p['category_name']); ?></span><a class="product-title" href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>"><?php echo e($p['name']); ?></a><div class="product-rating"><span>★</span> <?php echo number_format((float)$p['average_rating'],1); ?> <small>(<?php echo (int)$p['review_count']; ?> รีวิว)</small></div><div class="product-meta"><strong class="product-price"><?php echo formatCurrency($p['price']); ?></strong><span class="stock-text in-stock"><i class="fa-solid fa-circle-check"></i> พร้อมส่ง</span></div><div class="card-actions"><a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-outline">รายละเอียด</a><button class="btn btn-primary" onclick="addToCart(<?php echo (int)$p['id']; ?>,1)">ใส่ตะกร้า</button></div></div></article>
        <?php endforeach; ?></div>
    </section>
</div>
<script nonce="<?php echo e(cspNonce()); ?>">
document.querySelectorAll('[data-featured-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    const status = carousel.querySelector('[data-carousel-status]');
    if (!track) return;
    const cards = Array.from(track.querySelectorAll('.product-card'));
    if (cards.length < 2) {
        if (previous) previous.hidden = true;
        if (next) next.hidden = true;
        return;
    }

    const step = () => {
        const cardWidth = cards[0].getBoundingClientRect().width;
        const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 0) || 0;
        return cardWidth + gap;
    };
    const updateControls = () => {
        const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
        const atStart = track.scrollLeft <= 2;
        const atEnd = track.scrollLeft >= maxScroll - 2;
        previous.disabled = atStart;
        next.disabled = atEnd;
        const active = Math.min(cards.length, Math.max(1, Math.round(track.scrollLeft / step()) + 1));
        status.textContent = `${active} / ${cards.length}`;
    };
    previous.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', () => requestAnimationFrame(updateControls), { passive: true });
    window.addEventListener('resize', updateControls);
    updateControls();
});
</script>
<script src="<?php echo BASE_URL; ?>assets/js/coupon-center.js?v=1"></script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
