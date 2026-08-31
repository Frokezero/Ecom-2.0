<?php
// includes/footer.php
?>
    </main>

    <nav class="mobile-bottom-nav" aria-label="เมนูมือถือ">
        <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF'])==='index.php'?'active':''; ?>"><i class="fa-solid fa-house"></i><span>หน้าหลัก</span></a>
        <a href="<?php echo BASE_URL; ?>products.php" class="<?php echo basename($_SERVER['PHP_SELF'])==='products.php'?'active':''; ?>"><i class="fa-solid fa-border-all"></i><span>หมวดสินค้า</span></a>
        <a href="<?php echo BASE_URL; ?>products.php" class="mobile-search-link"><i class="fa-solid fa-magnifying-glass"></i><span>ค้นหา</span></a>
        <a href="<?php echo BASE_URL; ?>cart.php" class="<?php echo basename($_SERVER['PHP_SELF'])==='cart.php'?'active':''; ?>"><i class="fa-solid fa-basket-shopping"></i><span>ตะกร้า</span><?php if ($cart_count > 0): ?><b><?php echo $cart_count; ?></b><?php endif; ?></a>
        <a href="<?php echo isLoggedIn() ? BASE_URL.'profile.php' : BASE_URL.'login.php'; ?>"><i class="fa-regular fa-user"></i><span>บัญชี</span></a>
    </nav>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <div class="brand-logo" style="color: white; margin-bottom: 12px;">
                        <i class="fa-solid fa-kitchen-set" style="color: #d98a55;"></i>
                        <span>KitchenMart</span>
                    </div>
                    <p style="font-size: 0.9rem; line-height: 1.6;">ศูนย์รวมอุปกรณ์ครัวที่คัดสรรเพื่อการใช้งานจริง จัดส่งทั่วประเทศ ชำระสะดวกด้วย PromptPay หรือเก็บเงินปลายทาง</p>
                </div>

                <div class="footer-col">
                    <h4>เมนูด่วน</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                        <li><a href="<?php echo BASE_URL; ?>index.php">หน้าแรก</a></li>
                        <li><a href="<?php echo BASE_URL; ?>products.php">สินค้าทั้งหมด</a></li>
                        <li><a href="<?php echo BASE_URL; ?>cart.php">ตะกร้าสินค้า</a></li>
                        <li><a href="<?php echo BASE_URL; ?>my-orders.php">ตรวจสอบสถานะคำสั่งซื้อ</a></li>
                        <li><a href="<?php echo BASE_URL; ?>support.php">ศูนย์ช่วยเหลือ</a></li>
                        <li><a href="<?php echo BASE_URL; ?>privacy.php">ความเป็นส่วนตัว</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>ช่องทางชำระเงินที่รองรับ</h4>
                    <div style="display: flex; gap: 12px; font-size: 1.8rem; margin-top: 8px;">
                        <i class="fa-solid fa-qrcode" title="PromptPay QR Code" style="color: #6366f1;"></i>
                        <i class="fa-solid fa-truck-ramp-box" title="เก็บเงินปลายทาง (COD)" style="color: #10b981;"></i>
                        <i class="fa-solid fa-credit-card" title="Online Payment" style="color: #f59e0b;"></i>
                    </div>
                    <p style="font-size: 0.85rem; margin-top: 12px;">รองรับ PromptPay QR Code จำลองอัตโนมัติ และบริการชำระเงินปลายทาง (COD)</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> · ร้านอุปกรณ์ครัวสำหรับทุกบ้าน</p>
            </div>
        </div>
    </footer>

    <div id="floatingPromo" class="floating-promo" hidden aria-live="polite"></div>
    <button id="chatbotLauncher" class="chatbot-launcher" type="button" aria-label="คุยกับผู้ช่วย KitchenMart" aria-expanded="false"><i class="fa-solid fa-comments"></i><b>AI</b></button>
    <section id="chatbotPanel" class="chatbot-panel" aria-label="ผู้ช่วย KitchenMart"><header class="chatbot-head"><i class="fa-solid fa-robot"></i><span><strong>ผู้ช่วย KitchenMart</strong><small>ค้นหาจากข้อมูลจริงของร้าน</small></span><button id="chatbotClose" type="button" aria-label="ปิดแชท">×</button></header><div id="chatbotMessages" class="chatbot-messages" aria-live="polite"></div><div class="chat-quick"><button data-chat-prompt="แนะนำสินค้าขายดี">สินค้าแนะนำ</button><button data-chat-prompt="ตรวจสอบคำสั่งซื้อล่าสุด">เช็กออเดอร์</button><button data-chat-prompt="ใช้คูปองอย่างไร">วิธีใช้คูปอง</button><button data-chat-prompt="คืนสินค้าอย่างไร">คืนสินค้า</button></div><button id="chatEscalate" class="chat-escalate" type="button"><i class="fa-solid fa-headset"></i> คุยกับเจ้าหน้าที่</button><form id="chatbotForm" class="chatbot-form"><input id="chatbotInput" maxlength="1000" autocomplete="off" placeholder="พิมพ์คำถามเกี่ยวกับสินค้า..."><button type="submit" aria-label="ส่งข้อความ"><i class="fa-solid fa-paper-plane"></i></button></form></section>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Quick View Modal Overlay -->
    <div class="modal-overlay" id="quickViewModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('quickViewModal')">&times;</button>
            <div id="quickViewContent">
                <p>กำลังโหลดข้อมูลสินค้า...</p>
            </div>
        </div>
    </div>

    <!-- Global App JS Script -->
    <script nonce="<?php echo e(cspNonce()); ?>">
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo getCsrfToken(); ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/address-picker.js?v=3"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/notifications.js?v=2"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/promotions.js?v=1"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/coupon-selector.js?v=2"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/image-crop.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/cart.js?v=4"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/payment.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/chatbot.js?v=1"></script>
</body>
</html>
