        </main>
        <footer class="admin-footer">KitchenMart Admin · ข้อมูลอัปเดตจากระบบจริง</footer>
    </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<div id="floatingPromo" class="floating-promo" hidden></div>
<script>const BASE_URL=<?php echo json_encode(BASE_URL); ?>,CSRF_TOKEN=<?php echo json_encode(getCsrfToken()); ?>;</script>
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/notifications.js?v=1"></script>
<script src="<?php echo BASE_URL; ?>assets/js/promotions.js?v=1"></script>
<script src="<?php echo BASE_URL; ?>assets/js/image-crop.js"></script>
<script>
const adminSidebar=document.getElementById('adminSidebar'),adminSidebarScrim=document.getElementById('adminSidebarScrim');
function toggleAdminMenu(open){adminSidebar.classList.toggle('open',open);adminSidebarScrim.classList.toggle('open',open);document.body.classList.toggle('admin-menu-open',open)}
document.getElementById('adminMenuToggle').addEventListener('click',()=>toggleAdminMenu(true));adminSidebarScrim.addEventListener('click',()=>toggleAdminMenu(false));
</script>
</body>
</html>
