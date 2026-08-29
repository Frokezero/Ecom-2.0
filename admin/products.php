<?php
$page_title = 'สินค้าและสต็อก';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image_upload.php';
$db = (new Database())->getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf(false);
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $productName = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
        $description = trim($_POST['description'] ?? '');
        $imageUrl = 'assets/images/products/placeholder.svg';
        if ($action === 'edit') { $old=$db?->prepare('SELECT image_url FROM products WHERE id=?'); if($old){$old->execute([(int)($_POST['id']??0)]);$imageUrl=$old->fetchColumn() ?: $imageUrl;} }
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try { $imageUrl = saveProductImageUpload($_FILES['product_image']); $_FILES['product_image']['error'] = UPLOAD_ERR_NO_FILE; }
            catch (Throwable $uploadError) { $error = $uploadError->getMessage(); }
        }

        if (!$error && isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['product_image'];
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2*1024*1024) $error = 'รูปภาพต้องมีขนาดไม่เกิน 2 MB';
            else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extensions[$mime])) $error = 'รองรับเฉพาะไฟล์ JPG, PNG หรือ WebP';
                else {
                    $uploadDir = __DIR__ . '/../assets/images/products/uploads';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir,0775,true)) $error = 'ไม่สามารถสร้างโฟลเดอร์จัดเก็บรูปได้';
                    if (!$error) {
                        $uploadFileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                        if (!move_uploaded_file($file['tmp_name'],$uploadDir.'/'.$uploadFileName)) $error = 'ไม่สามารถบันทึกรูปภาพได้';
                        else $imageUrl = 'assets/images/products/uploads/' . $uploadFileName;
                    }
                }
            }
        }

        if (!$error && ($productName === '' || $price === false || $price <= 0 || $stock === false || $stock < 0 || $categoryId < 1)) $error = 'กรุณากรอกชื่อ หมวดหมู่ ราคา และสต็อกให้ถูกต้อง';
        if (!$error && $db) {
            if ($action === 'add') {
                $stmt=$db->prepare('INSERT INTO products(category_id,name,description,price,stock_quantity,image_url,is_featured) VALUES(?,?,?,?,?,?,?)');
                $stmt->execute([$categoryId,$productName,$description,$price,$stock,$imageUrl,$isFeatured]);
                $message='เพิ่มสินค้า “'.$productName.'” แล้ว';
            } else {
                $id=(int)($_POST['id'] ?? 0);
                $stmt=$db->prepare('UPDATE products SET category_id=?,name=?,description=?,price=?,stock_quantity=?,image_url=?,is_featured=? WHERE id=?');
                $stmt->execute([$categoryId,$productName,$description,$price,$stock,$imageUrl,$isFeatured,$id]);
                $message='บันทึกข้อมูลสินค้าแล้ว';
            }
        }
    } elseif ($action === 'delete' && $db) {
        $id=(int)($_POST['id'] ?? 0);
        $stmt=$db->prepare('DELETE FROM products WHERE id=?');
        $stmt->execute([$id]);
        $message='ลบสินค้าเรียบร้อยแล้ว';
    }
}

$q=trim($_GET['q'] ?? '');
$categoryFilter=max(0,(int)($_GET['category'] ?? 0));
$stockFilter=$_GET['stock'] ?? '';
if (!in_array($stockFilter,['','low','available','out'],true)) $stockFilter='';
$categories=[];$products=[];$totalProducts=0;
if ($db) {
    $categories=$db->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    $where=' FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE 1=1';$params=[];
    if ($q!=='') {$where.=' AND (p.name LIKE ? OR p.description LIKE ?)';$params[]="%{$q}%";$params[]="%{$q}%";}
    if ($categoryFilter>0) {$where.=' AND p.category_id=?';$params[]=$categoryFilter;}
    if ($stockFilter==='low') $where.=' AND p.stock_quantity BETWEEN 1 AND 5';
    elseif ($stockFilter==='available') $where.=' AND p.stock_quantity > 5';
    elseif ($stockFilter==='out') $where.=' AND p.stock_quantity = 0';
    $count=$db->prepare('SELECT COUNT(*)'.$where);$count->execute($params);$totalProducts=(int)$count->fetchColumn();
    $stmt=$db->prepare('SELECT p.*,c.name category_name'.$where.' ORDER BY p.id DESC');$stmt->execute($params);$products=$stmt->fetchAll();
}
require_once __DIR__ . '/../includes/admin_header.php';
?>
<header class="admin-page-header"><div><p class="eyebrow">PRODUCT MANAGEMENT</p><h1>สินค้าและสต็อก</h1><p>เพิ่ม แก้ไข และตรวจสินค้าที่ต้องเติมสต็อก</p></div><div class="admin-actions"><button type="button" onclick="openAddProductModal()" class="btn btn-primary"><i class="fa-solid fa-plus"></i> เพิ่มสินค้าใหม่</button></div></header>
<?php if($message): ?><div class="admin-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if($error): ?><div class="admin-alert error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<section class="admin-panel">
    <header class="admin-panel-header"><div><h2>รายการสินค้า</h2><p>จัดการข้อมูลที่แสดงบนหน้าร้าน</p></div><span class="result-count"><?php echo number_format($totalProducts); ?> รายการ</span></header>
    <div class="admin-toolbar"><form method="GET" class="admin-filter-form"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="ค้นหาชื่อสินค้า..."><select name="category"><option value="0">ทุกหมวดหมู่</option><?php foreach($categories as $category): ?><option value="<?php echo (int)$category['id']; ?>" <?php echo $categoryFilter===(int)$category['id']?'selected':''; ?>><?php echo e($category['name']); ?></option><?php endforeach; ?></select><select name="stock"><option value="">ทุกสถานะสต็อก</option><option value="low" <?php echo $stockFilter==='low'?'selected':''; ?>>สต็อกต่ำ 1–5</option><option value="available" <?php echo $stockFilter==='available'?'selected':''; ?>>พร้อมขาย</option><option value="out" <?php echo $stockFilter==='out'?'selected':''; ?>>สินค้าหมด</option></select><button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> กรอง</button><?php if($q!==''||$categoryFilter||$stockFilter!==''): ?><a class="btn btn-outline" href="<?php echo BASE_URL; ?>admin/products.php">ล้าง</a><?php endif; ?></form></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>สินค้า</th><th>ชื่อและรหัส</th><th class="hide-mobile">หมวดหมู่</th><th>ราคา</th><th>สต็อก</th><th>แนะนำ</th><th></th></tr></thead><tbody>
    <?php if(!$products): ?><tr><td colspan="7" class="admin-empty"><i class="fa-solid fa-box-open"></i>ไม่พบสินค้า</td></tr><?php endif; ?>
    <?php foreach($products as $product): ?><tr><td><img class="admin-thumb" src="<?php echo e(productImageUrl($product['image_url'])); ?>" alt=""></td><td><span class="cell-title"><?php echo e($product['name']); ?></span><span class="cell-subtitle">SKU-<?php echo str_pad((string)$product['id'],4,'0',STR_PAD_LEFT); ?></span></td><td class="hide-mobile"><?php echo e($product['category_name'] ?? 'ทั่วไป'); ?></td><td><strong><?php echo formatCurrency($product['price']); ?></strong></td><td><?php if((int)$product['stock_quantity']===0): ?><span class="status-badge low-stock">หมด</span><?php elseif((int)$product['stock_quantity']<=5): ?><span class="status-badge low-stock"><?php echo (int)$product['stock_quantity']; ?> ชิ้น</span><?php else: ?><span class="status-badge in-stock"><?php echo (int)$product['stock_quantity']; ?> ชิ้น</span><?php endif; ?></td><td><?php echo $product['is_featured']?'<span class="status-badge pending">แนะนำ</span>':'—'; ?></td><td><div class="table-actions"><button type="button" onclick='openEditProductModal(<?php echo json_encode($product,JSON_HEX_APOS|JSON_HEX_TAG|JSON_UNESCAPED_UNICODE); ?>)' class="btn btn-outline" title="แก้ไข"><i class="fa-regular fa-pen-to-square"></i></button><form method="POST" onsubmit="return confirm('ยืนยันการลบสินค้านี้? การลบไม่สามารถย้อนกลับได้')"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>"><button class="btn btn-danger" title="ลบ"><i class="fa-regular fa-trash-can"></i></button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>

<div class="modal-overlay admin-modal" id="productFormModal"><div class="modal-content"><button type="button" class="modal-close" onclick="closeModal('productFormModal')">&times;</button><h2 id="productModalTitle" style="margin-bottom:18px">เพิ่มสินค้าใหม่</h2><form method="POST" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>"><input type="hidden" name="action" id="productAction" value="add"><input type="hidden" name="id" id="productId">
    <div class="admin-form-grid"><div class="admin-form-field full"><label for="productName">ชื่อสินค้า *</label><input id="productName" name="name" required maxlength="200"></div><div class="admin-form-field"><label for="productCategory">หมวดหมู่ *</label><select id="productCategory" name="category_id" required><?php foreach($categories as $category): ?><option value="<?php echo (int)$category['id']; ?>"><?php echo e($category['name']); ?></option><?php endforeach; ?></select></div><div class="admin-form-field"><label for="productPrice">ราคา (บาท) *</label><input type="number" min="0.01" step="0.01" id="productPrice" name="price" required></div><div class="admin-form-field"><label for="productStock">จำนวนสต็อก *</label><input type="number" min="0" id="productStock" name="stock_quantity" value="10" required></div><div class="admin-form-field"><label for="productImageUrl">ที่อยู่รูปเดิม</label><input id="productImageUrl" name="image_url" placeholder="assets/images/products/placeholder.svg"></div><div class="admin-form-field full"><label for="productImage">อัปโหลดรูปใหม่</label><input type="file" id="productImage" name="product_image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG หรือ WebP ขนาดไม่เกิน 2 MB</small></div><div class="admin-form-field full"><label for="productDesc">รายละเอียดสินค้า</label><textarea id="productDesc" name="description" rows="4"></textarea></div><div class="admin-form-field full"><label><input type="checkbox" name="is_featured" id="productFeatured" value="1"> แสดงเป็นสินค้าแนะนำบนหน้าร้าน</label></div></div><button class="btn btn-primary" style="width:100%" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึกข้อมูลสินค้า</button>
</form></div></div>
<script>
function openAddProductModal(){document.getElementById('productModalTitle').textContent='เพิ่มสินค้าใหม่';document.getElementById('productAction').value='add';document.getElementById('productId').value='';document.getElementById('productName').value='';document.getElementById('productPrice').value='';document.getElementById('productStock').value='10';document.getElementById('productImageUrl').value='';document.getElementById('productDesc').value='';document.getElementById('productFeatured').checked=false;openModal('productFormModal')}
function openEditProductModal(p){document.getElementById('productModalTitle').textContent='แก้ไข: '+p.name;document.getElementById('productAction').value='edit';document.getElementById('productId').value=p.id;document.getElementById('productName').value=p.name;document.getElementById('productCategory').value=p.category_id;document.getElementById('productPrice').value=p.price;document.getElementById('productStock').value=p.stock_quantity;document.getElementById('productImageUrl').value=p.image_url;document.getElementById('productDesc').value=p.description||'';document.getElementById('productFeatured').checked=Number(p.is_featured)===1;openModal('productFormModal')}
document.getElementById('productImageUrl').removeAttribute('name');document.getElementById('productImageUrl').closest('.admin-form-field').hidden=true;
if(new URLSearchParams(location.search).get('action')==='add')window.addEventListener('DOMContentLoaded',openAddProductModal);
</script>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
