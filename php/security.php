<?php
/**
 * Security Helper Functions
 * IT 107 - Information Assurance and Security 1
 * Implements Protections 2, 3, 6, 8, 10, 12, 13
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

// Protection 10: Log PHP errors silently to log file outside web display
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');

/**
 * Protection 12: Send mandatory security headers on every response
 */
function send_security_headers(): void {
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-src https://www.google.com/recaptcha/;");
    }
}

/**
 * Protection 6: Start hardened PHP session with secure cookie parameters
 */
function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie expires when browser is closed
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true, // Protection 6: Mitigate XSS cookie theft
            'samesite' => 'Lax' // Protection 6: Mitigate CSRF cross-origin cookie sharing
        ]);
        session_start();
    }

    // Protection 6 & Handout 8.4: Idle session timeout enforcement (9 minutes for D1 = 9)
    if (isset($_SESSION['user_id'])) {
        $config = require dirname(__DIR__) . '/config.php';
        $idleLimitSeconds = ($config['seed']['idle_session_minutes'] ?? 9) * 60; // 9 minutes = 540 seconds

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idleLimitSeconds)) {
            // Idle timeout reached: Destroy session
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();

            // Redirect to login with message
            header('Location: login.php?msg=session_expired');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Prevent browser back button caching on protected pages (Handout 8.4)
 */
function prevent_browser_cache(): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

/**
 * Protection 2: Escape output against Cross-Site Scripting (XSS)
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Protection 3: Generate and verify CSRF token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Protection 13: AES-256 Symmetric Encryption at Rest (Sensitive Field: Contact Number)
 */
function encrypt_sensitive_field(string $plaintext): string {
    if (trim($plaintext) === '') {
        return '';
    }

    $config = require dirname(__DIR__) . '/config.php';
    $rawKey = base64_decode($config['security']['aes_key'] ?? '');

    if (strlen($rawKey) !== 32) {
        throw new RuntimeException('Encryption error: Invalid 32-byte AES key.');
    }

    // Cryptographically secure random 16-byte IV for CBC mode
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $rawKey, OPENSSL_RAW_DATA, $iv);

    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed.');
    }

    // Combine IV and ciphertext, then base64 encode for safe database text storage
    return base64_encode($iv . $ciphertext);
}

function decrypt_sensitive_field(?string $encryptedPayload): string {
    if (empty($encryptedPayload)) {
        return '';
    }

    $config = require dirname(__DIR__) . '/config.php';
    $rawKey = base64_decode($config['security']['aes_key'] ?? '');

    if (strlen($rawKey) !== 32) {
        throw new RuntimeException('Decryption error: Invalid 32-byte AES key.');
    }

    $decoded = base64_decode($encryptedPayload, true);
    if ($decoded === false || strlen($decoded) <= 16) {
        return '[Decryption Error: Invalid payload]';
    }

    $iv = substr($decoded, 0, 16);
    $ciphertext = substr($decoded, 16);

    $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $rawKey, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        return '[Decryption Error]';
    }

    return $plaintext;
}

/**
 * Get Client IP Address safely
 */
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }
    return substr($ip, 0, 45);
}

/**
 * Get User Agent safely
 */
function get_client_user_agent(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
    return substr($ua, 0, 255);
}
