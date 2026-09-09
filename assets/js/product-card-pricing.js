(function () {
    'use strict';
    const cards = [...document.querySelectorAll('.product-card')];
    if (!cards.length) return;
    const money = value => new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(value);
    fetch(BASE_URL + 'api/products.php', { headers: { Accept: 'application/json' } })
        .then(response => response.json())
        .then(result => {
            if (result.status !== 'success' || !Array.isArray(result.data)) return;
            const products = new Map(result.data.map(product => [String(product.id), product]));
            cards.forEach(card => {
                const link = card.querySelector('a[href*="product-detail.php?id="]');
                const priceNode = card.querySelector('.product-price');
                if (!link || !priceNode) return;
                const id = new URL(link.href, location.href).searchParams.get('id');
                const product = products.get(String(id));
                if (!product || !product.sale_price) return;
                const now = Date.now();
                const starts = product.sale_starts_at ? new Date(product.sale_starts_at.replace(' ', 'T')).getTime() : 0;
                const ends = product.sale_ends_at ? new Date(product.sale_ends_at.replace(' ', 'T')).getTime() : 0;
                if ((starts && starts > now) || (ends && ends <= now)) return;
                const sale = Number(product.sale_price);
                const regular = Number(product.compare_at_price || product.price);
                if (!Number.isFinite(sale) || !Number.isFinite(regular) || sale <= 0 || regular <= sale) return;
                const percent = Math.max(1, Math.min(99, Math.round((1 - sale / regular) * 100)));
                const wrapper = document.createElement('span');
                wrapper.className = 'card-sale-price';
                const current = document.createElement('strong');current.className = 'product-price';current.textContent = money(sale);
                const detail = document.createElement('span');
                const old = document.createElement('del');old.textContent = money(regular);
                const badge = document.createElement('mark');badge.textContent = '-' + percent + '%';
                detail.append(old, badge);wrapper.append(current, detail);priceNode.replaceWith(wrapper);
                card.classList.add('is-on-sale');
            });
        })
        .catch(() => {});
})();
