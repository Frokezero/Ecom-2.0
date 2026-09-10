/* assets/js/app.js - Toast & Modal Helper Functions */

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const normalizedMessage = String(message);
    const duplicate = Array.from(container.querySelectorAll('.toast')).find(item => item.dataset.message === normalizedMessage && item.dataset.type === type);
    if (duplicate) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.dataset.message = normalizedMessage;
    toast.dataset.type = type;
    
    const iconClass = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
    const icon = document.createElement('i');
    icon.className = iconClass;
    const text = document.createElement('span');
    text.textContent = normalizedMessage;
    toast.append(icon, document.createTextNode(' '), text);
    
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

function enhanceMallLabels(root = document) {
    const selectors = ['.product-title', '.purchase-panel h1', '.product-info h3', '.cart-table td:first-child span', '.cart-preview-item strong'];
    root.querySelectorAll(selectors.join(',')).forEach(element => {
        if (element.querySelector('.mall-name-badge')) return;
        const textNode = Array.from(element.childNodes).find(node => node.nodeType === Node.TEXT_NODE && /^\s*MALL\s+/i.test(node.textContent));
        if (!textNode) return;
        textNode.textContent = textNode.textContent.replace(/^\s*MALL\s+/i, '');
        const badge = document.createElement('span');
        badge.className = 'mall-name-badge';
        badge.textContent = 'MALL';
        element.insertBefore(badge, textNode);
    });
}

// Admin product modal handlers must be global because AJAX navigation replaces
// the page markup without executing inline scripts appended to the new page.
function openAddProductModal() {
    const modal = document.getElementById('productFormModal');
    if (!modal) return;
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value; };
    set('productModalTitle', 'เพิ่มสินค้าใหม่'); set('productAction', 'add'); set('productId', '');
    set('productName', ''); set('productPrice', ''); set('productStock', '10'); set('productImageUrl', ''); set('productDesc', '');
    const featured = document.getElementById('productFeatured'); if (featured) featured.checked = false;
    openModal('productFormModal');
}
function openEditProductModal(product) {
    const modal = document.getElementById('productFormModal');
    if (!modal || !product) return;
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value ?? ''; };
    set('productModalTitle', `แก้ไข: ${product.name || ''}`); set('productAction', 'edit'); set('productId', product.id);
    set('productName', product.name); set('productCategory', product.category_id); set('productPrice', product.price);
    set('productStock', product.stock_quantity); set('productImageUrl', product.image_url); set('productDesc', product.description);
    const featured = document.getElementById('productFeatured'); if (featured) featured.checked = Number(product.is_featured) === 1;
    openModal('productFormModal');
}

enhanceMallLabels();
document.addEventListener('ajax:page-loaded', () => enhanceMallLabels());

// Delegation keeps product media controls working after AJAX page replacement.
document.addEventListener('click', event => {
    const button = event.target.closest('[data-product-media]');
    if (!button) return;
    const gallery = button.closest('[data-product-gallery]');
    const image = document.getElementById('mainProductImage');
    const video = document.getElementById('mainProductVideo');
    if (button.dataset.productMedia === 'video') {
        if (!video) return;
        if (image) image.hidden = true;
        video.hidden = false;
        video.play().catch(() => {});
    } else {
        if (!image) return;
        if (button.dataset.src) image.src = button.dataset.src;
        image.hidden = false;
        if (video) { video.pause(); video.hidden = true; }
    }
    gallery?.querySelectorAll('[data-product-media]').forEach(item => item.classList.toggle('active', item === button));
});

const categoryMenuTrigger = document.querySelector('.category-menu-trigger');
if (categoryMenuTrigger) {
    categoryMenuTrigger.addEventListener('click', () => {
        const menu = categoryMenuTrigger.closest('.category-menu');
        const isOpen = menu.classList.toggle('menu-open');
        categoryMenuTrigger.setAttribute('aria-expanded', String(isOpen));
    });
}

// Quick View Product Modal Handler
async function quickViewProduct(productId) {
    const modalContent = document.getElementById('quickViewContent');
    if (!modalContent) return;

    modalContent.innerHTML = `<div style="text-align: center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i><p style="margin-top: 12px;">กำลังโหลดรายละเอียดสินค้า...</p></div>`;
    openModal('quickViewModal');

    try {
        const response = await fetch(`${BASE_URL}api/products.php?id=${productId}`);
        const res = await response.json();

        if (res.status === 'success') {
            const p = res.data;
            const safe = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
            const imageUrl = /^https?:\/\//i.test(p.image_url) ? p.image_url : BASE_URL + String(p.image_url).replace(/^\//, '');
            modalContent.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: center;">
                    <img src="${safe(imageUrl)}" alt="${safe(p.name)}" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 280px;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">${safe(p.category_name || 'หมวดหมู่ทั่วไป')}</span>
                        <h3 style="font-size: 1.3rem; margin: 6px 0 10px 0;">${safe(p.name)}</h3>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">${safe(p.description)}</p>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 20px;">฿${parseFloat(p.price).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                        <div style="display: flex; gap: 12px;">
                            <button class="btn btn-primary" onclick="addToCart(${p.id}, 1); closeModal('quickViewModal');" style="flex: 1;"><i class="fa-solid fa-cart-plus"></i> เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                </div>
            `;
            enhanceMallLabels(modalContent);
        } else {
            modalContent.innerHTML = `<p style="color: var(--danger); text-align: center; padding: 20px;">ไม่พบข้อมูลสินค้า</p>`;
        }
    } catch (err) {
        modalContent.innerHTML = `<p style="color: var(--danger); text-align: center; padding: 20px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>`;
    }
}
