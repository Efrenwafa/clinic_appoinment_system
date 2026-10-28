<?php
/**
 * Configuration Template (Sample)
 * IT 107 - Information Assurance and Security 1
 * Secure Clinic Appointment and Monitoring System
 *
 * INSTRUCTIONS:
 * 1. Copy this file and save it as "config.php".
 * 2. Fill in your actual local database, SMTP, and encryption credentials in "config.php".
 * 3. NEVER commit "config.php" to Git. It is listed in .gitignore to uphold Security Protection 11.
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

return [
    // Database Configuration (PDO MySQL)
    'db' => [
        'host'     => '127.0.0.1',
        'dbname'   => 'clinic_appointment_db',
        'username' => 'root',
        'password' => '', // Default XAMPP MySQL password is empty
        'charset'  => 'utf8mb4',
    ],

    // SMTP Configuration for PHPMailer (Email OTP & Security Alerts)
    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'auth'       => true,
        'secure'     => 'tls', // 'tls' (port 587) or 'ssl' (port 465)
        'username'   => 'your-email@gmail.com',
        'password'   => 'your-16-character-app-password', // Google App Password (NOT personal password)
        'from_email' => 'your-email@gmail.com',
        'from_name'  => 'Secure Clinic Appointment System',
    ],

    // CAPTCHA Configuration (Google reCAPTCHA v2 Checkbox)
    'captcha' => [
        'site_key'   => 'YOUR_RECAPTCHA_V2_SITE_KEY',
        'secret_key' => 'YOUR_RECAPTCHA_V2_SECRET_KEY',
    ],

    // AES-256 Symmetric Encryption Key for Sensitive Fields (Protection 13)
    // 32-byte key base64 encoded
    'security' => [
        'aes_key' => 'BASE64_ENCODED_32_BYTE_KEY_PLACEHOLDER',
    ],

    // System Seed Limits (D1 = 9, D2 = 1)
    'seed' => [
        'd1' => 9,
        'd2' => 1,
        'min_username_len'       => 6,   // 6 + (9 mod 3)
        'max_username_len'       => 17,  // 16 + (1 mod 5)
        'min_password_len'       => 9,   // 8 + (1 mod 4)
        'max_name_len'           => 21,  // 20 + 1
        'forgot_pwd_threshold'   => 3,   // 2 + (9 mod 2)
        'lockout_tier_1_sec'     => 24,  // 15 + 9
        'lockout_tier_2_sec'     => 48,  // 30 + (2 * 9)
        'lockout_tier_3_sec'     => 96,  // 60 + (4 * 9)
        'otp_expiry_minutes'     => 4,   // 3 + (1 mod 3)
        'idle_session_minutes'   => 9,   // 5 + (9 mod 5)
        'ip_block_threshold'     => 9,   // 8 + 1 (within 10 minutes)
    ]
];

