<?php
/**
 * Server-Side Validation Rules & Exact Handout Error Messages
 * IT 107 - Information Assurance and Security 1
 * Implements Protection S3 (Server-side validation) and Section 4 & 5
 */

// Prevent direct execution via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit('Page Not Found.');
}

require_once __DIR__ . '/db.php';

/**
 * Security Question Bank (Handout Section 5)
 * All 8 official questions from the Section 5 question bank
 */
const SECURITY_QUESTION_BANK = [
    'Q1' => 'What was the name of your first pet?',
    'Q2' => 'Who was your best friend in elementary school?',
    'Q3' => 'Who was your favorite teacher in high school?',
    'Q4' => "What is your mother's maiden first name?",
    'Q5' => 'What street did you live on in Grade 1?',
    'Q6' => 'What was the first song you learned by heart?',
    'Q7' => 'What was the name of your elementary school?',
    'Q8' => 'What dish did you cook for the first time?'
];

/**
 * Validate a name field (First Name, Middle Name, Family Name, etc.)
 * Strictly enforces rules from Section 4.5 & 4.10:
 * 1. Letters and spaces only. No digits.
 * 2. No special characters.
 * 3. No digit followed by letters.
 * 4. No double spaces.
 * 5. Not all capital letters.
 * 6. No three identical letters in a row (case-insensitive).
 * 7. First letter of each word capital, the rest small (Title Case).
 * 8. Length: 2 to 50 characters (Updated universal limit).
 */
function validate_name_field(?string $value, string $fieldName, bool $isRequired = true, int $maxLen = 50): ?string {
    $val = trim($value ?? '');

    if ($val === '') {
        if ($isRequired) {
            return "{$fieldName} is required.";
        }
        return null; // Optional and empty is valid
    }

    // 1. Length check
    if (mb_strlen($val) < 2) {
        return "{$fieldName} must be at least 2 characters.";
    }
    if (mb_strlen($val) > $maxLen) {
        return "{$fieldName} cannot exceed {$maxLen} characters.";
    }

    // 2. Check for digits
    if (preg_match('/[0-9]/', $val)) {
        return "Numbers are not allowed in {$fieldName}.";
    }

    // 3. Check for special characters (allow only letters and spaces)
    if (preg_match('/[^a-zA-Z\s]/', $val)) {
        return "Special characters are not allowed in {$fieldName}.";
    }

    // 4. No double spaces
    if (strpos($val, '  ') !== false) {
        return "Double space is not allowed.";
    }

    // 5. Not all capital letters (if more than 2 letters)
    $lettersOnly = preg_replace('/\s+/', '', $val);
    if (strlen($lettersOnly) > 2 && ctype_upper($lettersOnly)) {
        return "Do not use all capital letters.";
    }

    // 6. No three consecutive identical letters (case-insensitive: Juaaan, JuAaan)
    if (preg_match('/([a-zA-Z])\1\1/i', $val)) {
        return "Three consecutive identical letters are not allowed.";
    }

    // 7. Title case check: First letter of each word must be capital, rest small
    $words = explode(' ', $val);
    foreach ($words as $w) {
        if ($w === '') continue;
        $firstChar = mb_substr($w, 0, 1);
        $rest = mb_substr($w, 1);

        if (!ctype_upper($firstChar)) {
            return "First letter of your name must be capital.";
        }
        if ($rest !== '' && $rest !== mb_strtolower($rest)) {
            return "Only the first letter of each word should be capitalized.";
        }
    }

    return null;
}

/**
 * Validate ID Number: format xxxx-xxxx, primary key uniqueness (Section 4.4)
 */
function validate_id_number(?string $idNumber, ?string $currentUserId = null): ?string {
    $val = trim($idNumber ?? '');
    if ($val === '') {
        return "ID Number is required.";
    }

    // Format: four digits, hyphen, four digits
    if (!preg_match('/^\d{4}-\d{4}$/', $val)) {
        return "ID Number must follow the format xxxx-xxxx.";
    }

    // Uniqueness check via prepared statement
    $pdo = get_db_connection();
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `id_number` = :id AND `id_number` != :curr");
        $stmt->execute([':id' => $val, ':curr' => $currentUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `id_number` = :id");
        $stmt->execute([':id' => $val]);
    }

    if ($stmt->fetchColumn() > 0) {
        return "This ID Number is already registered.";
    }

    return null;
}

/**
 * Validate Birthdate & Age (Must be at least 18 years old, not future) (Section 4.6)
 */
function validate_birthdate(?string $birthdate): array {
    $val = trim($birthdate ?? '');
    if ($val === '') {
        return ['error' => 'Birthdate is required.', 'age' => 0];
    }

    $d = DateTime::createFromFormat('Y-m-d', $val);
    if (!$d || $d->format('Y-m-d') !== $val) {
        return ['error' => 'Invalid birthdate format.', 'age' => 0];
    }

    $today = new DateTime('today');
    if ($d > $today) {
        return ['error' => 'Birthdate cannot be in the future.', 'age' => 0];
    }

    $age = $d->diff($today)->y;
    if ($age < 18) {
        return ['error' => 'You must be at least 18 years old to register.', 'age' => $age];
    }

    return ['error' => null, 'age' => $age];
}

/**
 * Validate Address Fields (Section 4.7)
 */
function validate_address_purok(?string $val): ?string {
    $v = trim($val ?? '');
    if ($v === '') return "Purok / Street is required.";
    if (strpos($v, '  ') !== false) return "Double space is not allowed.";
    if (strlen($v) < 3 || strlen($v) > 50) return "Purok / Street must be between 3 and 50 characters.";
    if (!preg_match('/^[a-zA-Z0-9\s\.\,\#\-]+$/', $v)) return "Purok / Street contains invalid characters.";
    return null;
}

function validate_address_text(?string $val, string $fieldName): ?string {
    $v = trim($val ?? '');
    if ($v === '') return "{$fieldName} is required.";
    if (strpos($v, '  ') !== false) return "Double space is not allowed.";
    if (strlen($v) < 2 || strlen($v) > 50) return "{$fieldName} must be between 2 and 50 characters.";
    if (!preg_match('/^[a-zA-Z\s]+$/', $v)) return "{$fieldName} must contain letters and spaces only.";
    
    // Check title case
    $words = explode(' ', $v);
    foreach ($words as $w) {
        if ($w === '') continue;
        if (!ctype_upper(mb_substr($w, 0, 1))) {
            return "{$fieldName} must be in Title Case.";
        }
    }
    return null;
}

function validate_zip_code(?string $val): ?string {
    $v = trim($val ?? '');
    if ($v === '') return "ZIP Code is required.";
    if (!preg_match('/^\d{4}$/', $v)) return "ZIP Code must be exactly 4 digits.";
    return null;
}

/**
 * Validate Email (Section 4.8)
 */
function validate_email(?string $email, ?string $currentUserId = null): ?string {
    $v = trim($email ?? '');
    if ($v === '') return "Email is required.";
    if (strpos($v, ' ') !== false) return "Please enter a valid email address.";
    if (strlen($v) > 100) return "Email cannot exceed 100 characters.";
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return "Please enter a valid email address.";

    // Uniqueness
    $pdo = get_db_connection();
    $lower = strtolower($v);
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE LOWER(`email`) = :email AND `id_number` != :curr");
        $stmt->execute([':email' => $lower, ':curr' => $currentUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE LOWER(`email`) = :email");
        $stmt->execute([':email' => $lower]);
    }

    if ($stmt->fetchColumn() > 0) {
        return "This email is already registered.";
    }

    return null;
}

/**
 * Validate Username (Section 4.9)
 * Seed limits: Min 6, Max 17 characters. Letters & digits only, starts with a letter.
 */
function validate_username(?string $username, ?string $currentUserId = null): ?string {
    $v = trim($username ?? '');
    if ($v === '') return "Username is required.";

    // Starts with letter
    if (!preg_match('/^[a-zA-Z]/', $v)) {
        return "Username must start with a letter.";
    }

    // Letters and digits only
    if (!preg_match('/^[a-zA-Z0-9]+$/', $v)) {
        return "Username must contain letters and numbers only.";
    }

    // Universal limits: 6 to 20
    $len = strlen($v);
    if ($len < 6 || $len > 20) {
        return "Username must be between 6 and 20 characters.";
    }

    // Uniqueness
    $pdo = get_db_connection();
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = :u AND `id_number` != :curr");
        $stmt->execute([':u' => $v, ':curr' => $currentUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = :u");
        $stmt->execute([':u' => $v]);
    }

    if ($stmt->fetchColumn() > 0) {
        return "This username is already registered.";
    }

    return null;
}

/**
 * Validate Password Strength (Section 4.9)
 * Universal minimum: 8 characters, maximum 64 characters. Weak cannot be submitted.
 */
function evaluate_password_strength(string $password): string {
    $len = strlen($password);
    if ($len < 8) return 'Weak';

    $score = 0;
    if ($len >= 12) $score++;
    if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;

    if ($score >= 3) return 'Strong';
    if ($score >= 1) return 'Medium';
    return 'Weak';
}

function validate_password(?string $password, ?string $confirmPassword): ?string {
    $pwd = $password ?? '';
    $conf = $confirmPassword ?? '';

    if ($pwd === '') return "Password is required.";
    if (strlen($pwd) < 8) return "Password must be at least 8 characters.";
    if (strlen($pwd) > 64) return "Password cannot exceed 64 characters.";

    if (evaluate_password_strength($pwd) === 'Weak') {
        return "Password is too weak. Must include a mix of uppercase, lowercase, numbers, or symbols.";
    }

    if ($pwd !== $conf) {
        return "Passwords do not match.";
    }

    return null;
}

/**
 * Validate Clinic Specific Fields (Proposal Section)
 */
function validate_contact_number(?string $val, bool $isRequired = true): ?string {
    $v = trim($val ?? '');
    if ($v === '') {
        if ($isRequired) {
            return "Enter a valid contact number in the format 09XXXXXXXXX.";
        }
        return null;
    }

    // Philippine mobile format: 11 digits starting with 09
    if (!preg_match('/^09\d{9}$/', $v)) {
        return $isRequired ? "Enter a valid contact number in the format 09XXXXXXXXX." : "Enter a valid emergency contact number in the format 09XXXXXXXXX.";
    }

    return null;
}

function validate_patient_type(?string $val): ?string {
    $allowed = ['New Patient', 'Returning Patient'];
    if (!in_array($val, $allowed, true)) {
        return "Select a valid patient type.";
    }
    return null;
}

function validate_clinic_department(?string $val): ?string {
    $allowed = ['General Medicine', 'Pediatrics', 'Dental', 'Dermatology', 'Cardiology'];
    if (!in_array($val, $allowed, true)) {
        return "Select a valid clinic department.";
    }
    return null;
}

/**
 * Validate Security Question Answer (Section 5)
 * 2-50 characters, letters and spaces only, no double spaces.
 */
function validate_security_answer(?string $val): ?string {
    $v = trim($val ?? '');
    if ($v === '') return "Security answer is required.";
    if (strlen($v) < 2 || strlen($v) > 50) return "Security answer must be between 2 and 50 characters.";
    if (strpos($v, '  ') !== false) return "Double space is not allowed.";
    if (!preg_match('/^[a-zA-Z\s]+$/', $v)) return "Answer must contain letters and spaces only.";
    return null;
}

/**
 * Validate that the user picked 3 different questions from the Section 5 bank
 */
function validate_chosen_security_questions(array $chosenKeys, array $answers): array {
    $errors = [];
    if (count($chosenKeys) !== 3) {
        $errors[] = "You must select exactly 3 security questions.";
        return $errors;
    }

    if (count(array_unique($chosenKeys)) !== 3) {
        $errors[] = "Please select three different security questions.";
    }

    foreach ($chosenKeys as $i => $qKey) {
        if (!array_key_exists($qKey, SECURITY_QUESTION_BANK)) {
            $errors[] = "Invalid security question selection for Question " . ($i + 1) . ".";
        }
        $ansErr = validate_security_answer($answers[$i] ?? '');
        if ($ansErr !== null) {
            $errors[] = "Question " . ($i + 1) . ": {$ansErr}";
        }
    }

    return $errors;
}
