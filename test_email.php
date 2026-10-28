<?php
/**
 * Test Email Script for Checkpoint 1 Deliverable E
 * IT 107 - Information Assurance and Security 1
 * Secure Clinic Appointment and Monitoring System
 *
 * Usage from terminal:
 *   php test_email.php [recipient@example.com]
 *
 * If no recipient is provided as an argument, it sends to the 'from_email' configured in config.php.
 */

// Strict error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "========================================================\n";
echo "IT 107 - Checkpoint 1: SMTP Test Email Verification\n";
echo "System: Secure Clinic Appointment and Monitoring System\n";
echo "========================================================\n\n";

// 1. Verify Composer Autoloader
$autoloaderPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
    echo "[ERROR] Composer dependencies not found at /vendor/autoload.php\n";
    echo "Please ensure PHPMailer is installed via Composer.\n";
    exit(1);
}
require_once $autoloaderPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Verify config.php exists
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    echo "[CONFIGURATION REQUIRED]\n";
    echo "'config.php' was not found.\n\n";
    echo "To set up your SMTP credentials safely:\n";
    echo "1. Copy 'config.sample.php' to 'config.php'\n";
    echo "2. Open 'config.php' and set your SMTP settings:\n";
    echo "   - 'username'   => Your Gmail address\n";
    echo "   - 'password'   => Your 16-character Google App Password (NOT your login password)\n";
    echo "   - 'from_email' => Your Gmail address\n";
    echo "3. Save 'config.php'. (Note: config.php is in .gitignore and will never be committed).\n";
    echo "4. Re-run: php test_email.php your_email@example.com\n\n";
    exit(1);
}

$config = require $configPath;

if (!isset($config['smtp']) || empty($config['smtp']['username']) || empty($config['smtp']['password'])) {
    echo "[ERROR] SMTP configuration in 'config.php' is incomplete.\n";
    echo "Please ensure 'host', 'username', 'password', and 'port' are filled.\n";
    exit(1);
}

$smtp = $config['smtp'];

// Check for placeholder credentials
if ($smtp['username'] === 'your-email@gmail.com' || str_contains($smtp['password'], 'placeholder') || str_contains($smtp['password'], 'your-16-character')) {
    echo "[ACTION REQUIRED] 'config.php' still contains placeholder credentials.\n";
    echo "Please replace them with your actual Gmail address and Google App Password.\n";
    exit(1);
}

// 3. Determine Recipient Email
$recipient = $argv[1] ?? $smtp['from_email'];

if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo "[ERROR] Invalid recipient email address: '{$recipient}'\n";
    echo "Usage: php test_email.php recipient@example.com\n";
    exit(1);
}

echo "Attempting to send verification email...\n";
echo "- SMTP Host : {$smtp['host']}:{$smtp['port']}\n";
echo "- From      : {$smtp['from_name']} <{$smtp['from_email']}>\n";
echo "- Recipient : {$recipient}\n\n";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = $smtp['auth'];
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = ($smtp['secure'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp['port'];
    
    // Uncomment the line below if you need detailed SMTP conversation logs for debugging:
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    // Recipients
    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
    $mail->addAddress($recipient);

    // Email Content
    $timestamp = date('Y-m-d H:i:s T');
    $mail->isHTML(true);
    $mail->Subject = '[IT 107 IAS 1] Checkpoint 1 Email Delivery Verification';
    
    $mail->Body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #dcdcdc; border-radius: 8px;'>
        <h2 style='color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 8px;'>Caraga State University &middot; IT 107</h2>
        <h3 style='color: #2c3e50; margin-top: 5px;'>Secure Clinic Appointment and Monitoring System</h3>
        <p><strong>Checkpoint 1 Deliverable E: Test Email Verification</strong></p>
        <hr style='border: 0; border-top: 1px solid #eeeeee;'>
        <p>This email confirms that <strong>PHPMailer with SMTP authentication</strong> is correctly configured and successfully operational in your project environment.</p>
        <table style='width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 15px;'>
            <tr style='background-color: #f8f9fa;'><td style='padding: 8px; border: 1px solid #ddd;'><strong>Seed D1</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>9</td></tr>
            <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Seed D2</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>1</td></tr>
            <tr style='background-color: #f8f9fa;'><td style='padding: 8px; border: 1px solid #ddd;'><strong>OTP Expiry</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>4 minutes</td></tr>
            <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Idle Session Timeout</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>9 minutes</td></tr>
            <tr style='background-color: #f8f9fa;'><td style='padding: 8px; border: 1px solid #ddd;'><strong>Dispatched At</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$timestamp}</td></tr>
        </table>
        <p style='color: #27ae60; font-weight: bold;'>&check; Checkpoint 1 SMTP Delivery Verified.</p>
        <p style='font-size: 12px; color: #7f8c8d; margin-top: 25px;'><em>Note: Keep your config.php outside Git and never share your SMTP App Password.</em></p>
    </div>";

    $mail->AltBody = "Caraga State University - IT 107 Final Laboratory Project\n"
                   . "System: Secure Clinic Appointment and Monitoring System\n"
                   . "Checkpoint 1 Deliverable E: Test Email Verification\n\n"
                   . "PHPMailer with SMTP is successfully configured!\n"
                   . "Seed Values: D1=9, D2=1\n"
                   . "Dispatched at: {$timestamp}\n";

    $mail->send();
    echo "[SUCCESS] Real test email was successfully sent to {$recipient}!\n";
    echo "Please check your inbox (and spam/junk folder) to verify receipt.\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[MAILER ERROR] Message could not be sent.\n";
    echo "Mailer Error Information: {$mail->ErrorInfo}\n\n";
    echo "Troubleshooting Tips:\n";
    echo "1. If using Gmail, ensure 2-Step Verification is ON and you generated an 'App Password' (16 characters).\n";
    echo "2. Double-check that your email and App Password in 'config.php' are typed accurately without spaces.\n";
    echo "3. If on a university network or restricted Wi-Fi, port 587 or 465 might be blocked by the network firewall.\n";
    exit(1);
}

