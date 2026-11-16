<?php
/**
 * Two-Step Log-in Page
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 8, Section 7 (CAPTCHA), and Protections 1, 3, 4, 5, 6
 */

$pageTitle = 'Log-in';
$extraJs = 'js/lockout.js';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/captcha.php';
require_once __DIR__ . '/php/logger.php';

// If already logged in, redirect to appropriate portal
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: appointments.php');
    }
    exit;
}

$errorMessage = '';
$infoMessage = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'verified') {
    $infoMessage = 'Your account has been verified! You may now sign in.';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'session_expired') {
    $errorMessage = 'Your session expired due to 15 minutes of inactivity. Please sign in again.';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $infoMessage = 'You have successfully signed out.';
}

// 1. Protection 5: Check IP blocking status
$ipStatus = check_ip_status();
if ($ipStatus['is_blocked']) {
    $errorMessage = "Your IP address is temporarily blocked for security reasons. Time remaining: {$ipStatus['seconds_remaining']} seconds.";
}

// 2. Check Lockout Status for currently typed or remembered user
$lockoutState = [
    'is_locked' => false,
    'seconds_remaining' => 0,
    'show_forgot_password' => false
];

$typedUsername = trim($_POST['username'] ?? ($_GET['u'] ?? ''));

if ($typedUsername !== '') {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :u");
    $stmt->execute([':u' => $typedUsername]);
    $user = $stmt->fetch();
    if ($user) {
        $lockoutState = check_account_lockout($user);
    }
}

// Process POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if IP is blocked
    if ($ipStatus['is_blocked']) {
        $errorMessage = "Access blocked from this IP. Please wait {$ipStatus['seconds_remaining']} seconds.";
    }
    // Check CSRF token (Protection 3)
    elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        log_activity('CSRF_REJECTED', $typedUsername, null, 'Invalid CSRF token during login.');
        $errorMessage = 'Security token invalid. Please reload the page.';
    }
    // Check CAPTCHA (Section 7)
    elseif (!verify_captcha($_POST['captcha_code'] ?? '')) {
        log_activity('CAPTCHA_FAILED', $typedUsername, null, 'Login CAPTCHA verification failed.');
        $errorMessage = 'Incorrect CAPTCHA answer. Please try again.';
    }
    else {
        $password = $_POST['password'] ?? '';

        // Query user with prepared statement (Protection 1)
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :u");
        $stmt->execute([':u' => $typedUsername]);
        $user = $stmt->fetch();

        if ($user) {
            $lockoutState = check_account_lockout($user);

            // Handout 8.3: Server-enforced lockout check
            if ($lockoutState['is_locked']) {
                $errorMessage = "Account temporarily locked. Please wait {$lockoutState['seconds_remaining']} seconds before trying again.";
            } elseif (password_verify($password, $user['password_hash'])) {
                // Check if account is verified
                if ((int)$user['is_verified'] !== 1) {
                    // Send registration OTP again
                    create_otp_for_user($user['id_number'], $user['email'], $user['first_name'] . ' ' . $user['family_name'], 'register');
                    $_SESSION['pending_otp_user_id'] = $user['id_number'];
                    $_SESSION['pending_otp_purpose'] = 'register';
                    $_SESSION['pending_otp_email'] = $user['email'];
                    header('Location: verify_otp.php');
                    exit;
                }

                // Correct password: Send Log-in OTP (Handout 8.1: Do NOT create session yet)
                $fullName = $user['first_name'] . ' ' . $user['family_name'];
                create_otp_for_user($user['id_number'], $user['email'], $fullName, 'login');

                $_SESSION['pending_otp_user_id'] = $user['id_number'];
                $_SESSION['pending_otp_purpose'] = 'login';
                $_SESSION['pending_otp_email'] = $user['email'];

                header('Location: verify_otp.php');
                exit;

            } else {
                // Incorrect password for valid user: Record failure
                record_account_failure($user['id_number']);
                record_ip_failure();
                log_activity('LOGIN_FAILED', $typedUsername, $user['id_number'], 'Failed login: incorrect password.');

                // Refresh lockout state after recording failure
                $stmt->execute([':u' => $typedUsername]);
                $user = $stmt->fetch();
                $lockoutState = check_account_lockout($user);

                // Handout 8.1 Rule: Show ONLY "Invalid username or password."
                $errorMessage = 'Invalid username or password.';
            }
        } else {
            // Nonexistent username: Record IP failure
            record_ip_failure();
            log_activity('LOGIN_FAILED', $typedUsername, null, 'Failed login: nonexistent username.');

            // Handout 8.1 Rule: Exactly same message whether username exists or not (Protection 4)
            $errorMessage = 'Invalid username or password.';
        }
    }
}
?>

<div class="card" style="max-width: 480px; margin: 30px auto;">
    <h1 class="card-title" style="text-align: center; font-size: 24px;">Clinic Portal Sign-in</h1>
    <p class="card-subtitle" style="text-align: center;">Two-step authentication: Password followed by OTP</p>

    <?php if ($infoMessage): ?>
        <div class="alert alert-success">&#10003; <?= e($infoMessage) ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">&#9888; <?= e($errorMessage) ?></div>
    <?php endif; ?>

    <!-- Progressive Lockout Countdown Box (Handout 8.3) -->
    <?php if ($lockoutState['is_locked']): ?>
        <div id="lockout-box" class="lockout-box">
            <p><strong>&#128274; Account Temporarily Locked (Tier <?= (int)$lockoutState['lockout_tier'] ?>)</strong></p>
            <p class="field-hint">Too many failed sign-in attempts. You must wait before trying again:</p>
            <div id="lockout-countdown" class="lockout-timer"><?= (int)$lockoutState['seconds_remaining'] ?></div>
            <p class="field-hint">seconds remaining</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="login-form">
        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

        <div class="form-group">
            <label for="username" class="form-label">Username<span class="required-star">*</span></label>
            <input type="text" id="username" name="username" class="form-control"
                   maxlength="17" value="<?= e($typedUsername) ?>" placeholder="Your username" required
                   <?= $lockoutState['is_locked'] ? 'readonly' : '' ?>>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label for="password" class="form-label" style="margin-bottom: 0;">Password<span class="required-star">*</span></label>
                <!-- Handout Section 8.2: Show Password toggle -->
                <button type="button" id="toggle-pwd-btn" class="btn btn-secondary btn-sm"
                        style="padding: 2px 8px; font-size: 11px;"
                        onclick="let p=document.getElementById('password'); if(p.type==='password'){p.type='text'; this.textContent='Hide';}else{p.type='password'; this.textContent='Show';}">
                    Show
                </button>
            </div>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Your password" required
                   <?= $lockoutState['is_locked'] ? 'readonly' : '' ?>>
        </div>

        <!-- Handout Section 7: CAPTCHA on Login Form -->
        <div class="form-group">
            <label for="captcha_code" class="form-label">Security Verification (CAPTCHA)<span class="required-star">*</span></label>
            <div class="captcha-row">
                <img src="php/captcha_image.php?t=<?= time() ?>" id="login-captcha-img" class="captcha-img" alt="CAPTCHA">
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('login-captcha-img').src='php/captcha_image.php?t='+new Date().getTime()">
                    &#8634; Refresh
                </button>
                <input type="text" id="captcha_code" name="captcha_code" class="form-control" style="max-width: 140px;"
                       placeholder="Code" maxlength="6" required <?= $lockoutState['is_locked'] ? 'readonly' : '' ?>>
            </div>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" id="login-btn" class="btn btn-primary" style="width: 100%; padding: 12px;"
                    <?= ($lockoutState['is_locked'] || $ipStatus['is_blocked']) ? 'disabled' : '' ?>>
                Proceed to Two-Step Verification &#8594;
            </button>
        </div>
    </form>

    <!-- Handout Section 8.2: Show Forgot Password Link after 3 failed attempts -->
    <div style="margin-top: 24px; text-align: center; border-top: 1px solid var(--border); padding-top: 16px; font-size: 13.5px;">
        <?php if ($lockoutState['show_forgot_password']): ?>
            <p style="margin-bottom: 10px;">
                <a href="forgot_password.php?u=<?= urlencode($typedUsername) ?>" style="color: var(--danger); font-weight: bold;">
                    Forgot Password? Reset Here
                </a>
            </p>
        <?php endif; ?>

        <!-- Handout 8.3: Register link is disabled while locked -->
        <p>
            Don't have an account?
            <a href="register.php" id="register-link" style="color: var(--primary); font-weight: 600;">Please register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
