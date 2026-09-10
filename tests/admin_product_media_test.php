<?php
$root = dirname(__DIR__);
function adminMediaExpect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$page = file_get_contents($root . '/admin/products.php');
foreach ([
    'gallery_images[]',
    'multiple',
    'product_video',
    'primary_media_type',
    'saveProductVideoUpload',
    'saveProductImageUpload',
    'INSERT INTO product_images',
    'enforceRequestRate',
    'requireCsrf(false)',
    'auditLog',
    'recordSecurityEvent',
] as $needle) {
    adminMediaExpect(str_contains($page, $needle), 'Missing secured admin product media capability: ' . $needle);
}

adminMediaExpect(str_contains($page, "count(\$names) > 8"), 'Gallery upload count limit is missing');

$uploads = file_get_contents($root . '/includes/image_upload.php');
foreach (['video/mp4', 'video/webm', '50 * 1024 * 1024', 'is_uploaded_file', 'random_bytes(24)'] as $needle) {
    adminMediaExpect(str_contains($uploads, $needle), 'Missing secure product video validation: ' . $needle);
}

$detail = file_get_contents($root . '/product-detail.php');
adminMediaExpect(str_contains($detail, 'primary_media_type'), 'Product detail does not honor the primary media selection');

$sellerMedia = file_get_contents($root . '/seller-product-options.php');
foreach (['product_video', 'primary_media_type', 'saveProductVideoUpload', 'deleteManagedVideoUpload', 'protectApiMutation', 'requireCsrf'] as $needle) {
    adminMediaExpect(str_contains($sellerMedia, $needle), 'Seller media page missing: ' . $needle);
}
adminMediaExpect(str_contains($sellerMedia, "includes/security_monitor.php"), 'Seller media page does not load security helpers');
$sellerCreate = file_get_contents($root . '/seller-dashboard.php');
$sellerEdit = file_get_contents($root . '/my-store.php');
adminMediaExpect(str_contains($sellerCreate, 'product_video') && str_contains($sellerCreate, 'primary_media_type'), 'Seller create form lacks product video support');
adminMediaExpect(str_contains($sellerEdit, 'product_video') && str_contains($sellerEdit, 'primary_media_type'), 'Seller edit form lacks product video support');
$marketplaceApi = file_get_contents($root . '/api/marketplace.php');
adminMediaExpect(str_contains($marketplaceApi, 'security_monitor.php'), 'Marketplace API does not load security helpers');

echo "Admin product media tests passed\n";
