<?php
// การตั้งค่าหลัก: อ่านจาก environment และ config/local.php (ถ้ามี)
$localConfig = [];
$localFile = __DIR__ . '/local.php';
if (is_file($localFile)) {
    $localConfig = require $localFile;
    if (!is_array($localConfig)) $localConfig = [];
}

function appConfig(string $key, string $default = ''): string {
    global $localConfig;
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    return isset($localConfig[$key]) ? (string)$localConfig[$key] : $default;
}

function requestIsHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    if ($forwardedProto === 'https') return true;
    $cfVisitor = json_decode($_SERVER['HTTP_CF_VISITOR'] ?? '', true);
    return is_array($cfVisitor) && strtolower((string)($cfVisitor['scheme'] ?? '')) === 'https';
}

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = appConfig('SESSION_SAVE_PATH', '');
    if ($sessionPath !== '') ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (requestIsHttps()) ini_set('session.cookie_secure', '1');
    session_start();
}

date_default_timezone_set('Asia/Bangkok');
define('APP_NAME', appConfig('STORE_NAME', 'KitchenMart'));
define('STORE_TAGLINE', appConfig('STORE_TAGLINE', 'ครบทุกเรื่องครัว เพื่อทุกมื้อที่คุณรัก'));
define('PROMPTPAY_ID', appConfig('PROMPTPAY_ID', appConfig('PROMPTPAY_NUMBER', '')));
define('PROMPTPAY_NAME', appConfig('PROMPTPAY_NAME', 'KitchenMart Demo Store'));

$protocol = requestIsHttps() ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (substr($baseDir, -4) === '/api') $baseDir = substr($baseDir, 0, -4);
if (substr($baseDir, -6) === '/admin') $baseDir = substr($baseDir, 0, -6);
define('BASE_URL', $protocol . $host . ($baseDir ? $baseDir : '') . '/');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
