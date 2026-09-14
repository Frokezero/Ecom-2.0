<?php
$page_title = 'ร้านค้าในระบบ';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$status = (string)($_GET['status'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));
$where = " WHERE sp.status='approved'";
$params = [];
if (in_array($status, ['active','suspended','closed'], true)) {$where .= ' AND sp.store_status=?';$params[] = $status;}
if ($q !== '') {$where .= ' AND (sp.shop_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';$params = array_merge($params, array_fill(0, 3, '%'.$q.'%'));}
$sql = "SELECT sp.*,u.username,u.email,c.name category_name,COUNT(DISTINCT p.id) products,COUNT(DISTINCT o.id) orders,COALESCE(SUM(oi.subtotal),0) sales FROM seller_profiles sp JOIN users u ON u.id=sp.user_id JOIN categories c ON c.id=sp.primary_category_id LEFT JOIN products p ON p.seller_id=sp.user_id LEFT JOIN order_items oi ON oi.product_id=p.id LEFT JOIN orders o ON o.id=oi.order_id AND o.order_status<>'cancelled' $where GROUP BY sp.user_id,u.username,u.email,c.name ORDER BY FIELD(sp.store_status,'suspended','active','closed'),sp.updated_at DESC";
$stmt = $db->prepare($sql);$stmt->execute($params);$stores = $stmt->fetchAll();
$storeProducts = [];$sellerIds = array_map(static fn(array $store): int => (int)$store['user_id'], $stores);
if ($sellerIds) {$placeholders = implode(',', array_fill(0, count($sellerIds), '?'));$productStmt = $db->prepare("SELECT id,seller_id,name,image_url,price,stock_quantity,approval_status FROM products WHERE seller_id IN ($placeholders) ORDER BY seller_id,id DESC");$productStmt->execute($sellerIds);foreach ($productStmt->fetchAll() as $product) $storeProducts[(int)$product['seller_id']][] = $product;}
$summary = $db->query("SELECT COUNT(*) total,SUM(store_status='active') active,SUM(store_status='suspended') suspended,SUM(store_status='closed') closed FROM seller_profiles WHERE status='approved'")->fetch();
$productStatusLabels = ['pending'=>'รอตรวจ','approved'=>'อนุมัติแล้ว','rejected'=>'ปฏิเสธ'];
require_once __DIR__ . '/../includes/admin_header.php';
?>
<header class="admin-page-header"><div><p class="eyebrow">MARKETPLACE BY STORE</p><h1>ร้านค้าและสินค้าผู้ขาย</h1><p>แยกสินค้าตามร้าน เพื่อดูได้ทันทีว่าแต่ละร้านมีสินค้าอะไรและอยู่ในสถานะใด</p></div><a class="btn btn-outline" href="<?php echo BASE_URL; ?>admin/products.php"><i class="fa-solid fa-boxes-stacked"></i> สินค้าของเว็บไซต์</a></header>
<div class="admin-stats-grid"><article><small>ร้านทั้งหมด</small><strong><?php echo (int)$summary['total'];?></strong></article><article><small>เปิดขาย</small><strong><?php echo (int)$summary['active'];?></strong></article><article><small>ระงับ</small><strong><?php echo (int)$summary['suspended'];?></strong></article><article><small>ปิดร้าน</small><strong><?php echo (int)$summary['closed'];?></strong></article></div>
<section class="admin-panel">
 <form method="get" class="admin-filter-form store-list-filter"><input name="q" value="<?php echo e($q);?>" placeholder="ชื่อร้าน ผู้ขาย หรืออีเมล"><select name="status"><option value="">ทุกสถานะ</option><?php foreach(['active'=>'เปิดขาย','suspended'=>'ระงับ','closed'=>'ปิดร้าน'] as $key=>$label):?><option value="<?php echo $key;?>" <?php echo $status===$key?'selected':'';?>><?php echo $label;?></option><?php endforeach;?></select><button class="btn btn-primary">ค้นหา</button><?php if($q!==''||$status!==''):?><a class="btn btn-outline" href="stores.php">ล้าง</a><?php endif;?></form>
 <div class="admin-table-wrap"><table class="admin-table store-products-table"><thead><tr><th>ร้าน/เจ้าของ</th><th>สินค้าในร้าน</th><th>ออเดอร์</th><th>ยอดขาย</th><th>สถานะร้าน</th><th></th></tr></thead><tbody>
 <?php if(!$stores):?><tr><td colspan="6" class="admin-empty">ไม่พบร้านค้า</td></tr><?php endif;?>
 <?php foreach($stores as $store):$products=$storeProducts[(int)$store['user_id']]??[];?><tr><td><strong><?php echo e($store['shop_name']);?></strong><small><?php echo e($store['username'].' · '.$store['email']);?></small><small><?php echo e($store['category_name']);?></small></td>
 <td><div class="store-product-preview"><?php if(!$products):?><span class="store-no-products">ยังไม่มีสินค้า</span><?php else:foreach(array_slice($products,0,4) as $product):?><a href="<?php echo BASE_URL;?>admin/seller-products.php?id=<?php echo (int)$product['id'];?>"><img src="<?php echo e(productImageUrl($product['image_url']));?>" alt=""><span><strong><?php echo e($product['name']);?></strong><small><?php echo formatCurrency($product['price']);?> · <?php echo (int)$product['stock_quantity'];?> ชิ้น · <?php echo e($productStatusLabels[$product['approval_status']]??$product['approval_status']);?></small></span></a><?php endforeach;if(count($products)>4):?><small class="store-more-products">และอีก <?php echo count($products)-4;?> รายการ</small><?php endif;endif;?></div></td>
 <td><?php echo (int)$store['orders'];?></td><td><?php echo formatCurrency($store['sales']);?></td><td><span class="status-badge <?php echo $store['store_status']==='active'?'paid':'cancelled';?>"><?php echo e($store['store_status']);?></span></td><td><div class="table-actions"><a class="btn btn-primary" href="<?php echo BASE_URL;?>admin/store-detail.php?id=<?php echo (int)$store['user_id'];?>#products">ดูสินค้าทั้งร้าน (<?php echo count($products);?>)</a></div></td></tr><?php endforeach;?></tbody></table></div>
</section>
<?php require_once __DIR__.'/../includes/admin_footer.php';?>
