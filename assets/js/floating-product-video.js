(function () {
    'use strict';

    function mountProductVideo() {
        const link = document.querySelector('.product-video-link:not([data-video-mounted])');
        if (!link) return;

        link.dataset.videoMounted = '1';
        const src = link.href;
        const shell = document.createElement('aside');
        shell.className = 'floating-product-video';
        shell.innerHTML = '<div class="floating-video-head"><strong>วิดีโอสินค้า</strong><button type="button" class="floating-video-close" aria-label="ปิดวิดีโอ">×</button></div><div class="floating-video-frame"><button type="button" class="floating-video-play" aria-label="เล่นวิดีโอ"><i class="fa-solid fa-play"></i></button></div><div class="floating-video-actions"><button type="button" class="floating-video-mute">เปิดเสียง</button><a target="_blank" rel="noopener" class="floating-video-open">ขยายวิดีโอ</a></div>';

        const frame = shell.querySelector('.floating-video-frame');
        const play = shell.querySelector('.floating-video-play');
        const mute = shell.querySelector('.floating-video-mute');
        const open = shell.querySelector('.floating-video-open');
        const close = shell.querySelector('.floating-video-close');
        const youtube = src.match(/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
        let media;

        if (youtube) {
            const videoId = youtube[1];
            media = document.createElement('iframe');
            media.title = 'วิดีโอสินค้า';
            media.src = 'https://www.youtube-nocookie.com/embed/' + videoId
                + '?autoplay=1&mute=1&playsinline=1&loop=1&playlist=' + videoId
                + '&controls=1&rel=0';
            media.allow = 'autoplay; encrypted-media; picture-in-picture';
            media.allowFullscreen = true;
            play.hidden = true;
            mute.hidden = true;
            frame.append(media);
        } else if (/\.(mp4|webm|ogg)(?:[?#]|$)/i.test(src)) {
            media = document.createElement('video');
            media.src = src;
            media.autoplay = true;
            media.muted = true;
            media.loop = true;
            media.playsInline = true;
            media.controls = true;
            frame.append(media);
            media.addEventListener('loadedmetadata', function () {
                shell.classList.toggle('portrait', media.videoHeight > media.videoWidth);
            });
            media.addEventListener('playing', function () {
                play.hidden = true;
            });
            media.play().catch(function () {
                play.hidden = false;
            });
        } else {
            open.href = src;
            link.replaceWith(shell);
            return;
        }

        open.href = src;
        play.addEventListener('click', function () {
            if (media.tagName === 'VIDEO') media.play().catch(function () {});
        });
        mute.addEventListener('click', function () {
            if (media.tagName !== 'VIDEO') return;
            media.muted = !media.muted;
            mute.textContent = media.muted ? 'เปิดเสียง' : 'ปิดเสียง';
        });
        close.addEventListener('click', function () {
            media.src = '';
            shell.remove();
        });
        link.replaceWith(shell);
    }

    mountProductVideo();
    new MutationObserver(mountProductVideo).observe(document.body, {childList: true, subtree: true});
})();
