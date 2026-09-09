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

function appIpInCidr(string $ip, string $cidr): bool {
    $parts = explode('/', trim($cidr), 2);
    $ipBin = @inet_pton($ip);
    $networkBin = @inet_pton($parts[0] ?? '');
    if ($ipBin === false || $networkBin === false || strlen($ipBin) !== strlen($networkBin)) return false;
    $maximumBits = strlen($ipBin) * 8;
    $bits = isset($parts[1]) ? (int)$parts[1] : $maximumBits;
    if ($bits < 0 || $bits > $maximumBits) return false;
    $bytes = intdiv($bits, 8);
    $remaining = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($networkBin, 0, $bytes)) return false;
    if ($remaining === 0) return true;
    $mask = (0xff << (8 - $remaining)) & 0xff;
    return (ord($ipBin[$bytes]) & $mask) === (ord($networkBin[$bytes]) & $mask);
}

function appRequestFromTrustedProxy(): bool {
    if (appConfig('TRUST_CLOUDFLARE', '0') !== '1') return false;
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    foreach (array_filter(array_map('trim', explode(',', appConfig('TRUSTED_PROXY_CIDRS', '')))) as $cidr) {
        if (appIpInCidr($remote, $cidr)) return true;
    }
    return false;
}

function appClientIp(): string {
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (appRequestFromTrustedProxy()) {
        $forwarded = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) return $forwarded;
    }
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : 'unknown';
}

function appCountryCode(): ?string {
    if (!appRequestFromTrustedProxy()) return null;
    $country = strtoupper(trim((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
}

function requestIsHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!appRequestFromTrustedProxy()) return false;
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    if ($forwardedProto === 'https') return true;
    $cfVisitor = json_decode($_SERVER['HTTP_CF_VISITOR'] ?? '', true);
    return is_array($cfVisitor) && strtolower((string)($cfVisitor['scheme'] ?? '')) === 'https';
}
function cspNonce():string{static $nonce='';if($nonce==='')$nonce=base64_encode(random_bytes(18));return $nonce;}
function appRequestId():string{static $id='';if($id==='')$id=substr(bin2hex(random_bytes(16)),0,24);return $id;}

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = appConfig('SESSION_SAVE_PATH', '');
    // XAMPP's global temp directory is often not writable when the project is shared.
    // Prefer an app-local directory so sessions work consistently behind Cloudflare.
    $fallbackSessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.runtime-sessions';
    if ($sessionPath === '' || !is_dir($sessionPath) || !is_writable($sessionPath)) $sessionPath = $fallbackSessionPath;
    if (!is_dir($sessionPath)) @mkdir($sessionPath, 0700, true);
    if (is_dir($sessionPath) && is_writable($sessionPath)) ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (requestIsHttps()) ini_set('session.cookie_secure', '1');
    session_start();
}
if(PHP_SAPI!=='cli'&&!defined('CSP_NONCE_BUFFER')){define('CSP_NONCE_BUFFER',true);ob_start(static function(string $output):string{return preg_replace('/<script(?![^>]*\bnonce=)([^>]*)>/i','<script nonce="'.cspNonce().'"$1>',$output)??$output;});}

date_default_timezone_set('Asia/Bangkok');
if(appConfig('APP_ENV','development')==='production'){ini_set('display_errors','0');ini_set('log_errors','1');}
if (!headers_sent()) {
    header('X-Request-ID: '.appRequestId());
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    $csp = "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; frame-src 'self' https://www.youtube-nocookie.com; form-action 'self'; object-src 'none'; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com data:; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; script-src 'self' 'nonce-".cspNonce()."'; script-src-attr 'unsafe-inline'; connect-src 'self' https://raw.githubusercontent.com";
    header((appConfig('CSP_REPORT_ONLY', '0') === '1' ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ') . $csp);
    if (requestIsHttps()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
define('APP_NAME', appConfig('STORE_NAME', 'KitchenMart'));
define('STORE_TAGLINE', appConfig('STORE_TAGLINE', 'ครบทุกเรื่องครัว เพื่อทุกมื้อที่คุณรัก'));
define('PROMPTPAY_ID', appConfig('PROMPTPAY_ID', appConfig('PROMPTPAY_NUMBER', '')));
define('PROMPTPAY_NAME', appConfig('PROMPTPAY_NAME', 'KitchenMart Demo Store'));

$protocol = requestIsHttps() ? 'https://' : 'http://';
$configuredUrl = trim(appConfig('APP_URL', ''));
$configuredParts = $configuredUrl !== '' ? parse_url($configuredUrl) : false;
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
if (!preg_match('/^(?:localhost|\[[0-9a-f:]+\]|[a-z0-9.-]+)(?::\d{1,5})?$/i', $host)) $host = 'localhost';
$baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (substr($baseDir, -4) === '/api') $baseDir = substr($baseDir, 0, -4);
if (substr($baseDir, -6) === '/admin') $baseDir = substr($baseDir, 0, -6);
if (is_array($configuredParts) && in_array($configuredParts['scheme'] ?? '', ['http','https'], true) && !empty($configuredParts['host'])) {
    define('BASE_URL', rtrim($configuredUrl, '/') . '/');
} else {
    define('BASE_URL', $protocol . $host . ($baseDir ? $baseDir : '') . '/');
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
