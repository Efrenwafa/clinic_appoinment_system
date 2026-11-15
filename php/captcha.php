<?php
/**
 * CAPTCHA Verification Helper
 * IT 107 - Information Assurance and Security 1
 * Implements Protection S7 and Handout Section 7
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once __DIR__ . '/security.php';

/**
 * Verify submitted CAPTCHA answer
 * Single-use: invalidates current captcha code immediately to prevent replay
 */
function verify_captcha(?string $submittedCode): bool {
    start_secure_session();

    $expected = $_SESSION['captcha_code'] ?? '';
    // Invalidate immediately so this code cannot be re-used
    unset($_SESSION['captcha_code']);

    if (empty($submittedCode) || empty($expected)) {
        return false;
    }

    // Compare case-insensitively
    return strcasecmp(trim($submittedCode), trim($expected)) === 0;
}
