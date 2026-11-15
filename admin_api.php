<?php
/**
 * Admin Dashboard Live Auto-Refresh JSON Endpoint
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 13.3 & Protection S5
 */

require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/db.php';

send_security_headers();
start_secure_session();

// Section 13.3 Rule: "Opened while logged out, it returns nothing."
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');

$pdo = get_db_connection();
$today = date('Y-m-d');
$todayStart = $today . ' 00:00:00';
$todayEnd = $today . ' 23:59:59';

// 1. Summary Cards
$succLogins = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_SUCCESS' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$failLogins = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_FAILED' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$lockedNow = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `locked_until` > NOW()")->fetchColumn();
$blockedNow = (int)$pdo->query("SELECT COUNT(*) FROM `ip_blocks` WHERE `blocked_until` > NOW()")->fetchColumn();
$otpsSent = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'OTP_SENT' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$otpsFailed = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'OTP_FAILED' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$alertsSent = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'ALERT_EMAIL_SENT' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();

// 2. Failed logins per hour (last 24 hours)
$hourlyData = [];
$nowHour = (int)date('H');
for ($i = 23; $i >= 0; $i--) {
    $targetTime = strtotime("-{$i} hours");
    $hourLabel = date('H:00', $targetTime);
    $startH = date('Y-m-d H:00:00', $targetTime);
    $endH = date('Y-m-d H:59:59', $targetTime);

    $hStmt = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_FAILED' AND `created_at` BETWEEN :s AND :e");
    $hStmt->execute([':s' => $startH, ':e' => $endH]);
    $hourlyData[] = [
        'hour'  => $hourLabel,
        'count' => (int)$hStmt->fetchColumn()
    ];
}

// 3. Top 5 IPs (last 24 hours)
$twentyFourHoursAgo = date('Y-m-d H:i:s', time() - 86400);
$topIpsStmt = $pdo->prepare("
    SELECT l.ip_address, COUNT(*) as fail_count,
           CASE WHEN b.blocked_until > NOW() THEN 1 ELSE 0 END as is_blocked
    FROM `activity_logs` l
    LEFT JOIN `ip_blocks` b ON l.ip_address = b.ip_address
    WHERE l.event_type = 'LOGIN_FAILED' AND l.created_at >= :since
    GROUP BY l.ip_address
    ORDER BY fail_count DESC
    LIMIT 5
");
$topIpsStmt->execute([':since' => $twentyFourHoursAgo]);
$topIps = $topIpsStmt->fetchAll();

// 4. Flags from Monitoring Script
$flagsStmt = $pdo->query("SELECT * FROM `security_flags` ORDER BY `id` DESC LIMIT 10");
$flags = $flagsStmt->fetchAll();

// 5. Recent 50 Events
$recentStmt = $pdo->query("SELECT * FROM `activity_logs` ORDER BY `id` DESC LIMIT 50");
$recentEvents = $recentStmt->fetchAll();

echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'cards' => [
        'successful_logins' => $succLogins,
        'failed_logins'     => $failLogins,
        'accounts_locked'   => $lockedNow,
        'ips_blocked'       => $blockedNow,
        'otps_sent'         => $otpsSent,
        'otps_failed'       => $otpsFailed,
        'alerts_sent'       => $alertsSent
    ],
    'hourly_failed_logins' => $hourlyData,
    'top_ips'              => $topIps,
    'flags'                => $flags,
    'recent_events'        => $recentEvents
]);
