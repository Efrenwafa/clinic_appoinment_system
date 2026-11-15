<?php
/**
 * Database Connection Helper (PDO Prepared Statements)
 * IT 107 - Information Assurance and Security 1
 * Protection 1: Prepared statements for EVERY query to prevent SQL Injection
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $configPath = dirname(__DIR__) . '/config.php';
        if (!file_exists($configPath)) {
            error_log('[SECURITY CRITICAL] config.php is missing.');
            http_response_code(500);
            exit('A system error occurred. Please contact the administrator.');
        }

        $config = require $configPath;
        $db = $config['db'];

        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforce true server-side prepared statements
        ];

        try {
            $pdo = new PDO($dsn, $db['username'], $db['password'], $options);
        } catch (PDOException $e) {
            // Protection 10: Never display raw SQL/database errors to users
            error_log('[DB CONNECTION ERROR] ' . $e->getMessage(), 3, dirname(__DIR__) . '/logs/error.log');
            http_response_code(500);
            exit('Unable to connect to the database. Please try again later.');
        }
    }

    return $pdo;
}
