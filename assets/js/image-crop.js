(() => {
  const maxBytes = 3 * 1024 * 1024;
  const imageTypes = /^(image\/jpeg|image\/png|image\/webp)$/;

  function attachCropper(input) {
    if (input.dataset.cropReady) return;
    input.dataset.cropReady = '1';
    const form = input.closest('form');
    if (!form) return;
    const ratio = Math.max(0.2, Math.min(5, Number(input.dataset.cropRatio) || 1));
    const cropWidth = 840;
    const cropHeight = Math.round(cropWidth / ratio);
    const panel = document.createElement('div');
    panel.className = 'image-cropper';
    panel.hidden = true;
    panel.innerHTML = '<div class="image-cropper-head"><strong>ครอปรูปภาพ</strong><small>ลากรูปเพื่อจัดตำแหน่ง และเลื่อนเพื่อซูม</small></div><canvas width="' + cropWidth + '" height="' + cropHeight + '" aria-label="ตัวอย่างรูปที่ครอป"></canvas><label class="image-cropper-zoom">ซูม <input type="range" min="1" max="3" step="0.01" value="1"></label><button type="button">จัดกึ่งกลางใหม่</button>';
    input.after(panel);

    const canvas = panel.querySelector('canvas');
    const context = canvas.getContext('2d');
    const zoom = panel.querySelector('input');
    const reset = panel.querySelector('button');
    let image = null, baseScale = 1, scale = 1, offsetX = 0, offsetY = 0, dragging = false, dragStartX = 0, dragStartY = 0;
    const draw = () => {
      if (!image) return;
      context.fillStyle = '#f1f0eb';
      context.fillRect(0, 0, cropWidth, cropHeight);
      context.drawImage(image, offsetX, offsetY, image.width * baseScale * scale, image.height * baseScale * scale);
    };
    const keepCovered = () => {
      const width = image.width * baseScale * scale, height = image.height * baseScale * scale;
      offsetX = Math.min(0, Math.max(cropWidth - width, offsetX));
      offsetY = Math.min(0, Math.max(cropHeight - height, offsetY));
    };
    const center = () => {
      if (!image) return;
      baseScale = Math.max(cropWidth / image.width, cropHeight / image.height);
      scale = 1; zoom.value = '1';
      offsetX = (cropWidth - image.width * baseScale) / 2;
      offsetY = (cropHeight - image.height * baseScale) / 2;
      draw();
    };
    const point = event => {
      const box = canvas.getBoundingClientRect();
      return { x: (event.clientX - box.left) * (cropWidth / box.width), y: (event.clientY - box.top) * (cropHeight / box.height) };
    };

    input.addEventListener('change', () => {
      input.dataset.cropApplied = '';
      const file = input.files && input.files[0];
      if (!file || !imageTypes.test(file.type) || file.size > maxBytes) { image = null; panel.hidden = true; return; }
      const reader = new FileReader();
      reader.onload = () => {
        image = new Image();
        image.onload = () => { panel.hidden = false; center(); };
        image.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
    zoom.addEventListener('input', () => {
      if (!image) return;
      const previous = scale; scale = Number(zoom.value);
      offsetX -= image.width * baseScale * (scale - previous) / 2;
      offsetY -= image.height * baseScale * (scale - previous) / 2;
      keepCovered(); draw();
    });
    canvas.addEventListener('pointerdown', event => {
      if (!image) return;
      dragging = true;
      const current = point(event);
      dragStartX = current.x - offsetX; dragStartY = current.y - offsetY;
      canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener('pointermove', event => {
      if (!dragging || !image) return;
      const current = point(event);
      offsetX = current.x - dragStartX; offsetY = current.y - dragStartY;
      keepCovered(); draw();
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => canvas.addEventListener(type, () => { dragging = false; }));
    reset.addEventListener('click', center);
    form.addEventListener('submit', event => {
      if (!image || !input.files || !input.files.length || input.dataset.cropApplied) return;
      event.preventDefault();
      canvas.toBlob(blob => {
        if (!blob) { input.setCustomValidity('ไม่สามารถครอปรูปได้ กรุณาลองเลือกรูปใหม่'); input.reportValidity(); return; }
        const transfer = new DataTransfer();
        transfer.items.add(new File([blob], 'image-crop.jpg', { type: 'image/jpeg' }));
        input.files = transfer.files; input.dataset.cropApplied = '1';
        form.requestSubmit();
      }, 'image/jpeg', 0.9);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"][name="product_image"], input[type="file"][data-image-crop]').forEach(attachCropper);
  });
})();
