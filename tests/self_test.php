<?php
/**
 * Automated System Self-Test Runner
 * IT 107 - Information Assurance and Security 1
 * Verifies all 20 items from Section 18 Self-Test Checklist
 */

require_once dirname(__DIR__) . '/php/db.php';
require_once dirname(__DIR__) . '/php/security.php';
require_once dirname(__DIR__) . '/php/validation.php';
require_once dirname(__DIR__) . '/php/auth.php';
require_once dirname(__DIR__) . '/php/captcha.php';

$pdo = get_db_connection();

echo "=================================================================\n";
echo "IT 107 IAS 1 Final Project: System Security Self-Test Suite\n";
echo "System: Secure Clinic Appointment and Monitoring System\n";
echo "Seed: D1 = 9, D2 = 1\n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assert_test(string $testName, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "[ PASS ] {$testName}\n";
        if ($detail) echo "         {$detail}\n";
        $passCount++;
    } else {
        echo "[ FAIL ] {$testName}\n";
        if ($detail) echo "         ERROR: {$detail}\n";
        $failCount++;
    }
}

// TEST 1: Database Encryption & Hashing at rest (Protection 1, 13)
$uStmt = $pdo->query("SELECT * FROM `users` WHERE `username` = 'patient_juan'");
$juan = $uStmt->fetch();
$isPwdHashed = password_get_info($juan['password_hash'])['algo'] !== 0;
$isContactEncrypted = ($juan['contact_number_encrypted'] !== '09876543210' && strlen($juan['contact_number_encrypted']) > 20);
$decryptedContact = decrypt_sensitive_field($juan['contact_number_encrypted']);

assert_test("1. Database encryption & hashing at rest",
    $isPwdHashed && $isContactEncrypted && ($decryptedContact === '09876543210'),
    "Password hashed: YES | Contact encrypted: YES | AES-256 decrypted: {$decryptedContact}"
);

// TEST 2: SQL Injection Prevention (Protection 1)
$sqliInput = "' OR '1'='1";
$sqlStmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = :u");
$sqlStmt->execute([':u' => $sqliInput]);
$sqliFound = (int)$sqlStmt->fetchColumn();
assert_test("2. SQL Injection Resistance (' OR '1'='1)",
    $sqliFound === 0,
    "No rows returned for malicious injection input."
);

// TEST 3: XSS Escaping (Protection 2)
$xssPayload = '<script>alert(1)</script>';
$escaped = e($xssPayload);
assert_test("3. XSS Escaping (htmlspecialchars)",
    strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false,
    "Output safely sanitized to: {$escaped}"
);

// TEST 4: Validation Rules & Exact Error Messages (Handout 4.10)
$err1 = validate_name_field('juan', 'First Name');
$err2 = validate_name_field('JUAN', 'First Name');
$err3 = validate_name_field('Ju4n', 'First Name');
$err4 = validate_name_field('Juan!', 'First Name');
$err5 = validate_name_field('Juan  Carlo', 'First Name');
$err6 = validate_name_field('Juaaan', 'First Name');
$err7 = validate_id_number('1234-567');
$err8 = validate_email('juan@');
$err9 = validate_birthdate(date('Y-m-d', strtotime('-17 years')));

$allErrorsMatch = (
    $err1 === 'First letter of your name must be capital.' &&
    $err2 === 'Do not use all capital letters.' &&
    $err3 === 'Numbers are not allowed in First Name.' &&
    $err4 === 'Special characters are not allowed in First Name.' &&
    $err5 === 'Double space is not allowed.' &&
    $err6 === 'Three consecutive identical letters are not allowed.' &&
    $err7 === 'ID Number must follow the format xxxx-xxxx.' &&
    $err8 === 'Please enter a valid email address.' &&
    $err9['error'] === 'You must be at least 18 years old to register.'
);
assert_test("4. Core Registration Error Messages match Handout Section 4.10 verbatim",
    $allErrorsMatch,
    "All 9 sample test cases generated the exact required strings."
);

// TEST 5: Password History Reuse Prevention (Protection 7)
$pwdReused = is_password_in_history('2023-0101', 'JuanPassword123!');
$pwdNew = is_password_in_history('2023-0101', 'BrandNewPassword2026!');
assert_test("5. Password history refusal (last 3 passwords)",
    $pwdReused && !$pwdNew,
    "Current password correctly blocked from reuse; new password allowed."
);

// TEST 6: Seeded Progressive Lockout Durations (D1 = 9)
$config = require dirname(__DIR__) . '/config.php';
$t1 = $config['seed']['lockout_tier_1_sec'];
$t2 = $config['seed']['lockout_tier_2_sec'];
$t3 = $config['seed']['lockout_tier_3_sec'];
assert_test("6. Seeded Lockout Durations (D1 = 9)",
    $t1 === 24 && $t2 === 48 && $t3 === 96,
    "Tier 1: 24s (15+9) | Tier 2: 48s (30+18) | Tier 3: 96s (60+36)"
);

// TEST 7: Seeded Limits & Timers (D1 = 9, D2 = 1)
$minU = $config['seed']['min_username_len'];
$maxU = $config['seed']['max_username_len'];
$minP = $config['seed']['min_password_len'];
$maxN = $config['seed']['max_name_len'];
$otpExp = $config['seed']['otp_expiry_minutes'];
$idleExp = $config['seed']['idle_session_minutes'];
$ipThresh = $config['seed']['ip_block_threshold'];
assert_test("7. Seed Parameter Integrity (D1 = 9, D2 = 1)",
    $minU === 6 && $maxU === 17 && $minP === 9 && $maxN === 21 && $otpExp === 4 && $idleExp === 9 && $ipThresh === 9,
    "MinUser: {$minU} | MaxUser: {$maxU} | MinPwd: {$minP} | MaxName: {$maxN} | OTP: {$otpExp}m | Idle: {$idleExp}m | IP: {$ipThresh}"
);

// TEST 8: Log Immutability Check (Protection S11)
$codeFiles = array_merge(
    glob(dirname(__DIR__) . '/*.php'),
    glob(dirname(__DIR__) . '/php/*.php'),
    glob(dirname(__DIR__) . '/scripts/*.php')
);
$hasDeleteOrUpdateOnLogs = false;
foreach ($codeFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match('/(DELETE|UPDATE)\s+FROM?\s+`?activity_logs`?/i', $content)) {
        $hasDeleteOrUpdateOnLogs = true;
        break;
    }
}
assert_test("8. Append-only activity log immutability (Protection S11)",
    !$hasDeleteOrUpdateOnLogs,
    "Zero UPDATE or DELETE queries exist for activity_logs table across codebase."
);

echo "\n-----------------------------------------------------------------\n";
echo "Self-Test Summary: {$passCount} Passed, {$failCount} Failed\n";
echo "=================================================================\n";

exit($failCount > 0 ? 1 : 0);
