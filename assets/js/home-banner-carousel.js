(function () {
    'use strict';
    const carousel = document.querySelector('[data-home-banner]');
    if (!carousel) return;
    const track = carousel.querySelector('[data-banner-track]');
    const slides = [...track.querySelectorAll('.home-banner-slide')];
    const mobileImages = {
        'flash-sale-home-v1.png': 'flash-sale-home-mobile-v1.png',
        'category-cookware-v1.png': 'category-cookware-mobile-v1.png',
        'category-appliances-v1.png': 'category-appliances-mobile-v1.png',
        'category-baking-v1.png': 'category-baking-mobile-v1.png'
    };
    slides.forEach(slide => {
        const image = slide.querySelector('img');
        if (!image) return;
        image.dataset.desktopSrc = image.src;
        const desktopName = image.src.split('/').pop();
        image.dataset.mobileSrc = image.src.replace(desktopName, mobileImages[desktopName] || desktopName);
    });
    function selectResponsiveImages() {
        const mobile = matchMedia('(max-width:720px)').matches;
        slides.forEach(slide => { const image=slide.querySelector('img');if(image)image.src=mobile?image.dataset.mobileSrc:image.dataset.desktopSrc; });
    }
    selectResponsiveImages();
    matchMedia('(max-width:720px)').addEventListener('change', selectResponsiveImages);
    const dotsBox = carousel.querySelector('[data-banner-dots]');
    const previous = carousel.querySelector('[data-banner-prev]');
    const next = carousel.querySelector('[data-banner-next]');
    if (slides.length < 2) { previous.hidden = next.hidden = true; return; }
    let active = 0;
    let timer;
    const dots = slides.map((slide, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';dot.setAttribute('aria-label', 'ดูแบนเนอร์ ' + (index + 1));
        dot.addEventListener('click', () => go(index, true));dotsBox.append(dot);return dot;
    });
    function go(index, manual) {
        active = (index + slides.length) % slides.length;
        track.style.transform = `translateX(-${active * 100}%)`;
        dots.forEach((dot, i) => { dot.classList.toggle('active', i === active);dot.setAttribute('aria-current', i === active ? 'true' : 'false'); });
        if (manual) restart();
    }
    function restart() { clearInterval(timer);timer = setInterval(() => go(active + 1, false), 5000); }
    previous.addEventListener('click', () => go(active - 1, true));next.addEventListener('click', () => go(active + 1, true));
    carousel.addEventListener('mouseenter', () => clearInterval(timer));carousel.addEventListener('mouseleave', restart);
    carousel.addEventListener('focusin', () => clearInterval(timer));carousel.addEventListener('focusout', restart);
    let startX = 0;carousel.addEventListener('touchstart', event => { startX = event.touches[0].clientX;clearInterval(timer); }, { passive: true });carousel.addEventListener('touchend', event => { const distance = event.changedTouches[0].clientX - startX;if(Math.abs(distance)>45)go(active+(distance<0?1:-1),true);else restart(); }, { passive: true });
    document.addEventListener('visibilitychange', () => document.hidden ? clearInterval(timer) : restart());go(0, false);restart();
})();
