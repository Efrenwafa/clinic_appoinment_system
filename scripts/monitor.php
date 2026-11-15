<?php
/**
 * Automated Security Log Monitoring Script (CLI Only)
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 14 (Graded in F9, D1)
 *
 * Usage:
 *   php scripts/monitor.php
 */

// Handout Section 14.2 Rule: Must NOT be reachable from browser
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once dirname(__DIR__) . '/php/db.php';
require_once dirname(__DIR__) . '/php/security.php';

$pdo = get_db_connection();
$nowTime = time();
$currentDate = date('Y-m-d');
$phtTimestamp = date('Y-m-d H:i') . ' PHT';

$twentyFourHoursAgo = date('Y-m-d H:i:s', $nowTime - 86400);

echo "=================================================================\n";
echo "IT 107 Monitoring Report - {$phtTimestamp}\n";
echo "=================================================================\n";

// Count scanned events
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` WHERE `created_at` >= :since");
$countStmt->execute([':since' => $twentyFourHoursAgo]);
$scannedCount = (int)$countStmt->fetchColumn();

echo "Scanned: " . number_format($scannedCount) . " events (last 24 hours)\n";

$newFlagsWritten = 0;
$alreadyFlaggedToday = 0;

function record_or_check_flag(PDO $pdo, string $pattern, string $severity, string $target, string $details, string $date, int &$newCount, int &$existingCount): bool {
    // Check if already flagged today (Section 14.1: with no duplicate for same pattern and target on same day)
    $chk = $pdo->prepare("SELECT COUNT(*) FROM `security_flags` WHERE `pattern_type` = :pt AND `target` = :tgt AND `flagged_date` = :fdate");
    $chk->execute([':pt' => $pattern, ':tgt' => $target, ':fdate' => $date]);
    if ($chk->fetchColumn() > 0) {
        $existingCount++;
        return false;
    }

    $ins = $pdo->prepare("
        INSERT INTO `security_flags` (`pattern_type`, `severity`, `target`, `details`, `flagged_date`, `created_at`)
        VALUES (:pt, :sev, :tgt, :details, :fdate, NOW())
    ");
    $ins->execute([
        ':pt'      => $pattern,
        ':sev'     => $severity,
        ':tgt'     => $target,
        ':details' => $details,
        ':fdate'   => $date
    ]);
    $newCount++;
    return true;
}

// -------------------------------------------------------------
// PATTERN 1: IP OVER THRESHOLD (Section 14.1 #1)
// One IP with failures at or above seeded IP block threshold (9 failures)
// -------------------------------------------------------------
$p1Stmt = $pdo->prepare("
    SELECT `ip_address`, COUNT(*) as fail_count, COUNT(DISTINCT `username_typed`) as user_count
    FROM `activity_logs`
    WHERE `event_type` = 'LOGIN_FAILED' AND `created_at` >= :since
    GROUP BY `ip_address`
    HAVING fail_count >= 9
");
$p1Stmt->execute([':since' => $twentyFourHoursAgo]);
$p1Results = $p1Stmt->fetchAll();

foreach ($p1Results as $r) {
    $ip = $r['ip_address'];
    $fails = $r['fail_count'];
    $uCount = $r['user_count'];
    $details = "{$fails} failures, {$uCount} usernames targeted in 24h";

    $written = record_or_check_flag($pdo, 'IP over threshold', 'HIGH', $ip, $details, $currentDate, $newFlagsWritten, $alreadyFlaggedToday);
    printf("[HIGH] IP over threshold    %-16s %s%s\n", $ip, $details, $written ? " (NEW)" : "");
}

// -------------------------------------------------------------
// PATTERN 2: ACCOUNT TARGETED (Section 14.1 #2)
// One username with failures from 3 or more different IPs
// -------------------------------------------------------------
$p2Stmt = $pdo->prepare("
    SELECT `username_typed`, COUNT(DISTINCT `ip_address`) as ip_count
    FROM `activity_logs`
    WHERE `event_type` = 'LOGIN_FAILED' AND `username_typed` IS NOT NULL AND `created_at` >= :since
    GROUP BY `username_typed`
    HAVING ip_count >= 3
");
$p2Stmt->execute([':since' => $twentyFourHoursAgo]);
$p2Results = $p2Stmt->fetchAll();

foreach ($p2Results as $r) {
    $u = $r['username_typed'];
    $ips = $r['ip_count'];
    $details = "failures from {$ips} different IPs";

    $written = record_or_check_flag($pdo, 'Account targeted', 'MED', $u, $details, $currentDate, $newFlagsWritten, $alreadyFlaggedToday);
    printf("[MED ] Account targeted     %-16s %s%s\n", $u, $details, $written ? " (NEW)" : "");
}

// -------------------------------------------------------------
// PATTERN 3: OTP GUESSING (Section 14.1 #3)
// 5 or more OTP_FAILED for one account within 10 minutes
// -------------------------------------------------------------
$p3Stmt = $pdo->prepare("
    SELECT a1.user_id, COUNT(*) as otp_fail_count
    FROM `activity_logs` a1
    JOIN `activity_logs` a2 ON a1.user_id = a2.user_id
        AND a1.event_type = 'OTP_FAILED' AND a2.event_type = 'OTP_FAILED'
        AND a2.created_at BETWEEN a1.created_at AND DATE_ADD(a1.created_at, INTERVAL 10 MINUTE)
    WHERE a1.created_at >= :since AND a1.user_id IS NOT NULL
    GROUP BY a1.user_id, a1.id
    HAVING otp_fail_count >= 5
");
$p3Stmt->execute([':since' => $twentyFourHoursAgo]);
$p3Results = $p3Stmt->fetchAll();

// Group by unique user_id
$flaggedOtpUsers = [];
foreach ($p3Results as $r) {
    $uid = $r['user_id'];
    if (isset($flaggedOtpUsers[$uid])) continue;
    $flaggedOtpUsers[$uid] = true;

    $cnt = $r['otp_fail_count'];
    $details = "{$cnt} wrong OTPs within 10 min window";

    $written = record_or_check_flag($pdo, 'OTP guessing', 'MED', $uid, $details, $currentDate, $newFlagsWritten, $alreadyFlaggedToday);
    printf("[MED ] OTP guessing         %-16s %s%s\n", $uid, $details, $written ? " (NEW)" : "");
}

// -------------------------------------------------------------
// PATTERN 4: OFF-HOURS LOG-IN (Section 14.1 #4)
// A LOGIN_SUCCESS between 12:00 AM and 5:00 AM, Philippine time
// -------------------------------------------------------------
$p4Stmt = $pdo->prepare("
    SELECT `id`, `username_typed`, `user_id`, `created_at`
    FROM `activity_logs`
    WHERE `event_type` = 'LOGIN_SUCCESS'
      AND TIME(`created_at`) BETWEEN '00:00:00' AND '05:00:00'
      AND `created_at` >= :since
");
$p4Stmt->execute([':since' => $twentyFourHoursAgo]);
$p4Results = $p4Stmt->fetchAll();

foreach ($p4Results as $r) {
    $target = $r['username_typed'] ?? $r['user_id'];
    $time = date('h:i A', strtotime($r['created_at']));
    $details = "Successful login during off-hours at {$time}";

    $written = record_or_check_flag($pdo, 'Off-hours log-in', 'LOW', $target, $details, $currentDate, $newFlagsWritten, $alreadyFlaggedToday);
    printf("[LOW ] Off-hours log-in      %-16s %s%s\n", $target, $details, $written ? " (NEW)" : "");
}

echo "-----------------------------------------------------------------\n";
echo "New flags written: {$newFlagsWritten}   Already flagged today: {$alreadyFlaggedToday}\n";
echo "=================================================================\n";
