<?php
/**
 * Configuration and global utilities file for OCR Image to Document Converter
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // In production, you would want this, but for local XAMPP HTTP we can check or leave as 0
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Global Path Constants
define('BASE_DIR', __DIR__ . '/');
define('UPLOAD_DIR', BASE_DIR . 'uploads/');
define('DOMPDF_AUTOLOAD', BASE_DIR . 'libs/dompdf/autoload.inc.php');

// Define database defaults
$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'ocr_db';
$db_user = 'root';
$db_pass = '';

// Tesseract binary executable configuration
$tesseract_path = 'tesseract'; // Default to PATH

// Load local overrides if setup has run
if (file_exists(BASE_DIR . 'config_local.php')) {
    include BASE_DIR . 'config_local.php';
}

define('DB_HOST', $db_host);
define('DB_PORT', $db_port);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('TESSERACT_PATH', $tesseract_path);

// Global PDO connection function
function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // If setup.php is running, we might not want to die immediately, but elsewhere we do.
            if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
                die("Database connection failed. Please run setup.php to configure the application. Error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    return $pdo;
}

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// -------------------------------------------------------------
// Security & Authentication Helpers
// -------------------------------------------------------------

/**
 * Sanitize variables for outputs
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input arrays recursively
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
    } else {
        $data = trim($data ?? '');
    }
    return $data;
}

/**
 * Check if the user is authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Restrict page access to logged-in users only
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash_message('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header("Location: login.php");
        exit;
    }
}

/**
 * Restrict page access to guests only (e.g. login/register pages)
 */
function require_guest() {
    if (is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

// -------------------------------------------------------------
// CSRF Token Protection
// -------------------------------------------------------------

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// -------------------------------------------------------------
// Flash Messages Helpers
// -------------------------------------------------------------

/**
 * Set a flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
