<?php
/**
 * Account Recovery & Security Question Verification
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 10 & Protection 4 (No username enumeration)
 */

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/captcha.php';
require_once __DIR__ . '/php/logger.php';

$step = 1; // Step 1: Identify username + CAPTCHA, Step 2: Answer security question
$errorMessage = '';
$username = trim($_GET['u'] ?? ($_POST['username'] ?? ''));
$userQuestions = [];

$questionBank = [
    'Q2' => 'Who was your best friend in elementary school?',
    'Q5' => 'What street did you live on in Grade 1?',
    'Q7' => 'What was the name of your elementary school?'
];

// If username was provided and validated in Step 1, or continuing to Step 2
if ($username !== '') {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT `id_number`, `username`, `email`, `first_name`, `family_name`, `failed_logins`, `locked_until` FROM `users` WHERE `username` = :u");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        $lockout = check_account_lockout($user);
        if ($lockout['is_locked']) {
            $errorMessage = "This account is temporarily locked for {$lockout['seconds_remaining']} seconds.";
        } else {
            // Retrieve user's registered questions
            $qStmt = $pdo->prepare("SELECT `question_key` FROM `authentication_answers` WHERE `user_id` = :uid");
            $qStmt->execute([':uid' => $user['id_number']]);
            $registeredKeys = $qStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($registeredKeys as $k) {
                if (isset($questionBank[$k])) {
                    $userQuestions[$k] = $questionBank[$k];
                }
            }
            if (!empty($userQuestions)) {
                $step = 2;
            }
        }
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security token invalid. Please refresh.';
    } elseif ($action === 'identify') {
        // Step 1 Submission
        if (!verify_captcha($_POST['captcha_code'] ?? '')) {
            log_activity('CAPTCHA_FAILED', $username, null, 'Forgot Password CAPTCHA failed.');
            $errorMessage = 'Incorrect security code. Please try again.';
            $step = 1;
        } else {
            // Move to Step 2 if user exists, else simulate Step 2 (Section 10: Behave as if user existed)
            $step = 2;
            if (empty($userQuestions)) {
                // Dummy questions to prevent user enumeration
                $userQuestions = [
                    'Q2' => 'Who was your best friend in elementary school?',
                    'Q5' => 'What street did you live on in Grade 1?'
                ];
            }
        }
    } elseif ($action === 'verify_answer') {
        $selectedQuestion = $_POST['question_key'] ?? '';
        $yourAnswer = trim($_POST['your_answer'] ?? '');
        $reenterAnswer = trim($_POST['reenter_answer'] ?? '');

        // Handout Section 10: "Your Answer and Re-enter Answer; they must match before anything is checked."
        if ($yourAnswer === '' || $reenterAnswer === '') {
            $errorMessage = 'Please provide and confirm your security answer.';
            $step = 2;
        } elseif ($yourAnswer !== $reenterAnswer) {
            $errorMessage = 'Your answers do not match.';
            $step = 2;
        } else {
            // Check answer
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :u");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $ansStmt = $pdo->prepare("SELECT `answer_hash` FROM `authentication_answers` WHERE `user_id` = :uid AND `question_key` = :qkey");
                $ansStmt->execute([':uid' => $user['id_number'], ':qkey' => $selectedQuestion]);
                $storedHash = $ansStmt->fetchColumn();

                // Normalized comparison (lowercase, trimmed)
                if ($storedHash && password_verify(strtolower($yourAnswer), $storedHash)) {
                    // Correct answer! Send Reset OTP (Section 10.4)
                    $fullName = $user['first_name'] . ' ' . $user['family_name'];
                    create_otp_for_user($user['id_number'], $user['email'], $fullName, 'reset');

                    $_SESSION['pending_otp_user_id'] = $user['id_number'];
                    $_SESSION['pending_otp_purpose'] = 'reset';
                    $_SESSION['pending_otp_email'] = $user['email'];

                    log_activity('RESET_REQUESTED', $username, $user['id_number'], 'Security answer correct. Reset OTP dispatched.');
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    // Handout Section 10.3: "A wrong answer counts toward the same lockout as log-in."
                    record_account_failure($user['id_number']);
                    record_ip_failure();
                    log_activity('LOGIN_FAILED', $username, $user['id_number'], 'Wrong security answer submitted in recovery.');

                    $errorMessage = 'Incorrect security answer. This attempt counts toward account lockout.';
                    $step = 2;
                }
            } else {
                // Nonexistent user: Record IP failure, generic response
                record_ip_failure();
                $errorMessage = 'Incorrect security answer.';
                $step = 2;
            }
        }
    }
}
?>

<div class="card" style="max-width: 520px; margin: 30px auto;">
    <h1 class="card-title" style="text-align: center; font-size: 22px;">Account Recovery &middot; Forgot Password</h1>
    <p class="card-subtitle" style="text-align: center;">Verify your security question and identity via email OTP</p>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">&#9888; <?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <!-- STEP 1: IDENTIFY USERNAME & SOLVE CAPTCHA -->
        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="identify">

            <div class="form-group">
                <label for="username" class="form-label">Username<span class="required-star">*</span></label>
                <input type="text" id="username" name="username" class="form-control"
                       maxlength="17" value="<?= e($username) ?>" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="captcha_code" class="form-label">Security Verification (CAPTCHA)<span class="required-star">*</span></label>
                <div class="captcha-row">
                    <img src="php/captcha_image.php?t=<?= time() ?>" id="fp-captcha-img" class="captcha-img" alt="CAPTCHA">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('fp-captcha-img').src='php/captcha_image.php?t='+new Date().getTime()">
                        &#8634; Refresh
                    </button>
                    <input type="text" id="captcha_code" name="captcha_code" class="form-control" style="max-width: 140px;"
                           placeholder="Code" maxlength="6" required>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Continue to Security Question &#8594;
                </button>
            </div>
        </form>

    <?php else: ?>
        <!-- STEP 2: ANSWER CHOSEN SECURITY QUESTION -->
        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="verify_answer">
            <input type="hidden" name="username" value="<?= e($username) ?>">

            <div class="form-group">
                <!-- Handout Section 10.2: Show "Choose the following question" with user's registered questions -->
                <label for="question_key" class="form-label">Choose the following question<span class="required-star">*</span></label>
                <select id="question_key" name="question_key" class="form-control" required>
                    <?php foreach ($userQuestions as $qKey => $qText): ?>
                        <option value="<?= e($qKey) ?>"><?= e($qText) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <!-- Handout Section 10.2: Fields Your Answer and Re-enter Answer -->
                <label for="your_answer" class="form-label">Your Answer<span class="required-star">*</span></label>
                <input type="text" id="your_answer" name="your_answer" class="form-control"
                       maxlength="50" placeholder="Type your security answer" required>
            </div>

            <div class="form-group">
                <label for="reenter_answer" class="form-label">Re-enter Answer<span class="required-star">*</span></label>
                <input type="text" id="reenter_answer" name="reenter_answer" class="form-control"
                       maxlength="50" placeholder="Re-enter your security answer" required>
                <span class="field-hint">Answers are compared case-insensitively and must match.</span>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Verify Answer &amp; Request Reset OTP &#8594;
                </button>
            </div>
        </form>
    <?php endif; ?>

    <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border); padding-top: 14px;">
        <a href="login.php" style="color: var(--primary); font-size: 13.5px;">&#8592; Back to Sign-in</a>
    </div>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
