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

    // Universal System Security Settings (Handout Section 2.3)
    'seed' => [
        'min_username_len'       => 6,   // Min 6 characters
        'max_username_len'       => 20,  // Max 20 characters
        'min_password_len'       => 8,   // Min 8 characters
        'max_name_len'           => 50,  // Max 50 characters
        'forgot_pwd_threshold'   => 2,   // 2 failed logins before Forgot Password appears
        'lockout_tier_1_sec'     => 15,  // Tier 1 (3 failures): 15 seconds
        'lockout_tier_2_sec'     => 30,  // Tier 2 (6 failures): 30 seconds
        'lockout_tier_3_sec'     => 60,  // Tier 3 (9 failures): 60 seconds
        'otp_expiry_minutes'     => 5,   // OTP expiry: 5 minutes
        'idle_session_minutes'   => 15,  // Idle session timeout: 15 minutes
        'ip_block_threshold'     => 10,  // 10 failures in 10 minutes
    ]
];

