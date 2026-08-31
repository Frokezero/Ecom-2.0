<?php
// product-detail.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$db = (new Database())->getConnection();
$product = null;
$reviews = [];
$review_count = 0;
$average_rating = 0.0;
$current_review = null;
$can_review = false;
$is_wishlisted = false;
$variants=[];$product_images=[];

if ($db && $id > 0) {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.approval_status='approved'");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product) {
        $variantStmt=$db->prepare('SELECT * FROM product_variants WHERE product_id=? AND is_active=1 ORDER BY id');$variantStmt->execute([$id]);$variants=$variantStmt->fetchAll();
        $imageStmt=$db->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order,id');$imageStmt->execute([$id]);$product_images=$imageStmt->fetchAll();
        $summary=$db->prepare('SELECT COUNT(*) review_count,COALESCE(AVG(rating),0) average_rating FROM product_reviews WHERE product_id=?');
        $summary->execute([$id]);$reviewSummary=$summary->fetch();$review_count=(int)$reviewSummary['review_count'];$average_rating=(float)$reviewSummary['average_rating'];
        $stmt=$db->prepare("SELECT r.*,u.username,u.full_name,(r.is_demo=0 AND EXISTS(SELECT 1 FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=r.product_id AND o.user_id=r.user_id AND o.order_status<>'cancelled' AND o.is_demo=0)) verified_purchase FROM product_reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.updated_at DESC");
        $stmt->execute([$id]);$reviews=$stmt->fetchAll();
        if(isLoggedIn()){
            foreach($reviews as $review){if((int)$review['user_id']===(int)$_SESSION['user_id']){$current_review=$review;break;}}
            $purchase=$db->prepare("SELECT 1 FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.user_id=? AND o.order_status<>'cancelled' LIMIT 1");
            $purchase->execute([$id,(int)$_SESSION['user_id']]);$can_review=(bool)$purchase->fetchColumn();
            $wish=$db->prepare('SELECT 1 FROM wishlists WHERE user_id=? AND product_id=?');$wish->execute([(int)$_SESSION['user_id'],$id]);$is_wishlisted=(bool)$wish->fetchColumn();
        }
    }
}

if (!$product) {
    header("Location: " . BASE_URL . "products.php");
    exit;
}

$page_title = $product['name'];
$page_description=trim(strip_tags((string)$product['description']))?:'ดูรายละเอียด '.$product['name'].' พร้อมราคา สต็อก และรีวิวจากผู้ซื้อ';
$page_image=productImageUrl($product['image_url']);$page_canonical=BASE_URL.'product-detail.php?id='.(int)$product['id'];$og_type='product';
$structured_data=['@context'=>'https://schema.org','@type'=>'Product','name'=>$product['name'],'description'=>$page_description,'image'=>[$page_image],'sku'=>'SKU-'.str_pad((string)$product['id'],4,'0',STR_PAD_LEFT),'category'=>$product['category_name']??'','offers'=>['@type'=>'Offer','url'=>$page_canonical,'priceCurrency'=>'THB','price'=>(string)$product['price'],'availability'=>(int)$product['stock_quantity']>0?'https://schema.org/InStock':'https://schema.org/OutOfStock','itemCondition'=>'https://schema.org/NewCondition']];if($review_count>0)$structured_data['aggregateRating']=['@type'=>'AggregateRating','ratingValue'=>round($average_rating,1),'reviewCount'=>$review_count];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container product-detail-page">
    <nav class="breadcrumbs" aria-label="เส้นทางนำทาง"><a href="<?php echo BASE_URL; ?>index.php">หน้าหลัก</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo BASE_URL; ?>products.php?category=<?php echo (int)$product['category_id']; ?>"><?php echo e($product['category_name'] ?? 'สินค้า'); ?></a><i class="fa-solid fa-chevron-right"></i><span><?php echo e($product['name']); ?></span></nav>

    <div class="product-detail-shell">
        <div class="product-gallery">
            <?php if ((int)$product['is_featured'] === 1): ?><span class="product-badge badge-soft">สินค้าแนะนำ</span><?php endif; ?>
            <img id="mainProductImage" src="<?php echo e(productImageUrl($product['image_url'])); ?>" alt="<?php echo e($product['name']); ?>"><?php if($product_images):?><div style="display:flex;gap:8px;margin-top:10px;overflow:auto"><?php foreach(array_merge([['image_url'=>$product['image_url']]],$product_images) as $image):?><button type="button" onclick="mainProductImage.src=this.dataset.src" data-src="<?php echo e(productImageUrl($image['image_url']));?>"><img src="<?php echo e(productImageUrl($image['image_url']));?>" alt="" style="width:56px;height:56px;object-fit:cover"></button><?php endforeach;?></div><?php endif;?>
            <div class="gallery-note"><i class="fa-solid fa-magnifying-glass-plus"></i> ภาพสินค้าจริงอาจแตกต่างเล็กน้อยตามหน้าจอ</div>
        </div>

        <div class="purchase-panel">
            <span class="detail-category"><?php echo e($product['category_name'] ?? 'ทั่วไป'); ?></span>
            <h1><?php echo e($product['name']); ?></h1>
            <a href="#reviews" class="detail-rating"><span aria-label="คะแนน <?php echo number_format($average_rating,1); ?> จาก 5"><?php for($star=1;$star<=5;$star++)echo $star<=round($average_rating)?'★':'☆'; ?></span><strong><?php echo number_format($average_rating,1); ?></strong><em><?php echo $review_count; ?> รีวิวจากผู้ซื้อ</em><i class="fa-solid fa-chevron-right"></i></a>
            <p class="detail-description"><?php echo e($product['description']); ?></p>
            <div class="detail-price"><small>ราคาสินค้า</small><strong><?php echo formatCurrency($product['price']); ?></strong><span>รวมภาษีมูลค่าเพิ่มแล้ว</span></div>

            <div class="fulfilment-card">
                <div><i class="fa-solid fa-truck-fast"></i><span><strong>จัดส่งทั่วประเทศ</strong><small><?php echo (float)$product['price'] >= 1000 ? 'สินค้านี้จัดส่งฟรี' : 'ส่งฟรีเมื่อยอดรวมครบ ฿1,000'; ?></small></span></div>
                <div><i class="fa-solid fa-box-open"></i><span><strong><?php echo (int)$product['stock_quantity'] > 0 ? 'พร้อมจัดส่ง' : 'สินค้าหมดชั่วคราว'; ?></strong><small>คงเหลือ <?php echo (int)$product['stock_quantity']; ?> ชิ้น</small></span></div>
            </div>

            <div class="purchase-actions">
                <?php if($variants):?><select id="variantSelect" style="padding:10px;min-width:180px" onchange="updateVariant()"><option value="">เลือกตัวเลือกสินค้า</option><?php foreach($variants as $v):?><option value="<?php echo (int)$v['id'];?>" data-price="<?php echo e($v['price']);?>" data-stock="<?php echo (int)$v['stock_quantity'];?>"><?php echo e($v['name'].' · '.$v['sku'].' · '.formatCurrency($v['price']));?></option><?php endforeach;?></select><?php endif;?>
                <div class="quantity-control" aria-label="เลือกจำนวน"><button type="button" onclick="changeQty(-1)" aria-label="ลดจำนวน">−</button><input type="number" id="detailQty" value="1" min="1" max="<?php echo (int)$product['stock_quantity']; ?>" aria-label="จำนวนสินค้า"><button type="button" onclick="changeQty(1)" aria-label="เพิ่มจำนวน">+</button></div>
                <button class="btn btn-primary detail-add-button" onclick="addSelectedVariant()" <?php echo (int)$product['stock_quantity']<1&&!$variants?'disabled':''; ?>><i class="fa-solid fa-basket-shopping"></i> เพิ่มลงตะกร้า</button>
                <button class="btn btn-outline" id="wishlistButton" type="button" onclick="toggleWishlist()"><i class="fa-<?php echo $is_wishlisted?'solid':'regular';?> fa-heart"></i> <?php echo $is_wishlisted?'บันทึกแล้ว':'รายการโปรด';?></button>
            </div>
            <div class="purchase-assurances"><span><i class="fa-solid fa-shield-halved"></i> ชำระเงินปลอดภัย</span><span><i class="fa-solid fa-qrcode"></i> PromptPay</span><span><i class="fa-solid fa-truck-ramp-box"></i> เก็บเงินปลายทาง</span></div>
        </div>
    </div>

    <section id="reviews" style="margin-top:48px;border-top:1px solid var(--border-color);padding-top:38px;">
        <div class="section-header"><div><p class="eyebrow">CUSTOMER REVIEWS</p><h2 class="section-title">รีวิวจากผู้ใช้จริง</h2></div><div style="text-align:right"><strong style="font:700 2rem Georgia,serif;color:var(--primary)"><?php echo number_format($average_rating,1); ?></strong><div style="color:#d96d2f;letter-spacing:2px"><?php for($star=1;$star<=5;$star++)echo $star<=round($average_rating)?'★':'☆'; ?></div><small style="color:var(--text-muted)"><?php echo $review_count; ?> รีวิว</small></div></div>

        <div style="display:grid;grid-template-columns:minmax(280px,.75fr) 1.25fr;gap:28px;align-items:start;">
            <div class="checkout-card">
                <?php if(isLoggedIn() && $can_review): ?>
                    <h3 style="margin-bottom:6px"><?php echo $current_review?'แก้ไขรีวิวของคุณ':'เขียนรีวิวสินค้า'; ?></h3>
                    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:18px">รีวิวได้หนึ่งครั้งต่อสินค้า และกลับมาแก้ไขได้ภายหลัง</p>
                    <form id="reviewForm" onsubmit="saveReview(event)">
                        <input type="hidden" name="action" value="save"><input type="hidden" name="product_id" value="<?php echo $id; ?>"><input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                        <fieldset style="border:0;margin-bottom:16px"><legend style="font-weight:600;margin-bottom:8px">ให้คะแนน</legend><div class="rating-input"><?php for($star=5;$star>=1;$star--): ?><input type="radio" id="rating<?php echo $star; ?>" name="rating" value="<?php echo $star; ?>" <?php echo (int)($current_review['rating']??0)===$star?'checked':''; ?> required><label for="rating<?php echo $star; ?>" title="<?php echo $star; ?> ดาว">★</label><?php endfor; ?></div></fieldset>
                        <label for="reviewComment" style="display:block;font-weight:600;margin-bottom:7px">ความคิดเห็น</label><textarea id="reviewComment" name="comment" minlength="5" maxlength="1000" rows="5" required style="width:100%;padding:11px;margin-bottom:14px" placeholder="บอกเล่าประสบการณ์ใช้งานสินค้า..."><?php echo e($current_review['comment']??''); ?></textarea>
                        <div style="display:flex;gap:8px"><button type="submit" class="btn btn-primary" id="reviewSubmitBtn"><?php echo $current_review?'บันทึกการแก้ไข':'ส่งรีวิว'; ?></button><?php if($current_review): ?><button type="button" class="btn btn-danger" onclick="deleteReview()">ลบรีวิว</button><?php endif; ?></div>
                    </form>
                <?php elseif(isLoggedIn()): ?><h3>รีวิวได้หลังสั่งซื้อ</h3><p style="color:var(--text-muted);margin:8px 0 18px">คุณต้องสั่งซื้อสินค้าชิ้นนี้ก่อน จึงจะให้คะแนนและเขียนรีวิวได้</p><a href="#" onclick="addToCart(<?php echo $id; ?>,1);return false" class="btn btn-primary">เพิ่มลงตะกร้า</a>
                <?php else: ?><h3>เขียนรีวิวสินค้า</h3><p style="color:var(--text-muted);margin:8px 0 18px">กรุณาเข้าสู่ระบบ และสั่งซื้อสินค้าชิ้นนี้ก่อนเขียนรีวิว</p><a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary">เข้าสู่ระบบ</a><?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php if(!$reviews): ?><div style="background:#fff;border:1px solid var(--border-color);padding:34px;text-align:center;color:var(--text-muted)">ยังไม่มีรีวิว เป็นคนแรกที่รีวิวสินค้านี้</div><?php endif; ?>
                <?php foreach($reviews as $review): ?><article style="background:#fff;border:1px solid var(--border-color);padding:22px"><header style="display:flex;justify-content:space-between;gap:14px;margin-bottom:10px"><div><strong><?php echo e($review['full_name']?:$review['username']); ?></strong><?php if($review['verified_purchase']): ?><span style="font-size:.72rem;color:var(--primary);background:var(--primary-light);padding:3px 7px;margin-left:7px"><i class="fa-solid fa-circle-check"></i> ซื้อแล้ว</span><?php endif; ?><div style="color:#d96d2f;letter-spacing:2px;margin-top:3px" aria-label="<?php echo (int)$review['rating']; ?> ดาว"><?php echo str_repeat('★',(int)$review['rating']).str_repeat('☆',5-(int)$review['rating']); ?></div></div><time style="font-size:.78rem;color:var(--text-muted)"><?php echo date('d/m/Y',strtotime($review['updated_at'])); ?></time></header><p style="white-space:pre-line"><?php echo e($review['comment']); ?></p></article><?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<script nonce="<?php echo e(cspNonce()); ?>">
function addSelectedVariant(){const select=document.getElementById('variantSelect');if(select&&!select.value){showToast('กรุณาเลือกตัวเลือกสินค้า','error');return}addToCart(<?php echo (int)$product['id'];?>,parseInt(document.getElementById('detailQty').value),select?parseInt(select.value):0)}function updateVariant(){const option=document.getElementById('variantSelect')?.selectedOptions[0];if(!option?.value)return;document.querySelector('.detail-price strong').textContent=new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB'}).format(option.dataset.price);document.getElementById('detailQty').max=option.dataset.stock;}
let wishlistSaved=<?php echo $is_wishlisted?'true':'false';?>;async function toggleWishlist(){<?php if(!isLoggedIn()):?>location.href=`${BASE_URL}login.php`;return;<?php else:?>const d=new FormData();d.append('action',wishlistSaved?'remove':'add');d.append('product_id','<?php echo $id;?>');d.append('csrf_token',CSRF_TOKEN);const r=await fetch(`${BASE_URL}api/wishlist.php`,{method:'POST',body:d}),j=await r.json();if(j.status==='success'){wishlistSaved=j.data.saved;document.getElementById('wishlistButton').innerHTML=`<i class="fa-${wishlistSaved?'solid':'regular'} fa-heart"></i> ${wishlistSaved?'บันทึกแล้ว':'รายการโปรด'}`}showToast(j.message,j.status==='success'?'success':'error');<?php endif;?>}
function changeQty(delta) {
    const input = document.getElementById('detailQty');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > <?php echo $product['stock_quantity']; ?>) val = <?php echo $product['stock_quantity']; ?>;
    input.value = val;
}
async function reviewRequest(action,form=null){const data=form?new FormData(form):new FormData();if(!form){data.append('action',action);data.append('product_id','<?php echo $id; ?>');data.append('csrf_token',CSRF_TOKEN)}const response=await fetch(`${BASE_URL}api/reviews.php`,{method:'POST',body:data});const result=await response.json();if(!response.ok||result.status!=='success')throw new Error(result.message||'ไม่สามารถบันทึกรีวิวได้');return result}
async function saveReview(event){event.preventDefault();const btn=document.getElementById('reviewSubmitBtn');btn.disabled=true;try{const result=await reviewRequest('save',event.target);showToast(result.message,'success');setTimeout(()=>location.reload(),500)}catch(error){showToast(error.message,'error');btn.disabled=false}}
async function deleteReview(){if(!confirm('ยืนยันลบรีวิวของคุณ?'))return;try{const result=await reviewRequest('delete');showToast(result.message,'success');setTimeout(()=>location.reload(),500)}catch(error){showToast(error.message,'error')}}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
