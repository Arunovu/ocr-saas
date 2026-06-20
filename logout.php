<?php
/**
 * Logout Page
 */
require_once __DIR__ . '/config.php';

// Handle ping from inactivity JS — just keep session alive, don't logout
if (isset($_GET['ping'])) {
    // Touch the session to reset server-side timeout
    $_SESSION['last_active'] = time();
    http_response_code(200);
    exit;
}

// Unset all session variables
$_SESSION = [];

// If session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Show inactivity message if reason is given
$reason = $_GET['reason'] ?? '';
if ($reason === 'inactivity') {
    set_flash_message('warning', 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.');
}

// Redirect to landing
header("Location: landing.php");
exit;
