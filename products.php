<?php
$page_title = 'สินค้าทั้งหมด';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
$q = trim($_GET['q'] ?? '');
$cat_id = max(0, (int)($_GET['category'] ?? 0));
$min_price = max(0, (float)($_GET['min_price'] ?? 0));
$max_price = max(0, (float)($_GET['max_price'] ?? 0));
$in_stock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
$sort = $_GET['sort'] ?? 'newest';
$sortOptions = [
    'newest' => 'p.id DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating' => 'average_rating DESC, review_count DESC, p.id DESC',
];
if (!isset($sortOptions[$sort])) $sort = 'newest';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$totalProducts = 0;
$totalPages = 1;
$categories = [];
$products = [];
$activeCategory = null;

if ($db) {
    $categories = $db->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();
    foreach ($categories as $category) {
        if ((int)$category['id'] === $cat_id) $activeCategory = $category;
    }

    $where = ' FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
        $params[] = "%{$q}%";
        $params[] = "%{$q}%";
    }
    if ($cat_id > 0) {
        $where .= ' AND p.category_id = ?';
        $params[] = $cat_id;
    }
    if ($min_price > 0) {
        $where .= ' AND p.price >= ?';
        $params[] = $min_price;
    }
    if ($max_price > 0) {
        $where .= ' AND p.price <= ?';
        $params[] = $max_price;
    }
    if ($in_stock) $where .= ' AND p.stock_quantity > 0';

    $countStmt = $db->prepare('SELECT COUNT(*)' . $where);
    $countStmt->execute($params);
    $totalProducts = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalProducts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $sql = 'SELECT p.*, c.name category_name, '
        . '(SELECT COUNT(*) FROM product_reviews r WHERE r.product_id=p.id) review_count, '
        . '(SELECT COALESCE(AVG(rating),0) FROM product_reviews r WHERE r.product_id=p.id) average_rating'
        . $where . ' ORDER BY ' . $sortOptions[$sort] . " LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
}

$hasFilters = $q !== '' || $cat_id > 0 || $min_price > 0 || $max_price > 0 || $in_stock;
$baseQuery = ['q'=>$q, 'category'=>$cat_id, 'min_price'=>$min_price ?: '', 'max_price'=>$max_price ?: '', 'in_stock'=>$in_stock ? '1' : '', 'sort'=>$sort];
?>

<div class="container catalog-page">
    <nav class="breadcrumbs" aria-label="เส้นทางนำทาง">
        <a href="<?php echo BASE_URL; ?>index.php">หน้าหลัก</a><i class="fa-solid fa-chevron-right"></i>
        <span><?php echo $activeCategory ? e($activeCategory['name']) : 'สินค้าทั้งหมด'; ?></span>
    </nav>

    <header class="catalog-header">
        <div>
            <p class="eyebrow">KITCHENMART CATALOG</p>
            <h1><?php echo $activeCategory ? e($activeCategory['name']) : ($q !== '' ? 'ผลการค้นหา “'.e($q).'”' : 'อุปกรณ์ครัวทั้งหมด'); ?></h1>
            <p>พบ <?php echo number_format($totalProducts); ?> รายการที่พร้อมให้คุณเลือก</p>
        </div>
        <button type="button" class="btn btn-outline mobile-filter-button" id="openFilters"><i class="fa-solid fa-sliders"></i> ตัวกรอง</button>
    </header>

    <div class="category-scroller" aria-label="หมวดสินค้า">
        <a href="<?php echo BASE_URL; ?>products.php" class="<?php echo $cat_id===0?'active':''; ?>"><i class="fa-solid fa-border-all"></i><span>ทั้งหมด</span></a>
        <?php foreach ($categories as $category): ?>
            <a href="<?php echo BASE_URL; ?>products.php?category=<?php echo (int)$category['id']; ?>" class="<?php echo $cat_id===(int)$category['id']?'active':''; ?>"><i class="fa-solid <?php echo e($category['icon']); ?>"></i><span><?php echo e($category['name']); ?></span></a>
        <?php endforeach; ?>
    </div>

    <div class="catalog-layout">
        <aside class="filter-panel" id="filterPanel" aria-label="ตัวกรองสินค้า">
            <div class="filter-panel-heading"><strong>ตัวกรองสินค้า</strong><button type="button" id="closeFilters" aria-label="ปิดตัวกรอง"><i class="fa-solid fa-xmark"></i></button></div>
            <form method="GET">
                <div class="filter-group">
                    <label for="filterSearch">ค้นหาในรายการ</label>
                    <div class="filter-search"><i class="fa-solid fa-magnifying-glass"></i><input id="filterSearch" type="search" name="q" value="<?php echo e($q); ?>" placeholder="ชื่อสินค้า"></div>
                </div>
                <fieldset class="filter-group">
                    <legend>หมวดหมู่</legend>
                    <label class="filter-choice"><input type="radio" name="category" value="0" <?php echo $cat_id===0?'checked':''; ?>><span>ทั้งหมด</span></label>
                    <?php foreach ($categories as $category): ?>
                        <label class="filter-choice"><input type="radio" name="category" value="<?php echo (int)$category['id']; ?>" <?php echo $cat_id===(int)$category['id']?'checked':''; ?>><span><?php echo e($category['name']); ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset class="filter-group">
                    <legend>ช่วงราคา</legend>
                    <div class="price-inputs"><label>ต่ำสุด<input type="number" name="min_price" min="0" step="10" value="<?php echo $min_price ?: ''; ?>" placeholder="฿0"></label><span>–</span><label>สูงสุด<input type="number" name="max_price" min="0" step="10" value="<?php echo $max_price ?: ''; ?>" placeholder="฿3,000"></label></div>
                </fieldset>
                <label class="filter-choice stock-choice"><input type="checkbox" name="in_stock" value="1" <?php echo $in_stock?'checked':''; ?>><span>เฉพาะสินค้าที่พร้อมส่ง</span></label>
                <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
                <button class="btn btn-primary filter-submit" type="submit">ใช้ตัวกรอง</button>
                <?php if ($hasFilters): ?><a class="clear-filters" href="<?php echo BASE_URL; ?>products.php"><i class="fa-solid fa-rotate-left"></i> ล้างตัวกรองทั้งหมด</a><?php endif; ?>
            </form>
        </aside>
        <div class="filter-scrim" id="filterScrim"></div>

        <section class="catalog-results" aria-label="รายการสินค้า">
            <div class="catalog-toolbar">
                <span>แสดง <?php echo $totalProducts ? (($page-1)*$perPage)+1 : 0; ?>–<?php echo min($page*$perPage,$totalProducts); ?> จาก <?php echo $totalProducts; ?> รายการ</span>
                <form method="GET">
                    <?php foreach ($baseQuery as $key=>$value): if ($key==='sort' || $value==='') continue; ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>"><?php endforeach; ?>
                    <label for="sort">เรียงตาม</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort==='newest'?'selected':''; ?>>ใหม่ล่าสุด</option>
                        <option value="rating" <?php echo $sort==='rating'?'selected':''; ?>>คะแนนรีวิว</option>
                        <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>ราคาต่ำไปสูง</option>
                        <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>ราคาสูงไปต่ำ</option>
                        <option value="name_asc" <?php echo $sort==='name_asc'?'selected':''; ?>>ชื่อ ก–ฮ</option>
                    </select>
                </form>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state"><i class="fa-solid fa-box-open"></i><h2>ไม่พบสินค้าที่ตรงกับตัวกรอง</h2><p>ลองเปลี่ยนคำค้นหา ช่วงราคา หรือเลือกหมวดหมู่อื่น</p><a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">ดูสินค้าทั้งหมด</a></div>
            <?php else: ?>
                <div class="product-grid catalog-grid">
                    <?php foreach ($products as $p): ?>
                        <article class="product-card">
                            <?php if ((int)$p['is_featured'] === 1): ?><span class="product-badge badge-soft">แนะนำ</span><?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>" class="product-img-wrapper"><img src="<?php echo e(productImageUrl($p['image_url'])); ?>" alt="<?php echo e($p['name']); ?>" class="product-img"></a>
                            <div class="product-info">
                                <span class="product-category"><?php echo e($p['category_name'] ?? 'ทั่วไป'); ?></span>
                                <a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo (int)$p['id']; ?>" class="product-title"><?php echo e($p['name']); ?></a>
                                <div class="product-rating"><span>★</span> <?php echo number_format((float)$p['average_rating'],1); ?> <small>(<?php echo (int)$p['review_count']; ?> รีวิว)</small></div>
                                <div class="product-price-row"><strong class="product-price"><?php echo formatCurrency($p['price']); ?></strong><?php if ((int)$p['stock_quantity'] > 0): ?><span class="stock-text in-stock"><i class="fa-solid fa-circle-check"></i> พร้อมส่ง</span><?php else: ?><span class="stock-text out-stock">สินค้าหมด</span><?php endif; ?></div>
                                <div class="delivery-note"><i class="fa-solid fa-truck"></i> จัดส่งทั่วประเทศ · ฟรีเมื่อครบ ฿1,000</div>
                                <div class="card-actions"><button class="btn btn-outline quick-view-button" type="button" onclick="quickViewProduct(<?php echo (int)$p['id']; ?>)" aria-label="ดู <?php echo e($p['name']); ?> แบบย่อ"><i class="fa-regular fa-eye"></i></button><button class="btn btn-primary" type="button" onclick="addToCart(<?php echo (int)$p['id']; ?>,1)" <?php echo (int)$p['stock_quantity']<1?'disabled':''; ?>><i class="fa-solid fa-basket-shopping"></i> ใส่ตะกร้า</button></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="แบ่งหน้าสินค้า">
                        <?php for ($i=1; $i<=$totalPages; $i++): $query=http_build_query(array_merge($baseQuery,['page'=>$i])); ?>
                            <a href="<?php echo BASE_URL; ?>products.php?<?php echo e($query); ?>" class="<?php echo $i===$page?'active':''; ?>" <?php echo $i===$page?'aria-current="page"':''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<script>
const filterPanel=document.getElementById('filterPanel'),filterScrim=document.getElementById('filterScrim');
function setFilters(open){filterPanel.classList.toggle('open',open);filterScrim.classList.toggle('open',open);document.body.classList.toggle('filters-open',open)}
document.getElementById('openFilters').addEventListener('click',()=>setFilters(true));
document.getElementById('closeFilters').addEventListener('click',()=>setFilters(false));
filterScrim.addEventListener('click',()=>setFilters(false));
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
