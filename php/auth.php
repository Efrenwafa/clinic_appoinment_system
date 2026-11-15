<?php
/**
 * Authentication, Progressive Lockout, IP Blocking & OTP Engine
 * IT 107 - Information Assurance and Security 1
 * Implements Protections 4, 5, 6, 7 and Sections 6, 8, 9, 10
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/mailer.php';

/**
 * Protection 5: Check if client IP is blocked (9 failures in 10 minutes -> 10 minute block)
 */
function check_ip_status(): array {
    $pdo = get_db_connection();
    $ip = get_client_ip();

    $stmt = $pdo->prepare("SELECT * FROM `ip_blocks` WHERE `ip_address` = :ip");
    $stmt->execute([':ip' => $ip]);
    $block = $stmt->fetch();

    if ($block && !empty($block['blocked_until'])) {
        $blockedUntil = strtotime($block['blocked_until']);
        $now = time();

        if ($blockedUntil > $now) {
            $remaining = $blockedUntil - $now;
            return ['is_blocked' => true, 'seconds_remaining' => $remaining];
        } else {
            // Block expired: Reset block
            $resetStmt = $pdo->prepare("UPDATE `ip_blocks` SET `failure_count` = 0, `blocked_until` = NULL WHERE `ip_address` = :ip");
            $resetStmt->execute([':ip' => $ip]);
        }
    }

    return ['is_blocked' => false, 'seconds_remaining' => 0];
}

/**
 * Record IP-level failure for IP blocking defense
 */
function record_ip_failure(): void {
    $pdo = get_db_connection();
    $ip = get_client_ip();
    $now = date('Y-m-d H:i:s');
    $config = require dirname(__DIR__) . '/config.php';
    $threshold = $config['seed']['ip_block_threshold'] ?? 9; // 8 + D2 = 9

    $stmt = $pdo->prepare("SELECT * FROM `ip_blocks` WHERE `ip_address` = :ip");
    $stmt->execute([':ip' => $ip]);
    $row = $stmt->fetch();

    if ($row) {
        $firstFail = strtotime($row['first_failure_at']);
        // 10 minutes sliding window
        if (time() - $firstFail > 600) {
            // Window expired, reset counter to 1
            $upd = $pdo->prepare("UPDATE `ip_blocks` SET `failure_count` = 1, `first_failure_at` = :now, `blocked_until` = NULL, `updated_at` = :now WHERE `ip_address` = :ip");
            $upd->execute([':now' => $now, ':ip' => $ip]);
        } else {
            $newCount = $row['failure_count'] + 1;
            if ($newCount >= $threshold) {
                // Block IP for 10 minutes (600 seconds)
                $blockUntil = date('Y-m-d H:i:s', time() + 600);
                $upd = $pdo->prepare("UPDATE `ip_blocks` SET `failure_count` = :count, `blocked_until` = :blocked_until, `updated_at` = :now WHERE `ip_address` = :ip");
                $upd->execute([':count' => $newCount, ':blocked_until' => $blockUntil, ':now' => $now, ':ip' => $ip]);
                log_activity('IP_BLOCKED', null, null, "IP {$ip} blocked for 10 minutes after {$newCount} failures.");
            } else {
                $upd = $pdo->prepare("UPDATE `ip_blocks` SET `failure_count` = :count, `updated_at` = :now WHERE `ip_address` = :ip");
                $upd->execute([':count' => $newCount, ':now' => $now, ':ip' => $ip]);
            }
        }
    } else {
        $ins = $pdo->prepare("INSERT INTO `ip_blocks` (`ip_address`, `failure_count`, `first_failure_at`, `created_at`, `updated_at`) VALUES (:ip, 1, :now, :now, :now)");
        $ins->execute([':ip' => $ip, ':now' => $now]);
    }
}

/**
 * Check User Account Lockout (Section 8.3)
 * Tier 1 (3 failures): 24 seconds
 * Tier 2 (6 failures): 48 seconds
 * Tier 3 (9 failures): 96 seconds
 */
function check_account_lockout(array $user): array {
    $now = time();
    $isLocked = false;
    $remaining = 0;

    if (!empty($user['locked_until'])) {
        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil > $now) {
            $isLocked = true;
            $remaining = $lockedUntil - $now;
        }
    }

    $failedCount = (int)($user['failed_logins'] ?? 0);
    $showForgotPassword = ($failedCount >= 3);

    return [
        'is_locked'              => $isLocked,
        'seconds_remaining'      => $remaining,
        'failed_logins'          => $failedCount,
        'lockout_tier'           => (int)($user['lockout_tier'] ?? 0),
        'show_forgot_password'   => $showForgotPassword
    ];
}

/**
 * Record Account-level failed login attempt
 */
function record_account_failure(string $userId): void {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id_number` = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) return;

    $config = require dirname(__DIR__) . '/config.php';
    $t1Sec = $config['seed']['lockout_tier_1_sec'] ?? 24; // 24s
    $t2Sec = $config['seed']['lockout_tier_2_sec'] ?? 48; // 48s
    $t3Sec = $config['seed']['lockout_tier_3_sec'] ?? 96; // 96s

    $newFailCount = (int)$user['failed_logins'] + 1;
    $lockedUntil = null;
    $lockoutTier = 0;
    $triggerTierAlert = null;
    $duration = 0;

    if ($newFailCount >= 9) {
        $lockoutTier = 3;
        $duration = $t3Sec;
        $lockedUntil = date('Y-m-d H:i:s', time() + $duration);
        $triggerTierAlert = 3;
    } elseif ($newFailCount >= 6) {
        $lockoutTier = 2;
        $duration = $t2Sec;
        $lockedUntil = date('Y-m-d H:i:s', time() + $duration);
        $triggerTierAlert = 2;
    } elseif ($newFailCount >= 3) {
        $lockoutTier = 1;
        $duration = $t1Sec;
        $lockedUntil = date('Y-m-d H:i:s', time() + $duration);
        $triggerTierAlert = 1;
    }

    $upd = $pdo->prepare("UPDATE `users` SET `failed_logins` = :fails, `lockout_tier` = :tier, `locked_until` = :locked, `updated_at` = NOW() WHERE `id_number` = :id");
    $upd->execute([
        ':fails'  => $newFailCount,
        ':tier'   => $lockoutTier,
        ':locked' => $lockedUntil,
        ':id'     => $userId
    ]);

    // Send tier alert email (Section 9.1: Once per tier reached)
    if ($triggerTierAlert !== null && $triggerTierAlert > (int)$user['lockout_tier']) {
        log_activity("LOCKOUT_TIER_{$triggerTierAlert}", $user['username'], $userId, "Account reached lockout tier {$triggerTierAlert}. Locked for {$duration} seconds.");
        send_lockout_alert_email($user['email'], $user['first_name'] . ' ' . $user['family_name'], $triggerTierAlert, $newFailCount, $duration);
    }
}

/**
 * Reset failed logins upon successful login
 */
function reset_account_failures(string $userId): void {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE `users` SET `failed_logins` = 0, `lockout_tier` = 0, `locked_until` = NULL, `updated_at` = NOW() WHERE `id_number` = :id");
    $stmt->execute([':id' => $userId]);
}

/**
 * Generate and dispatch a 6-digit OTP (Section 6)
 */
function create_otp_for_user(string $userId, string $email, string $recipientName, string $purpose): bool {
    $pdo = get_db_connection();
    $config = require dirname(__DIR__) . '/config.php';
    $expiryMinutes = $config['seed']['otp_expiry_minutes'] ?? 4; // 4 minutes

    // Invalidate previous OTPs for this user and purpose (Handout 6.2: single use, requesting new cancels previous)
    $cancelStmt = $pdo->prepare("UPDATE `otps` SET `is_used` = 1 WHERE `user_id` = :uid AND `purpose` = :purpose AND `is_used` = 0");
    $cancelStmt->execute([':uid' => $userId, ':purpose' => $purpose]);

    // Cryptographically secure 6-digit OTP using random_int (never rand)
    $code = (string)random_int(100000, 999999);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);

    $expiresAt = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));
    $now = date('Y-m-d H:i:s');

    $ins = $pdo->prepare("
        INSERT INTO `otps` (`user_id`, `email`, `otp_hash`, `purpose`, `attempts`, `is_used`, `expires_at`, `created_at`)
        VALUES (:uid, :email, :hash, :purpose, 0, 0, :expires, :created)
    ");
    $ins->execute([
        ':uid'     => $userId,
        ':email'   => $email,
        ':hash'    => $codeHash,
        ':purpose' => $purpose,
        ':expires' => $expiresAt,
        ':created' => $now
    ]);

    // Send via PHPMailer
    return send_otp_email($email, $recipientName, $code, $purpose, $expiryMinutes);
}

/**
 * Check if user can resend OTP (60-second cooldown, max 3 resends per 15 min) (Section 6.2)
 */
function can_resend_otp(string $userId, string $purpose): array {
    $pdo = get_db_connection();

    // Check last OTP time
    $stmt = $pdo->prepare("SELECT `created_at` FROM `otps` WHERE `user_id` = :uid AND `purpose` = :purpose ORDER BY `id` DESC LIMIT 1");
    $stmt->execute([':uid' => $userId, ':purpose' => $purpose]);
    $lastOtp = $stmt->fetch();

    if ($lastOtp) {
        $secondsSince = time() - strtotime($lastOtp['created_at']);
        if ($secondsSince < 60) {
            return ['allowed' => false, 'error' => 'Please wait ' . (60 - $secondsSince) . ' seconds before requesting another code.', 'cooldown_remaining' => 60 - $secondsSince];
        }
    }

    // Check 15-minute resend limit (max 3)
    $fifteenMinutesAgo = date('Y-m-d H:i:s', time() - 900);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `otps` WHERE `user_id` = :uid AND `purpose` = :purpose AND `created_at` >= :limitTime");
    $countStmt->execute([':uid' => $userId, ':purpose' => $purpose, ':limitTime' => $fifteenMinutesAgo]);
    $resendsIn15Min = (int)$countStmt->fetchColumn();

    if ($resendsIn15Min >= 4) { // 1 initial + 3 resends = 4 total
        return ['allowed' => false, 'error' => 'Maximum resend limit reached (3 per 15 minutes). Please try again later.', 'cooldown_remaining' => 900];
    }

    return ['allowed' => true, 'error' => null, 'cooldown_remaining' => 0];
}

/**
 * Verify OTP input (Section 6.2: 3 wrong entries cancel OTP, expires after 4 min, single use)
 */
function verify_otp_submission(string $userId, string $purpose, string $submittedCode): array {
    $pdo = get_db_connection();

    // Fetch the active OTP
    $stmt = $pdo->prepare("
        SELECT * FROM `otps`
        WHERE `user_id` = :uid AND `purpose` = :purpose AND `is_used` = 0
        ORDER BY `id` DESC LIMIT 1
    ");
    $stmt->execute([':uid' => $userId, ':purpose' => $purpose]);
    $otp = $stmt->fetch();

    if (!$otp) {
        log_activity('OTP_FAILED', null, $userId, "No active OTP found for purpose {$purpose}");
        return ['success' => false, 'error' => 'No active verification code found. Please request a new one.'];
    }

    // Check expiration
    if (strtotime($otp['expires_at']) <= time()) {
        $cancelStmt = $pdo->prepare("UPDATE `otps` SET `is_used` = 1 WHERE `id` = :id");
        $cancelStmt->execute([':id' => $otp['id']]);
        log_activity('OTP_FAILED', null, $userId, "Expired OTP entered for purpose {$purpose}");
        return ['success' => false, 'error' => 'This verification code has expired. Please request a new one.'];
    }

    // Check attempt counter (handout: 3 wrong entries cancel OTP)
    $attempts = (int)$otp['attempts'];
    if ($attempts >= 3) {
        $cancelStmt = $pdo->prepare("UPDATE `otps` SET `is_used` = 1 WHERE `id` = :id");
        $cancelStmt->execute([':id' => $otp['id']]);
        log_activity('OTP_FAILED', null, $userId, "OTP cancelled after exceeding 3 failed attempts.");
        return ['success' => false, 'error' => 'Too many failed attempts. This code has been cancelled. Please request a new code.'];
    }

    // Verify hash
    if (password_verify($submittedCode, $otp['otp_hash'])) {
        // Correct OTP: Mark as used immediately
        $useStmt = $pdo->prepare("UPDATE `otps` SET `is_used` = 1 WHERE `id` = :id");
        $useStmt->execute([':id' => $otp['id']]);

        // If registration OTP, activate account
        if ($purpose === 'register') {
            $actStmt = $pdo->prepare("UPDATE `users` SET `is_verified` = 1, `updated_at` = NOW() WHERE `id_number` = :id");
            $actStmt->execute([':id' => $userId]);
            log_activity('ACCOUNT_VERIFIED', null, $userId, 'Account successfully verified via OTP.');
        }

        return ['success' => true, 'error' => null];
    } else {
        // Incorrect code: Increment attempts
        $newAttempts = $attempts + 1;
        $isCancelled = ($newAttempts >= 3);
        $upd = $pdo->prepare("UPDATE `otps` SET `attempts` = :att, `is_used` = :used WHERE `id` = :id");
        $upd->execute([':att' => $newAttempts, ':used' => $isCancelled ? 1 : 0, ':id' => $otp['id']]);

        log_activity('OTP_FAILED', null, $userId, "Incorrect OTP submitted (Attempt {$newAttempts}/3).");

        if ($isCancelled) {
            return ['success' => false, 'error' => 'Three incorrect entries. This code has been cancelled. Please request a new code.'];
        }

        $remainingAttempts = 3 - $newAttempts;
        return ['success' => false, 'error' => "Incorrect verification code. {$remainingAttempts} attempt(s) remaining."];
    }
}

/**
 * Protection 7: Password History & Reuse Prevention (Last 3 passwords)
 */
function is_password_in_history(string $userId, string $newPassword): bool {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT `password_hash` FROM `password_history` WHERE `user_id` = :uid ORDER BY `id` DESC LIMIT 3");
    $stmt->execute([':uid' => $userId]);
    $history = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($history as $oldHash) {
        if (password_verify($newPassword, $oldHash)) {
            return true; // Password was reused!
        }
    }
    return false;
}

function add_password_to_history(string $userId, string $passwordHash): void {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("INSERT INTO `password_history` (`user_id`, `password_hash`, `created_at`) VALUES (:uid, :hash, NOW())");
    $stmt->execute([':uid' => $userId, ':hash' => $passwordHash]);
}
