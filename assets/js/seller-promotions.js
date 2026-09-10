(function () {
    'use strict';

    function initPromotionPrice() {
        const form = document.querySelector('form input[name="action"][value="sale"]')?.closest('form');
        if (!form || form.dataset.priceAutofillReady === '1') return;
        form.dataset.priceAutofillReady = '1';

        const product = form.querySelector('select[name="product_id"]');
        const originalPrice = form.querySelector('input[name="compare_at_price"]');
        if (!product || !originalPrice) return;

        let requestNumber = 0;
        async function fillOriginalPrice() {
            const id = Number(product.value || 0);
            if (!id) return;
            const embeddedPrice = Number(product.selectedOptions[0]?.dataset.originalPrice);
            if (Number.isFinite(embeddedPrice) && embeddedPrice > 0) {
                originalPrice.value = embeddedPrice.toFixed(2);
                originalPrice.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
            const currentRequest = ++requestNumber;
            originalPrice.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(BASE_URL + 'api/products.php?id=' + encodeURIComponent(id), {
                    headers: { Accept: 'application/json' }
                });
                const result = await response.json();
                if (currentRequest !== requestNumber) return;
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'ไม่พบราคาสินค้า');
                const price = Number(result.data.original_price ?? result.data.price);
                if (!Number.isFinite(price) || price <= 0) throw new Error('ราคาสินค้าไม่ถูกต้อง');
                originalPrice.value = price.toFixed(2);
                originalPrice.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (error) {
                if (typeof showToast === 'function') showToast(error.message || 'โหลดราคาสินค้าไม่สำเร็จ', 'error');
            } finally {
                if (currentRequest === requestNumber) originalPrice.removeAttribute('aria-busy');
            }
        }

        product.addEventListener('change', fillOriginalPrice);
        fillOriginalPrice();
    }

    initPromotionPrice();
    document.addEventListener('ajax:page-loaded', initPromotionPrice);
})();
