<?php
/**
 * Reusable PHPMailer Helper
 * IT 107 - Information Assurance and Security 1
 * Implements Section 6 (Email OTP) and Section 9 (Security Alert Emails)
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logger.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function get_mailer_instance(): PHPMailer {
    $config = require dirname(__DIR__) . '/config.php';
    $smtp = $config['smtp'];

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = $smtp['auth'];
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = ($smtp['secure'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp['port'];
    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
    $mail->isHTML(true);

    return $mail;
}

/**
 * Send 6-digit OTP Email (Section 6.3)
 */
function send_otp_email(string $recipientEmail, string $recipientName, string $otpCode, string $purpose, int $expiryMinutes = 4): bool {
    try {
        $mail = get_mailer_instance();
        $mail->addAddress($recipientEmail, $recipientName);

        $titles = [
            'register' => 'Verify your account',
            'login'    => 'Your log-in code',
            'reset'    => 'Password reset code'
        ];
        $purposeText = $titles[$purpose] ?? 'Security Verification Code';

        $mail->Subject = "[Secure Clinic System] {$purposeText}";
        $timestamp = date('Y-m-d h:i A') . ' PHT';

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #dcdcdc; border-radius: 8px;'>
            <h2 style='color: #1a5276; margin-top: 0;'>Secure Clinic Appointment and Monitoring System</h2>
            <h3 style='color: #2c3e50;'>{$purposeText}</h3>
            <p>Hello <strong>" . e($recipientName) . "</strong>,</p>
            <p>Your one-time verification code is:</p>
            <div style='background-color: #f4f6f9; border: 2px dashed #1a5276; padding: 14px; text-align: center; margin: 18px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #1a5276;'>{$otpCode}</span>
            </div>
            <p>This code will expire in <strong>{$expiryMinutes} minutes</strong> (at {$timestamp}).</p>
            <p style='color: #7f8c8d; font-size: 13px;'>If you did not request this code, ignore this email and consider changing your password.</p>
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #95a5a6;'>Caraga State University &middot; IT 107 Final Laboratory Project</p>
        </div>";

        $mail->AltBody = "Secure Clinic Appointment and Monitoring System\n"
                       . "{$purposeText}\n\n"
                       . "Your OTP Code: {$otpCode}\n"
                       . "This code expires in {$expiryMinutes} minutes.\n"
                       . "If you did not request this code, ignore this email and consider changing your password.\n";

        $mail->send();
        log_activity('OTP_SENT', null, null, "Purpose: {$purpose} sent to {$recipientEmail}");
        return true;
    } catch (Exception $e) {
        error_log('[SMTP OTP ERROR] ' . $mail->ErrorInfo, 3, dirname(__DIR__) . '/logs/error.log');
        return false;
    }
}

/**
 * Send Account Lockout Alert Email (Section 9)
 */
function send_lockout_alert_email(string $recipientEmail, string $recipientName, int $tier, int $failCount, int $lockedSeconds): bool {
    try {
        $mail = get_mailer_instance();
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->Subject = "[Security Alert] Failed sign-in attempts on your account";
        $phtTime = date('Y-m-d h:i:s A') . ' (Philippine Time)';
        $clientIp = get_client_ip();
        $userAgent = get_client_user_agent();

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #e74c3c; border-radius: 8px;'>
            <h2 style='color: #c0392b; margin-top: 0;'>Security Alert: Account Lockout Warning</h2>
            <p>Hello <strong>" . e($recipientName) . "</strong>,</p>
            <p>Our security monitoring system detected <strong>{$failCount} consecutive failed sign-in attempts</strong> on your account.</p>
            <p>Your account has been temporarily locked for <strong>{$lockedSeconds} seconds</strong> (Lockout Tier {$tier}).</p>
            <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px;'>
                <tr style='background-color: #fdf2e9;'><td style='padding: 8px; border: 1px solid #eee;'><strong>Event Time</strong></td><td style='padding: 8px; border: 1px solid #eee;'>{$phtTime}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #eee;'><strong>Source IP Address</strong></td><td style='padding: 8px; border: 1px solid #eee;'>{$clientIp}</td></tr>
                <tr style='background-color: #fdf2e9;'><td style='padding: 8px; border: 1px solid #eee;'><strong>Browser / User Agent</strong></td><td style='padding: 8px; border: 1px solid #eee;'>" . e($userAgent) . "</td></tr>
            </table>
            <p style='color: #c0392b; font-weight: bold;'>If this was not you, reset your password now.</p>
            <p>You can initiate account recovery at: <a href='http://localhost/clinic_appoinment_system/forgot_password.php'>Forgot Password / Account Recovery</a></p>
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #95a5a6;'>Secure Clinic Appointment and Monitoring System</p>
        </div>";

        $mail->send();
        log_activity('ALERT_EMAIL_SENT', null, null, "Lockout tier {$tier} alert sent to {$recipientEmail}");
        return true;
    } catch (Exception $e) {
        error_log('[SMTP ALERT ERROR] ' . $mail->ErrorInfo, 3, dirname(__DIR__) . '/logs/error.log');
        return false;
    }
}

/**
 * Send Password Changed Confirmation Alert Email (Section 9.1)
 */
function send_password_changed_alert_email(string $recipientEmail, string $recipientName): bool {
    try {
        $mail = get_mailer_instance();
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->Subject = "[Security Alert] Your password was changed";
        $phtTime = date('Y-m-d h:i:s A') . ' (Philippine Time)';
        $clientIp = get_client_ip();
        $userAgent = get_client_user_agent();

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #27ae60; border-radius: 8px;'>
            <h2 style='color: #27ae60; margin-top: 0;'>Your Password Was Successfully Changed</h2>
            <p>Hello <strong>" . e($recipientName) . "</strong>,</p>
            <p>This confirmation is to notify you that the password for your <strong>Secure Clinic Appointment and Monitoring System</strong> account was successfully updated.</p>
            <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px;'>
                <tr style='background-color: #f4fdf8;'><td style='padding: 8px; border: 1px solid #eee;'><strong>Change Time</strong></td><td style='padding: 8px; border: 1px solid #eee;'>{$phtTime}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #eee;'><strong>IP Address</strong></td><td style='padding: 8px; border: 1px solid #eee;'>{$clientIp}</td></tr>
                <tr style='background-color: #f4fdf8;'><td style='padding: 8px; border: 1px solid #eee;'><strong>Browser / Device</strong></td><td style='padding: 8px; border: 1px solid #eee;'>" . e($userAgent) . "</td></tr>
            </table>
            <p>If you made this change, you can safely disregard this message.</p>
            <p style='color: #c0392b; font-weight: bold;'>If you did NOT change your password, please contact the clinic administrator immediately.</p>
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #95a5a6;'>Secure Clinic Appointment and Monitoring System</p>
        </div>";

        $mail->send();
        log_activity('ALERT_EMAIL_SENT', null, null, "Password changed alert sent to {$recipientEmail}");
        return true;
    } catch (Exception $e) {
        error_log('[SMTP ALERT ERROR] ' . $mail->ErrorInfo, 3, dirname(__DIR__) . '/logs/error.log');
        return false;
    }
}
