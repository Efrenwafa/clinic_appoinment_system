<?php
/**
 * Activity & Audit Logger (Append-Only)
 * IT 107 - Information Assurance and Security 1
 * Implements Protection 9 (Activity log) and Section 13.4 of Course Handout
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function log_activity(string $eventType, ?string $usernameTyped = null, ?string $userId = null, ?string $details = null): void {
    try {
        $pdo = get_db_connection();
        $ip = get_client_ip();
        $userAgent = get_client_user_agent();
        $now = date('Y-m-d H:i:s');

        // Protection 1: Prepared statement for logging
        $stmt = $pdo->prepare("
            INSERT INTO `activity_logs` (`event_type`, `username_typed`, `user_id`, `ip_address`, `user_agent`, `details`, `created_at`)
            VALUES (:event_type, :username_typed, :user_id, :ip_address, :user_agent, :details, :created_at)
        ");

        $stmt->execute([
            ':event_type'     => substr($eventType, 0, 50),
            ':username_typed' => $usernameTyped ? substr($usernameTyped, 0, 100) : null,
            ':user_id'        => $userId ? substr($userId, 0, 9) : null,
            ':ip_address'     => $ip,
            ':user_agent'     => $userAgent,
            ':details'        => $details,
            ':created_at'     => $now
        ]);
    } catch (Exception $e) {
        // Protection 10: Fail silently to log file
        error_log('[ACTIVITY LOG ERROR] ' . $e->getMessage(), 3, dirname(__DIR__) . '/logs/error.log');
    }
}
