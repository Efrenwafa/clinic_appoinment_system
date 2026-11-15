<?php
/**
 * Secure Session Logout Handler
 * IT 107 - Information Assurance and Security 1
 * Implements Protection 6 and Handout Section 8.4
 */

require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/logger.php';

send_security_headers();
prevent_browser_cache();
start_secure_session();

if (isset($_SESSION['user_id'])) {
    log_activity('LOGOUT', $_SESSION['username'] ?? null, $_SESSION['user_id'] ?? null, 'User logged out.');
}

// 1. Unset all session variables
$_SESSION = [];

// 2. Invalidate session cookie in browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroy session on server
session_destroy();

header('Location: login.php?msg=logged_out');
exit;
