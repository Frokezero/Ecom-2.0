        </main>
        <footer class="admin-footer">KitchenMart Admin · ข้อมูลอัปเดตจากระบบจริง</footer>
    </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<div id="floatingPromo" class="floating-promo" hidden></div>
<script nonce="<?php echo e(cspNonce()); ?>">const BASE_URL=<?php echo json_encode(BASE_URL); ?>,CSRF_TOKEN=<?php echo json_encode(getCsrfToken()); ?>;</script>
<script src="<?php echo BASE_URL; ?>assets/js/app.js?v=6"></script>
<script src="<?php echo BASE_URL; ?>assets/js/notifications.js?v=1"></script>
<script src="<?php echo BASE_URL; ?>assets/js/promotions.js?v=1"></script>
<script src="<?php echo BASE_URL; ?>assets/js/image-crop.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/navigation.js?v=1"></script>
<?php if(($admin_page??'')==='mall-promotions.php'):?><script src="<?php echo BASE_URL;?>assets/js/seller-promotions.js?v=1"></script><?php endif;?>
<script nonce="<?php echo e(cspNonce()); ?>">
const adminSidebar=document.getElementById('adminSidebar'),adminSidebarScrim=document.getElementById('adminSidebarScrim');
function toggleAdminMenu(open){adminSidebar.classList.toggle('open',open);adminSidebarScrim.classList.toggle('open',open);document.body.classList.toggle('admin-menu-open',open)}
document.getElementById('adminMenuToggle').addEventListener('click',()=>toggleAdminMenu(true));adminSidebarScrim.addEventListener('click',()=>toggleAdminMenu(false));
<?php if(($admin_page??'')==='sellers.php'):?>document.querySelectorAll('.admin-table tbody tr').forEach(row=>{const id=row.querySelector('input[name="user_id"]')?.value,title=row.querySelector('.cell-title');if(id&&title&&!title.closest('a')){const link=document.createElement('a');link.href=BASE_URL+'admin/store-detail.php?id='+encodeURIComponent(id);link.title='ดูรายละเอียดร้านค้า';title.replaceWith(link);link.append(title)}});<?php endif;?>
</script>
</body>
</html>
