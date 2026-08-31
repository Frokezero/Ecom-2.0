<?php
function saveProductImageUpload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('กรุณาอัปโหลดรูปสินค้า');
    if (($file['size'] ?? 0) < 1 || $file['size'] > 3 * 1024 * 1024) throw new RuntimeException('รูปต้องมีขนาดไม่เกิน 3 MB');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($types[$mime])) throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG และ WebP');
    $size = @getimagesize($file['tmp_name']);
    if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 20000000) throw new RuntimeException('ขนาดหรือความละเอียดรูปภาพไม่ถูกต้อง');
    $directory = __DIR__ . '/../assets/images/products/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('ไม่สามารถเตรียมพื้นที่เก็บรูปได้');
    $name = bin2hex(random_bytes(24)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) throw new RuntimeException('ไม่สามารถบันทึกรูปภาพได้');
    return 'assets/images/products/uploads/' . $name;
}

function saveStoreImageUpload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('กรุณาอัปโหลดรูปภาพ');
    if (($file['size'] ?? 0) < 1 || $file['size'] > 3 * 1024 * 1024) throw new RuntimeException('รูปต้องมีขนาดไม่เกิน 3 MB');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($types[$mime])) throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG และ WebP');
    $size = @getimagesize($file['tmp_name']);
    if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 20000000) throw new RuntimeException('ขนาดหรือความละเอียดรูปภาพไม่ถูกต้อง');
    $directory = __DIR__ . '/../assets/images/stores/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('ไม่สามารถเตรียมพื้นที่เก็บรูปได้');
    $name = bin2hex(random_bytes(24)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) throw new RuntimeException('ไม่สามารถบันทึกรูปภาพได้');
    return 'assets/images/stores/uploads/' . $name;
}

function saveBannerImageUpload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('กรุณาอัปโหลดรูปแบนเนอร์');
    if (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('รูปแบนเนอร์ต้องมีขนาดไม่เกิน 5 MB');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($types[$mime])) throw new RuntimeException('รองรับเฉพาะ JPG, PNG และ WebP');
    $size=@getimagesize($file['tmp_name']); if(!$size || $size[0]<1 || $size[1]<1 || $size[0]*$size[1]>30000000) throw new RuntimeException('ขนาดรูปไม่ถูกต้อง');
    $directory=__DIR__.'/../assets/images/banners/uploads'; if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory)) throw new RuntimeException('สร้างโฟลเดอร์แบนเนอร์ไม่ได้');
    $name=bin2hex(random_bytes(24)).'.'.$types[$mime]; if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$name)) throw new RuntimeException('บันทึกรูปแบนเนอร์ไม่ได้');
    return 'assets/images/banners/uploads/'.$name;
}
function saveReturnEvidenceUpload(array $file): string {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))throw new RuntimeException('กรุณาแนบรูปหลักฐานสินค้า');
    if(($file['size']??0)<1||$file['size']>5*1024*1024)throw new RuntimeException('รูปหลักฐานต้องไม่เกิน 5 MB');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];if(!isset($types[$mime]))throw new RuntimeException('หลักฐานรองรับเฉพาะ JPG, PNG และ WebP');
    $size=@getimagesize($file['tmp_name']);if(!$size||$size[0]*$size[1]>20000000)throw new RuntimeException('รูปหลักฐานไม่ถูกต้อง');
    $directory=__DIR__.'/../assets/images/returns/uploads';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('เตรียมพื้นที่หลักฐานไม่สำเร็จ');$name=bin2hex(random_bytes(24)).'.'.$types[$mime];if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$name))throw new RuntimeException('บันทึกหลักฐานไม่สำเร็จ');return 'assets/images/returns/uploads/'.$name;
}
