/* assets/js/app.js - Toast & Modal Helper Functions */

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const iconClass = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
    const icon = document.createElement('i');
    icon.className = iconClass;
    const text = document.createElement('span');
    text.textContent = String(message);
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
        } else {
            modalContent.innerHTML = `<p style="color: var(--danger); text-align: center; padding: 20px;">ไม่พบข้อมูลสินค้า</p>`;
        }
    } catch (err) {
        modalContent.innerHTML = `<p style="color: var(--danger); text-align: center; padding: 20px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>`;
    }
}
