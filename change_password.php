<?php
/**
 * Safe Password Change (One-Time Token Authorized)
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 11 & Protection S5
 */

$pageTitle = 'Change Password';
$extraJs = 'js/strength.js';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/validation.php';
require_once __DIR__ . '/php/mailer.php';
require_once __DIR__ . '/php/logger.php';

// Handout Protection S5: Direct access defense
// Instructor test: Open change_password.php directly in browser. Must refuse!
$authorizedUserId = $_SESSION['reset_authorized_user_id'] ?? null;
$sessionToken = $_SESSION['reset_authorized_token'] ?? null;
$queryToken = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$authorizedUserId || !$sessionToken || empty($queryToken) || !hash_equals($sessionToken, $queryToken)) {
    log_activity('UNAUTHORIZED_ACCESS', null, null, 'Direct attempt to access change_password.php without reset authorization.');
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security token invalid. Please refresh.';
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation rules from Section 11 & 4.9
        if (strlen($newPassword) < 9) {
            $errorMessage = 'Password must be at least 9 characters.';
        } elseif (strlen($newPassword) > 64) {
            $errorMessage = 'Password cannot exceed 64 characters.';
        } elseif (evaluate_password_strength($newPassword) === 'Weak') {
            $errorMessage = 'Password is too weak. Weak passwords are not accepted.';
        } elseif ($newPassword !== $confirmPassword) {
            // Handout Section 11 exact message: "Mismatch Password."
            $errorMessage = 'Mismatch Password.';
        } elseif (is_password_in_history($authorizedUserId, $newPassword)) {
            // Handout Section 11 exact message: "You cannot reuse a recent password."
            $errorMessage = 'You cannot reuse a recent password.';
        } else {
            // Change password
            $pdo = get_db_connection();
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update user password and reset lockout
            $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :hash, `failed_logins` = 0, `lockout_tier` = 0, `locked_until` = NULL, `updated_at` = NOW() WHERE `id_number` = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $authorizedUserId]);

            // Add to password history (Protection 7)
            add_password_to_history($authorizedUserId, $newHash);

            // Fetch user email for alert
            $uStmt = $pdo->prepare("SELECT `email`, `first_name`, `family_name`, `username` FROM `users` WHERE `id_number` = :id");
            $uStmt->execute([':id' => $authorizedUserId]);
            $u = $uStmt->fetch();

            if ($u) {
                // Handout Section 9: Send "Your password was changed" alert email
                send_password_changed_alert_email($u['email'], $u['first_name'] . ' ' . $u['family_name']);
                log_activity('PASSWORD_CHANGED', $u['username'], $authorizedUserId, 'Password changed successfully via recovery flow.');
            }

            // Invalidate the reset token immediately (Handout Section 11: Single-use, reloading cannot use it again)
            unset($_SESSION['reset_authorized_user_id'], $_SESSION['reset_authorized_token']);

            // Handout Section 11 exact message: "Successfully Change Password."
            $successMessage = 'Successfully Change Password.';
        }
    }
}
?>

<div class="card" style="max-width: 480px; margin: 30px auto;">
    <h1 class="card-title" style="text-align: center; font-size: 22px;">Set New Password</h1>
    <p class="card-subtitle" style="text-align: center;">Choose a strong new password that you haven't used recently.</p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">&#10003; <?= e($successMessage) ?></div>
        <p style="text-align: center; margin-top: 18px;">
            <a href="login.php" class="btn btn-primary">Proceed to Log-in &#8594;</a>
        </p>
    <?php else: ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">&#9888; <?= e($errorMessage) ?></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php?token=<?= urlencode($queryToken) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= e($queryToken) ?>">

            <div class="form-group">
                <label for="password" class="form-label">Enter Password<span class="required-star">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       maxlength="64" placeholder="Minimum 9 characters" required>
                <!-- Strength Meter (Weak / Medium / Strong) -->
                <div class="strength-meter-container">
                    <div class="strength-bar-track">
                        <div id="strength-bar" class="strength-bar"></div>
                    </div>
                    <span id="strength-text" class="strength-text">None</span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Re-enter Password<span class="required-star">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       maxlength="64" placeholder="Repeat your new password" required>
                <span id="error-confirm_password" class="field-error"></span>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Update Password &amp; Finish &#8594;
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
