<?php
/**
 * Patient Registration Form
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 4, 5, 6, 7 and Protections 1, 2, 3, 8, 12, 13
 */

$pageTitle = 'Patient Registration';
$extraJs = 'js/strength.js';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/validation.php';
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/captcha.php';
require_once __DIR__ . '/php/logger.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$values = [
    'id_number' => '',
    'first_name' => '',
    'middle_name' => '',
    'family_name' => '',
    'name_extension_select' => '',
    'name_extension_other' => '',
    'birthdate' => '',
    'age' => '',
    'purok_street' => '',
    'barangay' => '',
    'municipality_city' => '',
    'province' => '',
    'country' => 'Philippines',
    'zip_code' => '',
    'email' => '',
    'username' => '',
    'contact_number' => '',
    'patient_type' => '',
    'preferred_department' => '',
    'emergency_contact' => '',
    'sel_q1' => 'Q2',
    'sel_q2' => 'Q5',
    'sel_q3' => 'Q7',
    'ans_1' => '',
    'ans_2' => '',
    'ans_3' => '',
    'ans_q2' => '',
    'ans_q5' => '',
    'ans_q7' => '',
    'privacy_consent' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Protection 8: Honeypot check
    if (!empty($_POST['clinic_partner_website'])) {
        // Bot detected: Silent rejection
        log_activity('BOT_DETECTED', null, null, 'Honeypot field was filled during registration.');
        header('Location: index.php');
        exit;
    }

    // 2. Protection 3: CSRF token verification
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        log_activity('CSRF_REJECTED', null, null, 'Invalid CSRF token during registration submission.');
        $errors['form'] = 'Session security token invalid or expired. Please reload the page.';
    }

    // 3. Protection S7: CAPTCHA verification
    if (!verify_captcha($_POST['captcha_code'] ?? '')) {
        log_activity('CAPTCHA_FAILED', null, null, 'Registration CAPTCHA failed.');
        $errors['captcha_code'] = 'Incorrect security code. Please try again.';
    }

    // Read and sanitize raw inputs
    foreach ($values as $key => $default) {
        if ($key === 'privacy_consent') {
            $values[$key] = isset($_POST[$key]) ? '1' : '';
        } else {
            $values[$key] = trim($_POST[$key] ?? '');
        }
    }

    // 4. Validate Core Identity Fields (Handout 4.4, 4.5, 4.6, 4.7, 4.8, 4.9)
    $err = validate_id_number($values['id_number']);
    if ($err) $errors['id_number'] = $err;

    $err = validate_name_field($values['first_name'], 'First Name', true, 50);
    if ($err) $errors['first_name'] = $err;

    if ($values['middle_name'] !== '') {
        $err = validate_name_field($values['middle_name'], 'Middle Name', false, 50);
        if ($err) $errors['middle_name'] = $err;
    }

    $err = validate_name_field($values['family_name'], 'Family Name', true, 50);
    if ($err) $errors['family_name'] = $err;

    // Name Extension (Jr., Sr., or Other)
    $finalExtension = null;
    if ($values['name_extension_select'] === 'Other') {
        $finalExtension = $values['name_extension_other'];
        if ($finalExtension !== '') {
            $err = validate_name_field($finalExtension, 'Name Extension', false, 10);
            if ($err) $errors['name_extension'] = $err;
        }
    } elseif (!empty($values['name_extension_select'])) {
        $finalExtension = $values['name_extension_select'];
    }

    $birthCheck = validate_birthdate($values['birthdate']);
    if ($birthCheck['error']) {
        $errors['birthdate'] = $birthCheck['error'];
    }
    $calculatedAge = $birthCheck['age'];

    // Address fields
    $err = validate_address_purok($values['purok_street']);
    if ($err) $errors['purok_street'] = $err;

    $err = validate_address_text($values['barangay'], 'Barangay');
    if ($err) $errors['barangay'] = $err;

    $err = validate_address_text($values['municipality_city'], 'Municipality / City');
    if ($err) $errors['municipality_city'] = $err;

    $err = validate_address_text($values['province'], 'Province');
    if ($err) $errors['province'] = $err;

    $err = validate_address_text($values['country'], 'Country');
    if ($err) $errors['country'] = $err;

    $err = validate_zip_code($values['zip_code']);
    if ($err) $errors['zip_code'] = $err;

    $err = validate_email($values['email']);
    if ($err) $errors['email'] = $err;

    $err = validate_username($values['username']);
    if ($err) $errors['username'] = $err;

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $err = validate_password($password, $confirmPassword);
    if ($err) $errors['password'] = $err;

    // 5. Validate Clinic-Specific Fields
    $err = validate_contact_number($values['contact_number'], true);
    if ($err) $errors['contact_number'] = $err;

    $err = validate_patient_type($values['patient_type']);
    if ($err) $errors['patient_type'] = $err;

    $err = validate_clinic_department($values['preferred_department']);
    if ($err) $errors['preferred_department'] = $err;

    if ($values['emergency_contact'] !== '') {
        $err = validate_contact_number($values['emergency_contact'], false);
        if ($err) $errors['emergency_contact'] = $err;
    }

    // 6. Validate Authentication Questions (Section 5: User picks 3 different questions from bank)
    if ($values['ans_1'] === '' && $values['ans_q2'] !== '') $values['ans_1'] = $values['ans_q2'];
    if ($values['ans_2'] === '' && $values['ans_q5'] !== '') $values['ans_2'] = $values['ans_q5'];
    if ($values['ans_3'] === '' && $values['ans_q7'] !== '') $values['ans_3'] = $values['ans_q7'];

    $chosenKeys = [$values['sel_q1'], $values['sel_q2'], $values['sel_q3']];
    $answersArr = [$values['ans_1'], $values['ans_2'], $values['ans_3']];
    $qErrors = validate_chosen_security_questions($chosenKeys, $answersArr);
    if (!empty($qErrors)) {
        $errors['security_questions'] = implode('<br>', $qErrors);
    }

    // 7. Privacy Consent Checkbox (Section 4.3 & 4.10)
    if (empty($values['privacy_consent'])) {
        $errors['privacy_consent'] = 'You must agree to the Privacy Notice to register.';
    }

    // If completely valid, persist account into database
    if (empty($errors)) {
        try {
            $pdo = get_db_connection();
            $pdo->beginTransaction();

            // Hash password with bcrypt/Argon2 (Protection 1)
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Protection 13: Encrypt Contact Number with AES-256
            $contactEncrypted = encrypt_sensitive_field($values['contact_number']);
            $emergencyEncrypted = !empty($values['emergency_contact']) ? encrypt_sensitive_field($values['emergency_contact']) : null;

            $now = date('Y-m-d H:i:s');

            // Insert into users
            $userStmt = $pdo->prepare("
                INSERT INTO `users` (
                    `id_number`, `first_name`, `middle_name`, `family_name`, `name_extension`,
                    `birthdate`, `age`, `purok_street`, `barangay`, `municipality_city`,
                    `province`, `country`, `zip_code`, `email`, `username`, `password_hash`,
                    `contact_number_encrypted`, `patient_type`, `preferred_department`,
                    `emergency_contact_encrypted`, `role`, `is_verified`, `privacy_consent_at`,
                    `created_at`, `updated_at`
                ) VALUES (
                    :id, :first, :middle, :family, :ext,
                    :bdate, :age, :purok, :brgy, :city,
                    :province, :country, :zip, :email, :username, :pwd_hash,
                    :contact_enc, :patient_type, :department,
                    :emergency_enc, 'patient', 0, :consent_at,
                    :created, :updated
                )
            ");

            $userStmt->execute([
                ':id'             => $values['id_number'],
                ':first'          => $values['first_name'],
                ':middle'         => $values['middle_name'] !== '' ? $values['middle_name'] : null,
                ':family'         => $values['family_name'],
                ':ext'            => $finalExtension,
                ':bdate'          => $values['birthdate'],
                ':age'            => $calculatedAge,
                ':purok'          => $values['purok_street'],
                ':brgy'           => $values['barangay'],
                ':city'           => $values['municipality_city'],
                ':province'       => $values['province'],
                ':country'        => $values['country'],
                ':zip'            => $values['zip_code'],
                ':email'          => strtolower($values['email']),
                ':username'       => $values['username'],
                ':pwd_hash'       => $passwordHash,
                ':contact_enc'    => $contactEncrypted,
                ':patient_type'   => $values['patient_type'],
                ':department'     => $values['preferred_department'],
                ':emergency_enc'  => $emergencyEncrypted,
                ':consent_at'     => $now,
                ':created'        => $now,
                ':updated'        => $now
            ]);

            // Insert normalized security answers (lowercase, trimmed, hashed) (Section 5)
            $authStmt = $pdo->prepare("
                INSERT INTO `authentication_answers` (`user_id`, `question_key`, `answer_hash`, `created_at`)
                VALUES (:uid, :qkey, :ahash, :created)
            ");

            $questions = [
                $values['sel_q1'] => strtolower($values['ans_1']),
                $values['sel_q2'] => strtolower($values['ans_2']),
                $values['sel_q3'] => strtolower($values['ans_3'])
            ];

            foreach ($questions as $qKey => $cleanAns) {
                $authStmt->execute([
                    ':uid'     => $values['id_number'],
                    ':qkey'    => $qKey,
                    ':ahash'   => password_hash($cleanAns, PASSWORD_DEFAULT),
                    ':created' => $now
                ]);
            }

            // Record initial password in password history (Protection 7)
            add_password_to_history($values['id_number'], $passwordHash);

            // Append-only activity log (Section 13.4)
            log_activity('REGISTERED', $values['username'], $values['id_number'], 'New patient account registered. Awaiting email OTP verification.');

            $pdo->commit();

            // Dispatch 6-digit Registration OTP (Section 6)
            $fullName = $values['first_name'] . ' ' . $values['family_name'];
            create_otp_for_user($values['id_number'], $values['email'], $fullName, 'register');

            // Store pending OTP session variables
            $_SESSION['pending_otp_user_id'] = $values['id_number'];
            $_SESSION['pending_otp_purpose'] = 'register';
            $_SESSION['pending_otp_email'] = $values['email'];

            header('Location: verify_otp.php');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[REGISTRATION ERROR] ' . $e->getMessage(), 3, dirname(__DIR__) . '/logs/error.log');
            $errors['form'] = 'A registration database error occurred. Please try again.';
        }
    }
}
?>

<div class="card" style="max-width: 850px; margin: 0 auto;">
    <h1 class="card-title">Patient Account Registration</h1>
    <p class="card-subtitle">Fill in all required fields accurately. Input is strictly validated both in your browser and on our servers.</p>

    <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-danger">&#9888; <?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registration-form" novalidate>
        <!-- Protection 3: CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

        <!-- Protection 8: Honeypot field (hidden from genuine users via display:none) -->
        <div style="display:none !important;" aria-hidden="true">
            <label>Do not fill this field if human:</label>
            <input type="text" name="clinic_partner_website" tabindex="-1" autocomplete="off">
        </div>

        <!-- SECTION: PATIENT IDENTITY -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 20px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            1. Patient Identification &amp; Personal Info
        </h3>

        <div class="form-row">
            <div class="form-group">
                <!-- Handout 3.3 Rule: Field name ABOVE input box, required star -->
                <label for="id_number" class="form-label">ID Number<span class="required-star">*</span></label>
                <!-- Handout 3.3 Rule: Placeholder xxxx-xxxx -->
                <input type="text" id="id_number" name="id_number" class="form-control <?= isset($errors['id_number']) ? 'is-invalid' : '' ?>"
                       placeholder="xxxx-xxxx" maxlength="9" value="<?= e($values['id_number']) ?>" required>
                <!-- Handout 3.3 Rule: Error directly under field -->
                <span id="error-id_number" class="field-error"><?= e($errors['id_number'] ?? '') ?></span>
                <span class="field-hint">Format: 2023-0147 (Assigned University ID)</span>
            </div>

            <div class="form-group">
                <label for="patient_type" class="form-label">Patient Type<span class="required-star">*</span></label>
                <select id="patient_type" name="patient_type" class="form-control <?= isset($errors['patient_type']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select Patient Type --</option>
                    <option value="New Patient" <?= $values['patient_type'] === 'New Patient' ? 'selected' : '' ?>>New Patient</option>
                    <option value="Returning Patient" <?= $values['patient_type'] === 'Returning Patient' ? 'selected' : '' ?>>Returning Patient</option>
                </select>
                <span id="error-patient_type" class="field-error"><?= e($errors['patient_type'] ?? '') ?></span>
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label for="first_name" class="form-label">First Name<span class="required-star">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                       maxlength="21" value="<?= e($values['first_name']) ?>" placeholder="e.g. Juan" required>
                <span id="error-first_name" class="field-error"><?= e($errors['first_name'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <!-- Handout 3.3 Rule: word optional in red after name -->
                <label for="middle_name" class="form-label">Middle Name<span class="optional-text">optional</span></label>
                <input type="text" id="middle_name" name="middle_name" class="form-control <?= isset($errors['middle_name']) ? 'is-invalid' : '' ?>"
                       maxlength="21" value="<?= e($values['middle_name']) ?>" placeholder="e.g. Santos">
                <span id="error-middle_name" class="field-error"><?= e($errors['middle_name'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="family_name" class="form-label">Family Name<span class="required-star">*</span></label>
                <input type="text" id="family_name" name="family_name" class="form-control <?= isset($errors['family_name']) ? 'is-invalid' : '' ?>"
                       maxlength="21" value="<?= e($values['family_name']) ?>" placeholder="e.g. Dela Cruz" required>
                <span id="error-family_name" class="field-error"><?= e($errors['family_name'] ?? '') ?></span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="name_extension_select" class="form-label">Name Extension<span class="optional-text">optional</span></label>
                <select id="name_extension_select" name="name_extension_select" class="form-control">
                    <option value="">-- None --</option>
                    <option value="Jr." <?= $values['name_extension_select'] === 'Jr.' ? 'selected' : '' ?>>Jr.</option>
                    <option value="Sr." <?= $values['name_extension_select'] === 'Sr.' ? 'selected' : '' ?>>Sr.</option>
                    <option value="Other" <?= $values['name_extension_select'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                <input type="text" id="name_extension_other" name="name_extension_other" class="form-control"
                       style="margin-top: 8px; display: <?= $values['name_extension_select'] === 'Other' ? 'block' : 'none' ?>;"
                       placeholder="Type extension (e.g. III, IV, X)" value="<?= e($values['name_extension_other']) ?>">
                <span id="error-name_extension" class="field-error"><?= e($errors['name_extension'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="birthdate" class="form-label">Birthdate<span class="required-star">*</span></label>
                <input type="date" id="birthdate" name="birthdate" class="form-control <?= isset($errors['birthdate']) ? 'is-invalid' : '' ?>"
                       value="<?= e($values['birthdate']) ?>" required>
                <span id="error-birthdate" class="field-error"><?= e($errors['birthdate'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="age" class="form-label">Age (Auto-calculated)<span class="required-star">*</span></label>
                <!-- Handout 4.6: Read-only, auto-calculated -->
                <input type="number" id="age" name="age" class="form-control" value="<?= e($values['age']) ?>" readonly placeholder="Auto">
                <span class="field-hint">Recalculated on server. Must be &ge; 18.</span>
            </div>
        </div>

        <!-- SECTION: ADDRESS -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 24px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            2. Permanent Address
        </h3>

        <div class="form-row">
            <div class="form-group" style="grid-column: span 2;">
                <label for="purok_street" class="form-label">Purok / Street<span class="required-star">*</span></label>
                <input type="text" id="purok_street" name="purok_street" class="form-control <?= isset($errors['purok_street']) ? 'is-invalid' : '' ?>"
                       maxlength="50" value="<?= e($values['purok_street']) ?>" placeholder="e.g. Purok 4 Ampayon" required>
                <span id="error-purok_street" class="field-error"><?= e($errors['purok_street'] ?? '') ?></span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="barangay" class="form-label">Barangay<span class="required-star">*</span></label>
                <input type="text" id="barangay" name="barangay" class="form-control <?= isset($errors['barangay']) ? 'is-invalid' : '' ?>"
                       maxlength="50" value="<?= e($values['barangay']) ?>" placeholder="e.g. Ampayon" required>
                <span id="error-barangay" class="field-error"><?= e($errors['barangay'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="municipality_city" class="form-label">Municipality / City<span class="required-star">*</span></label>
                <input type="text" id="municipality_city" name="municipality_city" class="form-control <?= isset($errors['municipality_city']) ? 'is-invalid' : '' ?>"
                       maxlength="50" value="<?= e($values['municipality_city']) ?>" placeholder="e.g. Butuan City" required>
                <span id="error-municipality_city" class="field-error"><?= e($errors['municipality_city'] ?? '') ?></span>
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label for="province" class="form-label">Province<span class="required-star">*</span></label>
                <input type="text" id="province" name="province" class="form-control <?= isset($errors['province']) ? 'is-invalid' : '' ?>"
                       maxlength="50" value="<?= e($values['province']) ?>" placeholder="e.g. Agusan Del Norte" required>
                <span id="error-province" class="field-error"><?= e($errors['province'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="country" class="form-label">Country<span class="required-star">*</span></label>
                <input type="text" id="country" name="country" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>"
                       value="<?= e($values['country']) ?>" required>
                <span id="error-country" class="field-error"><?= e($errors['country'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="zip_code" class="form-label">ZIP Code<span class="required-star">*</span></label>
                <input type="text" id="zip_code" name="zip_code" class="form-control <?= isset($errors['zip_code']) ? 'is-invalid' : '' ?>"
                       maxlength="4" value="<?= e($values['zip_code']) ?>" placeholder="e.g. 8600" required>
                <span id="error-zip_code" class="field-error"><?= e($errors['zip_code'] ?? '') ?></span>
            </div>
        </div>

        <!-- SECTION: CONTACT & CLINIC DEPARTMENT -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 24px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            3. Contact Information &amp; Clinic Preferences
        </h3>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_number" class="form-label">Contact Number<span class="required-star">*</span></label>
                <input type="text" id="contact_number" name="contact_number" class="form-control <?= isset($errors['contact_number']) ? 'is-invalid' : '' ?>"
                       maxlength="11" value="<?= e($values['contact_number']) ?>" placeholder="09XXXXXXXXX" required>
                <span id="error-contact_number" class="field-error"><?= e($errors['contact_number'] ?? '') ?></span>
                <span class="field-hint">&#128274; Encrypted at rest in database with AES-256 (Protection 13).</span>
            </div>

            <div class="form-group">
                <label for="emergency_contact" class="form-label">Emergency Contact Number<span class="optional-text">optional</span></label>
                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control <?= isset($errors['emergency_contact']) ? 'is-invalid' : '' ?>"
                       maxlength="11" value="<?= e($values['emergency_contact']) ?>" placeholder="09XXXXXXXXX">
                <span id="error-emergency_contact" class="field-error"><?= e($errors['emergency_contact'] ?? '') ?></span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email" class="form-label">Email Address<span class="required-star">*</span></label>
                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       maxlength="100" value="<?= e($values['email']) ?>" placeholder="e.g. juan@example.com" required>
                <span id="error-email" class="field-error"><?= e($errors['email'] ?? '') ?></span>
                <span class="field-hint">Must be an accessible inbox. Verification OTPs will be sent here.</span>
            </div>

            <div class="form-group">
                <label for="preferred_department" class="form-label">Preferred Clinic Department<span class="required-star">*</span></label>
                <select id="preferred_department" name="preferred_department" class="form-control <?= isset($errors['preferred_department']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select Clinic Department --</option>
                    <option value="General Medicine" <?= $values['preferred_department'] === 'General Medicine' ? 'selected' : '' ?>>General Medicine</option>
                    <option value="Pediatrics" <?= $values['preferred_department'] === 'Pediatrics' ? 'selected' : '' ?>>Pediatrics</option>
                    <option value="Dental" <?= $values['preferred_department'] === 'Dental' ? 'selected' : '' ?>>Dental</option>
                    <option value="Dermatology" <?= $values['preferred_department'] === 'Dermatology' ? 'selected' : '' ?>>Dermatology</option>
                    <option value="Cardiology" <?= $values['preferred_department'] === 'Cardiology' ? 'selected' : '' ?>>Cardiology</option>
                </select>
                <span id="error-preferred_department" class="field-error"><?= e($errors['preferred_department'] ?? '') ?></span>
            </div>
        </div>

        <!-- SECTION: ACCOUNT CREDENTIALS -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 24px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            4. Account Credentials &amp; Password Strength
        </h3>

        <div class="form-group">
            <label for="username" class="form-label">Username<span class="required-star">*</span></label>
            <input type="text" id="username" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                   maxlength="17" value="<?= e($values['username']) ?>" placeholder="6 to 17 letters & numbers" required>
            <span id="error-username" class="field-error"><?= e($errors['username'] ?? '') ?></span>
            <span class="field-hint">Seed limits: 6 to 17 characters, starts with a letter. Checked live as you type.</span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password" class="form-label">Password<span class="required-star">*</span></label>
                <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       maxlength="64" placeholder="Minimum 9 characters" required>
                <!-- Live strength meter (Section 4.9) -->
                <div class="strength-meter-container">
                    <div class="strength-bar-track">
                        <div id="strength-bar" class="strength-bar"></div>
                    </div>
                    <span id="strength-text" class="strength-text">None</span>
                </div>
                <span id="error-password" class="field-error"><?= e($errors['password'] ?? '') ?></span>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Re-enter Password<span class="required-star">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       maxlength="64" placeholder="Repeat your password" required>
                <span id="error-confirm_password" class="field-error"></span>
            </div>
        </div>

        <!-- SECTION: SECURITY QUESTIONS (Section 5) -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 24px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            5. Three Authentication Questions (Choose 3 different questions from the bank)
        </h3>
        <p class="field-hint" style="margin-bottom: 14px;">Select 3 different questions from the bank. Answers must be 2 to 50 characters, letters and spaces only. Stored as one-way Bcrypt hashes.</p>

        <?php if (!empty($errors['security_questions'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 15px; font-size: 13.5px;">
                <?= $errors['security_questions'] ?>
            </div>
        <?php endif; ?>

        <!-- Question 1 -->
        <div class="form-group" style="background: #fdfefe; border: 1px solid var(--border); border-radius: 6px; padding: 14px; margin-bottom: 14px;">
            <label for="sel_q1" class="form-label" style="font-weight: 600;">Security Question 1<span class="required-star">*</span></label>
            <select id="sel_q1" name="sel_q1" class="form-control" style="margin-bottom: 8px;" required>
                <?php foreach (SECURITY_QUESTION_BANK as $qCode => $qText): ?>
                    <option value="<?= $qCode ?>" <?= $values['sel_q1'] === $qCode ? 'selected' : '' ?>>
                        <?= $qCode ?>: <?= e($qText) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="ans_1" name="ans_1" class="form-control"
                   maxlength="50" value="<?= e($values['ans_1'] !== '' ? $values['ans_1'] : $values['ans_q2']) ?>" placeholder="Answer to Question 1" required>
        </div>

        <!-- Question 2 -->
        <div class="form-group" style="background: #fdfefe; border: 1px solid var(--border); border-radius: 6px; padding: 14px; margin-bottom: 14px;">
            <label for="sel_q2" class="form-label" style="font-weight: 600;">Security Question 2<span class="required-star">*</span></label>
            <select id="sel_q2" name="sel_q2" class="form-control" style="margin-bottom: 8px;" required>
                <?php foreach (SECURITY_QUESTION_BANK as $qCode => $qText): ?>
                    <option value="<?= $qCode ?>" <?= $values['sel_q2'] === $qCode ? 'selected' : '' ?>>
                        <?= $qCode ?>: <?= e($qText) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="ans_2" name="ans_2" class="form-control"
                   maxlength="50" value="<?= e($values['ans_2'] !== '' ? $values['ans_2'] : $values['ans_q5']) ?>" placeholder="Answer to Question 2" required>
        </div>

        <!-- Question 3 -->
        <div class="form-group" style="background: #fdfefe; border: 1px solid var(--border); border-radius: 6px; padding: 14px; margin-bottom: 14px;">
            <label for="sel_q3" class="form-label" style="font-weight: 600;">Security Question 3<span class="required-star">*</span></label>
            <select id="sel_q3" name="sel_q3" class="form-control" style="margin-bottom: 8px;" required>
                <?php foreach (SECURITY_QUESTION_BANK as $qCode => $qText): ?>
                    <option value="<?= $qCode ?>" <?= $values['sel_q3'] === $qCode ? 'selected' : '' ?>>
                        <?= $qCode ?>: <?= e($qText) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="ans_3" name="ans_3" class="form-control"
                   maxlength="50" value="<?= e($values['ans_3'] !== '' ? $values['ans_3'] : $values['ans_q7']) ?>" placeholder="Answer to Question 3" required>
        </div>

        <!-- SECTION: PRIVACY NOTICE & CAPTCHA -->
        <h3 style="color: var(--primary); font-size: 16px; margin: 24px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px;">
            6. Privacy Consent &amp; Bot Protection
        </h3>

        <!-- Handout Section 4.3: Unchecked checkbox with exact text -->
        <div class="form-group" style="background-color: #f8f9fa; padding: 14px; border: 1px solid var(--border); border-radius: 5px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13.5px; line-height: 1.4;">
                <input type="checkbox" id="privacy_consent" name="privacy_consent" value="1"
                       <?= $values['privacy_consent'] === '1' ? 'checked' : '' ?> style="margin-top: 3px;" required>
                <span>
                    I have read the <a href="privacy_notice.php" target="_blank" style="color: var(--primary); font-weight: 600;">Privacy Notice</a>
                    and agree to the collection and processing of my personal data in accordance with the Data Privacy Act of 2012 (RA 10173).<span class="required-star">*</span>
                </span>
            </label>
            <span id="error-privacy_consent" class="field-error"><?= e($errors['privacy_consent'] ?? '') ?></span>
        </div>

        <!-- Handout Section 7: CAPTCHA protection -->
        <div class="form-group">
            <label for="captcha_code" class="form-label">Security Verification (CAPTCHA)<span class="required-star">*</span></label>
            <div class="captcha-row">
                <img src="php/captcha_image.php?t=<?= time() ?>" id="captcha-img" class="captcha-img" alt="CAPTCHA">
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('captcha-img').src='php/captcha_image.php?t='+new Date().getTime()">
                    &#8634; Refresh
                </button>
                <input type="text" id="captcha_code" name="captcha_code" class="form-control" style="max-width: 180px;"
                       placeholder="Enter characters" maxlength="6" required>
            </div>
            <span id="error-captcha_code" class="field-error"><?= e($errors['captcha_code'] ?? '') ?></span>
            <span class="field-hint">Case-insensitive. Single-use bot protection.</span>
        </div>

        <div style="margin-top: 28px; text-align: center;">
            <button type="submit" id="submit-btn" class="btn btn-primary" style="padding: 13px 36px; font-size: 15px;">
                Complete Registration &amp; Verify Email &#8594;
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
