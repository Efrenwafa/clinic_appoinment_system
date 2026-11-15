<?php
/**
 * 6-Digit Email OTP Verification Portal
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 6 (Email verification and OTP) & Protection S5
 */

$pageTitle = 'Verify One-Time Password';
$extraJs = 'js/lockout.js';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/logger.php';

// Check if there is an active pending OTP flow
$pendingUserId = $_SESSION['pending_otp_user_id'] ?? null;
$pendingPurpose = $_SESSION['pending_otp_purpose'] ?? null;
$pendingEmail = $_SESSION['pending_otp_email'] ?? '';

// Protection S5: Direct URL access without pending OTP sends user back to login/home
if (!$pendingUserId || !$pendingPurpose) {
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle Resend Request
if (isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid security token. Please refresh.';
    } else {
        $check = can_resend_otp($pendingUserId, $pendingPurpose);
        if (!$check['allowed']) {
            $errorMessage = $check['error'];
        } else {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id_number` = :id");
            $stmt->execute([':id' => $pendingUserId]);
            $user = $stmt->fetch();

            if ($user) {
                $fullName = $user['first_name'] . ' ' . $user['family_name'];
                if (create_otp_for_user($pendingUserId, $user['email'], $fullName, $pendingPurpose)) {
                    $successMessage = 'A new 6-digit verification code has been dispatched to your email.';
                } else {
                    $errorMessage = 'Failed to deliver verification email. Please contact clinic support.';
                }
            }
        }
    }
}

// Handle OTP Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security token expired. Please try again.';
    } else {
        $submittedCode = trim($_POST['otp_code'] ?? '');

        if (!preg_match('/^\d{6}$/', $submittedCode)) {
            $errorMessage = 'Please enter a valid 6-digit numerical code.';
        } else {
            $result = verify_otp_submission($pendingUserId, $pendingPurpose, $submittedCode);

            if (!$result['success']) {
                $errorMessage = $result['error'];
            } else {
                // Correct OTP verified! Route based on purpose
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id_number` = :id");
                $stmt->execute([':id' => $pendingUserId]);
                $user = $stmt->fetch();

                if ($pendingPurpose === 'register') {
                    // Registration complete
                    unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_purpose'], $_SESSION['pending_otp_email']);
                    header('Location: login.php?msg=verified');
                    exit;

                } elseif ($pendingPurpose === 'login') {
                    // Handout 8.1: Create session, regenerate session ID, reset failure counters
                    session_regenerate_id(true); // Protection 6: Regenerate session ID on elevation
                    $_SESSION['user_id'] = $user['id_number'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['family_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // Reset failed login counter upon authenticated entry
                    reset_account_failures($user['id_number']);

                    unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_purpose'], $_SESSION['pending_otp_email']);
                    log_activity('LOGIN_SUCCESS', $user['username'], $user['id_number'], 'Successful two-step login (Password + OTP). Session established.');

                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: appointments.php');
                    }
                    exit;

                } elseif ($pendingPurpose === 'reset') {
                    // Forgot Password flow: Unlock Change Password page with single-use reset token
                    $resetToken = bin2hex(random_bytes(32));
                    $_SESSION['reset_authorized_user_id'] = $user['id_number'];
                    $_SESSION['reset_authorized_token'] = $resetToken;

                    unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_purpose'], $_SESSION['pending_otp_email']);
                    header('Location: change_password.php?token=' . urlencode($resetToken));
                    exit;
                }
            }
        }
    }
}

// Calculate resend cooldown status
$resendStatus = can_resend_otp($pendingUserId, $pendingPurpose);
$cooldownSec = $resendStatus['cooldown_remaining'];
?>

<div class="card" style="max-width: 520px; margin: 20px auto; text-align: center;">
    <div style="font-size: 40px; color: var(--primary); margin-bottom: 8px;">&#9993;</div>
    <h1 class="card-title" style="font-size: 22px;">Enter Verification Code</h1>
    <p class="card-subtitle">
        A 6-digit code was sent to <strong><?= e($pendingEmail) ?></strong>.<br>
        Code expires in <strong>4 minutes</strong> based on your seed.
    </p>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger" style="text-align: left;">&#9888; <?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success" style="text-align: left;">&#10003; <?= e($successMessage) ?></div>
    <?php endif; ?>

    <form method="POST" action="verify_otp.php" style="margin-top: 20px;">
        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="verify_otp">

        <div class="form-group">
            <label for="otp_code" class="form-label" style="text-align: center;">
                6-Digit Code<span class="required-star">*</span>
            </label>
            <input type="text" id="otp_code" name="otp_code" class="form-control"
                   maxlength="6" autofocus placeholder="&bull; &bull; &bull; &bull; &bull; &bull;"
                   style="font-size: 28px; letter-spacing: 8px; text-align: center; max-width: 260px; margin: 0 auto; font-family: Consolas, monospace;"
                   required>
            <span class="field-hint">Note: 3 wrong entries will automatically cancel this code.</span>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 15px;">
                Verify Code &amp; Continue &#8594;
            </button>
        </div>
    </form>

    <!-- Resend OTP Section with 60-Second Cooldown (Section 6.2) -->
    <div style="margin-top: 28px; border-top: 1px solid var(--border); padding-top: 18px;">
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;">Didn't receive the email? Check your spam folder or request a new code.</p>
        <form method="POST" action="verify_otp.php">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="resend_otp">

            <?php if ($cooldownSec > 0): ?>
                <div id="lockout-box" class="lockout-box" style="padding: 10px; margin-bottom: 10px;">
                    <span style="font-size: 13px; color: #7e5109;">Resend available in: </span>
                    <strong id="lockout-countdown"><?= (int)$cooldownSec ?></strong> seconds
                </div>
                <button type="submit" id="login-btn" class="btn btn-secondary btn-sm" disabled>Resend Code</button>
            <?php else: ?>
                <button type="submit" class="btn btn-secondary btn-sm">&#8634; Resend Code</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
