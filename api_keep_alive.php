<?php
/**
 * Session Keep-Alive Endpoint
 * IT 107 - Information Assurance and Security 1
 */

require_once __DIR__ . '/php/security.php';

send_security_headers();
start_secure_session();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'unauthenticated']);
}
