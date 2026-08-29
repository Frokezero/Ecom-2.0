<?php
$page_title = 'ร้านค้าของฉัน';
require_once __DIR__ . '/includes/auth_check.php';
requireSeller();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/image_upload.php';

$db = (new Database())->getConnection();
if (!$db) { http_response_code(503); exit('ไม่สามารถเชื่อมต่อฐานข้อมูลได้'); }
$sellerId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

$profileStmt = $db->prepare('SELECT * FROM seller_profiles WHERE user_id=? AND status="approved" LIMIT 1');
$profileStmt->execute([$sellerId]);
$profile = $profileStmt->fetch();
if (!$profile) { header('Location: '.BASE_URL.'seller.php'); exit; }

function uploadedFilePresent(string $field): bool {
    return isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}
function cleanStoreUrl(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    if (!filter_var($value, FILTER_VALIDATE_URL)) throw new RuntimeException('ลิงก์ปุ่มโปรโมชันต้องเป็น URL ที่ถูกต้อง');
    $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http','https'], true)) throw new RuntimeException('ลิงก์ปุ่มโปรโมชันต้องเริ่มด้วย http:// หรือ https://');
    return $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf(false);
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_store') {
            $shopName = trim((string)($_POST['shop_name'] ?? ''));
            $description = trim((string)($_POST['shop_description'] ?? ''));
            $promoTitle = trim((string)($_POST['promo_title'] ?? ''));
            $promoText = trim((string)($_POST['promo_text'] ?? ''));
            $promoUrl = cleanStoreUrl((string)($_POST['promo_url'] ?? ''));
            if (mb_strlen($shopName) < 2 || mb_strlen($shopName) > 80) throw new RuntimeException('ชื่อร้านต้องมี 2–80 ตัวอักษร');
            if (mb_strlen($description) > 1200 || mb_strlen($promoTitle) > 120 || mb_strlen($promoText) > 250) throw new RuntimeException('รายละเอียดร้านหรือข้อความโปรโมชันยาวเกินกำหนด');

            $images = [
                'shop_logo' => $profile['shop_logo'],
                'cover_image' => $profile['cover_image'],
                'promo_image' => $profile['promo_image']
            ];
            foreach ($images as $field => $current) {
                if (uploadedFilePresent($field)) $images[$field] = saveStoreImageUpload($_FILES[$field]);
            }
            $stmt = $db->prepare('UPDATE seller_profiles SET shop_name=?,shop_description=?,shop_logo=?,cover_image=?,promo_image=?,promo_title=?,promo_text=?,promo_url=? WHERE user_id=? AND status="approved"');
            $stmt->execute([$shopName, $description ?: null, $images['shop_logo'], $images['cover_image'], $images['promo_image'], $promoTitle ?: null, $promoText ?: null, $promoUrl, $sellerId]);
            $message = 'บันทึกหน้าร้านแล้ว';
        }

        if ($action === 'edit_product') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $locked = $db->prepare('SELECT * FROM products WHERE id=? AND seller_id=? LIMIT 1');
            $locked->execute([$productId, $sellerId]);
            $old = $locked->fetch();
            if (!$old) throw new RuntimeException('ไม่พบสินค้านี้ หรือคุณไม่มีสิทธิ์แก้ไข');
            $name = trim((string)($_POST['name'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
            $description = trim((string)($_POST['description'] ?? ''));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 200 || !$categoryId || $price === false || $price <= 0 || $stock === false || $stock < 0 || mb_strlen($description) > 3000) throw new RuntimeException('กรุณากรอกข้อมูลสินค้าให้ถูกต้อง');
            $categoryCheck = $db->prepare('SELECT 1 FROM categories WHERE id=? LIMIT 1');
            $categoryCheck->execute([$categoryId]);
            if (!$categoryCheck->fetchColumn()) throw new RuntimeException('ไม่พบหมวดสินค้าที่เลือก');
            $image = $old['image_url'];
            $hasNewImage = uploadedFilePresent('product_image');
            if ($hasNewImage) $image = saveProductImageUpload($_FILES['product_image']);
            $requiresReview = $hasNewImage || $name !== $old['name'] || $categoryId !== (int)$old['category_id'] || (float)$price !== (float)$old['price'] || $description !== (string)$old['description'];
            $status = $requiresReview ? 'pending' : $old['approval_status'];
            $stmt = $db->prepare('UPDATE products SET name=?,category_id=?,price=?,stock_quantity=?,description=?,image_url=?,approval_status=?,admin_note=? WHERE id=? AND seller_id=?');
            $stmt->execute([$name, $categoryId, $price, $stock, $description, $image, $status, $requiresReview ? null : $old['admin_note'], $productId, $sellerId]);
            $message = $requiresReview ? 'บันทึกสินค้าแล้ว และส่งให้ทีมงานตรวจสอบอีกครั้ง' : 'อัปเดตสต็อกสินค้าแล้ว';
        }

        if ($action === 'delete_product') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $owned = $db->prepare('SELECT 1 FROM products WHERE id=? AND seller_id=? LIMIT 1');
            $owned->execute([$productId, $sellerId]);
            if (!$owned->fetchColumn()) throw new RuntimeException('ไม่พบสินค้านี้ หรือคุณไม่มีสิทธิ์ลบ');
            $activeOrder = $db->prepare('SELECT 1 FROM order_items WHERE product_id=? LIMIT 1');
            $activeOrder->execute([$productId]);
            if ($activeOrder->fetchColumn()) throw new RuntimeException('ลบสินค้านี้ไม่ได้ เพราะมีประวัติคำสั่งซื้ออยู่แล้ว ให้ปรับสต็อกเป็น 0 แทน');
            $stmt = $db->prepare('DELETE FROM products WHERE id=? AND seller_id=?');
            $stmt->execute([$productId, $sellerId]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('ไม่พบสินค้านี้ หรือคุณไม่มีสิทธิ์ลบ');
            $message = 'ลบสินค้าออกจากร้านแล้ว';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
    $profileStmt->execute([$sellerId]);
    $profile = $profileStmt->fetch() ?: $profile;
}

$categories = $db->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$productStmt = $db->prepare('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.seller_id=? ORDER BY p.created_at DESC');
$productStmt->execute([$sellerId]);
$products = $productStmt->fetchAll();
$stats = ['all'=>count($products),'approved'=>0,'pending'=>0,'stock'=>0];
foreach ($products as $product) {
    $stats[$product['approval_status']] = ($stats[$product['approval_status']] ?? 0) + 1;
    $stats['stock'] += (int)$product['stock_quantity'];
}

require_once __DIR__ . '/includes/header.php';
$coverStyle = $profile['cover_image'] ? ' style="background-image:linear-gradient(90deg,rgba(13,51,40,.92),rgba(13,51,40,.36)),url('.e(productImageUrl($profile['cover_image'])).')"' : '';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/seller.css">
<div class="seller-page my-store-page"><div class="container">
    <section class="storefront-hero"<?php echo $coverStyle; ?>>
        <div class="storefront-identity">
            <span class="storefront-logo"><?php if ($profile['shop_logo']): ?><img src="<?php echo e(productImageUrl($profile['shop_logo'])); ?>" alt="โลโก้ <?php echo e($profile['shop_name']); ?>"><?php else: ?><i class="fa-solid fa-store"></i><?php endif; ?></span>
            <div><p class="eyebrow">MY STOREFRONT</p><h1><?php echo e($profile['shop_name']); ?></h1><p><?php echo e($profile['shop_description'] ?: 'เลือกสินค้าและจัดหน้าร้านของคุณได้จากที่เดียว'); ?></p></div>
        </div>
        <div class="storefront-actions"><a class="btn btn-outline" href="<?php echo BASE_URL; ?>shop.php?id=<?php echo $sellerId; ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> ดูหน้าร้าน</a><a class="btn" href="<?php echo BASE_URL; ?>seller-dashboard.php#add-product"><i class="fa-solid fa-plus"></i> เพิ่มสินค้า</a></div>
    </section>

    <section class="store-stats">
        <div><small>สินค้าทั้งหมด</small><strong><?php echo $stats['all']; ?></strong></div>
        <div><small>กำลังขาย</small><strong><?php echo $stats['approved']; ?></strong></div>
        <div><small>รอตรวจ</small><strong><?php echo $stats['pending']; ?></strong></div>
        <div><small>สต็อกรวม</small><strong><?php echo number_format($stats['stock']); ?></strong></div>
    </section>

    <?php if ($message): ?><p class="seller-success"><?php echo e($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="seller-error"><?php echo e($error); ?></p><?php endif; ?>

    <nav class="store-tabs" aria-label="เมนูร้านค้า"><a href="#products"><i class="fa-solid fa-box"></i> สินค้าของฉัน</a><a href="#design"><i class="fa-solid fa-wand-magic-sparkles"></i> ตกแต่งหน้าร้าน</a><a href="#promotion"><i class="fa-solid fa-bullhorn"></i> โปรโมชัน</a></nav>

    <section class="my-store-products" id="products">
        <header class="store-section-header"><div><p class="eyebrow">PRODUCT CATALOG</p><h2>สินค้าของฉัน</h2><p>แก้ไขรายละเอียด สต็อก หรือเอาสินค้าออกจากร้านได้เอง</p></div><a class="btn btn-primary" href="<?php echo BASE_URL; ?>seller-dashboard.php#add-product"><i class="fa-solid fa-plus"></i> เพิ่มสินค้า</a></header>
        <div class="seller-product-grid">
            <?php if (!$products): ?><div class="store-empty"><i class="fa-solid fa-box-open"></i><strong>ยังไม่มีสินค้าในร้าน</strong><span>เริ่มเพิ่มสินค้าเพื่อให้ลูกค้าเห็นหน้าร้านของคุณ</span><a class="btn btn-primary" href="<?php echo BASE_URL; ?>seller-dashboard.php#add-product">เพิ่มสินค้าชิ้นแรก</a></div><?php endif; ?>
            <?php foreach ($products as $product): ?>
            <article class="seller-product-card">
                <img src="<?php echo e(productImageUrl($product['image_url'])); ?>" alt="<?php echo e($product['name']); ?>">
                <div><span class="status-badge <?php echo $product['approval_status']==='approved'?'paid':($product['approval_status']==='rejected'?'cancelled':'pending'); ?>"><?php echo e($product['approval_status']); ?></span><small><?php echo e($product['category_name']); ?></small><h3><?php echo e($product['name']); ?></h3><strong><?php echo formatCurrency($product['price']); ?></strong><span>คงเหลือ <?php echo (int)$product['stock_quantity']; ?> ชิ้น</span></div>
                <details><summary><i class="fa-regular fa-pen-to-square"></i> แก้ไขสินค้า</summary>
                    <form method="POST" enctype="multipart/form-data" class="seller-form product-edit-form"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" value="edit_product"><input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                        <div class="seller-fields"><label class="full">ชื่อสินค้า<input name="name" value="<?php echo e($product['name']); ?>" required maxlength="200"></label><label>หมวดสินค้า<select name="category_id" required><?php foreach($categories as $category): ?><option value="<?php echo (int)$category['id']; ?>" <?php echo (int)$category['id']===(int)$product['category_id']?'selected':''; ?>><?php echo e($category['name']); ?></option><?php endforeach; ?></select></label><label>ราคา<input type="number" name="price" min="0.01" step="0.01" value="<?php echo e($product['price']); ?>" required></label><label>สต็อก<input type="number" name="stock_quantity" min="0" value="<?php echo (int)$product['stock_quantity']; ?>" required></label><label class="full">เปลี่ยนรูปสินค้า<input type="file" name="product_image" data-image-crop data-crop-ratio="1" accept="image/jpeg,image/png,image/webp"><small>หากเปลี่ยนรูปหรือรายละเอียด สินค้าจะส่งตรวจสอบอีกครั้ง</small></label><label class="full">รายละเอียด<textarea name="description" rows="4" maxlength="3000"><?php echo e($product['description']); ?></textarea></label></div><footer><button class="btn btn-primary" type="submit">บันทึกการแก้ไข</button></footer>
                    </form>
                    <a class="btn btn-outline" href="<?php echo BASE_URL;?>seller-product-options.php?id=<?php echo (int)$product['id'];?>"><i class="fa-solid fa-layer-group"></i> ตัวเลือกและรูปเพิ่มเติม</a><form method="POST" class="seller-delete-form" onsubmit="return confirm('ลบสินค้านี้ออกจากร้านใช่หรือไม่?');"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>"><button class="btn btn-danger" type="submit"><i class="fa-regular fa-trash-can"></i> ลบสินค้า</button></form>
                </details>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="store-customize" id="design">
        <aside><p class="eyebrow">STORE DESIGN</p><h2>ตกแต่งร้าน<br>ให้เป็นคุณ</h2><p>ลูกค้าจะเห็นข้อมูลเหล่านี้บนหน้าร้านของคุณ สามารถกลับมาเปลี่ยนได้ทุกเมื่อ</p><ul><li><i class="fa-solid fa-image"></i> โลโก้และภาพปก</li><li><i class="fa-solid fa-pen-nib"></i> เรื่องราวของร้าน</li><li><i class="fa-solid fa-bullhorn"></i> แบนเนอร์โปรโมชัน</li></ul></aside>
        <form method="POST" enctype="multipart/form-data" class="seller-form"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" value="save_store"><header><p class="eyebrow">STORE PROFILE</p><h2>โปรไฟล์และภาพปก</h2></header><div class="seller-fields"><label class="full">ชื่อร้าน<input name="shop_name" value="<?php echo e($profile['shop_name']); ?>" required maxlength="80"></label><label class="full">คำอธิบายร้าน<textarea name="shop_description" rows="4" maxlength="1200"><?php echo e($profile['shop_description']); ?></textarea></label><label>โลโก้ร้าน (สี่เหลี่ยม)<input type="file" name="shop_logo" data-image-crop data-crop-ratio="1" accept="image/jpeg,image/png,image/webp"><small>แนะนำ 1:1</small></label><label>ภาพปกหน้าร้าน<input type="file" name="cover_image" data-image-crop data-crop-ratio="3" accept="image/jpeg,image/png,image/webp"><small>แนะนำ 3:1</small></label></div>
        <section class="store-promo-fields" id="promotion"><p class="eyebrow">PROMOTION BANNER</p><h2>แบนเนอร์โปรโมชัน</h2><div class="seller-fields"><label class="full">ภาพแบนเนอร์<input type="file" name="promo_image" data-image-crop data-crop-ratio="2.4" accept="image/jpeg,image/png,image/webp"><small>แนะนำ 12:5</small></label><label>หัวข้อโปรโมชัน<input name="promo_title" value="<?php echo e($profile['promo_title']); ?>" maxlength="120" placeholder="เช่น ลดพิเศษสำหรับลูกค้าใหม่"></label><label>ข้อความสั้น<input name="promo_text" value="<?php echo e($profile['promo_text']); ?>" maxlength="250" placeholder="เช่น ช้อปครบ 1,000 บาท รับส่วนลดทันที"></label><label class="full">ลิงก์ปุ่มโปรโมชัน<input name="promo_url" type="url" value="<?php echo e($profile['promo_url']); ?>" maxlength="500" placeholder="https://..."></label></div></section>
        <footer><span>รูปใหม่จะแสดงทันทีหลังบันทึก</span><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึกหน้าร้าน</button></footer></form>
    </section>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
