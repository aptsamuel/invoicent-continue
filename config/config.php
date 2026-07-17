<?php
/**
 * Invoicent - config.php
 * Path: /home/urjuktbj/://vibgrace.com
 */

/** Minimal .env loader */
function loadDotEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value,1,-1);
        }
        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/* Load .env from domain root */
$envPath = dirname(__DIR__) . '/.env';
loadDotEnv($envPath);

/* Helper to read env with fallback */
function env($key, $default = null) {
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}

/* Database Configuration */
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_USER', env('DB_USER', 'orjzkmhj_SaasApplication'));
define('DB_PASS', env('DB_PASS', '0H7%6ukeolaMSS4u'));
define('DB_NAME', env('DB_NAME', 'orjzkmhj_invoicent_db'));
define('DB_PORT', intval(env('DB_PORT', 3306)));

/* Application Settings */
define('APP_NAME', env('APP_NAME', 'Invoicent'));
$detectedUrl = env('APP_URL', '');
if (empty($detectedUrl) && isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $detectedUrl = $scheme . '://' . $host;
}
define('APP_URL', $detectedUrl ?: 'https://inapp.vibgrace.com');
define('APP_ENV', env('APP_ENV', 'production'));

/* Session & Security */
define('SESSION_TIMEOUT', intval(env('SESSION_TIMEOUT', 1800)));
define('REMEMBER_ME_DURATION', intval(env('REMEMBER_ME_DURATION', 2592000)));
define('CSRF_TOKEN_EXPIRY', intval(env('CSRF_TOKEN_EXPIRY', 3600)));
define('MAX_LOGIN_ATTEMPTS', intval(env('MAX_LOGIN_ATTEMPTS', 5)));
define('LOGIN_ATTEMPT_TIMEOUT', intval(env('LOGIN_ATTEMPT_TIMEOUT', 900)));

/* Uploads */
define('MAX_UPLOAD_SIZE', intval(env('MAX_UPLOAD_SIZE', 5242880)));
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');

/* PDF settings */
define('PDF_PAGE_FORMAT', env('PDF_PAGE_FORMAT', 'A5'));
define('PDF_MARGIN_LEFT', intval(env('PDF_MARGIN_LEFT', 10)));
define('PDF_MARGIN_RIGHT', intval(env('PDF_MARGIN_RIGHT', 10)));
define('PDF_MARGIN_TOP', intval(env('PDF_MARGIN_TOP', 10)));
define('PDF_MARGIN_BOTTOM', intval(env('PDF_MARGIN_BOTTOM', 10)));

/* Email Settings */
define('SMTP_HOST', env('SMTP_HOST', 'das116.truehost.cloud'));
define('SMTP_PORT', intval(env('SMTP_PORT', 587)));
define('SMTP_USER', env('SMTP_USER', 'noreply@://vibgrace.com'));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_AUTH', filter_var(env('SMTP_AUTH', true), FILTER_VALIDATE_BOOLEAN));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'noreply@://vibgrace.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', env('APP_NAME', 'Invoicent Billing')));


/* WhatsApp */
define('WHATSAPP_API_URL', env('WHATSAPP_API_URL', 'https://whatsapp.com'));
define('WHATSAPP_BUSINESS_PHONE', env('WHATSAPP_BUSINESS_PHONE', ''));

/* Currency */
define('CURRENCY_SYMBOLS', [
    'NGN' => '₦',
    'USD' => '$',
    'GBP' => '£',
    'EUR' => '€'
]);

/* Error display defaults */
if (APP_ENV === 'production') {
    @ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
    @ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/* Ensure uploads & logs exist natively */
$uploadsDir = UPLOAD_DIR;
$logsDir = dirname(__DIR__) . '/logs/';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}
$logFile = $logsDir . 'error.log';
if (!file_exists($logFile)) {
    @touch($logFile);
    @chmod($logFile, 0644);
}

/* Session cookie configurations */
$cookieDomain = env('SESSION_COOKIE_DOMAIN', '');
if (empty($cookieDomain) && !empty($_SERVER['HTTP_HOST'])) {
    $cookieDomain = preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST']);
}
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $cookieDomain ?: '',
    'secure' => $secureFlag,
    'httponly' => true,
    'samesite' => 'Lax'
];
if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params($cookieParams);
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/* Connection Gateway */
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    if (APP_ENV === 'development') {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    if (APP_ENV === 'production') {
        die("An error occurred. Please try again later.");
    } else {
        die("Database Error: " . $e->getMessage());
    }
}

/* Headers Engine */
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header("Content-Security-Policy: default-src 'self' data: https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:;");
}

/* Cryptographic Security Utility Wrappers */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function executeQuery($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    return $stmt;
}

function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if (!empty($context)) {
        $log_message .= " | Context: " . json_encode($context);
    }
    @error_log($log_message . PHP_EOL, 3, dirname(__DIR__) . '/logs/error.log');
}

function logActivity($user_id, $action, $details = '') {
    global $conn;
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $query = "INSERT INTO login_logs (user_id, action, ip_address, user_agent, details, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = executeQuery($conn, $query, [$user_id, $action, $ip_address, $user_agent, $details], 'issss');
        $stmt->close();
    } catch (Exception $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

function destroySession() {
    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        session_destroy();
    }
}
