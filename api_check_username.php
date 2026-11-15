<?php
/**
 * Live Username Availability API
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 4.9 live typing check
 */

require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/db.php';

send_security_headers();
header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

if (empty($username) || strlen($username) < 6 || strlen($username) > 17) {
    echo json_encode(['taken' => false]);
    exit;
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = :u");
    $stmt->execute([':u' => $username]);
    $taken = ((int)$stmt->fetchColumn() > 0);
    echo json_encode(['taken' => $taken]);
} catch (Exception $e) {
    echo json_encode(['taken' => false]);
}
